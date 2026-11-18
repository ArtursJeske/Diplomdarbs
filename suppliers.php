<?php
//Piegādātāju pārvaldība: saraksts, pievienošana, labošana, dzēšana.

require_once 'includes/functions.php';  //DB savienojums ($pdo) un palīgfunkcijas
require_login();                        // ja nav ielogojies, tad pāradresē uz login.php

//Sākuma vērtības tukša forma, ja nav ne POST, ne ?edit=
$error = '';
$edit_id = (int)($_GET['edit'] ?? 0);  
$nosaukums = '';
$kontaktpersona = '';
$talrunis = '';
$talrunis = '';
$epasts = '';

//Forma iesniegta (Saglabāt vai Dzēst)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST ['id'];  // 0 = jauns piegādātājs, >0 =labošana/dzēšana
    // Dzēšana tikai administratoram.
    if ($_POST['action'] == 'delete') {
        require_admin();

        // Piegādātāju, no kura saņemtas preces, dzēst nedrīkst - tad pazustu kustību vēsture
        // DB to bloķētu ar ON DELETE RESTRICT, bet lietotājs redzētu kļudu, nevis saprotamu ziņu
        // Precēm piegādātājs pēc dzēšanas kļūst NULL (ON DELETE SET NULL).
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kustibas WHERE piegadatajs_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            flash('Piegādātājam ir reģistrētas kustības, tāpēc to nevar dzēst.', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM piegadataji WHERE id =?');
            $stmt->execute([$id]);
            flash('Piegādātājs dzēsts.');
        }
        // POST/Redirect/GET - atjaunojot lapu, dzēšana neatkārtojas.
        header('Location: suppliers.php');
        exit;
    }

    // Pievienošana / labošana
    $nosaukums = trim($_POST['nosaukums']);
    $kontaktpersona = trim($_POST['kontaktpersona']);
    $talrunis = trim($_POST['talrunis']);
    $epasts = trim($_POST['epasts']);

    // Pārbaudes: nosaukums obligāts, e-pasts nav obligāts, bet ja ievadīts tam jābūt derīgam
    if ($nosaukums == '') {
        $error = 'Nosaukums ir obligāts';

    } elseif ($epasts != '' && !filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        // filter_var -PHP iebūvēta e-pasta formāta pārbaude
        $error = 'Nederīga e-pasta adrese';
    }

    //Ja kļūdu nav - saglabā
    if ($error == '') {
        if ($id > 0) {
            // Labo esošo piegādātaju
            $stmt = $pdo->prepare('UPDATE piegadataji SET nosaukums = ?, kontaktpersona = ?, talrunis = ?, epasts = ? WHERE id = ?');
            $stmt->execute([$nosaukums, $kontaktpersona, $talrunis, $epasts, $id]);
            flash('Piegādātājs atjaunots.');
        } else {
            // Pievieno jaunu piegādātāju
            $stmt = $pdo->prepare('INSERT INTO piegadataji (nosaukums, kontaktpersona, talrunis, epasts) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nosaukums, $kontaktpersona, $talrunis, $epasts]);
            flash('Piegādātājs pievienots');
        }
        header('Location: suppliers.php');
        exit;
    }
} elseif ($edit_id > 0) {
    // Labošanas režīms: ielādē piegādātāja datus formā
    $stmt = $pdo->prepare('SELECT * FROM piegadataji WHERE id = ?');
    $stmt->execute([$edit_id]);
    $row = $stmt->fetch();
    if ($row) {  // ja ieraksts neeksistē, forma paliek tukša.
        $nosaukums = $row['nosaukums'];
        $kontaktpersona = $row['kontaktpersona'];
        $talrunis = $row['talrunis'];
        $epasts = $row['epasts'];
    } 
}

// Visi piegādātaji ar preču skaitu
// LEFT JOIN - lai redzami arī piegādātāji bez precēm, GROUP BY - viena rinda katram piegādātajam
$suppliers = $pdo->query(
    'SELECT s.*, COUNT(p.id) AS preces_skaits
     FROM piegadataji s
     LEFT JOIN preces p on p.piegadatajs_id = s.id
     GROUP BY s.id
     ORDER BY s.nosaukums'
)->fetchAll();

$title = 'Piegādātāji';
require 'includes/header.php';
?>
<h1>Piegādātāji</h1>

<!-- Kļūdas paziņojums-->
<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<!-- Viena forma pievienošanai un labošanai -->
<form method="post" class="card form-inline">
    <!-- Virsraksts atkarīgs no režīma -->
    <h2><?= $edit_id > 0 ? 'Labot piegādātāju' : 'Jauns piegādātājs' ?></h2>
    <!-- Slēptie lauki : darbība (save) un piegādātaja id (0 = jauns) -->
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit_id ?>">
    <!-- maxlength atbilst kolonnu garumam datubāzē-->
    <input type="text" name="nosaukums" placeholder="Nosaukums" maxlength="150" value="<?= e($nosaukums) ?>" required>
    <input type="text" name="kontaktpersona" placeholder="Kontaktpersona" maxlength="100" value="<?= e($kontaktpersona) ?>">
    <!-- Tālrunis ir teksts , lai var izmantot + zīmi-->
    <input type="text" name="talrunis" placeholder="Tālrunis" maxlength="30" value="<?= e($talrunis) ?>">
    <input type="email" name="epasts" placeholder="E-pasts" maxlength="100" value="<?= e($epasts) ?>">
    <button type="submit" class="btn">Saglabāt</button>
    <!--Atcelt redzams tikai labošanas režīmā -->
    <?php if ($edit_id > 0): ?>
        <a href="suppliers.php">Atcelt</a>
    <?php endif; ?>
</form>

<!-- Piegādātāju saraksts -->
<table>
    <tr><th>Nosaukums</th><th>Kontaktpersona</th><th>Tālrunis</th><th>E-pasts</th><th>Preces</th><th>Darbības</th></tr>
    <!-- Katram piegādātājam viena tabulas rinda; visu tekstu izvada caur e() -->
    <?php foreach ($suppliers as $s): ?>
        <tr>
            <td><?= e($s['nosaukums']) ?></td>
            <td><?= e($s['kontaktpersona']) ?></td>
            <td><?= e($s['talrunis']) ?></td>
            <td><?= e($s['epasts']) ?></td>
            <!-- Preču skaits no SQL vaicājuma (COUNT) -->
            <td><?= $s['preces_skaits'] ?></td>
            <td class="actions">
                <!-- Labošana: atver šo pašu lapu ar ?edit=id -->
                <a href="suppliers.php?edit=<?= $s['id'] ?>">Labot</a>
                <!-- Dzēšanas poga redzama tikai adminam -->
                <?php if (is_admin()): ?>
                    <!-- Dzēšana ar POST formu (nevis saiti) un apstiprinājumu -->
                    <form method="post" onsubmit="return confirm('Dzēst piegādātāju?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="link danger">Dzēst</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require 'includes/footer.php'; ?>