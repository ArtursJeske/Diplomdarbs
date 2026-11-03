<?php

require_once 'includes/functions.php';
require_login();

$product_count = $pdo->query('SELECT COUNT(*) FROM preces')->fetchColumn();
$total_qty = $pdo->query('SELECT SUM(daudzums) FROM preces')->fetchColumn();
$total_value = $pdo->query('SELECT SUM(daudzums * cena) FROM preces')->fetchColumn();
$low_count = $pdo->query('SELECT COUNT(*) FROM preces WHERE daudzums <= min_daudzums')->fetchColumn();
$low_stock = $pdo->query('SELECT * FROM preces WHERE daudzums <= min_daudzums ORDER BY daudzums LIMIT 10')->fetchAll();


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

<div class="stats">
    <div><?= (int)$low_count ?></div>
    <div>Preces ar zemu atlikumu></div>
</div>

<div class="stats">
    <section>
        <h2> Preces ar zemu atlikumu</h2>
        <?php if (count($low_stock) == 0): ?>
            <p> Visu preču atlikums ir pietiekams.</p>
        <?php else: ?>
            <table>
                <tr><th>Kods</th><th>Nosaukums</th><th>Atlikums</th><th>Min.</th><th></th><tr>
                <?php foreach ($low_stock as $p): ?>
                    <tr>
                        <td><?= e($p['kods']) ?></td>
                        <td><?= e($p['nosaukums']) ?></td>
                        <td><?= e($p['daudzums']) ?> <?= e($p['vieniba']) ?></td>
                        <td><?= e($p['min_daudzums']) ?> </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </section>                           
</div>
<br></br>
<?php require 'includes/footer.php'; ?>