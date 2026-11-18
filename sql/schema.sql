-- Noliktavas uzskaites sistēmas datubāzes struktūra.
-- Izveido datubāzi ar utf8mb4 kodējumu, lai varētu glabāt latviešu burtus.
CREATE DATABASE IF NOT EXISTS noliktava
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SET NAMES utf8mb4;  -- Savienojums ar serveri arī izmanto UTF8.
USE noliktava;

-- Izdzēš vecās tabulas, lai skriptu varētu palaist atkārtoti.
-- Sākam ar tabulām, kuras atsaucas uz citām (ārējo PK dēļ)
DROP TABLE IF EXISTS kustibas;
DROP TABLE IF EXISTS preces;
DROP TABLE IF EXISTS klienti;
DROP TABLE IF EXISTS piegadataji;
DROP TABLE IF EXISTS kategorijas;
DROP TABLE IF EXISTS lietotaji;

-- Sistēmas lietotāji (pieslēgšanās un tiesības)
CREATE TABLE lietotaji (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    lietotajvards     VARCHAR(50) NOT NULL UNIQUE,  --lietotājvārds nedrīkst atkārtoties.
    parole_hash       VARCHAR(255) NOT NULL,        --password_hash() rezultāts, nevis pati parole.
    vards             VARCHAR(100) NOT NULL,        --vārds kas redzams sistēmā
    loma              ENUM('admin', 'darbinieks') NOT NULL DEFAULT 'darbinieks', --admin drīkst dzēst un pārvaldīt lietotājus
    izveidots         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Preču kategorijas
CREATE TABLE kategorijas (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nosaukums         VARCHAR(100) NOT NULL UNIQUE,
    apraksts          VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Piegādatāji no kā saņem preces.
CREATE TABLE piegadataji (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nosaukums         VARCHAR(150) NOT NULL,
    kontaktpersona    VARCHAR(100) NULL,
    talrunis          VARCHAR(30)  NULL,
    epasts            VARCHAR(100) NULL
) ENGINE=InnoDB;

-- Klienti - tiem noliktava izsniedz preces.
CREATE TABLE klienti (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nosaukums         VARCHAR(150) NOT NULL,
    kontaktpersona    VARCHAR(100) NULL,
    talrunis          VARCHAR(30)  NULL,
    epasts            VARCHAR(100) NULL
) ENGINE=InnoDB;

-- Preces un to pašreizējais atlikums noliktavā.
CREATE TABLE preces (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    kods             VARCHAR(30) NOT NULL UNIQUE,           -- Preces kods, unikāls.
    nosaukums        VARCHAR(150) NOT NULL,       
    kategorija_id    INT NOT NULL,                          -- Katrai precei obligāti ir kategorija.
    piegadatajs_id   INT NULL,                              -- Galvenais piegādātājs, nav obligāti.
    vieniba          VARCHAR(10) NOT NULL DEFAULT 'gab',    -- Mērvienība, standarta = gab.
    cena             DECIMAL(10,2) NOT NULL DEFAULT 0.00,   -- Decimal nevis float, lai nav noapaļošanas kļūdu
    daudzums         INT NOT NULL DEFAULT 0,                -- Pašreizējais atlikums, mainās tikai caur kustībām.
    min_daudzums     INT NOT NULL DEFAULT 0,                -- Ja atlikums <= , tad šī prece tiek izcelta.
    izveidots        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Kategoriju, kurā ir preces nedrīkst dzēst.
    CONSTRAINT fk_prece_kategorija FOREIGN KEY (kategorija_id)
        REFERENCES kategorijas(id) ON DELETE RESTRICT,
    -- Ja piegādātāju izdzēš, prece paliek bez piegādātāja.    
    CONSTRAINT fk_prece_piegadatajs FOREIGN KEY (piegadatajs_id)
        REFERENCES piegadataji(id) ON DELETE SET NULL,
    -- Atlikums nevar būt negatīvs. Šo var arī iespējams ņemt āra, jo reālā situācijā dažreiz prece ir uzreiz jāizsniedz un vēlak jāpieņem.    
    CONSTRAINT chk_daudzums CHECK (daudzums >= 0)
) ENGINE=InnoDB;

-- Noliktavas kurstības - katras saņemšanas un izsniegšanas vēsture.
CREATE TABLE kustibas(
    id              INT AUTO_INCREMENT PRIMARY KEY,
    prece_id        INT NOT NULL,
    tips            ENUM('sanemta', 'izsniegta') NOT NULL, -- Kustības virziens.
    daudzums        INT NOT NULL,      -- vienmēr pozitīvs, virzienu nosaka tips.
    piegadatajs_id  INT NULL,          -- no kā saņemts (tikai saņemšanai)
    klients_id      INT NULL,          -- kam izsniegts (tikai izsniegšanai)
    piezime         VARCHAR(255) NULL,
    lietotajs_id    INT NOT NULL,
    datums          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Dzēšot preci, tiek izdzēsta arī tās kustību vēsture.
    CONSTRAINT fk_kustiba_prece FOREIGN KEY (prece_id)
        REFERENCES preces(id) ON DELETE CASCADE,
    -- Piegādātāju vai klientu, kam ir kustības , dzēst nedrīkst.    
    CONSTRAINT fk_kustiba_piegadatajs FOREIGN KEY (piegadatajs_id)
        REFERENCES piegadataji(id) ON DELETE RESTRICT,
    CONSTRAINT fk_kustiba_klients FOREIGN KEY (klients_id)
        REFERENCES klienti(id) ON DELETE RESTRICT,
    -- Bez ON DELETE = RESTRICT, lietotāju ar kustībām dzēst nevar.    
    CONSTRAINT fk_kustiba_lietotajs FOREIGN KEY (lietotajs_id)
        REFERENCES lietotaji(id),
   CONSTRAINT chk_kustiba_daudzums CHECK (daudzums > 0),
    -- saņemšanai nevar būt klients, izsniegšanai nevar būt piegādātājs
    CONSTRAINT chk_kustiba_partneris CHECK (
        (tips = 'sanemta' AND klients_id IS NULL) OR
        (tips = 'izsniegta' AND piegadatajs_id IS NULL))
) ENGINE=InnoDB;

CREATE INDEX idx_kustibas_datums ON kustibas (datums);