

CREATE DATABASE IF NOT EXISTS noliktava
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SET NAMES utf8mb4;
USE noliktava;


DROP TABLE IF EXISTS kustibas;
DROP TABLE IF EXISTS preces;
DROP TABLE IF EXISTS piegadataji;
DROP TABLE IF EXISTS kategorijas;
DROP TABLE IF EXISTS lietotaji;


CREATE TABLE lietotaji (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    lietotajvards     VARCHAR(50) NOT NULL UNIQUE,
    parole_hash       VARCHAR(255) NOT NULL,
    vards             VARCHAR(100) NOT NULL,
    loma              ENUM('admin', 'darbinieks') NOT NULL DEFAULT 'darbinieks',
    izveidots         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE kategorijas (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nosaukums         VARCHAR(100) NOT NULL UNIQUE,
    apraksts          VARCHAR(255) NULL
) ENGINE=InnoDB;


CREATE TABLE piegadataji (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nosaukums         VARCHAR(150) NOT NULL,
    kontaktpersona    VARCHAR(100) NULL,
    talrunis          VARCHAR(30)  NULL,
    epasts            VARCHAR(100) NULL
) ENGINE=InnoDB;


CREATE TABLE preces (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    kods             VARCHAR(30) NOT NULL UNIQUE,
    nosaukums        VARCHAR(150) NOT NULL,
    kategorija_id    INT NOT NULL,
    piegadatajs_id   INT NULL,
    vieniba          VARCHAR(10) NOT NULL DEFAULT 'gab',
    cena             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    daudzums         INT NOT NULL DEFAULT 0,
    min_daudzums     INT NOT NULL DEFAULT 0,
    izveidots        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prece_kategorija FOREIGN KEY (kategorija_id)
        REFERENCES kategorijas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_prece_piegadatajs FOREIGN KEY (piegadatajs_id)
        REFERENCES piegadataji(id) ON DELETE SET NULL,
    CONSTRAINT chk_daudzums CHECK (daudzums >= 0)
) ENGINE =InnoDB;


CREATE TABLE kustibas(
    id              INT AUTO_INCREMENT PRIMARY KEY,
    prece_id        INT NOT NULL,
    tips            ENUM('saņemta', 'izsniegta') NOT NULL,
    daudzums        INT NOT NULL,
    piezime         VARCHAR(255) NULL,
    lietotajs_id    INT NOT NULL,
    datums          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kustiba_prece FOREIGN KEY (prece_id)
        REFERENCES preces(id) ON DELETE CASCADE,
    CONSTRAINT fk_kustiba_lietotajs FOREIGN KEY (lietotajs_id)
        REFERENCES lietotaji(id),
   CONSTRAINT chk_kustiba_daudzums CHECK (daudzums > 0)          
) ENGINE=InnoDB;

CREATE INDEX idx_kustibas_datums ON kustibas (datums);