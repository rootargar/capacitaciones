<?php
// Incluir validación de autenticación y permisos
require_once 'auth_check.php';

// Verificar que el usuario tenga permiso para actividades por puesto
verificar_permiso('cursoxpuesto');

// Incluir el archivo de conexión
include 'conexion2.php';

// Inicializar variables
$puestoSeleccionado = isset($_GET['puesto']) ? $_GET['puesto'] : NULL;
$idPuestoSeleccionado = isset($_GET['id_puesto']) ? $_GET['id_puesto'] : NULL;
$departamentoSeleccionado = isset($_GET['depto']) ? $_GET['depto'] : NULL;
$mensajeExito = '';
$mensajeError = '';

// Procesar asignación de actividad a puesto
if(isset($_POST['asignar_actividad']) && isset($_POST['id_actividad']) && !empty($_POST['id_actividad']) && !empty($idPuestoSeleccionado)) {
    $idActividad = $_POST['id_actividad'];
    $actividad = $_POST['actividad'];
    
    // Verificar si la actividad ya está asignada a este puesto
    $sqlVerificar = "SELECT COUNT(*) AS existe FROM ActividadesXPuesto WHERE puesto = ? AND IdActividad = ?";
    $paramsVerificar = array($puestoSeleccionado, $idActividad);
    $stmtVerificar = sqlsrv_query($conn, $sqlVerificar, $paramsVerificar);
    
    if($stmtVerificar !== false) {
        $row = sqlsrv_fetch_array($stmtVerificar, SQLSRV_FETCH_ASSOC);
        if($row['existe'] == 0) {
            // Insertar la asignación de actividad a puesto
            $sqlInsertar = "INSERT INTO ActividadesXPuesto (puesto, Actividad, IdActividad, Departamento) VALUES (?, ?, ?, ?)";
            $paramsInsertar = array($puestoSeleccionado, $actividad, $idActividad, $departamentoSeleccionado);
            $stmtInsertar = sqlsrv_query($conn, $sqlInsertar, $paramsInsertar);
            
            if($stmtInsertar !== false) {
                $mensajeExito = "Actividad asignada correctamente al puesto.";
            } else {
                $mensajeError = "Error al asignar la actividad al puesto: " . print_r(sqlsrv_errors(), true);
            }
        } else {
            $mensajeError = "Esta actividad ya está asignada a este puesto.";
        }
    } else {
        $mensajeError = "Error al verificar la existencia de la actividad en el puesto: " . print_r(sqlsrv_errors(), true);
    }
}

// Procesar desasignación de actividad a puesto
if(isset($_GET['desasignar']) && !empty($_GET['desasignar']) && !empty($idPuestoSeleccionado) && !empty($puestoSeleccionado)) {
    $idActividadDesasignar = $_GET['desasignar'];
    
    // Eliminar la asignación de la actividad al puesto
    $sqlEliminar = "DELETE FROM ActividadesXPuesto WHERE puesto = ? AND IdActividad = ?";
    $paramsEliminar = array($puestoSeleccionado, $idActividadDesasignar);
    $stmtEliminar = sqlsrv_query($conn, $sqlEliminar, $paramsEliminar);
    
    if($stmtEliminar !== false) {
        $mensajeExito = "Actividad desasignada correctamente del puesto.";
    } else {
        $mensajeError = "Error al desasignar la actividad del puesto: " . print_r(sqlsrv_errors(), true);
    }
}

// Consultar todos los puestos para el selector
$sqlPuestos = "SELECT Id, puesto, depto FROM puestos ORDER BY depto, puesto";
$stmtPuestos = sqlsrv_query($conn, $sqlPuestos);

if($stmtPuestos === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consultar todas las actividades disponibles (si hay un puesto seleccionado)
$actividades = array();
if(!empty($puestoSeleccionado)) {
    $sqlActividades = "SELECT IdActividad, Actividad FROM Actividades 
                      WHERE IdActividad NOT IN (
                        SELECT IdActividad FROM ActividadesXPuesto WHERE puesto = ?
                      )
                      ORDER BY Actividad";
    $paramsActividades = array($puestoSeleccionado);
    $stmtActividades = sqlsrv_query($conn, $sqlActividades, $paramsActividades);
    
    if($stmtActividades !== false) {
        while($row = sqlsrv_fetch_array($stmtActividades, SQLSRV_FETCH_ASSOC)) {
            $actividades[] = $row;
        }
    } else {
        $mensajeError = "Error al consultar actividades disponibles: " . print_r(sqlsrv_errors(), true);
    }
}

// Consultar las actividades ya asignadas al puesto seleccionado
$actividadesAsignadas = array();
if(!empty($puestoSeleccionado)) {
    $sqlAsignadas = "SELECT ap.IdActividad, ap.Actividad
                     FROM ActividadesXPuesto ap
                     WHERE ap.puesto = ?
                     ORDER BY ap.Actividad";
    $paramsAsignadas = array($puestoSeleccionado);
    $stmtAsignadas = sqlsrv_query($conn, $sqlAsignadas, $paramsAsignadas);
    
    if($stmtAsignadas !== false) {
        while($row = sqlsrv_fetch_array($stmtAsignadas, SQLSRV_FETCH_ASSOC)) {
            $actividadesAsignadas[] = $row;
        }
    } else {
        $mensajeError = "Error al consultar actividades asignadas: " . print_r(sqlsrv_errors(), true);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Actividades por Puesto</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome para íconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 20px;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .card {
            margin-bottom: 20px;
        }
        .alert {
            margin-top: 20px;
        }
        .table-card {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Asignación de Actividades por Puesto</h2>
        
        <?php if(!empty($mensajeExito)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensajeExito; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($mensajeError)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $mensajeError; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Selector de Puesto -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Seleccione un Puesto</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <select id="selector-puesto" class="form-select form-select-lg mb-3" aria-label="Seleccionar Puesto">
                            <option value="">-- Seleccione un Puesto --</option>
                            <?php while($puesto = sqlsrv_fetch_array($stmtPuestos, SQLSRV_FETCH_ASSOC)): ?>
                                <option value="<?php echo $puesto['Id']; ?>" 
                                        data-nombre="<?php echo $puesto['puesto']; ?>"
                                        data-depto="<?php echo $puesto['depto']; ?>"
                                        <?php echo ($idPuestoSeleccionado == $puesto['Id']) ? 'selected' : ''; ?>>
                                    <?php echo $puesto['puesto'] . ' - ' . $puesto['depto']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(!empty($puestoSeleccionado)): ?>
            <div class="row">
                <!-- Actividades Disponibles -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Actividades Disponibles</h5>
                        </div>
                        <div class="card-body table-card">
                            <table id="tablaActividadesDisponibles" class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Actividad</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($actividades)): ?>
                                        <?php foreach($actividades as $actividad): ?>
                                        <tr>
                                            <td><?php echo $actividad['Actividad']; ?></td>
                                            <td class="action-buttons">
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="id_actividad" value="<?php echo $actividad['IdActividad']; ?>">
                                                    <input type="hidden" name="actividad" value="<?php echo $actividad['Actividad']; ?>">
                                                    <button type="submit" name="asignar_actividad" class="btn btn-success btn-sm">
                                                        <i class="fas fa-plus"></i> Asignar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>No hay actividades disponibles para asignar.</td>
                                            <td></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Actividades Asignadas -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Actividades Asignadas a: <?php echo $puestoSeleccionado; ?></h5>
                        </div>
                        <div class="card-body table-card">
                            <table id="tablaActividadesAsignadas" class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Actividad</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($actividadesAsignadas)): ?>
                                        <?php foreach($actividadesAsignadas as $asignada): ?>
                                        <tr>
                                            <td><?php echo $asignada['Actividad']; ?></td>
                                            <td class="action-buttons">
                                                <a href="javascript:void(0);" onclick="confirmarDesasignar(<?php echo $asignada['IdActividad']; ?>)" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-minus"></i> Desasignar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>No hay actividades asignadas a este puesto.</td>
                                            <td></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                Por favor, seleccione un puesto para ver y gestionar las actividades asignadas.
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS y Popper -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- jQuery y DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#tablaActividadesDisponibles, #tablaActividadesAsignadas').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true,
                searching: true,
                paging: true,
                info: true,
                lengthMenu: [5, 10, 25, 50],
                pageLength: 5
            });
            
            // Evento change del selector de puesto
            $('#selector-puesto').change(function() {
                var idPuesto = $(this).val();
                var nombrePuesto = $(this).find('option:selected').data('nombre');
                var departamento = $(this).find('option:selected').data('depto');
                
                if(idPuesto) {
                    window.location.href = 'actividadesxpuesto.php?id_puesto=' + idPuesto + '&puesto=' + encodeURIComponent(nombrePuesto) + '&depto=' + encodeURIComponent(departamento);
                } else {
                    window.location.href = 'actividadesxpuesto.php';
                }
            });
        });
        
        // Función para confirmar desasignación
        function confirmarDesasignar(idActividad) {
            if(confirm('¿Está seguro de que desea desasignar esta actividad del puesto?')) {
                window.location.href = 'actividadesxpuesto.php?id_puesto=<?php echo $idPuestoSeleccionado; ?>&puesto=<?php echo urlencode($puestoSeleccionado); ?>&depto=<?php echo urlencode($departamentoSeleccionado); ?>&desasignar=' + idActividad;
            }
        }
    </script>
</body>
</html>