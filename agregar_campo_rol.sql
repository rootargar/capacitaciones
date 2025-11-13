-- Script SQL para agregar el campo 'rol' a la tabla usuarios
-- Base de datos: RHKW
-- Servidor: SQL Server

USE RHKW;
GO

-- Verificar si la columna 'rol' ya existe antes de agregarla
IF NOT EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.usuarios')
    AND name = 'rol'
)
BEGIN
    -- Agregar columna 'rol' a la tabla usuarios
    ALTER TABLE usuarios
    ADD rol NVARCHAR(50) NULL;

    PRINT 'Columna rol agregada exitosamente';
END
ELSE
BEGIN
    PRINT 'La columna rol ya existe en la tabla usuarios';
END
GO

-- Actualizar usuarios existentes con un rol por defecto
-- Se asigna 'Empleado' como rol por defecto a usuarios sin rol
UPDATE usuarios
SET rol = 'Empleado'
WHERE rol IS NULL OR rol = '';
GO

-- Crear restricción CHECK para validar que solo se ingresen roles válidos
IF NOT EXISTS (
    SELECT * FROM sys.check_constraints
    WHERE name = 'CHK_usuarios_rol'
)
BEGIN
    ALTER TABLE usuarios
    ADD CONSTRAINT CHK_usuarios_rol
    CHECK (rol IN ('Administrador', 'Supervisor', 'Instructor', 'Gerente', 'Empleado'));

    PRINT 'Restricción CHECK creada para validar roles';
END
ELSE
BEGIN
    PRINT 'La restricción CHECK para roles ya existe';
END
GO

-- Opcional: Crear un índice en la columna rol para mejorar rendimiento en consultas
IF NOT EXISTS (
    SELECT * FROM sys.indexes
    WHERE name = 'IX_usuarios_rol'
    AND object_id = OBJECT_ID(N'dbo.usuarios')
)
BEGIN
    CREATE INDEX IX_usuarios_rol ON usuarios(rol);
    PRINT 'Índice creado en la columna rol';
END
ELSE
BEGIN
    PRINT 'El índice en la columna rol ya existe';
END
GO

-- Mostrar los usuarios actualizados
SELECT Clave, NombreCompleto, usuario, rol
FROM usuarios
ORDER BY rol, NombreCompleto;
GO

PRINT 'Script completado exitosamente';
GO
