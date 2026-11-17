<?php
// Sākumlapa: noliktavas kopsavilkums un preces ar zemu atlikumu.
require_once 'includes/functions.php';
require_login();

//Kopsavilkuma rādītaji (vaicājumos nav lietotāja datu, tāpēc var lietot query())
$product_count = $pdo->query('SELECT COUNT(*) FROM preces')->fetchColumn();              // preču veidu skaits
$total_qty = $pdo->query('SELECT SUM(daudzums) FROM preces')->fetchColumn();             // vienību kopskaits
$total_value = $pdo->query('SELECT SUM(daudzums * cena) FROM preces')->fetchColumn();    // Noliktavas vērtība EUR
$low_count = $pdo->query('SELECT COUNT(*) FROM preces WHERE daudzums <= min_daudzums')->fetchColumn(); 

//Preces kuras japasūta - atlikums sasniedzis minimumu (10 ar mazāko atlikumu, Limitu var nomainīt schema.sql)
$low_stock = $pdo->query('SELECT * FROM preces WHERE daudzums <= min_daudzums ORDER BY daudzums LIMIT 10')->fetchAll();


$title = 'Sākums';

require 'includes/header.php';

?>

<h1>Sākums</h1>

<!-- Kopsavilkuma rādītaji. (int) - lai tukšā sistēmā rādītu 0, nevis neko (SUM atgriež NULL) -->
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
    <div>Preces ar zemu atlikumu</div>
</div>

<!-- Preces, kuru atlikums ir minimālajā līmenī vai zem tā -->
<div class="card">
    <section>
        <h2> Preces ar zemu atlikumu</h2>
        <?php if (count($low_stock) == 0): ?>
            <p> Visu preču atlikums ir pietiekams.</p>
        <?php else: ?>
            <table>
                <tr><th>Kods</th><th>Nosaukums</th><th>Atlikums</th><th>Min.</th></tr>
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

<?php require 'includes/footer.php'; ?>