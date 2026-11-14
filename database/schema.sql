CREATE DATABASE  rumbo_al_cielo
USE rumbo_al_cielo;

CREATE TABLE  rol (
    idRol INT AUTO_INCREMENT PRIMARY KEY,
    nombreRol VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE  persona (
    idPersona INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE  usuario (
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
    
    CREATE TABLE  familiar (
    idFamiliar INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    idUsuario INT NOT NULL,
    parentesco ENUM('conyuge', 'hijo', 'hija') NOT NULL,
    estado ENUM('Activo', 'Eliminado') NOT NULL DEFAULT 'Activo',
    FOREIGN KEY (idPersona) REFERENCES persona (idPersona),
    FOREIGN KEY (idUsuario) REFERENCES usuario (idUsuario)
) ENGINE=InnoDB;

CREATE TABLE  feriado (
    idFeriado INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    motivo VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE  menu (
    idMenu INT AUTO_INCREMENT PRIMARY KEY,
    fechaInicio DATE NOT NULL UNIQUE,
    estado ENUM('Pendiente', 'Publicado') NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB;

CREATE TABLE  plato (
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
CREATE TABLE pension (
    idPension INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    idUsuario INT NOT NULL,
    mes DATE NOT NULL,
    tarifa DECIMAL(6,2) NOT NULL DEFAULT 15.00,
    estado ENUM('Pendiente','Activa','Rechazada') NOT NULL DEFAULT 'Pendiente',
    CONSTRAINT uq_pension_persona_mes UNIQUE (idPersona, mes),
    CONSTRAINT fk_pension_persona FOREIGN KEY (idPersona) REFERENCES persona(idPersona),
    CONSTRAINT fk_pension_usuario FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario)
);

CREATE TABLE reserva (
    idReserva INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    idUsuario INT NOT NULL,
    idPlato INT NOT NULL,
    fecha DATE NOT NULL,
    modalidad ENUM('Diaria','Pensionada') NOT NULL DEFAULT 'Diaria',
    tarifa DECIMAL(6,2) NOT NULL,
    estado ENUM('Confirmada','Entregada','Cancelada') NOT NULL DEFAULT 'Confirmada',
    codigoQR VARCHAR(64) NOT NULL UNIQUE,
    horaEntrega DATETIME NULL,
    CONSTRAINT fk_reserva_persona FOREIGN KEY (idPersona) REFERENCES persona(idPersona),
    CONSTRAINT fk_reserva_usuario FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario),
    CONSTRAINT fk_reserva_plato FOREIGN KEY (idPlato) REFERENCES plato(idPlato)
);