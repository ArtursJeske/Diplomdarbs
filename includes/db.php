<?php
// Savienojums ar datubāzi.
// Ielādē includes/functions.php, tāpēc $pdo ir pieejams visās lapās.

//Pieslēguma dati no konfigurācijas faila.
require_once __DIR__ . '/../config.php';

try {
    // Izveido PDO savienojumu ar MariaDB
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);

    // SQL kļūdas izraisa izņēmumu (exception), nevis tiek ignorētas.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Rezultāti tiek atgriezti kā masīvi
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Nerāda tehnisko kļūdas tekstu, tikai vispārīgu ziņojumu (jo var parādīt svarīgu informāciju)
    die('Neizdevās pieslēgties datubāzei. Pārbaudi config.php iestatījumus.');
}