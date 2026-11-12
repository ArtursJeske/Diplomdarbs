<?php

require_once 'includes/functions.php';
require_login();

$error = '';
$edit_id = (int)($_GET['edit'] ?? 0);
$nosaukums = '';
$kontaktpersona = '';
$talrunis = '';
$talrunis = '';
$epasts = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST ['id'];

    if ($_POST['action'] == 'delete') {
        require_admin();

        $stmt = $pdo->prepare('DELETE FROM piegadatajo WHERE id =?');
        $stmt->execute([$id]);
        //flash('Piegādātājs dzēsts.');
        header('Location: suppliers.php');
        exit;
    }

    $nosaukums = trim($_POST['nosaukums']);
    $kontaktpersona = trim($_POST['kontaktpersona']);
    $talrunis = trim($_POST['talrunis']);
    $epasts = trim($POST['epasts']);

    if ($nosaukums == '') {
        $error = 'Nosaukums ir obligāts';

    } elseif ($epasts != '' && !filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $error = 'Nederīga e-pasta adrese';
    }

    if ($errpr == '') {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE piegadataji SET nosaukums = ?, kontaktpersona = ?, talrunis = ?, epasts = ? WHERE id = ?');
            $stmt->execute([$nosaukums, $kontaktpersona, $talrunis, $epasts, $id]);
            //flash('Piegādātājs atjaunots.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO piegadataji (nosaukums, kontaktpersona, talrunis, epasts) VALUES (?, ?, ?, ?');
            $stmt->execute([$nosaukums, $kontaktpersona, $talrunis, $epasts]);
            //flash('Piegādātājs pievienots');
        }
        header('Location: suppliers.php');
        exit;
    }
} elseif ($edit_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM piegadataji WHERE id = ?');
    $stmt->execute([$edit_id]);
    $row = $stmt->fetch();
    if ($row) {
        $nosaukums = $row['nosaukums'];
        $kontaktpersona = $row['kontaktpersona'];
        $talrunis = $row['talrunis'];
        $epasts = $row['epasts'];
    } 
}

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

<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="card form-inline">
    <h2><?= $edit_id > 0 ? 'Labot piegādātāju' : 'Jauns piegādātājs' ?></h2>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit_id ?>">
    <input type="text" name="nosaukums" placeholder="Nosaukums" maxlength="150" value="<?= e($nosaukums) ?>" required>
    <input type="text" name="kontaktpersona" placeholder="Kontaktpersona" maxlength="100" value="<?= e($kontaktpersona) ?>">
    <input type="text" name="talrunis" placeholder="Tālrunis" maxlength="30" value="<?= e($talrunis) ?>">
    <input type="email" name="epasts" placeholder="E-pasts" maxlength="100" value="<?= e($epasts) ?>">
    <button type="submit" class="btn">Saglabāt</button>
    <?php if ($edit_id > 0): ?>
        <a href="suppliers.php">Atcelt</a>
    <?php endif; ?>
</form>

<table>
    <tr><th>Nosaukums</th><th>Kontaktpersona</th><th>Tālrunis</th><th>E-pasts</th><th>Preces</th><th>Darbības</th></tr>
    <?php foreach ($suppliers as $s): ?>
        <tr>
            <td><?= e($s['nosaukums']) ?></td>
            <td><?= e($s['kontaktpersona']) ?></td>
            <td><?= e($s['talrunis']) ?></td>
            <td><?= e($s['epasts']) ?></td>
            <td><?= $s['preces_skaits'] ?></td>
            <td class="actions">
                <a href="suppliers.php?edit=<?= $s['id'] ?>">Labot</a>
                <?php if (is_admin()): ?>
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