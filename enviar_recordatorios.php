<?php
/**
 * Script de Recordatorios Automáticos de Capacitaciones
 * Sistema de Capacitaciones KWDAF
 *
 * Este script debe ejecutarse mediante un cron job para enviar
 * recordatorios automáticos a los empleados antes de sus capacitaciones.
 *
 * Configuración recomendada de cron (ejecutar diariamente a las 9:00 AM):
 * 0 9 * * * /usr/bin/php /ruta/al/proyecto/enviar_recordatorios.php
 *
 * O también se puede ejecutar manualmente:
 * php enviar_recordatorios.php
 */

// Incluir archivos necesarios
require_once __DIR__ . '/conexion2.php';
require_once __DIR__ . '/EmailService.php';

// Verificar que se esté ejecutando desde CLI o con permisos adecuados
if (php_sapi_name() !== 'cli') {
    // Si se ejecuta desde el navegador, verificar autenticación (opcional)
    // session_start();
    // if (!isset($_SESSION['usuario'])) {
    //     die("Acceso no autorizado");
    // }
}

// Función para registrar logs
function registrarLog($mensaje, $tipo = 'INFO') {
    $fecha = date('Y-m-d H:i:s');
    $log = "[{$fecha}] [{$tipo}] {$mensaje}" . PHP_EOL;

    // Escribir en archivo de log
    $archivoLog = __DIR__ . '/logs/recordatorios.log';

    // Crear directorio de logs si no existe
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }

    file_put_contents($archivoLog, $log, FILE_APPEND);

    // También mostrar en consola si se ejecuta desde CLI
    if (php_sapi_name() === 'cli') {
        echo $log;
    }
}

// Iniciar procesamiento
registrarLog("=== Iniciando proceso de envío de recordatorios ===");

try {
    // Crear instancia del servicio de email
    $emailService = new EmailService($conn);

    // Obtener capacitaciones que necesitan recordatorio
    $capacitaciones = $emailService->obtenerCapacitacionesParaRecordatorio();

    registrarLog("Se encontraron " . count($capacitaciones) . " capacitaciones pendientes de recordatorio");

    if (empty($capacitaciones)) {
        registrarLog("No hay capacitaciones para enviar recordatorios en este momento");
        registrarLog("=== Proceso finalizado ===");
        exit(0);
    }

    // Contador de emails enviados
    $enviados = 0;
    $errores = 0;

    // Enviar recordatorio a cada empleado
    foreach ($capacitaciones as $capacitacion) {
        try {
            $datosEmail = array(
                'IdCapacitacion' => $capacitacion['IdCapacitacion'],
                'IdEmp' => $capacitacion['IdEmp'],
                'Empleado' => $capacitacion['Empleado'],
                'NomCurso' => $capacitacion['NomCurso'],
                'Area' => $capacitacion['Area'],
                'FechaIni' => $capacitacion['FechaIni'],
                'correo' => $capacitacion['correo']
            );

            $resultado = $emailService->enviarRecordatorioCapacitacion($datosEmail);

            if ($resultado) {
                $enviados++;
                registrarLog(
                    "Recordatorio enviado a {$capacitacion['Empleado']} ({$capacitacion['correo']}) " .
                    "para el curso '{$capacitacion['NomCurso']}'",
                    'SUCCESS'
                );
            } else {
                $errores++;
                registrarLog(
                    "Error al enviar recordatorio a {$capacitacion['Empleado']} ({$capacitacion['correo']})",
                    'ERROR'
                );
            }

            // Pequeña pausa entre envíos para no saturar el servidor SMTP
            usleep(500000); // 0.5 segundos

        } catch (Exception $e) {
            $errores++;
            registrarLog(
                "Excepción al procesar recordatorio para {$capacitacion['Empleado']}: " . $e->getMessage(),
                'ERROR'
            );
        }
    }

    // Resumen final
    registrarLog("=== Resumen del proceso ===");
    registrarLog("Total capacitaciones procesadas: " . count($capacitaciones));
    registrarLog("Recordatorios enviados exitosamente: {$enviados}");
    registrarLog("Errores: {$errores}");
    registrarLog("=== Proceso finalizado ===");

    // Cerrar conexión
    sqlsrv_close($conn);

    exit(0);

} catch (Exception $e) {
    registrarLog("Error crítico: " . $e->getMessage(), 'CRITICAL');
    registrarLog("=== Proceso finalizado con errores ===");

    if (isset($conn)) {
        sqlsrv_close($conn);
    }

    exit(1);
}
?>
