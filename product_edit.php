<?php

require_once 'includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$errors = [];

$product = [
    'kods' => '',
    'nosaukums' => '',
    'kategorija_id' => '',
    'piegadatajs_id' => '',
    'vieniba' => 'gab.',
    'cena' => '0.00',
    'min_daudzums' => 0,
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM preces WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        $errors[] = 'Prece nav atrasta';
        header('Location: products.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product['kods'] = trim($_POST['kods']);
    $product['nosaukums'] = trim($_POST['nosaukums']);
    $product['kategorija_id'] = (int)$_POST['kategorija_id'];
    $product['vieniba'] = trim($_POST['vieniba']);
    $product['cena'] = str_replace(',', '.', trim($_POST['cena']));
    $product['min_daudzums'] = (int)$_POST['min_daudzums'];
   
    if ($_POST['piegadatajs_id'] == '') {
        $product['piegadatajs_id'] = null;        
    } else {
        $product['piegadatajs_id'] = (int)$_POST['piegadatajs_id'];
    }
    if ($product['vieniba'] == '') {
        $product['vieniba'] = 'gab.';
    }
    if ($product['kods'] == '') {
        $errors[] = 'Kods ir obligāts';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM preces WHERE kods = ? AND id != ?');
        $stmt->execute([$product['kods'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Prece ar šādu koddu jau eksistē';
        }
    }
    if ($product['nosaukums'] == '') {
        $errors[] = "Nosaukums ir obligāts";
    }
    if ($product['kategorija_id'] == 0) {
        $errors[] = 'Izvēlies kategoriju.';
    }
    if ($product['cena'] < 0){
        $errors[] = 'Cenai jābūt pozitīvai.';
    }
    if ($product['min_daudzums'] < 0) {
        $errors[] = 'Minimālais atlikums nevar būt negatīvs.';
    }

    if (count($errors) == 0) {
        if ($id == 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO preces (kods, nosaukums, kategorija_id, piegadatajs_id, vieniba, cena, min_daudzums)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $product['kods'], $product['nosaukums'], $product['kategorija_id'], $product['piegadatajs_id'],
                $product['vieniba'], $product['cena'], $product['min_daudzums'],
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE preces SET kods = ?, nosaukums = ?, kategorija_id = ?, piegadatajs_id = ?,
                 vieniba = ?, cena = ?, min_daudzums = ? WHERE id = ?'
            );
            $stmt->execute([
                $product['kods'], $product['nosaukums'], $product['kategorija_id'], $product['piegadatajs_id'],
                $product['vieniba'], $product['cena'], $product['min_daudzums'], $id,
            ]);
        }
        header('Location: products.php');
        exit;
    }
   
}


$categories = $pdo->query('SELECT id, nosaukums FROM kategorijas ORDER BY nosaukums')->fetchAll();
$suppliers = $pdo->query('SELECT id, nosaukums FROM piegadataji ORDER BY nosaukums')->fetchAll();

$title = $id == 0 ? 'Jauna prece' : 'Labot preci';
require 'includes/header.php';
?>
<h1><?= $title ?></h1>

<?php if (count($errors) > 0): ?>
    <div class="alert error">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="card form">
    <label>Kods *
        <input type="text" name="kods" maxlength="30" value="<?= e($product['kods']) ?>" required>
    </label>
    <label>Nosaukums *
        <input type="text" name="nosaukums" maxlength="150" value="<?= e($product['nosaukums']) ?>" required>
    </label>
    <label>Kategorija *
        <select name="kategorija_id" required>
            <option value="">— izvēlies —</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $product['kategorija_id'] ? 'selected' : '' ?>><?= e($c['nosaukums']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Piegādātājs
        <select name="piegadatajs_id">
            <option value="">— nav —</option>
            <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $product['piegadatajs_id'] ? 'selected' : '' ?>><?= e($s['nosaukums']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Mērvienība
        <input type="text" name="vieniba" maxlength="10" value="<?= e($product['vieniba']) ?>">
    </label>
    <label>Cena (€) *
        <input type="text" name="cena" value="<?= e($product['cena']) ?>" required>
    </label>
    <label>Minimālais atlikums
        <input type="number" name="min_daudzums" min="0" value="<?= e($product['min_daudzums']) ?>">
    </label>
    <?php if ($id > 0): ?>
        <p>Pašreizējais atlikums: <strong><?= $product['daudzums'] ?></strong>
            (mainās ar <a href="movements.php?prece=<?= $id ?>">kustībām</a>)</p>
    <?php endif; ?>
    <div class="form-buttons">
        <button type="submit" class="btn">Saglabāt</button>
        <a href="products.php">Atcelt</a>
    </div>
</form>
<?php require 'includes/footer.php'; ?>

