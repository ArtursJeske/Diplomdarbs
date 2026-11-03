<?php

require_once 'includes/functions.php';
require_login();

$product_count = $pdo->query('SELECT COUNT(*) FROM preces')->fetchColumn();
$total_qty = $pdo->query('SELECT SUM(daudzums) FROM preces')->fetchColumn();
$total_value = $pdo->query('SELECT SUM(daudzums * cena) FROM preces')->fetchColumn();


$title = 'Sākums';

require 'includes/header.php';

?>

<h1>Sākums</h1>

<div class="stats">
    <div><?= (int)$product_count ?></div>
    <div>Preču veidi</div>
</div>
<div class="stats">
    <div><?= (int)$total_qty ?></div>
    <div>Kopējais preču daudzums</div>
</div>    
<div class="stats">
    <div><?= money($total_value) ?></div>
    <div>Atlikumu kopējā summa</div>
</div>
    
<?php require 'includes/footer.php'; ?>