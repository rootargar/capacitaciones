<?php
// Incluir validación de autenticación y permisos
require_once 'auth_check.php';

// Verificar que el usuario tenga permiso para gestionar usuarios
verificar_permiso('empleados');

// Conexión a la base de datos
include("conexion2.php");

// Incluir configuración de roles
require_once 'roles_config.php';

// Función para sanitizar entradas
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Manejo de acciones CRUD
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Procesar formulario para crear o actualizar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || $action === 'update')) {
    $clave = isset($_POST['Clave']) ? sanitize($_POST['Clave']) : '';
    $nombreCompleto = isset($_POST['NombreCompleto']) ? sanitize($_POST['NombreCompleto']) : '';
    $departamento = isset($_POST['Departamento']) ? sanitize($_POST['Departamento']) : '';
    $sucursal = isset($_POST['Sucursal']) ? sanitize($_POST['Sucursal']) : '';
    
    // Convertir la fecha a un formato que SQL Server pueda manejar
    $fechaIngreso = isset($_POST['FechaIngreso']) ? $_POST['FechaIngreso'] : '';
    // Crear un objeto DateTime PHP para la fecha
    if (!empty($fechaIngreso)) {
        $fechaObj = new DateTime($fechaIngreso);
        // No convertimos a string, dejamos que sqlsrv maneje el objeto DateTime
    } else {
        $fechaObj = null;
    }
    
    $puesto = isset($_POST['Puesto']) ? sanitize($_POST['Puesto']) : '';
    $usuario = isset($_POST['usuario']) ? sanitize($_POST['usuario']) : '';
    $pass = isset($_POST['pass']) ? sanitize($_POST['pass']) : '';
    $correo = isset($_POST['correo']) ? sanitize($_POST['correo']) : '';
    $rol = isset($_POST['rol']) ? sanitize($_POST['rol']) : 'Empleado';

    // Validar que el rol sea válido
    if (!es_rol_valido($rol)) {
        $rol = 'Empleado'; // Por defecto
    }

    if ($action === 'create') {
        // Verificar permiso para crear usuarios
        if (!verificar_permiso_accion('usuarios', 'crear', false)) {
            $error = "No tiene permisos para crear usuarios";
        } else {
            // Crear nuevo usuario
            $sql = "INSERT INTO usuarios (Clave, NombreCompleto, Departamento, Sucursal, FechaIngreso, Puesto, usuario, pass, correo, rol)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = array($clave, $nombreCompleto, $departamento, $sucursal, $fechaObj, $puesto, $usuario, $pass, $correo, $rol);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $error = "Error al crear usuario: " . print_r(sqlsrv_errors(), true);
            } else {
                $success = "Usuario creado exitosamente";
            }
        }
    } elseif ($action === 'update') {
        // Verificar permiso para editar usuarios
        if (!verificar_permiso_accion('usuarios', 'editar', false)) {
            $error = "No tiene permisos para editar usuarios";
        } else {
            // Actualizar usuario existente
            $sql = "UPDATE usuarios SET NombreCompleto = ?, Departamento = ?, Sucursal = ?,
                    FechaIngreso = ?, Puesto = ?, usuario = ?, pass = ?, correo = ?, rol = ? WHERE Clave = ?";
            $params = array($nombreCompleto, $departamento, $sucursal, $fechaObj, $puesto,
                            $usuario, $pass, $correo, $rol, $clave);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $error = "Error al actualizar usuario: " . print_r(sqlsrv_errors(), true);
            } else {
                $success = "Usuario actualizado exitosamente";
            }
        }
    }
}

 elseif ($action === 'delete' && isset($_GET['id'])) {
    // Verificar permiso para eliminar usuarios
    if (!verificar_permiso_accion('usuarios', 'eliminar', false)) {
        $error = "No tiene permisos para eliminar usuarios";
    } else {
        // Eliminar usuario
        $clave = sanitize($_GET['id']);
        $sql = "DELETE FROM usuarios WHERE Clave = ?";
        $params = array($clave);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $error = "Error al eliminar usuario: " . print_r(sqlsrv_errors(), true);
        } else {
            $success = "Usuario eliminado exitosamente";
        }
    }
}

// Obtener usuario específico para editar
$userToEdit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $clave = sanitize($_GET['id']);
    $sql = "SELECT * FROM usuarios WHERE Clave = ?";
    $params = array($clave);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt !== false) {
        $userToEdit = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
}

// Obtener todos los usuarios para mostrar en la tabla
$sql = "SELECT * FROM usuarios ORDER BY Clave";
$stmt = sqlsrv_query($conn, $sql);
$usuarios = array();

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $usuarios[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Gestión de Empleados</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Formulario para crear/editar usuario -->
        <div class="card mb-4">
            <div class="card-header">
                <?php echo $action === 'edit' ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?>
            </div>
            <div class="card-body">
                <form method="post" action="?action=<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="Clave" class="form-label">Clave</label>
                            <input type="text" class="form-control" id="Clave" name="Clave" 
                                value="<?php echo $userToEdit ? $userToEdit['Clave'] : ''; ?>" 
                                <?php echo $action === 'edit' ? 'readonly' : ''; ?> required>
                        </div>
                        <div class="col-md-6">
                            <label for="NombreCompleto" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="NombreCompleto" name="NombreCompleto" 
                                value="<?php echo $userToEdit ? $userToEdit['NombreCompleto'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="Departamento" class="form-label">Departamento</label>
                            <select class="form-select" id="Departamento" name="Departamento" required>
                                <option value="" disabled <?php echo !$userToEdit ? 'selected' : ''; ?>>Seleccione un departamento</option>
                                <option value="Refacciones" <?php echo $userToEdit && $userToEdit['Departamento'] === 'Refacciones' ? 'selected' : ''; ?>>Refacciones</option>
                                <option value="Servicio" <?php echo $userToEdit && $userToEdit['Departamento'] === 'Servicio' ? 'selected' : ''; ?>>Servicio</option>
                                <option value="Unidades" <?php echo $userToEdit && $userToEdit['Departamento'] === 'Unidades' ? 'selected' : ''; ?>>Unidades</option>
                                <option value="Administración" <?php echo $userToEdit && $userToEdit['Departamento'] === 'Administración' ? 'selected' : ''; ?>>Administración</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="Sucursal" class="form-label">Sucursal</label>
                            <select class="form-select" id="Sucursal" name="Sucursal" required>
                                <option value="" disabled <?php echo !$userToEdit ? 'selected' : ''; ?>>Seleccione una sucursal</option>
                                <option value="Matriz" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Matriz' ? 'selected' : ''; ?>>Matriz</option>
                                <option value="Mazatlan" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Mazatlan' ? 'selected' : ''; ?>>Mazatlan</option>
                                <option value="Mochis" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Mochis' ? 'selected' : ''; ?>>Mochis</option>
                                <option value="Guasave" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Guasave' ? 'selected' : ''; ?>>Guasave</option>
                                <option value="SE" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'SE' ? 'selected' : ''; ?>>SE</option>
                                <option value="Guamuchil" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Guamuchil' ? 'selected' : ''; ?>>Guamuchil</option>
                                <option value="LaCruz" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'LaCruz' ? 'selected' : ''; ?>>LaCruz</option>
                                <option value="TRPMazatlan" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'TRPMazatlan' ? 'selected' : ''; ?>>TRPMazatlan</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="FechaIngreso" class="form-label">Fecha de Ingreso</label>
                            <input type="date" class="form-control" id="FechaIngreso" name="FechaIngreso" 
                                value="<?php echo $userToEdit && $userToEdit['FechaIngreso'] ? date('Y-m-d', strtotime($userToEdit['FechaIngreso']->format('Y-m-d'))) : ''; ?>" required>
                        </div>

                       <div class="col-md-6">
                        <label for="Puesto" class="form-label">Puesto</label>
                        <select class="form-select" id="Puesto" name="Puesto" required>
                            <option value="">Seleccione un puesto</option>
                            <?php
                            // Consulta para obtener los puestos de la base de datos
                            $query = "SELECT id, puesto, depto FROM puestos ORDER BY puesto";
                            $stmt = sqlsrv_query($conn, $query);
                            
                            if ($stmt !== false) {
                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                    $selected = ($userToEdit && $userToEdit['Puesto'] == $row['puesto']) ? 'selected' : '';
                                    echo "<option value='" . $row['puesto'] . "' " . $selected . ">" . $row['puesto'] . " - " . $row['depto'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>


                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="usuario" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                value="<?php echo $userToEdit ? $userToEdit['usuario'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="pass" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="pass" name="pass" 
                                value="<?php echo $userToEdit ? $userToEdit['pass'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="correo" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="correo" name="correo"
                                value="<?php echo $userToEdit ? $userToEdit['correo'] : ''; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="rol" class="form-label">Rol del Sistema</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="" disabled <?php echo !$userToEdit ? 'selected' : ''; ?>>Seleccione un rol</option>
                                <?php
                                // Mostrar todos los roles disponibles
                                foreach ($roles_validos as $rol_opcion) {
                                    $selected = ($userToEdit && $userToEdit['rol'] === $rol_opcion) ? 'selected' : '';
                                    $descripcion = get_descripcion_rol($rol_opcion);
                                    echo "<option value='$rol_opcion' $selected>$rol_opcion - $descripcion</option>";
                                }
                                ?>
                            </select>
                            <small class="form-text text-muted">
                                El rol determina los permisos de acceso en el sistema
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-3 text-end">
                        <a href="?" class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'edit' ? 'Actualizar' : 'Crear'; ?> Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card">
            <div class="card-header">
                Lista de Usuarios
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Nombre</th>
                                <th>Departamento</th>
                                <th>Sucursal</th>
                                <th>Puesto</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No hay usuarios registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['Clave']; ?></td>
                                        <td><?php echo $usuario['NombreCompleto']; ?></td>
                                        <td><?php echo $usuario['Departamento']; ?></td>
                                        <td><?php echo $usuario['Sucursal']; ?></td>
                                        <td><?php echo $usuario['Puesto']; ?></td>
                                        <td><?php echo $usuario['usuario']; ?></td>
                                        <td><?php echo $usuario['correo']; ?></td>
                                        <td>
                                            <?php
                                            $rol_usuario = isset($usuario['rol']) ? $usuario['rol'] : 'Sin asignar';
                                            $badge_class = 'bg-secondary';
                                            if ($rol_usuario === ROL_ADMINISTRADOR) $badge_class = 'bg-danger';
                                            elseif ($rol_usuario === ROL_SUPERVISOR) $badge_class = 'bg-primary';
                                            elseif ($rol_usuario === ROL_INSTRUCTOR) $badge_class = 'bg-info';
                                            elseif ($rol_usuario === ROL_GERENTE) $badge_class = 'bg-success';
                                            elseif ($rol_usuario === ROL_EMPLEADO) $badge_class = 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $rol_usuario; ?></span>
                                        </td>
                                        <td>
                                            <?php if (verificar_permiso_accion('usuarios', 'editar', false)): ?>
                                            <a href="?action=edit&id=<?php echo $usuario['Clave']; ?>" class="btn btn-sm btn-warning me-1">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                            <?php endif; ?>
                                            <?php if (verificar_permiso_accion('usuarios', 'eliminar', false)): ?>
                                            <a href="?action=delete&id=<?php echo $usuario['Clave']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Está seguro de eliminar este usuario?')">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="validacion-usuarios.js"></script>
</body>
</html>
