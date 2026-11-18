<?php
// Preces dzēšana. Lapa neko nerāda - izdzēš un pāradresē atpakaļ uz products.php.
// Izsauc dzēšanas forma preču sarakstā (POST, action="product_delete.php").
// Dzēst var tikai preci, kurai nav kustību (tāpat kā piegādātājus un klientus).

require_once 'includes/functions.php';   // DB savienojums ($pdo) un palīgfunkcijas
require_admin();                          // tikai administratoram (iekļauj arī require_login)

// id no POST, nevis no adreses - parasta saite (GET) neko neizdzēsīs
$id = (int)($_POST['id'] ?? 0);

// Ielādē preci: vai eksistē, nosaukums paziņojumam
$stmt = $pdo->prepare('SELECT nosaukums FROM preces WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

// Vai precei ir kustības (saņemšanas/izsniegšanas).
// Atlikuma pārbaude nav vajadzīga: atlikums mainās tikai caur kustībām,
// tātad, ja kustību nav, atlikums noteikti ir 0.
$stmt = $pdo->prepare('SELECT COUNT(*) FROM kustibas WHERE prece_id = ?');
$stmt->execute([$id]);
$kustibu_skaits = $stmt->fetchColumn();

if (!$product) {
    flash('Prece nav atrasta.', 'error');
} elseif ($kustibu_skaits > 0) {
    // Preci, kurai ir kustības, dzēst nedrīkst - tad pazustu kustību vēsture
    // (DB to bloķē ar ON DELETE RESTRICT, bet lietotājs redzētu kļūdu, nevis saprotamu ziņu)
    flash('Precei ir reģistrētas kustības, tāpēc to nevar dzēst.', 'error');
} else {
    // Prece bez kustībām - piem., pievienota kļūdas pēc
    $stmt = $pdo->prepare('DELETE FROM preces WHERE id = ?');
    $stmt->execute([$id]);
    flash('Prece "' . $product['nosaukums'] . '" dzēsta.');   // header.php izvada caur e()
}

// Atpakaļ uz sarakstu, kur header.php parāda paziņojumu
header('Location: products.php');
exit;
