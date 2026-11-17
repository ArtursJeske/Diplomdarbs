<?php
// Kopīgās funkcijas. Šo failu ielāde katra lapa.

require_once __DIR__ . '/db.php';  //Datubāzes savienojums ($pdo)
require_once __DIR__ . '/auth.php'; //Sesija un tiesību pārbaudes

// Droša teksta izvade HTML lapā (aizsardzība pret XSS).
// ?? '' - lai NULL vērtības no datubāzes nerādītu brīdinājumu
function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

//Naudas formatējums latviešu formātā, 2 = decimāldaļas, ','= atdalītajs, ' '= tūkstošu atdalītājs . '€' = pievieno beigās Eiro simbolu (var arī ņemt nost)
function money($amount)
{
    return number_format((float)$amount, 2, ',', ' ') . ' €';
}

// Datums no datubāzes formāta YYYY-MM-DD , pārvērsts uz DD-MM-YYYY.
function format_date($date)
{
    return date('d.m.Y H:i', strtotime($date));
}

//Saglabā paziņojumu sesijā, lai to parādītu nākamajā lapā pēc pāradresācijas.
//Paziņojumu velāk izvada un izdzēš includes/header.php.
//$type: `success` (zaļš) vai `error` (sarkans)
function flash($message, $type = 'success')
{
    $_SESSION['flash'] = $message;
    $_SESSION['flash_type'] = $type;
}
