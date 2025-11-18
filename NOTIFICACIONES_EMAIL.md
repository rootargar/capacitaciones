# Sistema de Notificaciones por Email

Sistema automatizado de notificaciones por correo electrónico para el Sistema de Capacitaciones KWDAF.

## Características Implementadas

### 1. Tipos de Notificaciones

El sistema envía automáticamente los siguientes tipos de notificaciones:

- **Asignación a Capacitación**: Cuando un empleado es asignado a un nuevo curso
- **Recordatorio de Capacitación**: 24 horas antes del inicio de la capacitación
- **Confirmación de Asistencia**: Cuando se registra la asistencia del empleado
- **Certificado Disponible**: Cuando se carga un certificado (funcionalidad preparada)

### 2. Componentes del Sistema

```
├── config_email.php          # Configuración del servicio de email
├── .env.example               # Plantilla de variables de entorno
├── EmailService.php           # Clase principal del servicio
├── lib/phpmailer/            # Librería PHPMailer
├── enviar_recordatorios.php  # Script para recordatorios automáticos
├── sql/                      # Scripts SQL
│   └── create_email_logs_table.sql  # Crear tabla de logs
└── logs/                     # Logs de envío (se crea automáticamente)
```

## Instalación

### Paso 1: Crear la Tabla de Logs

Ejecutar el script SQL en SQL Server Management Studio:

```sql
-- Ejecutar el archivo:
sql/create_email_logs_table.sql
```

Esto creará la tabla `email_logs` que registra todos los envíos de email.

### Paso 2: Configurar el Servidor SMTP

#### Opción A: Usar Gmail (Recomendado para pruebas)

1. Ir a https://myaccount.google.com/security
2. Activar "Verificación en 2 pasos"
3. Generar una "Contraseña de aplicación"
4. Editar `config_email.php`:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'tu-email@gmail.com');
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx'); // Contraseña de aplicación
define('SMTP_FROM_EMAIL', 'tu-email@gmail.com');
define('SMTP_FROM_NAME', 'Sistema de Capacitaciones KWDAF');
```

#### Opción B: Usar Outlook/Office 365

```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'tu-email@empresa.com');
define('SMTP_PASSWORD', 'tu-password');
define('SMTP_FROM_EMAIL', 'tu-email@empresa.com');
define('SMTP_FROM_NAME', 'Sistema de Capacitaciones KWDAF');
```

#### Opción C: Servidor SMTP Propio

```php
define('SMTP_HOST', 'mail.tuempresa.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'capacitaciones@tuempresa.com');
define('SMTP_PASSWORD', 'password-seguro');
define('SMTP_FROM_EMAIL', 'capacitaciones@tuempresa.com');
define('SMTP_FROM_NAME', 'Sistema de Capacitaciones KWDAF');
```

### Paso 3: Activar/Desactivar Notificaciones

En `config_email.php`:

```php
// Activar notificaciones
define('EMAIL_ENABLED', true);

// Desactivar notificaciones (útil para desarrollo/pruebas)
define('EMAIL_ENABLED', false);

// Modo debug (muestra información detallada de SMTP)
define('EMAIL_DEBUG', true);  // Activar para depuración
define('EMAIL_DEBUG', false); // Desactivar en producción
```

### Paso 4: Configurar Recordatorios Automáticos

#### En Windows (Programador de Tareas)

1. Abrir "Programador de tareas" de Windows
2. Crear tarea básica
3. Configurar para ejecutar diariamente a las 9:00 AM
4. Acción: Iniciar programa
   - Programa: `C:\xampp\php\php.exe` (ajustar ruta)
   - Argumentos: `C:\ruta\al\proyecto\enviar_recordatorios.php`

#### En Linux (Cron Job)

```bash
# Editar crontab
crontab -e

# Agregar línea para ejecutar diariamente a las 9:00 AM
0 9 * * * /usr/bin/php /ruta/al/proyecto/enviar_recordatorios.php
```

#### Ejecutar Manualmente

```bash
php enviar_recordatorios.php
```

Los logs se guardarán en: `logs/recordatorios.log`

## Uso del Sistema

### Notificaciones Automáticas

El sistema enviará notificaciones automáticamente cuando:

1. **Se asigna un empleado a una capacitación**
   - Archivo: `asignar_participantes.php`
   - Se envía inmediatamente al asignar

2. **Se registra la asistencia del empleado**
   - Archivo: `asignar_participantes.php`
   - Se envía cuando se marca "Asistió"

3. **Recordatorios programados**
   - Archivo: `enviar_recordatorios.php`
   - Se ejecuta mediante cron/tarea programada

### Verificar Envíos

Puedes verificar todos los envíos de email en la tabla de la base de datos:

```sql
-- Ver todos los emails enviados
SELECT * FROM email_logs
ORDER BY FechaEnvio DESC;

-- Ver emails por tipo
SELECT TipoNotificacion, Estado, COUNT(*) as Total
FROM email_logs
GROUP BY TipoNotificacion, Estado;

-- Ver emails con error
SELECT * FROM email_logs
WHERE Estado = 'error'
ORDER BY FechaEnvio DESC;

-- Ver emails de un empleado específico
SELECT el.*, u.NombreCompleto
FROM email_logs el
LEFT JOIN usuarios u ON el.IdEmpleado = u.Clave
WHERE u.NombreCompleto LIKE '%nombre%'
ORDER BY FechaEnvio DESC;
```

## Personalización de Plantillas

### Modificar Plantillas de Email

Las plantillas HTML se encuentran en `EmailService.php`:

```php
// Línea 281: Plantilla de asignación
private function getPlantillaAsignacion($datos)

// Línea 305: Plantilla de recordatorio
private function getPlantillaRecordatorio($datos)

// Línea 329: Plantilla de asistencia
private function getPlantillaAsistencia($datos)

// Línea 349: Plantilla de certificado
private function getPlantillaCertificado($datos)
```

### Personalizar Logo y Colores

En `config_email.php`:

```php
define('EMAIL_LOGO_URL', 'https://tu-servidor.com/logo.png');
```

En `EmailService.php` (método `getPlantillaBase`):

```php
// Cambiar color del header (línea 382)
<td style='background-color: #007bff; ...'>

// Cambiar colores de los botones y secciones según necesidad
```

## Solución de Problemas

### Los emails no se envían

1. Verificar que `EMAIL_ENABLED` esté en `true` en `config_email.php`
2. Verificar credenciales SMTP
3. Activar modo debug: `define('EMAIL_DEBUG', true);`
4. Revisar logs en `logs/recordatorios.log`
5. Verificar tabla `email_logs` para ver errores

### Error de autenticación SMTP

- Gmail: Usar contraseña de aplicación, no la contraseña normal
- Verificar que la cuenta tenga permisos para enviar via SMTP
- Verificar firewall/antivirus no bloquee puerto 587

### Los empleados no tienen correo

```sql
-- Actualizar correos de empleados
UPDATE usuarios
SET correo = 'empleado@ejemplo.com'
WHERE Clave = 'ID_EMPLEADO';

-- Ver empleados sin correo
SELECT Clave, NombreCompleto, correo
FROM usuarios
WHERE correo IS NULL OR correo = '';
```

### Recordatorios no se envían automáticamente

1. Verificar que el cron job o tarea programada esté configurado
2. Ejecutar manualmente para verificar: `php enviar_recordatorios.php`
3. Revisar logs: `logs/recordatorios.log`
4. Verificar que `EMAIL_REMINDER_HOURS` en `config_email.php` sea correcto (default: 24)

## Configuración Avanzada

### Cambiar tiempo de recordatorio

En `config_email.php`:

```php
// Recordatorio 48 horas antes
define('EMAIL_REMINDER_HOURS', 48);

// Recordatorio 12 horas antes
define('EMAIL_REMINDER_HOURS', 12);
```

### Agregar administradores que reciban copias

En `config_email.php`:

```php
define('ADMIN_EMAILS', [
    'admin@ejemplo.com',
    'rh@ejemplo.com',
    'supervisor@ejemplo.com'
]);
```

Luego en `EmailService.php`, agregar en el método `enviarEmail`:

```php
// Agregar CCO a administradores
foreach (ADMIN_EMAILS as $adminEmail) {
    $this->mailer->addBCC($adminEmail);
}
```

### Deshabilitar temporalmente las notificaciones

Para mantenimiento o pruebas, simplemente:

```php
define('EMAIL_ENABLED', false);
```

## Seguridad

### Recomendaciones

1. **No compartir credenciales SMTP** en el código fuente
2. **Usar contraseñas de aplicación** en lugar de contraseñas normales
3. **Mantener `.env` en `.gitignore`** (ya configurado)
4. **Revisar regularmente los logs** para detectar intentos de abuso
5. **Limitar tasa de envío** si el servidor SMTP lo requiere (ya implementado con `usleep()`)

### Variables de Entorno (Opcional)

Para mayor seguridad, puedes usar variables de entorno reales en lugar del archivo `config_email.php`:

```php
// En config_email.php
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'default@ejemplo.com');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'default-password');
```

## Estructura de la Base de Datos

### Tabla email_logs

| Campo | Tipo | Descripción |
|-------|------|-------------|
| Id | INT (PK) | ID único del log |
| IdCapacitacion | INT (FK) | ID de la capacitación |
| IdEmpleado | VARCHAR(50) (FK) | ID del empleado |
| EmailDestino | VARCHAR(255) | Email del destinatario |
| TipoNotificacion | VARCHAR(100) | Tipo: asignacion, recordatorio, asistencia, certificado |
| Asunto | VARCHAR(500) | Asunto del email |
| Estado | VARCHAR(50) | Estado: enviado, error, pendiente |
| MensajeError | TEXT | Mensaje de error si falla |
| FechaEnvio | DATETIME | Fecha y hora de envío |
| IntentoNumero | INT | Número de intento |

## Soporte

Para reportar problemas o solicitar nuevas funcionalidades, contactar al administrador del sistema.

---

**Versión**: 1.0
**Fecha**: 2025-11-18
**Sistema**: Capacitaciones KWDAF
