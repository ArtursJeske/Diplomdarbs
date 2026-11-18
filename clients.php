<?php
// Klientu pārvaldība: saraksts, pievienošana, labošana, dzēšana
require_once 'includes/functions.php';   // DB savienojums ($pdo) un palīgfunkcijas
require_login();                          // nepieteiktu lietotāju pāradresē uz login.php

// Sākuma vērtības: tukša forma.
$error = '';
$edit_id = (int)($_GET['edit'] ?? 0);   // labojamā klienta id no adreses (?edit=5), (int) - tikai skaitlis
$nosaukums = '';
$kontaktpersona = '';
$talrunis = '';
$epasts = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id']; // 0 = jauns klients, >0 = labošana/dzēšana
    // Dzēšana tikai adminam
    if ($_POST['action'] == 'delete') {
        require_admin();   // pārbaude serverī - nepietiek tikai paslēpt pogu

        // Klientu, kuram izsniegtas preces, dzēst nedrīkst - tad pazustu kustību vēsture
        // DB to bloķē ar ON DELETE RESTRICT
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kustibas WHERE klients_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            flash('Klientam ir reģistrētas kustības, tāpēc to nevar dzēst.', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM klienti WHERE id = ?');
            $stmt->execute([$id]);
            flash('Klients dzēsts.');
        }
        // POST/REDIRECT/GET - atsvaidzinot lapu, dzēšana neatkārtojas
        header('Location: clients.php');
        exit;
    }

    // Pievienošana un labošana
    // trim() noņem atstarpes sākumā un beigās
    $nosaukums = trim($_POST['nosaukums']);
    $kontaktpersona = trim($_POST['kontaktpersona']);
    $talrunis = trim($_POST['talrunis']);
    $epasts = trim($_POST['epasts']);

    // Pārbaudes: nosaukums ir obligāts, e-pasts nav obligāts, bet ja ievadīts tam jābūt derīgam
    if ($nosaukums == '') {
        $error = 'Nosaukums ir obligāts';
    } elseif ($epasts != '' && !filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        // filter_var - PHP iebūvēta e-pasta formāta pārbaude.
        $error = 'Nederīga e-pasta adrese';
    }

    // Ja kļūdu nav - saglabā
    if ($error == '') {
        if ($id > 0) {
            // Labo esošo klientu
            $stmt = $pdo->prepare('UPDATE klienti SET nosaukums = ?, kontaktpersona = ?, talrunis = ?, epasts = ? WHERE id = ?');
            $stmt->execute([$nosaukums, $kontaktpersona, $talrunis, $epasts, $id]);
        } else {
            // Pievieno jaunu klientu
            $stmt = $pdo->prepare('INSERT INTO klienti (nosaukums, kontaktpersona, talrunis, epasts) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nosaukums, $kontaktpersona, $talrunis, $epasts]);
        }
        // Pāradresācija lai pārlādēšana neiesniedz formu vēlreiz.
        header('Location: clients.php');
        exit;
    }
    $edit_id = $id;  // kļūdas gadījumā forma paliek labošanas režīmā
} elseif ($edit_id > 0) {
    // Labošanas režīms: ielādē klienta datus formā
    $stmt = $pdo->prepare('SELECT * FROM klienti WHERE id = ?');
    $stmt->execute([$edit_id]);
    $row = $stmt->fetch();
    if ($row) {  // ja ieraksts neeksistē, forma paliek tukša
        $nosaukums = $row['nosaukums'];
        $kontaktpersona = $row['kontaktpersona'];
        $talrunis = $row['talrunis'];
        $epasts = $row['epasts'];
    }
}

// Katram klientam - cik reizes un cik preču vienību tam izsniegts
// LEFT JOIN - lai redzami arī klienti bez kustībām, GROUP BY - viena rinda katram klientam.
// COALESCENCE - ja kustību nav, SUM atgriež NULL, tapēc aizstāj ar 0
// FILTRS pēc tipa nav vajadzīgs: klients_id ir aizpildīts tikai izsniegšanām (CHECK schema.sql)
$clients = $pdo->query(
    'SELECT c.*, COUNT(k.id) AS kustibu_skaits, COALESCE(SUM(k.daudzums), 0) AS izsniegts_kopa
     FROM klienti c
     LEFT JOIN kustibas k ON k.klients_id = c.id
     GROUP BY c.id
     ORDER BY c.nosaukums'
)->fetchAll();

$title = 'Klienti';               // lapas virsraksts pārlūka cilnē (izmanto header.php)
require 'includes/header.php';    // lapas augšdaļa: <head>, izvēlne, flash paziņojumi
?>
<h1>Klienti</h1>

<!-- Kļūdas paziņojums (tukšs nosaukums vai nederīgs epasts) -->
<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>


<!-- Viena forma pievienošanai un labošanai -->
<form method="post" class="card form-inline">
    <!-- Virsraksts atkarīgs no režīma -->
    <h2><?= $edit_id > 0 ? 'Labot klientu' : 'Jauns klients' ?></h2>
    <!-- Slēptie lauki: darbība (save) un klienta id (0 = jauns) -->
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit_id ?>">
    <!-- Klients var būt gan uzņēmums, gan privātpersona -->
    <!-- maxlength atbilst kolonnu garumam datubāzē; e() aizsargā pret XSS -->
    <input type="text" name="nosaukums" placeholder="Nosaukums vai vārds, uzvārds" maxlength="150" value="<?= e($nosaukums) ?>" required>
    <input type="text" name="kontaktpersona" placeholder="Kontaktpersona" maxlength="100" value="<?= e($kontaktpersona) ?>">
    <!-- Tālrunis ir teksts, nevis skaitlis: var saturēt +, atstarpes, nulli sākumā -->
    <input type="text" name="talrunis" placeholder="Tālrunis" maxlength="30" value="<?= e($talrunis) ?>">
    <!-- type="email" - pārbaude pārlūkā; serverī to pašu pārbauda filter_var -->
    <input type="email" name="epasts" placeholder="E-pasts" maxlength="100" value="<?= e($epasts) ?>">
    <button type="submit" class="btn">Saglabāt</button>
    <!-- "Atcelt" redzams tikai labošanas režīmā -->
    <?php if ($edit_id > 0): ?>
        <a href="clients.php">Atcelt</a>
    <?php endif; ?>
</form>

<!-- Klientu saraksts -->
<table>
    <tr><th>Nosaukums</th><th>Kontaktpersona</th><th>Tālrunis</th><th>E-pasts</th><th>Izsniegšanas</th><th>Darbības</th></tr>
    <!-- Katram klientam viena tabulas rinda; visu tekstu izvada caur e() -->
    <?php foreach ($clients as $c): ?>
        <tr>
            <td><?= e($c['nosaukums']) ?></td>
            <td><?= e($c['kontaktpersona']) ?></td>
            <td><?= e($c['talrunis']) ?></td>
            <td><?= e($c['epasts']) ?></td>
            <!-- Izsniegšanu skaits un kopējais izsniegto vienību daudzums -->
            <td><?= $c['kustibu_skaits'] ?> (<?= $c['izsniegts_kopa'] ?> vien.)</td>
            <td class="actions">
                <!-- Labošana: atver šo pašu lapu ar ?edit=id -->
                <a href="clients.php?edit=<?= $c['id'] ?>">Labot</a>
                <!-- Dzēšanas poga redzama tikai administratoram -->
                <?php if (is_admin()): ?>
                    <!-- Dzēšana ar POST formu (nevis saiti) un apstiprinājumu -->
                    <form method="post" onsubmit="return confirm('Dzēst klientu?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="link danger">Dzēst</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <!-- Ja klientu vēl nav, parāda paziņojumu, nevis tukšu tabulu -->
    <?php if (count($clients) == 0): ?>
        <tr><td colspan="6">Klientu nav.</td></tr>
    <?php endif; ?>
</table>
<?php require 'includes/footer.php'; // lapas apakšdaļa ?>
