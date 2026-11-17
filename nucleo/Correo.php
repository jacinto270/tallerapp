<?php
// Envío de correos.
//  - Modo 'archivo' (solo desarrollo): se guardan en almacen/correos y se leen
//    en index.php?accion=correos_dev desde el propio ordenador.
//  - Modo 'smtp': se envían por un servidor SMTP con TLS y usuario/contraseña.
final class Correo
{
    public static function enviar(string $para, string $asunto, string $texto): bool
    {
        // Nada de saltos de línea en las cabeceras (evita inyectar cabeceras)
        $para = str_replace(["\r", "\n"], '', $para);
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (Seguridad::config('correoModo', 'smtp') === 'archivo') {
            if (Seguridad::enProduccion()) {
                error_log('Correo: el modo «archivo» no se permite en producción.');
                return false;
            }
            return self::guardarEnArchivo($para, $asunto, $texto);
        }
        return self::smtp($para, $asunto, $texto);
    }

    public static function carpeta(): string
    {
        return __DIR__ . '/../almacen/correos';
    }

    private static function guardarEnArchivo(string $para, string $asunto, string $texto): bool
    {
        $carpeta = self::carpeta();
        if (!is_dir($carpeta) && !mkdir($carpeta, 0700, true)) {
            return false;
        }
        $nombre = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.eml';
        return file_put_contents($carpeta . '/' . $nombre, self::mensaje($para, $asunto, $texto, false)) !== false;
    }

    private static function mensaje(string $para, string $asunto, string $texto, bool $base64 = true): string
    {
        $remitente = (string) Seguridad::config('correoRemitente', '');
        $nombre = (string) Seguridad::config('correoNombre', 'Taller Genil');
        $host = parse_url((string) Seguridad::config('urlBase', ''), PHP_URL_HOST) ?: 'localhost';
        $codificar = fn(string $t) => '=?UTF-8?B?' . base64_encode($t) . '?=';

        $cabeceras = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $codificar($nombre) . ' <' . $remitente . '>',
            'To: <' . $para . '>',
            'Subject: ' . $codificar($asunto),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $host . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: ' . ($base64 ? 'base64' : '8bit'),
        ];
        $cuerpo = $base64 ? rtrim(chunk_split(base64_encode($texto), 76, "\r\n")) : $texto;
        return implode("\r\n", $cabeceras) . "\r\n\r\n" . $cuerpo;
    }

    // Cliente SMTP mínimo: TLS obligatorio (465 con SSL o 587 con STARTTLS) y AUTH LOGIN
    private static function smtp(string $para, string $asunto, string $texto): bool
    {
        $servidor = (string) Seguridad::config('smtpServidor', '');
        $puerto = (int) Seguridad::config('smtpPuerto', 587);
        $usuario = (string) Seguridad::config('smtpUsuario', '');
        $clave = (string) Seguridad::config('smtpPassword', '');
        $remitente = (string) Seguridad::config('correoRemitente', '');
        if ($servidor === '' || $remitente === '') {
            error_log('Correo: falta configurar el servidor SMTP o el remitente.');
            return false;
        }

        $contexto = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $prefijo = $puerto === 465 ? 'ssl://' : 'tcp://';
        $conexion = @stream_socket_client($prefijo . $servidor . ':' . $puerto, $errno, $error, 15, STREAM_CLIENT_CONNECT, $contexto);
        if (!$conexion) {
            error_log("Correo: no se puede conectar con $servidor:$puerto ($error)");
            return false;
        }
        stream_set_timeout($conexion, 15);

        $orden = function (string $linea, array $esperado) use ($conexion): void {
            if ($linea !== '') {
                fwrite($conexion, $linea . "\r\n");
            }
            $respuesta = '';
            while (($fila = fgets($conexion, 515)) !== false) {
                $respuesta .= $fila;
                if (isset($fila[3]) && $fila[3] === ' ') {
                    break;
                }
            }
            if (!in_array((int) substr($respuesta, 0, 3), $esperado, true)) {
                throw new RuntimeException('SMTP: ' . trim($respuesta));
            }
        };

        try {
            $host = parse_url((string) Seguridad::config('urlBase', ''), PHP_URL_HOST) ?: 'localhost';
            $orden('', [220]);
            $orden("EHLO $host", [250]);
            if ($puerto !== 465) {
                $orden('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($conexion, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('SMTP: no se ha podido activar TLS');
                }
                $orden("EHLO $host", [250]);
            }
            if ($usuario !== '') {
                $orden('AUTH LOGIN', [334]);
                $orden(base64_encode($usuario), [334]);
                $orden(base64_encode($clave), [235]);
            }
            $orden("MAIL FROM:<$remitente>", [250]);
            $orden("RCPT TO:<$para>", [250, 251]);
            $orden('DATA', [354]);
            $orden(self::mensaje($para, $asunto, $texto) . "\r\n.", [250]);
            $orden('QUIT', [221, 0]);   // el correo ya está aceptado: da igual cómo cierre
            return true;
        } catch (RuntimeException $e) {
            error_log('Correo: ' . $e->getMessage());
            return false;
        } finally {
            fclose($conexion);
        }
    }
}
