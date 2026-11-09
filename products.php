<?php

require_once 'includes/functions.php';
require_login();

$search = trim($_GET['q'] ?? '');
$category = (int)($_GET['kategorija'] ?? 0);
$only_low = isset($_GET['low']);

$sql = 'SELECT p.*, k.nosaukums AS kategorija, s.nosaukums AS piegadatajs
        FROM preces p
        JOIN kategorijas k ON k.id = p.kategorija_id
        LEFT JOIN piegadataji s ON s.id = p.piegadatajs_id 
        WHERE 1=1';
$params = [];

if ($search != '') {
    $sql .= ' AND (p.nosaukums LIKE ? OR p.kods LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($category > 0) {
    $sql .= ' AND p.kategorija_id = ?';
    $params[] = $category;
}
if ($only_low) {
    $sql .= ' AND p.daudzums <= p.min_daudzums';
}
$sql .= ' ORDER BY p.nosaukums';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories =$pdo->query('SELECT id, nosaukums FROM kategorijas ORDER BY nosaukums')->fetchAll();

$title = 'Preces';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Preces (<?= count($products) ?>)</h1>
    <a class="btn" href="product_edit.php">+ Jauna prece</a>
</div>

<form method="get" class="filters">
    <input type="text" name="q" placeholder="Meklēt pēc nosaukuma vai koda" value="<?= e($search) ?>">
    <select name="kategorija">
        <option value="0">Visas kategorijas</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id'] == $category ? 'selected' : '' ?>><?= e($c['nosaukums']) ?></option>
        <?php endforeach; ?>
        </select>
        <label class="inline"><input type="checkbox" name="low" <?= $only_low ? 'checked' : '' ?>>Tikai zems atlikums</label>
        <button type="submit" class="btn">Meklēt</button>
        <a href="products.php">Notīrīt</a>
</form>

<table>
    <tr>
        <th>Kods</th><th>Nosaukums</th><th>Kategorija</th><th>Piegādātājs</th>
        <th>Cena</th><th>Atlikums</th><th>Vērtība</th><th>Darbības</th>
    </tr>
    <?php foreach ($products as $p): ?>
        <tr class="<?= $p['daudzums'] <= $p['min_daudzums'] ? 'low' : '' ?>">
            <td><?= e($p['kods']) ?></td>
            <td><?= e($p['nosaukums']) ?></td>
            <td><?= e($p['kategorija']) ?></td>
            <td><?= e($p['piegadatajs'] ?? '-') ?></td>
            <td><?= money($p['cena']) ?></td>
            <td><?= $p['daudzums'] ?> <?= e($p['vieniba']) ?></td>
            <td><?= money($p['daudzums'] * $p['cena']) ?></td>
            <td class="actions">
                <a href="product_edit.php?id=<?= $p['id'] ?>">Labot</a>
                <?php if (is_admin()): ?>
                    <form method="post" action="product_delete.php" onsubmit="return confirm('Dzēst preci?');">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit">Dzēst</button>
                    </form>
                <?php endif; ?>
            </td>            
        </tr>
    <?php endforeach; ?>
    <?php if (count($products) == 0): ?>
        <tr><td colspan="8">Nekas nav atrasts.</td></tr>
    <?php endif; ?>
</table>
<?php require 'includes/footer.php'; ?>