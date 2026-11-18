-- Prezentācijas dati
-- Paroles admin / admin123, janis /darbs123
-- Ielādēt pēc schema.sql (tabulām jābūt tukšām, id sākas no 1)

SET NAMES utf8mb4;
USE noliktava;

INSERT INTO lietotaji (lietotajvards, parole_hash, vards, loma) VALUES
('admin', '$2y$10$vul2lYp6WGN8NfHYVR6IfuX9Sh3ZD0DesovFWaZFiUGPtlRIEGvWW', 'admin', 'admin'),
('janis', '$2y$10$jXSSYRfQhJql.G6IdyqGu.JoOXom7nuazL8.9a3ovtEmdYNzkjueO', 'Jānis Tests', 'darbinieks');

INSERT INTO kategorijas (nosaukums, apraksts) VALUES
('Elektroinstrumenti', 'Urbjmašīnas, slīpmašīnas, akumulatori'),
('Rokas instrumenti', NULL),
('Mērinstrumenti', 'Līmeņrāži, mērlentes'),
('Urbji un diski', 'Izejmateriāli'),
('Darba drošība', 'Cimdi, brilles');

INSERT INTO piegadataji (nosaukums, kontaktpersona, talrunis, epasts) VALUES
('SIA "Instrumentu centrs"', 'Māris Kalniņš', '+371 26112739', 'maris@instrcentrs.lv'),
('SIA "BaltTools"', 'Inga Liepa', '67 445 120', NULL),
('SIA "Darbs Drošībā"', NULL, '29870014', 'info@darbsdrosiba.lv');

INSERT INTO klienti (nosaukums, kontaktpersona, talrunis, epasts) VALUES
('SIA "Kurzemes Būve"', 'Raitis Bērziņš', '+371 26408813', 'raitis@kurzemesbuve.lv'),
('SIA "Jumiķis"', 'Andris', '25 670 331', NULL),
('Aigars Pētersons', NULL, '28004512', NULL),
('SIA "RE-Būvnieks"', 'Līga Kronberga', NULL, 'liga@rebuvnieks.lv');

-- Atlikums (daudzums) sakrīt ar kustību summu zemāk.
-- Zems atlikums: GWS750-125, LL-360G, SDS-8-160, CIMDI-NIT-10
INSERT INTO preces (kods, nosaukums, kategorija_id, piegadatajs_id, vieniba, cena, daudzums, min_daudzums) VALUES
('DHP485Z', 'Akumulatora urbjmašīna Makita DHP485Z 18V (bez akum.)', 1, 2, 'gab', 119.00, 8, 3),
('GWS750-125', 'Leņķa slīpmašīna Bosch GWS 750-125', 1, 2, 'gab', 79.90, 2, 2),
('BL1850B', 'Akumulators Makita 18V 5.0Ah', 1, 2, 'gab', 68.50, 6, 4),
('HM-450', 'Āmurs 450g, stiklšķiedras kāts', 2, 1, 'gab', 12.40, 11, 5),
('SKR-PH8', 'Skrūvgriežu kompl. PH/PZ 8gab', 2, 1, 'kompl.', 17.95, 6, 3),
('LIM-60', 'Līmeņrādis 60cm alumīnija', 3, 1, 'gab', 21.00, 8, 4),
('ML-5M', 'Mērlente 5m', 3, 1, 'gab', 6.80, 18, 10),
('LL-360G', 'Lāzera līmeņrādis 360°, zaļais stars', 3, NULL, 'gab', 189.00, 0, 1),
('SDS-8-160', 'Urbis SDS-plus 8x160', 4, 1, 'gab', 3.15, 15, 20),
('SDS-10-210', 'Urbis SDS-plus 10x210', 4, 1, 'gab', 4.30, 18, 15),
('DISK-125-1', 'Griešanas disks metālam 125x1,0', 4, 2, 'gab', 0.85, 125, 100),
('CIMDI-NIT-10', 'Darba cimdi ar nitrila pārklājumu, 10. izm.', 5, 3, 'pāri', 1.95, 20, 50),
('BRILLES-CL', 'Aizsargbrilles, caurspīdīgas', 5, 3, 'gab', 3.60, 18, 10);

-- Kustības: saņemšanai ir piegādātājs, izsniegšanai - klients
INSERT INTO kustibas (prece_id, tips, daudzums, piegadatajs_id, klients_id, piezime, lietotajs_id, datums) VALUES
(1,  'sanemta',   6,   2, NULL, 'Pavadzīme BT-2041', 1, '2026-10-20 09:12:00'),
(2,  'sanemta',   5,   2, NULL, 'Pavadzīme BT-2041', 1, '2026-10-20 09:15:00'),
(3,  'sanemta',   10,  2, NULL, 'Pavadzīme BT-2041', 1, '2026-10-20 09:16:00'),
(9,  'sanemta',   50,  1, NULL, NULL, 2, '2026-10-22 14:40:00'),
(10, 'sanemta',   40,  1, NULL, NULL, 2, '2026-10-22 14:41:00'),
(4,  'sanemta',   12,  1, NULL, NULL, 2, '2026-10-22 14:45:00'),
(6,  'sanemta',   8,   1, NULL, NULL, 2, '2026-10-22 14:47:00'),
(7,  'sanemta',   20,  1, NULL, NULL, 2, '2026-10-22 14:48:00'),
(5,  'sanemta',   6,   1, NULL, NULL, 2, '2026-10-22 14:50:00'),
(11, 'sanemta',   200, 2, NULL, NULL, 1, '2026-10-27 10:05:00'),
(12, 'sanemta',   120, 3, NULL, 'Pavadzīme DD-118', 2, '2026-10-28 08:30:00'),
(13, 'sanemta',   30,  3, NULL, 'Pavadzīme DD-118', 2, '2026-10-28 08:31:00'),
(1,  'izsniegta', 2,   NULL, 1, 'Rēķins 2026/112', 2, '2026-10-29 11:20:00'),
(3,  'izsniegta', 4,   NULL, 1, 'Rēķins 2026/112', 2, '2026-10-29 11:21:00'),
(11, 'izsniegta', 75,  NULL, 2, NULL, 2, '2026-11-03 15:02:00'),
(12, 'izsniegta', 40,  NULL, 2, NULL, 2, '2026-11-03 15:03:00'),
(4,  'izsniegta', 1,   NULL, 3, 'samaksāts skaidrā', 2, '2026-11-05 09:47:00'),
(7,  'izsniegta', 2,   NULL, 3, 'samaksāts skaidrā', 2, '2026-11-05 09:48:00'),
(9,  'izsniegta', 35,  NULL, 4, NULL, 1, '2026-11-10 13:30:00'),
(10, 'izsniegta', 22,  NULL, 4, NULL, 1, '2026-11-10 13:31:00'),
(2,  'izsniegta', 3,   NULL, 1, NULL, 2, '2026-11-12 10:10:00'),
(12, 'izsniegta', 60,  NULL, 1, 'Objekts Ventspilī', 2, '2026-11-14 16:25:00'),
(1,  'sanemta',   4,   2, NULL, 'Pavadzīme BT-2107', 1, '2026-11-17 08:55:00'),
(13, 'izsniegta', 12,  NULL, 4, NULL, 2, '2026-11-17 12:40:00');
