# Sistema de Roles y Autenticación - Documentación de Implementación

## Resumen de Cambios

Se ha implementado un sistema completo de validación de usuarios y roles para el sistema de control de capacitaciones. El sistema ahora controla el acceso basándose en 5 roles diferentes con permisos específicos.

## Roles Implementados

### 1. Administrador
- **Acceso**: Total al sistema
- **Permisos**:
  - Gestión completa de usuarios (crear, editar, eliminar)
  - Gestión de cursos, puestos, y asignaciones
  - Acceso a todos los reportes
  - Administración de capacitaciones y certificados

### 2. Supervisor
- **Acceso**: Reportes y usuarios limitados
- **Permisos**:
  - Gestión de usuarios (crear, editar - sin eliminar)
  - Gestión de cursos, puestos, y asignaciones
  - Acceso a todos los reportes
  - Administración de capacitaciones y certificados

### 3. Instructor
- **Acceso**: Catálogo de cursos y captura de capacitaciones
- **Permisos**:
  - Ver catálogo de cursos
  - Programar capacitaciones
  - Asignar participantes
  - Gestionar certificaciones
  - Sin acceso a gestión de usuarios, puestos o reportes

### 4. Gerente
- **Acceso**: Reportes generales
- **Permisos**:
  - Visualización de reportes generales
  - Visualización de certificaciones
  - Sin acceso a módulos administrativos

### 5. Empleado
- **Acceso**: Solo ver sus propias capacitaciones
- **Permisos**:
  - Ver únicamente sus propias capacitaciones y certificados
  - Sin acceso a otros módulos del sistema

## Archivos Creados

### 1. `roles_config.php`
Archivo de configuración centralizada de roles y permisos.

**Funcionalidades:**
- Define las constantes de roles
- Configura la matriz de permisos por módulo
- Funciones auxiliares para verificación de permisos:
  - `tiene_permiso($rol, $modulo)`: Verifica acceso a módulos
  - `tiene_permiso_accion($rol, $modulo, $accion)`: Verifica permisos específicos
  - `es_rol_valido($rol)`: Valida roles
  - `get_descripcion_rol($rol)`: Obtiene descripciones

### 2. `auth_check.php`
Middleware de autenticación y validación de sesiones.

**Funcionalidades:**
- `requerir_autenticacion()`: Protege páginas que requieren login
- `verificar_permiso($modulo)`: Valida acceso a módulos
- `verificar_permiso_accion($modulo, $accion)`: Valida acciones específicas
- `get_rol_usuario()`: Obtiene el rol del usuario actual
- `get_usuario_actual()`: Obtiene el nombre de usuario
- `es_administrador()`, `es_supervisor()`, etc.: Verificadores de rol
- `puede_ver_registro($clave_usuario)`: Para empleados que solo ven lo suyo

### 3. `agregar_campo_rol.sql`
Script SQL para agregar el campo 'rol' a la tabla usuarios.

**Características:**
- Agrega columna 'rol' tipo NVARCHAR(50)
- Establece 'Empleado' como valor por defecto
- Crea restricción CHECK para validar roles
- Crea índice para optimizar consultas
- Maneja casos donde la columna ya existe

## Archivos Modificados

### 1. `login.php` (líneas 11-32)
**Cambios:**
- Se modificó la consulta SQL para incluir campos: `rol`, `NombreCompleto`, `Clave`
- Se agregaron variables de sesión:
  - `$_SESSION['rol']`: Rol del usuario
  - `$_SESSION['nombre_completo']`: Nombre completo
  - `$_SESSION['clave_usuario']`: Clave del empleado

### 2. `usuarios.php` (múltiples secciones)
**Cambios:**
- **Líneas 1-12**: Agregada validación de autenticación y permisos
- **Líneas 43-48**: Validación del campo rol en POST
- **Líneas 50-66**: Verificación de permisos para crear usuarios
- **Líneas 67-84**: Verificación de permisos para editar usuarios
- **Líneas 88-104**: Verificación de permisos para eliminar usuarios
- **Líneas 247-265**: Nuevo campo de selección de rol en formulario
- **Líneas 285-295**: Agregada columna 'Rol' en tabla
- **Líneas 313-324**: Badge de rol con colores según tipo
- **Líneas 326-336**: Botones condicionados a permisos

### 3. `principal.php` (múltiples secciones)
**Cambios:**
- **Líneas 1-14**: Reemplazo de validación básica por sistema de roles
- **Líneas 61-132**: Menú de navegación con permisos condicionales
- **Líneas 138-153**: Información del usuario con nombre completo y badge de rol

### 4. Páginas protegidas (todas con el mismo patrón)
Se agregó validación de autenticación al inicio de cada archivo:

- **`cursos.php`**: Requiere permiso 'cursos'
- **`puestos.php`**: Requiere permiso 'puestos'
- **`cursoxpuesto.php`**: Requiere permiso 'cursoxpuesto'
- **`planeacion.php`**: Requiere permiso 'planeacion'
- **`asignar_participantes.php`**: Requiere permiso 'capacitacion'
- **`certificaciones.php`**: Requiere permiso 'certificados'
- **`reportes.php`**: Requiere permiso 'reportes'
- **`actividades.php`**: Requiere permiso 'actividades'
- **`actividadesxpuesto.php`**: Requiere permiso 'cursoxpuesto'

## Pasos de Implementación

### 1. Ejecutar el Script SQL
```sql
-- Ejecutar en SQL Server Management Studio o herramienta similar
-- Conectarse a la base de datos RHKW
USE RHKW;
GO

-- Ejecutar el contenido de agregar_campo_rol.sql
```

### 2. Verificar los archivos subidos
Asegúrese de que los siguientes archivos estén en el directorio raíz:
- `roles_config.php`
- `auth_check.php`
- `agregar_campo_rol.sql`

### 3. Asignar roles a usuarios existentes
Después de ejecutar el script SQL, todos los usuarios tendrán el rol 'Empleado' por defecto. Debe asignar los roles correspondientes:

**Opción A - Mediante la interfaz web:**
1. Iniciar sesión como administrador (deberá asignar temporalmente este rol manualmente)
2. Ir a "Empleados" en el menú
3. Editar cada usuario y asignar el rol correspondiente

**Opción B - Mediante SQL:**
```sql
-- Ejemplo: Asignar rol de administrador
UPDATE usuarios SET rol = 'Administrador' WHERE usuario = 'admin';

-- Ejemplo: Asignar rol de supervisor
UPDATE usuarios SET rol = 'Supervisor' WHERE usuario = 'supervisor1';
```

### 4. Probar el sistema
1. Cerrar todas las sesiones activas
2. Iniciar sesión con diferentes usuarios
3. Verificar que el menú muestre solo las opciones permitidas
4. Intentar acceder directamente a URLs protegidas (debe redirigir)

## Matriz de Permisos por Rol

| Módulo | Administrador | Supervisor | Instructor | Gerente | Empleado |
|--------|--------------|------------|------------|---------|----------|
| Empleados | ✓ | ✓ | ✗ | ✗ | ✗ |
| Cursos | ✓ | ✓ | ✓ | ✗ | ✗ |
| Puestos | ✓ | ✓ | ✗ | ✗ | ✗ |
| Cursos x Puesto | ✓ | ✓ | ✗ | ✗ | ✗ |
| Planeación | ✓ | ✓ | ✓ | ✗ | ✗ |
| Asignar Participantes | ✓ | ✓ | ✓ | ✗ | ✗ |
| Certificados | ✓ | ✓ | ✓ | ✓ | ✓* |
| Reportes | ✓ | ✓ | ✗ | ✓ | ✗ |
| Actividades | ✓ | ✓ | ✓ | ✗ | ✗ |

*Los empleados solo pueden ver sus propios certificados

## Características de Seguridad

### 1. Validación de Sesión
- Todas las páginas verifican que el usuario esté autenticado
- Si no hay sesión activa, redirige automáticamente al login
- La sesión incluye información del usuario: nombre, rol, clave

### 2. Control de Acceso por Roles
- Cada página valida que el usuario tenga el rol adecuado
- El menú de navegación se adapta dinámicamente según el rol
- Intentos de acceso no autorizado redirigen a la página principal

### 3. Validación de Acciones
- Dentro de cada módulo se validan acciones específicas (crear, editar, eliminar)
- Los botones de acción se muestran solo si el usuario tiene permisos
- Por ejemplo: solo Administradores pueden eliminar usuarios

### 4. Registro de Auditoría
- El sistema de login ya registra intentos exitosos y fallidos
- Se pueden agregar logs adicionales de accesos denegados

## Personalización

### Agregar un nuevo rol
1. Editar `roles_config.php`
2. Definir la nueva constante: `define('ROL_NUEVO', 'NombreRol');`
3. Agregarlo al array `$roles_validos`
4. Configurar permisos en la matriz `$permisos`
5. Actualizar la restricción CHECK en la base de datos

### Modificar permisos de un rol existente
1. Editar `roles_config.php`
2. Modificar la matriz `$permisos` para el módulo deseado
3. Ajustar `$permisos_acciones` si es necesario

### Agregar protección a una nueva página
```php
<?php
// Al inicio del archivo
require_once 'auth_check.php';

// Verificar autenticación
requerir_autenticacion();

// Verificar permiso para el módulo
verificar_permiso('nombre_modulo');

// El resto del código...
?>
```

## Solución de Problemas

### Error: "No tiene un rol asignado"
**Causa**: El usuario no tiene un rol configurado en la base de datos
**Solución**: Ejecutar el script SQL o asignar manualmente el rol al usuario

### Error: "No tiene permisos para acceder a este módulo"
**Causa**: El usuario no tiene el rol adecuado para acceder a la página
**Solución**: Verificar que el rol del usuario sea correcto y tenga los permisos necesarios

### La sesión se cierra automáticamente
**Causa**: Puede ser un problema de configuración de sesiones de PHP
**Solución**: Verificar la configuración de `session.gc_maxlifetime` en php.ini

### El menú no muestra las opciones correctas
**Causa**: Problema con la variable de sesión `$_SESSION['rol']`
**Solución**:
1. Cerrar sesión completamente
2. Verificar que el rol esté correctamente almacenado en la BD
3. Volver a iniciar sesión

## Mejoras Futuras Sugeridas

1. **Hash de Contraseñas**: Implementar `password_hash()` y `password_verify()` de PHP
2. **Tokens CSRF**: Protección contra ataques Cross-Site Request Forgery
3. **Logs de Auditoría Mejorados**: Registrar todas las acciones importantes
4. **Recuperación de Contraseña**: Sistema de restablecimiento por email
5. **Sesiones con Timeout**: Cierre automático de sesión por inactividad
6. **Autenticación de Dos Factores**: Mayor seguridad para roles administrativos
7. **API de Permisos**: Endpoints para consultar permisos desde JavaScript

## Contacto y Soporte

Para preguntas o problemas con la implementación, contactar al equipo de desarrollo.

---
**Fecha de Implementación**: 2025-01-13
**Versión del Sistema**: 2.0
**Última actualización de este documento**: 2025-01-13
