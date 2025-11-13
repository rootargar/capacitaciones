<?php
// Incluir validación de autenticación y permisos
require_once 'auth_check.php';

// Verificar que el usuario tenga permiso para actividades
verificar_permiso('actividades');

// Conexión a la base de datos
include("conexion2.php");
// Inicializar variables
$id = "";
$numero = "";
$actividad = "";
$mensaje = "";
$accion = "";

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST["accion"] ?? "";
    
    // Obtener datos del formulario
    $id = $_POST["IdActividad"] ?? "";
    $numero = $_POST["Numero"] ?? "";
    $actividad = $_POST["Actividad"] ?? "";
    
    // Crear nueva actividad
    if ($accion == "crear") {
        if (!empty($numero) && !empty($actividad)) {
            $sql = "INSERT INTO Actividades (Numero, Actividad) VALUES (?, ?)";
            $params = array($numero, $actividad);
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                $mensaje = "Error al crear actividad: " . print_r(sqlsrv_errors(), true);
            } else {
                $mensaje = "Actividad creada correctamente";
                // Limpiar formulario
                $id = "";
                $numero = "";
                $actividad = "";
            }
        } else {
            $mensaje = "Por favor complete todos los campos";
        }
    }
    
    // Actualizar actividad existente
    if ($accion == "actualizar") {
        if (!empty($id) && !empty($numero) && !empty($actividad)) {
            $sql = "UPDATE Actividades SET Numero = ?, Actividad = ? WHERE IdActividad = ?";
            $params = array($numero, $actividad, $id);
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                $mensaje = "Error al actualizar actividad: " . print_r(sqlsrv_errors(), true);
            } else {
                $mensaje = "Actividad actualizada correctamente";
                // Limpiar formulario
                $id = "";
                $numero = "";
                $actividad = "";
            }
        } else {
            $mensaje = "Por favor complete todos los campos";
        }
    }
    
    // Eliminar actividad
    if ($accion == "eliminar") {
        if (!empty($id)) {
            $sql = "DELETE FROM Actividades WHERE IdActividad = ?";
            $params = array($id);
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                $mensaje = "Error al eliminar actividad: " . print_r(sqlsrv_errors(), true);
            } else {
                $mensaje = "Actividad eliminada correctamente";
                // Limpiar formulario
                $id = "";
                $numero = "";
                $actividad = "";
            }
        } else {
            $mensaje = "ID de actividad no especificado";
        }
    }
}

// Cargar datos para editar
if (isset($_GET["editar"]) && !empty($_GET["editar"])) {
    $id_editar = $_GET["editar"];
    $sql = "SELECT * FROM Actividades WHERE IdActividad = ?";
    $params = array($id_editar);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $mensaje = "Error al cargar datos: " . print_r(sqlsrv_errors(), true);
    } else {
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $id = $row["IdActividad"];
            $numero = $row["Numero"];
            $actividad = $row["Actividad"];
        }
    }
}

// Consultar todas las actividades
$sql = "SELECT * FROM Actividades ORDER BY Numero";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    $mensaje = "Error al consultar actividades: " . print_r(sqlsrv_errors(), true);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
        }
        .contenedor {
            max-width: 1200px;
            margin: 0 auto;
        }
        .mensaje {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1 class="mb-4">Gestión de Actividades</h1>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-info mensaje">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <?php echo !empty($id) ? 'Editar Actividad' : 'Nueva Actividad'; ?>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="IdActividad" value="<?php echo $id; ?>">
                            
                            <div class="mb-3">
                                <label for="Numero" class="form-label">Número:</label>
                                <input type="text" class="form-control" id="Numero" name="Numero" value="<?php echo $numero; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="Actividad" class="form-label">Actividad:</label>
                                <input type="text" class="form-control" id="Actividad" name="Actividad" value="<?php echo $actividad; ?>" required>
                            </div>
                            
                            <?php if (!empty($id)): ?>
                                <input type="hidden" name="accion" value="actualizar">
                                <button type="submit" class="btn btn-primary">Actualizar</button>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Cancelar</a>
                            <?php else: ?>
                                <input type="hidden" name="accion" value="crear">
                                <button type="submit" class="btn btn-success">Crear</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        Lista de Actividades
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Número</th>
                                    <th>Actividad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $row["IdActividad"]; ?></td>
                                    <td><?php echo $row["Numero"]; ?></td>
                                    <td><?php echo $row["Actividad"]; ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?editar=" . $row["IdActividad"]; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: inline;">
                                            <input type="hidden" name="IdActividad" value="<?php echo $row["IdActividad"]; ?>">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar esta actividad?');">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
