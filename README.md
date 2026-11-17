# Taller Genil · Racing service

Aplicación web para gestionar un taller mecánico: reparaciones, piezas, mano de obra, clientes, facturas y estadísticas de ventas. Los clientes también pueden entrar, pero solo ven sus facturas y sus datos.

Está hecha en **PHP 8 sin frameworks**, con el patrón **MVC** (modelos, vistas y controladores) y **MySQL/MariaDB**.

## Qué hace

**Personal del taller (rol `admin`)**
- Reparaciones: alta, piezas (añadir y quitar), mano de obra por horas y precio por hora, buscador y filtro «en el taller».
- Facturación: «Cobrar, facturar y entregar» en un paso, número correlativo por año (`F-2026-00001`) e impresión o PDF.
- Clientes con sus vehículos y su historial de reparaciones.
- Ventas: resúmenes de la semana, el mes y el año, y gráficos de ventas por mes, piezas más vendidas, averías más comunes y ventas por mecánico.
- Usuarios: crear administradores y clientes, bajas (con fecha y motivo, sin borrar a nadie), edición, vincular la ficha de cliente de quien se registra, contraseñas y registro de accesos.

**Clientes (rol `usuario`)**
- *Crear cuenta*: cualquiera puede registrarse. La cuenta empieza **sin ficha de cliente**, así que no ve ninguna factura hasta que el taller la vincula después de comprobar en persona quién es (DNI). Así, saber el teléfono de un cliente no basta para ver sus facturas.
- *Mis facturas*: ver e imprimir sus facturas, y nada más.
- *Mi cuenta*: sus datos, sus vehículos y el cambio de contraseña.

**Para todos**
- Tema claro, oscuro o automático, adaptado al móvil.
- *Sala de espera* con un minijuego de carreras para quien espera su coche.

## Seguridad

- **Funciona con o sin correo** (`$correoModo` en `config/config.php`):
  - **Sin correo** (`'ninguno'`, el modo por defecto): se entra con email y contraseña. Las contraseñas iniciales y las olvidadas las pone el admin como **temporales**, y el usuario tiene que cambiarlas al entrar, antes de hacer nada más.
  - **Con correo** (`'smtp'`): enlaces de activación, verificación del email, «¿Has olvidado la contraseña?» y avisos por email. Con `$dosPasos = true` se añade un **segundo paso** al entrar: un código de 6 cifras por email que caduca en 10 minutos.
- **Contraseñas con Argon2id**: mínimo 8 caracteres, con letras y números, sin datos personales y sin contraseñas típicas. Los enlaces y códigos de un solo uso se guardan como HMAC, nunca en claro.
- **Altas**: se piden nombre, apellidos, DNI o NIE (se comprueba la letra), email y teléfono.
  - **Registro público** con aceptación del tratamiento de datos, campo trampa contra robots y un máximo de 5 registros por hora y conexión. Si el email o el DNI ya existen, la respuesta es la misma (no se revela quién tiene cuenta).
- **Contraseñas**:
  - Cada usuario cambia la suya escribiendo la actual, con bloqueo tras 5 fallos.
  - El admin puede poner una **contraseña temporal** a cualquier usuario (y, con correo, también enviarle un enlace de cambio).
  - Cualquier cambio de contraseña **cierra las demás sesiones abiertas**.
- **Roles comprobados en el servidor** en cada petición. Un cliente que pida una factura ajena recibe un 404. Una baja o un cambio de rol se aplican al momento, aunque el usuario esté conectado.
- **Freno a la fuerza bruta**: bloqueo de 15 minutos tras 5 fallos por cuenta o 20 por IP. Los mensajes de error no revelan qué emails existen.
- **Token CSRF** en todos los formularios y comprobación de `Origin`.
- **Sesión protegida**: cookie `HttpOnly`, `SameSite=Lax` y `Secure` con HTTPS, identificador nuevo al entrar y caducidad a los 30 minutos sin actividad o a las 12 horas.
- **Cabeceras de seguridad**: CSP estricta (sin scripts en línea), `X-Frame-Options`, `nosniff`, `Referrer-Policy` y HSTS.
- **Consultas preparadas** en todo el SQL y salida escapada en todas las vistas.
- **`.htaccess`**: solo se sirven `index.php` y los archivos públicos; las carpetas internas, los `.sql` y `.git` dan 403 o 404.
- En **producción**: HTTPS obligatorio y errores ocultos.

## Requisitos

- PHP **8.1 o superior** (8.2 recomendado) con `pdo_mysql`, `openssl` y `mbstring`, y soporte para Argon2id (viene en los PHP oficiales y en XAMPP).
- MySQL 5.7+ o MariaDB 10.3+.
- **Apache** con `AllowOverride All` y los módulos `mod_headers` y `mod_alias` (XAMPP ya viene así). Con otro servidor, como nginx, hay que reproducir las reglas de `.htaccess`.
- Opcional: un servidor **SMTP**, si quieres enlaces por email y el segundo paso al entrar.

## Instalación

1. **Copia el proyecto** en la carpeta web (por ejemplo, `C:\xampp\htdocs\tallerapp`).

2. **Crea la base de datos** e importa la estructura. No lleva datos: la aplicación empieza vacía.
   ```bash
   mysql -u root -p -e "CREATE DATABASE tallerapp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
   mysql -u root -p tallerapp_db < data/tallerapp_db.sql
   ```

3. **Crea la configuración** a partir del ejemplo. `config/config.php` **nunca se sube a GitHub**: está en `.gitignore`.
   ```bash
   cp config/config_example.php config/config.php
   ```
   Rellena los datos de MySQL y genera tu clave secreta:
   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```
   Copia el resultado en `$claveSecreta`.

4. **Crea el primer admin** desde la línea de comandos (no se puede hacer desde el navegador):
   ```bash
   php herramientas/crear_admin.php --nombre="Nombre" --apellidos="Apellidos" \
       --dni=12345678Z --email=tu@email.es --telefono=600000000
   ```
   Te mostrará un enlace de activación (caduca en 48 horas). Ábrelo y elige tu contraseña: mínimo 8 caracteres, con letras y números.

5. **Entra** en `http://localhost/tallerapp/` con tu email y tu contraseña. Desde *Usuarios* puedes dar de alta a otros administradores y clientes, con una contraseña temporal que cambiarán al entrar.

   Para probar los correos sin un servidor real, pon `$correoModo = 'archivo'`: se guardan en `almacen/correos/` y se leen en `index.php?accion=correos_dev`, que solo funciona en desarrollo y desde el propio ordenador.

## Antes de publicar en internet

En `config/config.php`:

| Ajuste | Valor en producción |
|---|---|
| `$entorno` | `'produccion'`: obliga a usar HTTPS, oculta los errores y desactiva la bandeja de correos de desarrollo |
| `$urlBase` | Tu dirección con `https://` y terminada en `/` |
| `$claveSecreta` | Una propia, de 64 caracteres; nunca la del ejemplo ni la de otra instalación |
| `$correoModo` | `'ninguno'` si no tienes correo (las contraseñas iniciales y olvidadas las pone el admin). Si lo tienes, `'smtp'` con `$smtpServidor`, `$smtpPuerto` (587 o 465), `$smtpUsuario`, `$smtpPassword` y `$correoRemitente`, y si quieres, `$dosPasos = true` |
| `$usuario` / `$password` | Un usuario de MySQL solo para la aplicación, **nunca `root`** (ver abajo) |

**Usuario de MySQL con los permisos justos**: puede leer y escribir datos, pero no borrar tablas ni crear usuarios.
```sql
CREATE USER 'tallerapp'@'localhost' IDENTIFIED BY 'una-contraseña-larga-y-aleatoria';
GRANT SELECT, INSERT, UPDATE, DELETE ON tallerapp_db.* TO 'tallerapp'@'localhost';
```

Además:
- **Certificado HTTPS** en el servidor (por ejemplo, con Let's Encrypt).
- Que el **directorio web apunte a esta carpeta** con `.htaccess` activo. Puedes comprobarlo abriendo `https://tu-dominio/config/config.php`: debe responder 403 o 404.
- **Copias de seguridad** de la base de datos fuera de la carpeta web y fuera de git.

## Estructura

```
index.php            Controlador frontal: rutas y permisos de cada página
nucleo/              Seguridad (sesión, CSRF, cabeceras), validación y envío de correo
controladores/       Reparaciones, clientes, facturas, estadísticas, acceso y usuarios
modelos/             Todo el SQL (consultas preparadas)
vistas/              HTML de cada página
config/              conexion.php y config_example.php (config.php no se sube)
data/                tallerapp_db.sql: estructura de la base de datos, sin datos
herramientas/        crear_admin.php (solo desde la línea de comandos)
almacen/             Correos de desarrollo (no se suben)
estilos.css          Estilos y temas claro y oscuro
tema.js, app.js      Tema, efectos, confirmaciones, filtros y Turbo, el mecánico guía
graficos.js          Gráficos de ventas en SVG, sin librerías
juego.js             Minijuego de la sala de espera
```

## Qué no se sube a GitHub

Lo controla `.gitignore`:
- `config/config.php`: contraseña de MySQL, clave secreta y datos del SMTP.
- Cualquier volcado o copia de la base de datos (`data/*`, salvo la estructura).
- Los correos guardados en desarrollo (`almacen/`), que llevan códigos y enlaces de acceso.
- Archivos `.env`, claves, certificados y registros.
