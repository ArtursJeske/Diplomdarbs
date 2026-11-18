<?php
// Kategoriju pārvaldība: saraksts, pārvietošana, labošana, dzēšana.

require_once 'includes/functions.php';
require_login();

$error = '';
$edit_id = (int)($_GET['edit'] ?? 0);  //Labojamās kategorijas id no adreses (?edit=5)
$nosaukums = '';
$apraksts = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];    //0 = Jauna kategorija, >0 = labošana.

    //Dzēst var tikai admins
    if ($_POST['action'] == 'delete') { 
        require_admin();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM preces WHERE kategorija_id = ?');
        $stmt->execute([$id]);
        //Kategorijā kurā ir preces, dzēst nedrīkst.
        if ($stmt->fetchColumn() > 0) {
            flash('Kategorijā ir preces, tāpēc to nevar dzēst!', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM kategorijas WHERE id = ?');
            $stmt->execute([$id]);
            flash('Kategorija dzēsta!');
        }
        header('Location: categories.php');
        exit;
    }   
    
    //Pievienošana un labošana.

    $nosaukums = trim($_POST['nosaukums']);
    $apraksts = trim($_POST['apraksts']);

    if ($nosaukums == '') {
        $error = 'Nosaukums ir obligāts.';
    } else {
        // Vai šāds nosaukums jau nav citai kategorijai (id != ? - lai labojot neskaitītu pašu sevi)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kategorijas WHERE nosaukums = ? AND id !=?');
        $stmt->execute([$nosaukums, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Šāda kategorija jau eksistē.';
        }
    }


    if ($error =='') {
        if ($id > 0) {
            $stmt = $pdo->prepare ('UPDATE kategorijas SET nosaukums = ?, apraksts = ? WHERE id = ?');    
            $stmt->execute([$nosaukums, $apraksts, $id]);
            flash('Kategorija atjaunota.');
        } else {
            $stmt =$pdo->prepare('INSERT INTO kategorijas (nosaukums, apraksts) VALUES (?, ?)');
            $stmt->execute([$nosaukums, $apraksts]);
            flash('Kategorija pievienota.');    
        }
        // Pāradresācija, lai F5 neiesniegtu formu atkārtoti
        header('Location: categories.php');
        exit;
    }

    $edit_id = $id;   // Kļūdas gadījumā forma paliek labošanas režīmā.
} elseif ($edit_id > 0) {
    // ielādē labojamās kategorijas datus formā
    $stmt = $pdo->prepare('SELECT * FROM kategorijas WHERE id =?');
    $stmt->execute([$edit_id]);
    $row = $stmt->fetch();
    if ($row) {
        $nosaukums = $row['nosaukums'];
        $apraksts = $row['apraksts'];
    }
}

// Visas kategorijas ar preču skaiti (LEFT JOIN - lai redzamas arī tukšās kategorijas.)
$categories = $pdo->query(
    'SELECT k.*, COUNT(p.id) AS preces_skaits
     FROM kategorijas k
     LEFT JOIN preces p ON p.kategorija_id = k.id
     GROUP BY k.id
     ORDER BY k.nosaukums'
)->fetchAll();

$title = 'Kategorijas';   // lapas virsraksts pārlūka cilnē (izmanto header.php)
require 'includes/header.php';   // lapas augšdaļa: <head>, izvēlne, flash paziņojumi
?>
<h1>Kategorijas</h1>

<!-- Kļūdas paziņojums (piem., tukšs nosaukums vai dublikāts) -->
<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<!-- Viena forma pievienošanai un labošanai -->
<form class= "card form-inline" method="post">
    <!-- Virsraksts atkarīgs no režīma: labošana vai jauna kategorija -->
    <h2><?= $edit_id > 0 ? 'Labot kategoriju' : 'Jauna kategorija' ?></h2>
    <!-- Slēptie lauki: darbība (save) un kategorijas id (0 = jauna) -->
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit_id ?>">
    <!-- maxlength atbilst kolonnu garumam datubāzē; e() aizsargā pret XSS -->
    <input type="text" name="nosaukums" placeholder="Nosaukums" maxlength="100" value="<?= e($nosaukums) ?>" required>
    <input type="text" name="apraksts" placeholder="Apraksts" maxlength="255" value="<?= e($apraksts) ?>">
    <button type="submit" class="btn">Saglabāt</button>
    <!-- "Atcelt" redzams tikai labošanas režīmā -->
    <?php if ($edit_id >0): ?>
        <a href="categories.php">Atcelt</a>
    <?php endif; ?>
</form>

<!-- Kategoriju saraksts -->
<table>
    <tr><th>Nosaukums</th><th>Apraksts</th><th>Preces</th><th>Darbības</th></tr>
    <!-- Katrai kategorijai viena tabulas rinda -->
    <?php foreach ($categories as $c): ?>
        <tr>
            <!-- Nosaukums ved uz preču sarakstu, filtrētu pēc šīs kategorijas -->
            <td><a href="products.php?kategorija=<?= $c['id'] ?>"><?= e($c['nosaukums']) ?></a></td>
            <td><?= e($c['apraksts']) ?></td>
            <!-- Preču skaits no SQL vaicājuma (COUNT) -->
            <td><?= $c['preces_skaits'] ?></td>
            <td class="actions">
                <!-- Labošana: atver šo pašu lapu ar ?edit=id -->
                <a href="categories.php?edit=<?= $c['id'] ?>">Labot</a>
                <!-- Dzēšanas poga redzama tikai administratoram -->
                <?php if (is_admin()): ?>
                    <!-- Dzēšana ar POST formu (nevis saiti) un apstiprinājumu -->
                    <form method="post" onsubmit ="return confirm('Dzēst kategoriju?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class ="danger">Dzēst</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require 'includes/footer.php'; // lapas apakšdaļa ?>