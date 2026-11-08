<?php

require_once 'includes/functions.php';
require_login();

$error = '';
$edit_id = (int)($_GET['edit'] ?? 0);
$nosaukums = '';
$apraksts = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];

    if ($_POST['action'] == 'delete') {
        
        require_admin();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM preces WHERE kategorija_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            //flash('Kategorijā ir preces, tāpēc to nevar dzēst!', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM kategorijas WHERE id = ?');
            $stmt->execute([$id]);
            //flash('Kategorija dzēsta!');
        }
        header('Location: categories.php');
        exit;
    }   
    
    $nosaukums = trim($_POST['nosaukums']);
    $apraksts = trim($_POST['apraksts']);

    if ($nosaukums == '') {
        $error = 'Nosaukums ir obligāts.';
    } else {

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
            //flash('Kategorija atjaunota.');
        } else {
            $stmt =$pdo->prepare('INSERT INTO kategorijas (nosaukums, apraksts) VALUES (?, ?)');
            $stmt->execute([$nosaukums, $apraksts]);
            //flash('Kategorija pievienota.');    
        }
        header('Location: categories.php');
        exit;
    }

    $edit_id = $id;
} elseif ($edit_id > 0) {

    $stmt = $pdo->prepare('SELECT * FROM kategorijas WHERE id =?');
    $stmt->execute([$edit_id]);
    $row = $stmt->fetch();
    if ($row) {
        $nosaukums = $row['nosaukums'];
        $apraksts = $row['apraksts'];
    }
}


$categories = $pdo->query(
    'SELECT k.*, COUNT(p.id) AS preces_skaits
     FROM kategorijas k
     LEFT JOIN preces p ON p.kategorija_id = k.id
     GROUP BY k.id
     ORDER BY k.nosaukums'
)->fetchAll();

$title = 'Kategorijas';
require 'includes/header.php';
?>
<h1>Kategorijas</h1>

<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
    <h2><?= $edit_id > 0 ? 'Labot kategoriju' : 'Jauna kategorija' ?></h2>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit_id ?>">
    <input type="text" name="nosaukums" placeholder="Nosaukums" maxlength="100" value="<?= e($nosaukums) ?>" required>
    <input type="text" name="apraksts" placeholder="Apraksts" maxlength="255" value="<?= e($apraksts) ?>">
    <button type="submit" class="btn">Saglabāt</button>
    <?php if ($edit_id >0): ?>
        <a href="categories.php">Atcelt</a>
    <?php endif; ?>
</form>

<table>
    <tr><th>Nosaukums</th><th>Apraksts</th><th>Preces</th><th>Darbības</th></tr>
    <?php foreach ($categories as $c): ?>
        <tr>
            <td><a href="products.php?kategorija=<?= $c['id'] ?>"><?= e($c['nosaukums']) ?></a></td>
            <td><?= e($c['apraksts']) ?></td>
            <td><?= $c['preces_skaits'] ?></td>
            <td class="actions">
                <a href="categories.php?edit=<?= $c['id'] ?>">Labot</a>
                <?php if (is_admin()): ?>
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
<?php require 'includes/footer.php'; ?>