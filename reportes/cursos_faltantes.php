<?php
// Incluir validación de autenticación y permisos
require_once __DIR__ . '/../auth_check.php';

// Verificar que el usuario tenga permiso para este reporte
if (!tiene_permiso_accion(get_rol_usuario(), 'reportes', 'cursos_faltantes')) {
    die('No tiene permisos para ver este reporte');
}

// Conexión a la base de datos
include __DIR__ . '/../conexion2.php';

// Obtener información del usuario
$rol_actual = get_rol_usuario();
$clave_actual = $_SESSION['clave_usuario'];

// Construir consulta según el rol
if (es_empleado()) {
    // Empleados solo ven sus propios cursos faltantes
    $sql = "SELECT
                u.Clave,
                u.NombreCompleto,
                u.Departamento,
                u.Sucursal,
                u.Puesto,
                c.NombreCurso,
                c.Duracion,
                cp.Obligatorio
            FROM usuarios u
            INNER JOIN puestos p ON u.Puesto = p.puesto
            INNER JOIN cursoxpuesto cp ON p.id = cp.IdPuesto
            INNER JOIN cursos c ON cp.IdCurso = c.IdCurso
            WHERE u.Clave = ?
            AND NOT EXISTS (
                SELECT 1
                FROM participantes part
                INNER JOIN plancursos pc ON part.IdPlan = pc.IdPlan
                WHERE part.Clave = u.Clave
                AND pc.IdCurso = c.IdCurso
                AND pc.Estado = 'completado'
            )
            ORDER BY c.NombreCurso";
    $params = array($clave_actual);
    $stmt = sqlsrv_query($conn, $sql, $params);
    $titulo = "Mis Cursos Pendientes";
} else {
    // Administradores, Supervisores y Gerentes ven todos los cursos faltantes
    $sql = "SELECT
                u.Clave,
                u.NombreCompleto,
                u.Departamento,
                u.Sucursal,
                u.Puesto,
                c.NombreCurso,
                c.Duracion,
                cp.Obligatorio
            FROM usuarios u
            INNER JOIN puestos p ON u.Puesto = p.puesto
            INNER JOIN cursoxpuesto cp ON p.id = cp.IdPuesto
            INNER JOIN cursos c ON cp.IdCurso = c.IdCurso
            WHERE NOT EXISTS (
                SELECT 1
                FROM participantes part
                INNER JOIN plancursos pc ON part.IdPlan = pc.IdPlan
                WHERE part.Clave = u.Clave
                AND pc.IdCurso = c.IdCurso
                AND pc.Estado = 'completado'
            )
            ORDER BY u.NombreCompleto, c.NombreCurso";
    $stmt = sqlsrv_query($conn, $sql);
    $titulo = "Reporte de Cursos Faltantes por Empleado";
}

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .badge-obligatorio {
            background-color: #dc3545;
        }
        .badge-opcional {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><i class="bi bi-exclamation-triangle"></i> <?php echo $titulo; ?></h2>
        <p class="mb-0">Cursos requeridos que aún no han sido completados</p>
    </div>

    <div class="table-container">
        <div class="mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <button onclick="exportarExcel()" class="btn btn-success">
                <i class="bi bi-file-excel"></i> Exportar a Excel
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover" id="tablaCursos">
                <thead class="table-dark">
                    <tr>
                        <th>Clave</th>
                        <th>Empleado</th>
                        <th>Departamento</th>
                        <th>Sucursal</th>
                        <th>Puesto</th>
                        <th>Curso Faltante</th>
                        <th>Duración (hrs)</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $contador = 0;
                    $contador_obligatorios = 0;
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $contador++;
                        $obligatorio = $row['Obligatorio'];
                        if ($obligatorio == 1 || $obligatorio === 'SI' || $obligatorio === 'Si') {
                            $badge_class = 'badge-obligatorio';
                            $badge_text = 'Obligatorio';
                            $contador_obligatorios++;
                        } else {
                            $badge_class = 'badge-opcional';
                            $badge_text = 'Opcional';
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Clave']); ?></td>
                            <td><?php echo htmlspecialchars($row['NombreCompleto']); ?></td>
                            <td><?php echo htmlspecialchars($row['Departamento']); ?></td>
                            <td><?php echo htmlspecialchars($row['Sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($row['Puesto']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['NombreCurso']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['Duracion']); ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($contador === 0): ?>
            <div class="alert alert-success mt-3">
                <i class="bi bi-check-circle"></i> ¡Felicidades! No tienes cursos pendientes.
            </div>
        <?php else: ?>
            <div class="mt-3">
                <strong>Total de cursos faltantes: <?php echo $contador; ?></strong>
                <?php if ($contador_obligatorios > 0): ?>
                    <span class="badge badge-obligatorio ms-2"><?php echo $contador_obligatorios; ?> Obligatorios</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportarExcel() {
            const tabla = document.getElementById('tablaCursos');
            const wb = XLSX.utils.table_to_book(tabla, {sheet: "Cursos Faltantes"});
            XLSX.writeFile(wb, 'cursos_faltantes.xlsx');
        }
    </script>
</body>
</html>
