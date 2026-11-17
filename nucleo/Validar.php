<?php
// Comprobación de los datos de los usuarios.
// Cada función devuelve el dato limpio y normalizado, o null si no es válido.
final class Validar
{
    // Contraseñas tan comunes que se prueban las primeras en cualquier ataque
    private const COMUNES = [
        '12345678a', 'a12345678', '1234abcd', 'abcd1234', 'abc12345', 'abc123456', 'password1', 'password12',
        'password123', 'contraseña1', 'contrasena1', 'contraseña123', 'contrasena123', 'qwerty123', 'qwerty12',
        'iloveyou1', 'taller123', 'tallergenil1', 'admin123', 'admin1234', 'usuario1', 'usuario123',
        'granada1', 'granada123', 'futbol123', 'madrid123', 'barcelona1', '1q2w3e4r', '1qaz2wsx', 'q1w2e3r4',
    ];

    public static function nombre(string $texto, int $maximo): ?string
    {
        $texto = trim(preg_replace('/\s+/u', ' ', $texto));
        $largo = mb_strlen($texto);
        if ($largo < 2 || $largo > $maximo || !preg_match("/^\p{L}[\p{L} '\-.]*$/u", $texto)) {
            return null;
        }
        return $texto;
    }

    // DNI (12345678Z) o NIE (X1234567L), con la letra de control comprobada
    public static function dni(string $texto): ?string
    {
        $dni = strtoupper(preg_replace('/[\s.\-]/', '', $texto));
        if (!preg_match('/^([XYZ]\d{7}|\d{8})([A-Z])$/', $dni, $partes)) {
            return null;
        }
        $numero = strtr($partes[1], ['X' => '0', 'Y' => '1', 'Z' => '2']);
        $letra = 'TRWAGMYFPDXBNJZSQVHLCKE'[(int) $numero % 23];
        return $letra === $partes[2] ? $dni : null;
    }

    // Teléfono español: 9 cifras que empiezan por 6, 7, 8 o 9 (admite +34 delante)
    public static function telefono(string $texto): ?string
    {
        $telefono = preg_replace('/[\s.\-()]/', '', $texto);
        $telefono = preg_replace('/^(\+34|0034)/', '', $telefono);
        return preg_match('/^[6789]\d{8}$/', $telefono) ? $telefono : null;
    }

    // Formato correcto y, en producción, que el dominio pueda recibir correo.
    // Que el email es de verdad suyo se demuestra después con el enlace de activación
    public static function email(string $texto): ?string
    {
        $email = mb_strtolower(trim($texto));
        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        if (Seguridad::enProduccion()) {
            $dominio = substr(strrchr($email, '@'), 1);
            if (!checkdnsrr($dominio, 'MX') && !checkdnsrr($dominio, 'A')) {
                return null;
            }
        }
        return $email;
    }

    // Devuelve los problemas de la contraseña (vacío si es buena)
    public static function password(string $password, string $repetida, array $usuario): array
    {
        $errores = [];
        $largo = mb_strlen($password);
        if ($largo < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($largo > 128) {
            $errores[] = 'La contraseña no puede tener más de 128 caracteres.';
        }
        if ($password !== $repetida) {
            $errores[] = 'Las dos contraseñas no coinciden.';
        }
        if (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
            $errores[] = 'La contraseña debe mezclar letras y números.';
        }

        $minusculas = mb_strtolower($password);
        $personales = array_filter([
            mb_strtolower($usuario['nombre'] ?? ''),
            mb_strtolower($usuario['apellidos'] ?? ''),
            mb_strtolower(strstr($usuario['email'] ?? '', '@', true) ?: ''),
            mb_strtolower($usuario['dni'] ?? ''),
            $usuario['telefono'] ?? '',
        ], fn($dato) => mb_strlen($dato) >= 4);
        foreach ($personales as $dato) {
            if (str_contains($minusculas, $dato)) {
                $errores[] = 'La contraseña no puede contener tu nombre, tu email, tu DNI ni tu teléfono.';
                break;
            }
        }
        if (in_array($minusculas, self::COMUNES, true) || count(array_unique(mb_str_split($password))) < 5) {
            $errores[] = 'Esa contraseña es demasiado fácil de adivinar.';
        }
        return $errores;
    }
}
