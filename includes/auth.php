<?php
//Pieslēgšanās un tiesību parbaudes.
//Sesijā pēc pieslēgšanās (login.php) glabājas: user_id, vards, loma.

// Palaiž sesiju - bez tās nevar izmantot $_SESSION
session_start();

// Aizsargā lapu: ka lietotājs nav pieslēdzies, pārsūta uz login.php.
// Exit ir obligāts - citādi pārējais lapas kods tik un tā izpildītos.
function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

//Pārbauda vai lietotājs ir administrators.
// Izmanto, lai vēlak slēptu pogas (Dzēst, labot utt.)
function is_admin()
{
    return isset($_SESSION['loma']) && $_SESSION['loma'] == 'admin';
}

// Atļauj darbību tikai administratoram (dzēšana, lietotāju pārvaldība).
// Ja nav admins - darbība tiek pārtraukta.
function require_admin()
{
    require_login();
    if (!is_admin()) {
        die('Šī darbība pieejama tikai administratoram');
    }
}