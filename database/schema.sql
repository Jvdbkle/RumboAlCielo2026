CREATE DATABASE IF NOT EXISTS rumbo_al_cielo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rumbo_al_cielo;

CREATE TABLE IF NOT EXISTS rol (
    idRol INT AUTO_INCREMENT PRIMARY KEY,
    nombreRol VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS persona (
    idPersona INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuario (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    contrasenia VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    estado ENUM('Habilitado', 'Deshabilitado') NOT NULL DEFAULT 'Habilitado',
    idRol INT NOT NULL,
    primerIngreso TINYINT(1) NOT NULL DEFAULT 1,
    intentosFallidos INT NOT NULL DEFAULT 0,
    bloqueadoHasta DATETIME NULL,
    FOREIGN KEY (idPersona) REFERENCES persona (idPersona),
    FOREIGN KEY (idRol) REFERENCES rol (idRol)
) ENGINE=InnoDB;

INSERT IGNORE INTO rol (idRol, nombreRol) VALUES
    (1, 'Administrador'),
    (2, 'Empleado');