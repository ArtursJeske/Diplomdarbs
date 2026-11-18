<?php
// Lietotnes konfigurācija.
// Šeit glabājas iestatījumi, kas atšķiras katrā datorā/serverī.
// Ja prajektu pārnes uz citu datoru, jāmaina tikai šis fails.

// Datubāzes pieslēguma dati (izmanto includes /db.php)
$db_host = 'localhost';  // datubāzes serveris (XAMPP - tas pats dators)
$db_name = 'noliktava';  // Datubāzes nosaukums (izveidota ar sql/schema.sql)
$db_user = 'root';       // MariaDB lietotājs.
$db_pass = '';           // MariaDB parole.

// Laika josla, lai datumi un laiki tiktu rādīti pēc Latvijas laika
date_default_timezone_set('Europe/Riga');