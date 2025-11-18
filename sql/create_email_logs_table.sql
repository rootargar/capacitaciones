-- Script SQL para crear la tabla de logs de emails
-- Base de datos: RHKW
-- Sistema de Capacitaciones

USE RHKW;
GO

-- Eliminar tabla si existe (descomentar solo si necesitas recrearla)
-- DROP TABLE IF EXISTS email_logs;
-- GO

-- Crear tabla de logs de emails
CREATE TABLE email_logs (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    IdCapacitacion INT NULL,                  -- FK a capacitaciones (puede ser NULL para emails no relacionados)
    IdEmpleado VARCHAR(50) NULL,              -- FK a usuarios (puede ser NULL)
    EmailDestino VARCHAR(255) NOT NULL,       -- Email del destinatario
    TipoNotificacion VARCHAR(100) NOT NULL,   -- Tipo: 'asignacion', 'recordatorio', 'asistencia', 'certificado'
    Asunto VARCHAR(500) NOT NULL,             -- Asunto del email
    Estado VARCHAR(50) NOT NULL,              -- Estado: 'enviado', 'error', 'pendiente'
    MensajeError TEXT NULL,                   -- Mensaje de error si falla
    FechaEnvio DATETIME DEFAULT GETDATE(),    -- Fecha y hora de envío
    IntentoNumero INT DEFAULT 1,              -- Número de intento
    CONSTRAINT FK_EmailLogs_Capacitaciones FOREIGN KEY (IdCapacitacion)
        REFERENCES capacitaciones(Id) ON DELETE CASCADE,
    CONSTRAINT FK_EmailLogs_Empleados FOREIGN KEY (IdEmpleado)
        REFERENCES usuarios(Clave) ON DELETE CASCADE
);
GO

-- Crear índices para mejorar performance
CREATE INDEX IDX_EmailLogs_Estado ON email_logs(Estado);
CREATE INDEX IDX_EmailLogs_TipoNotificacion ON email_logs(TipoNotificacion);
CREATE INDEX IDX_EmailLogs_FechaEnvio ON email_logs(FechaEnvio);
CREATE INDEX IDX_EmailLogs_EmailDestino ON email_logs(EmailDestino);
GO

-- Comentarios en las columnas
EXEC sp_addextendedproperty
    @name = N'MS_Description', @value = 'ID único del log de email',
    @level0type = N'SCHEMA', @level0name = 'dbo',
    @level1type = N'TABLE', @level1name = 'email_logs',
    @level2type = N'COLUMN', @level2name = 'Id';

EXEC sp_addextendedproperty
    @name = N'MS_Description', @value = 'Tipos: asignacion, recordatorio, asistencia, certificado',
    @level0type = N'SCHEMA', @level0name = 'dbo',
    @level1type = N'TABLE', @level1name = 'email_logs',
    @level2type = N'COLUMN', @level2name = 'TipoNotificacion';

EXEC sp_addextendedproperty
    @name = N'MS_Description', @value = 'Estados: enviado, error, pendiente',
    @level0type = N'SCHEMA', @level0name = 'dbo',
    @level1type = N'TABLE', @level1name = 'email_logs',
    @level2type = N'COLUMN', @level2name = 'Estado';

PRINT 'Tabla email_logs creada exitosamente';
GO
