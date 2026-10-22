# SMTP & Email Logger - toolkitAC

## Creador

Desarrollado por Anderson Chila.

Portafolio profesional: https://anderson526.github.io/portfolio-profesional/

## Lenguaje y stack técnico

- PHP 7.4+
- WordPress 6.x
- PHPMailer
- Hooks de WordPress como wp_mail, phpmailer_init y wp_mail_failed
- MySQL para historial de correos
- JavaScript y CSS para administración

## Uso

1. Instala el plugin en wp-content/plugins/.
2. Actívalo desde el panel de WordPress.
3. Configura SMTP con el host, puerto, cifrado y credenciales.
4. Prueba el envío desde la pestaña de prueba.
5. Consulta el historial de correos enviados y errores desde la administración.

## Descripción

SMTP & Email Logger - toolkitAC es un plugin para WordPress orientado a la entrega de correos mediante SMTP y al registro de cada envío para fines de auditoría, diagnóstico y validación operativa. El complemento intercepta la salida generada por `wp_mail` y configura PHPMailer a través del hook `phpmailer_init`, garantizando que los mensajes salgan por el servidor SMTP definido en la configuración del sitio.

La solución incluye además un panel de administración dentro de WordPress para gestionar credenciales SMTP, forzar los datos del remitente, verificar la entrega mediante un correo de prueba y consultar el historial de mensajes enviados.

## Autor

- Nombre: Anderson Chila
- Sitio personal: https://anderson526.github.io/portfolio-profesional/
- Proyecto: Parte de la suite AC Essential

## Requisitos

- WordPress 6.0 o superior
- PHP 7.4 o superior
- Un servidor SMTP o relay con credenciales válidas
- Permisos de administración para configurar el plugin en WordPress

## Características

- Envío de todos los correos del sitio mediante SMTP
- Configuración de host, puerto, protocolo de cifrado y autenticación
- Soporte para TLS, SSL o sin cifrado
- Opción para forzar el correo y nombre del remitente
- Registro de cada email enviado con estado, destinatario, asunto y detalle
- Registro de errores cuando el envío falla
- Envío de correo de prueba desde el panel administrativo
- Almacenamiento seguro de credenciales recomendado mediante constantes en `wp-config.php`

## Arquitectura

El plugin integra varios puntos del ciclo de vida de WordPress:

- `phpmailer_init`: se utiliza para configurar la instancia de PHPMailer con los datos del servidor SMTP.
- `wp_mail_from` y `wp_mail_from_name`: permiten sobrescribir el remitente globalmente si la opción está habilitada.
- `wp_mail`: se intercepta para registrar cada intento de envío.
- `wp_mail_succeeded` y `wp_mail_failed`: actualizan el estado final del registro.

El historial de correo se persiste en una tabla personalizada con nombre basado en el prefijo de WordPress: `wp_anderc_email_logs`.

## Instalación

1. Descarga el plugin o copia la carpeta del proyecto en el directorio:

   ```bash
   wp-content/plugins/smtp-mailerAC
   ```

2. Activa el plugin desde el panel administrativo de WordPress:

   - WordPress > Plugins > Plugins instalados
   - Buscar "SMTP & Email Logger - toolkitAC"
   - Activar

3. Accede al menú administrativo:

   - WordPress > AC SMTP

## Configuración

Dentro del panel de administración, la pestaña "Ajustes SMTP" permite configurar:

- Activar SMTP
- Servidor SMTP
- Puerto
- Tipo de cifrado: TLS, SSL o Ninguno
- Autenticación SMTP
- Usuario SMTP
- Contraseña SMTP
- Correo del remitente
- Nombre del remitente
- Forzar el remitente en todos los correos
- Registro de correos

### Configuración recomendada

Para evitar guardar credenciales sensibles en la base de datos, se recomienda definirlas en `wp-config.php`:

```php
define( 'ANDERC_SMTP_USER', 'usuario@dominio.com' );
define( 'ANDERC_SMTP_PASS', 'tu_password_seguro' );
```

Cuando estas constantes existen, tienen prioridad sobre los valores almacenados en la opción del plugin.

## Uso

### 1. Activar el transporte SMTP

En la pantalla de configuración del plugin:

- marca la opción "Activar SMTP"
- introduce el host del servidor SMTP
- define el puerto compatible con tu proveedor
- selecciona el tipo de cifrado apropiado
- habilita autenticación si el servicio lo requiere
- guarda los cambios

### 2. Validar la entrega

Desde la pestaña "Enviar prueba":

- ingresa una dirección de correo de destino
- ejecuta el envío de prueba
- revisa si el correo llega a la bandeja de entrada o a la carpeta de spam

El plugin usa `wp_mail()` de WordPress, por lo que funciona con todas las llamadas normales del core y con la mayoría de plugins de correo y formularios.

### 3. Consultar el registro de correos

En la pestaña "Registro de correos" se muestran los mensajes enviados con la siguiente información:

- Fecha y hora
- Destinatario
- Asunto
- Estado (`pendiente`, `enviado`, `fallido`)
- Mensaje completo y detalle del error si aplica

La tabla permite vaciar el historial completo cuando sea necesario.

## Registro de correo

La clase `ASM_Email_Logger` intercepta el filtro `wp_mail` y registra una fila preliminar antes del envío. Posteriormente, los hooks `wp_mail_succeeded` y `wp_mail_failed` actualizan el estado del registro.

Cada entrada almacena lo siguiente:

- `sent_at`
- `to_email`
- `subject`
- `message`
- `headers`
- `status`
- `error`

Esto permite realizar auditoría operativa y resolver problemas de entrega o configuración.

## Seguridad y buenas prácticas

- No almacenes credenciales SMTP en repositorios públicos.
- Usa `wp-config.php` para credenciales críticas cuando sea posible.
- Mantén el plugin actualizado y revisa los logs ante errores de entrega.
- Verifica que el servidor SMTP acepte conexiones desde la IP del sitio.
- Prueba el correo de salida con un destinatario real antes de desplegar cambios en producción.

## Solución de problemas

### El correo no se envía

Verifica los siguientes puntos:

- El SMTP está activado.
- El host y el puerto son correctos.
- El protocolo de cifrado coincide con el proveedor.
- Las credenciales son correctas.
- El servidor SMTP acepta conexiones desde tu infraestructura.

### El correo llega a spam

Revisa lo siguiente:

- Configuración correcta de SPF y DKIM en el dominio
- Identidad del remitente coherente con el dominio
- Reputación del servidor SMTP
- Uso del remitente forzado cuando corresponda

### El plugin no registra los correos

Comprueba que la opción "Guardar cada correo enviado en el registro" esté activada y que la base de datos del sitio tenga permisos de escritura.

## Licencia

Este plugin se distribuye bajo la licencia GPL-2.0-or-later.

## Notas finales

El plugin es una solución ligera y funcional para sitios WordPress que requieren una capa fiable de envío por SMTP y observabilidad del flujo de correo. Está diseñado para ser gestionado desde el panel de administración sin depender de complejos servicios externos.

## Agradecimiento

Gracias a la comunidad de desarrolladores WordPress, a la comunidad PHP y a quienes aprecian el trabajo del creador. Este proyecto busca aportar confiabilidad, control y tranquilidad a la entrega de correos en entornos profesionales.
