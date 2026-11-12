<?php
require_once 'includes/functions.php';
require_login();

$error = '';
$selected_product = (int)($_GET['prece'] ?? 0);
$form_type = 'saņemta';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['prece_id'];
    $type = $_POST['tips'];
    $qty = (int)$_POST['daudzums'];
    $note = trim($_POST['piezime']);

   
    $stmt = $pdo->prepare('SELECT daudzums FROM preces WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        $error = 'Izvēlies preci.';
    } elseif ($type != 'saņemta' && $type != 'izsniegta') {
        $error = 'Nezināms kustības tips.';
    } elseif ($qty <= 0) {
        $error = 'Daudzumam jābūt lielākam par 0.';
    } elseif ($type == 'izsniegta' && $qty > $product['daudzums']) {
        $error = 'Nepietiekams atlikums. Noliktavā ir tikai ' . $product['daudzums'] . '.';
    }

    if ($error == '') {
       
        if ($type == 'saņemta') {
            $change = $qty;
        } else {
            $change = -$qty;
        }

     
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE preces SET daudzums = daudzums + ? WHERE id = ?');
        $stmt->execute([$change, $product_id]);

        $stmt = $pdo->prepare('INSERT INTO kustibas (prece_id, tips, daudzums, piezime, lietotajs_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$product_id, $type, $qty, $note, $_SESSION['user_id']]);

        $pdo->commit();

        //flash('Kustība reģistrēta.');
        header('Location: movements.php');
        exit;
    }
    $selected_product = $product_id;
    $form_type = $type;
}


$movements = $pdo->query(
    'SELECT k.*, p.kods, p.nosaukums, p.vieniba, l.vards
     FROM kustibas k
     JOIN preces p ON p.id = k.prece_id
     JOIN lietotaji l ON l.id = k.lietotajs_id
     ORDER BY k.datums DESC
     LIMIT 50'
)->fetchAll();


$products = $pdo->query('SELECT id, kods, nosaukums, daudzums, vieniba FROM preces ORDER BY nosaukums')->fetchAll();

$title = 'Kustības';
require 'includes/header.php';
?>
<h1>Noliktavas kustības</h1>

<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="card form-inline">
    <h2>Jauna kustība</h2>
    <select name="prece_id" required>
        <option value="">— prece —</option>
        <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id'] == $selected_product ? 'selected' : '' ?>>
                <?= e($p['kods'] . ' — ' . $p['nosaukums'] . ' (' . $p['daudzums'] . ' ' . $p['vieniba'] . ')') ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="tips">
        <option value="saņemta">Saņemts (pieņemšana)</option>
        <option value="izsniegta" <?= $form_type == 'izsniegta' ? 'selected' : '' ?>>Izsniegts</option>
    </select>
    <input type="number" name="daudzums" min="1" placeholder="Daudzums" required>
    <input type="text" name="piezime" maxlength="255" placeholder="Piezīme (nav obligāta)">
    <button type="submit" class="btn">Reģistrēt</button>
</form>

<h2>Pēdējās kustības</h2>
<table>
    <tr><th>Datums</th><th>Kods</th><th>Prece</th><th>Tips</th><th>Daudzums</th><th>Piezīme</th><th>Lietotājs</th></tr>
    <?php foreach ($movements as $m): ?>
        <tr>
            <td><?= format_date($m['datums']) ?></td>
            <td><?= e($m['kods']) ?></td>
            <td><?= e($m['nosaukums']) ?></td>
            <?php if ($m['tips'] == 'saņemta'): ?>
                <td><span class="badge sanemts">Saņemts</span></td>
                <td>+<?= $m['daudzums'] ?> <?= e($m['vieniba']) ?></td>
            <?php else: ?>
                <td><span class="badge izsniegts">Izsniegts</span></td>
                <td>−<?= $m['daudzums'] ?> <?= e($m['vieniba']) ?></td>
            <?php endif; ?>
            <td><?= e($m['piezime']) ?></td>
            <td><?= e($m['vards']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (count($movements) == 0): ?>
        <tr><td colspan="7">Kustību nav.</td></tr>
    <?php endif; ?>
</table>
<?php require 'includes/footer.php'; ?>
