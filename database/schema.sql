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
    telefono VARCHAR(8) NOT NULL,
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
    
    CREATE TABLE IF NOT EXISTS familiar (
    idFamiliar INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    idUsuario INT NOT NULL,
    parentesco ENUM('conyuge', 'hijo', 'hija') NOT NULL,
    estado ENUM('Activo', 'Eliminado') NOT NULL DEFAULT 'Activo',
    FOREIGN KEY (idPersona) REFERENCES persona (idPersona),
    FOREIGN KEY (idUsuario) REFERENCES usuario (idUsuario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feriado (
    idFeriado INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    motivo VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu (
    idMenu INT AUTO_INCREMENT PRIMARY KEY,
    fechaInicio DATE NOT NULL UNIQUE,
    estado ENUM('Pendiente', 'Publicado') NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plato (
    idPlato INT AUTO_INCREMENT PRIMARY KEY,
    idMenu INT NOT NULL,
    fecha DATE NOT NULL UNIQUE,
    sopa VARCHAR(100) NOT NULL,
    platoPrincipal VARCHAR(100) NOT NULL,
    ensaladaGuarnicion VARCHAR(100) NOT NULL,
    postre VARCHAR(100) NOT NULL,
    refresco VARCHAR(100) NOT NULL,
    fotoSopa VARCHAR(255) NOT NULL,
    fotoPlatoPrincipal VARCHAR(255) NOT NULL,
    FOREIGN KEY (idMenu) REFERENCES menu (idMenu)
) ENGINE=InnoDB;