<?php
// Incluir validación de autenticación y permisos
require_once __DIR__ . '/../auth_check.php';

// Verificar que el usuario tenga permiso para este reporte
if (!tiene_permiso_accion(get_rol_usuario(), 'reportes', 'cursos_concluidos')) {
    die('No tiene permisos para ver este reporte');
}

// Conexión a la base de datos
include __DIR__ . '/../conexion2.php';

// Obtener información del usuario
$rol_actual = get_rol_usuario();
$clave_actual = $_SESSION['clave_usuario'];

// Construir consulta según el rol
if (es_empleado()) {
    // Empleados solo ven sus propios cursos concluidos
    $sql = "SELECT
                u.Clave,
                u.NombreCompleto,
                u.Departamento,
                u.Sucursal,
                u.Puesto,
                c.NombreCurso,
                pc.FechaFin,
                pc.Estado,
                pc.Instructor
            FROM participantes p
            INNER JOIN usuarios u ON p.Clave = u.Clave
            INNER JOIN plancursos pc ON p.IdPlan = pc.IdPlan
            INNER JOIN cursos c ON pc.IdCurso = c.IdCurso
            WHERE pc.Estado = 'completado'
            AND u.Clave = ?
            ORDER BY pc.FechaFin DESC";
    $params = array($clave_actual);
    $stmt = sqlsrv_query($conn, $sql, $params);
    $titulo = "Mis Cursos Concluidos";
} else {
    // Administradores, Supervisores y Gerentes ven todos los cursos concluidos
    $sql = "SELECT
                u.Clave,
                u.NombreCompleto,
                u.Departamento,
                u.Sucursal,
                u.Puesto,
                c.NombreCurso,
                pc.FechaFin,
                pc.Estado,
                pc.Instructor
            FROM participantes p
            INNER JOIN usuarios u ON p.Clave = u.Clave
            INNER JOIN plancursos pc ON p.IdPlan = pc.IdPlan
            INNER JOIN cursos c ON pc.IdCurso = c.IdCurso
            WHERE pc.Estado = 'completado'
            ORDER BY u.NombreCompleto, pc.FechaFin DESC";
    $stmt = sqlsrv_query($conn, $sql);
    $titulo = "Reporte de Cursos Concluidos";
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .badge-completado {
            background-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><i class="bi bi-check-circle"></i> <?php echo $titulo; ?></h2>
        <p class="mb-0">Lista de cursos completados exitosamente</p>
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
                        <th>Curso</th>
                        <th>Fecha Conclusión</th>
                        <th>Instructor</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $contador = 0;
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $contador++;
                        $fecha_fin = $row['FechaFin'] ? $row['FechaFin']->format('d/m/Y') : 'N/A';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Clave']); ?></td>
                            <td><?php echo htmlspecialchars($row['NombreCompleto']); ?></td>
                            <td><?php echo htmlspecialchars($row['Departamento']); ?></td>
                            <td><?php echo htmlspecialchars($row['Sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($row['Puesto']); ?></td>
                            <td><?php echo htmlspecialchars($row['NombreCurso']); ?></td>
                            <td><?php echo $fecha_fin; ?></td>
                            <td><?php echo htmlspecialchars($row['Instructor']); ?></td>
                            <td><span class="badge badge-completado">Completado</span></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($contador === 0): ?>
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle"></i> No se encontraron cursos concluidos.
            </div>
        <?php else: ?>
            <div class="mt-3">
                <strong>Total de registros: <?php echo $contador; ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportarExcel() {
            const tabla = document.getElementById('tablaCursos');
            const wb = XLSX.utils.table_to_book(tabla, {sheet: "Cursos Concluidos"});
            XLSX.writeFile(wb, 'cursos_concluidos.xlsx');
        }
    </script>
</body>
</html>
