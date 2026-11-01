-- Prezentācijas dati
-- Paroles admin / admin123, janis /darbs123

SET NAMES utf8mb4;
USE noliktava;

INSERT INTO lietotaji (lietotajvards, parole_hash, vards, loma) VALUES
('admin', '$2y$10$vul2lYp6WGN8NfHYVR6IfuX9Sh3ZD0DesovFWaZFiUGPtlRIEGvWW', 'admin', 'admin');
('janis', '$2y$10$jXSSYRfQhJql.G6IdyqGu.JoOXom7nuazL8.9a3ovtEmdYNzkjueO', 'Jānis Tests', 'Darbinieks');

INSERT INTO kategorijas (nosaukums, apraksts) VALUES
('Skrūves', 'Bultskrūves un kokskrūves'),
('Uzgriežņi', 'Uzgriežņi'),
('Paplāksnes', 'Paplāksnes');

INSERT INTO piegadataji (nosaukums, kontaktpersona, talrunis, epasts) VALUES
('SIA "Vītne"', 'Ieva Stiprā', '+371 90909090', 'Ieva.Stipra@vitne.lv');

INSERT INTO preces (kods, nosaukums, kategorija_id, piegadatajs_id, vieniba, cena, daudzums, min_daudzums) VALUES
('933-10x120', 'Bultskrūve 10x120 8.8 Zn', 1, 1, 'gab.', 0.90, 1000, 2000),
('934-10', 'Uzgrieznis M10, 5.6', 2, 1, 'gab', 0.10, 200000, 100000),
('125-10', 'Paplāksne M10 HV100', 3, 1, 'gab', 0.05, 500000, 500000)


