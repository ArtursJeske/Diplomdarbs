<?php

require_once 'includes/functions.php';
require_login();

$error = '';
$edit_id = (int)($_GET['edit'] ?? 0);
$nosaukums = '';
$kontaktpersona = '';
$talrunis = '';
$epasts = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];

    if ($_POST['action'] == 'delete') {
        require_admin();

        // Klientu, kuram izsniegtas preces, dzēst nedrīkst - tad pazustu kustību vēsture
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kustibas WHERE klients_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            flash('Klientam ir reģistrētas kustības, tāpēc to nevar dzēst.', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM klienti WHERE id = ?');
            $stmt->execute([$id]);
            flash('Klients dzēsts.');
        }
        header('Location: clients.php');
        exit;
    }

    $nosaukums = trim($_POST['nosaukums']);
    $kontaktpersona = trim($_POST['kontaktpersona']);
    $talrunis = trim($_POST['talrunis']);
    $epasts = trim($_POST['epasts']);

    if ($nosaukums == '') {
        $error = 'Nosaukums ir obligāts';
    } elseif ($epasts != '' && !filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $error = 'Nederīga e-pasta adrese';
    }

    if ($error == '') {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE klienti SET nosaukums = ?, kontaktpersona = ?, talrunis = ?, epasts = ? WHERE id = ?');
            $stmt->execute([$nosaukums, $kontaktpersona, $talrunis, $epasts, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO klienti (nosaukums, kontaktpersona, talrunis, epasts) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nosaukums, $kontaktpersona, $talrunis, $epasts]);
        }
        header('Location: clients.php');
        exit;
    }
    $edit_id = $id;
} elseif ($edit_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM klienti WHERE id = ?');
    $stmt->execute([$edit_id]);
    $row = $stmt->fetch();
    if ($row) {
        $nosaukums = $row['nosaukums'];
        $kontaktpersona = $row['kontaktpersona'];
        $talrunis = $row['talrunis'];
        $epasts = $row['epasts'];
    }
}

// Katram klientam - cik reizes un cik preču vienību tam izsniegts
$clients = $pdo->query(
    'SELECT c.*, COUNT(k.id) AS kustibu_skaits, COALESCE(SUM(k.daudzums), 0) AS izsniegts_kopa
     FROM klienti c
     LEFT JOIN kustibas k ON k.klients_id = c.id
     GROUP BY c.id
     ORDER BY c.nosaukums'
)->fetchAll();

$title = 'Klienti';
require 'includes/header.php';
?>
<h1>Klienti</h1>

<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="card form-inline">
    <h2><?= $edit_id > 0 ? 'Labot klientu' : 'Jauns klients' ?></h2>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit_id ?>">
    <input type="text" name="nosaukums" placeholder="Nosaukums vai vārds, uzvārds" maxlength="150" value="<?= e($nosaukums) ?>" required>
    <input type="text" name="kontaktpersona" placeholder="Kontaktpersona" maxlength="100" value="<?= e($kontaktpersona) ?>">
    <input type="text" name="talrunis" placeholder="Tālrunis" maxlength="30" value="<?= e($talrunis) ?>">
    <input type="email" name="epasts" placeholder="E-pasts" maxlength="100" value="<?= e($epasts) ?>">
    <button type="submit" class="btn">Saglabāt</button>
    <?php if ($edit_id > 0): ?>
        <a href="clients.php">Atcelt</a>
    <?php endif; ?>
</form>

<table>
    <tr><th>Nosaukums</th><th>Kontaktpersona</th><th>Tālrunis</th><th>E-pasts</th><th>Izsniegšanas</th><th>Darbības</th></tr>
    <?php foreach ($clients as $c): ?>
        <tr>
            <td><?= e($c['nosaukums']) ?></td>
            <td><?= e($c['kontaktpersona']) ?></td>
            <td><?= e($c['talrunis']) ?></td>
            <td><?= e($c['epasts']) ?></td>
            <td><?= $c['kustibu_skaits'] ?> (<?= $c['izsniegts_kopa'] ?> vien.)</td>
            <td class="actions">
                <a href="clients.php?edit=<?= $c['id'] ?>">Labot</a>
                <?php if (is_admin()): ?>
                    <form method="post" onsubmit="return confirm('Dzēst klientu?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="link danger">Dzēst</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (count($clients) == 0): ?>
        <tr><td colspan="6">Klientu nav.</td></tr>
    <?php endif; ?>
</table>
<?php require 'includes/footer.php'; ?>
