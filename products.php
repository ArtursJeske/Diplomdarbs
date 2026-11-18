<?php
// Preču saraksts ar meklēšanu un filtriem (kategorija, zems atlikums).
// Lapa datus tikai lasa, pievienošana/labošana - product_edit.php, dzēšana - product_delete.php.

require_once 'includes/functions.php';
require_login();

//Filtri no adreses (GET)
$search = trim($_GET['q'] ?? '');                // meklējamais teksts
$category = (int)($_GET['kategorija'] ?? 0);     // kategorijas id, 0 = visas
$only_low = isset($_GET['low']);                 // neatzīmēta prece netiek nosūtīta vispār

// Vaicājuma salikšana
// JOIN - kategorija precei ir obligāta; LEFT JOIN - piegādātājs nav obligāts,
// bez LEFT preces bez piegādātāja sarakstā nebūtu redzamas.
// WHERE 1=1 - vienmēr patiess, ļauj katru filtru pievienot ar "AND..."
$sql = 'SELECT p.*, k.nosaukums AS kategorija, s.nosaukums AS piegadatajs
        FROM preces p
        JOIN kategorijas k ON k.id = p.kategorija_id
        LEFT JOIN piegadataji s ON s.id = p.piegadatajs_id 
        WHERE 1=1';
$params = []; // Vērtības vaicājumā ? vietām, tādā pašā secībā.

// Meklēšana pēc nosaukuma vai koda; % - jebkādi simboli pirms un pēc teksta
if ($search != '') {
    $sql .= ' AND (p.nosaukums LIKE ? OR p.kods LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
// Filtrs pēc kateforijas
if ($category > 0) {
    $sql .= ' AND p.kategorija_id = ?';
    $params[] = $category;
}
// Tikai preces, kuru atlikums sasniedzis minimumu (bez ?, jo nav lietotāja vērtības)
if ($only_low) {
    $sql .= ' AND p.daudzums <= p.min_daudzums';
}
$sql .= ' ORDER BY p.nosaukums';

//Sagatavots vaicājums - lietotāja ievade nekad nenonāk SQL tekstā
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

//Kategoriju saraksts filtra izvēlnei
$categories =$pdo->query('SELECT id, nosaukums FROM kategorijas ORDER BY nosaukums')->fetchAll();

$title = 'Preces';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Preces (<?= count($products) ?>)</h1>
    <a class="btn" href="product_edit.php">+ Jauna prece</a>
</div>

<!-- Filtru forma: GET, jo meklēšana datus nemaina - filtri redzami adresē -->
<form method="get" class="filters">
    <!-- value - pēc meklēšanas ievadītais teksts paliek laukā -->
    <input type="text" name="q" placeholder="Meklēt pēc nosaukuma vai koda" value="<?= e($search) ?>">
    <!-- kategoriju izvēlne; selected -izvēlētā kategorija paliek atzīmēta -->
    <select name="kategorija">
        <option value="0">Visas kategorijas</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id'] == $category ? 'selected' : '' ?>><?= e($c['nosaukums']) ?></option>
        <?php endforeach; ?>
        </select>
        <!-- Izvēles rūtiņa; checked - paliek atzīmēta pēc meklēšanas -->
        <label class="inline"><input type="checkbox" name="low" <?= $only_low ? 'checked' : '' ?>>Tikai zems atlikums</label>
        <button type="submit" class="btn">Meklēt</button>
        <!-- Notīrīt - atver lapu bez parametriem, bez filtriem -->
        <a href="products.php">Notīrīt</a>
</form>

<!-- Preču saraksts -->
<table>
    <tr>
        <th>Kods</th><th>Nosaukums</th><th>Kategorija</th><th>Piegādātājs</th>
        <th>Cena</th><th>Atlikums</th><th>Vērtība</th><th>Darbības</th>
    </tr>
    <!-- Katrai precei viena rinda; klase "low" izceļ preces ar zemu atlikumu (CSS) -->
    <?php foreach ($products as $p): ?>
        <tr class="<?= $p['daudzums'] <= $p['min_daudzums'] ? 'low' : '' ?>">
            <td><?= e($p['kods']) ?></td>
            <td><?= e($p['nosaukums']) ?></td>
            <td><?= e($p['kategorija']) ?></td>
            <!-- Ja piegādātaja nav rāda "-" -->
            <td><?= e($p['piegadatajs'] ?? '-') ?></td>
            <!-- money() - formatē naudas izskatu -->
            <td><?= money($p['cena']) ?></td>
            <td><?= $p['daudzums'] ?> <?= e($p['vieniba']) ?></td>
            <!-- Vērtība = atlikums x cena; aprēķina šeit , DB to neglabā -->
            <td><?= money($p['daudzums'] * $p['cena']) ?></td>
            <td class="actions">
                <!-- Labošana atsevišķā lapā -->
                <a href="product_edit.php?id=<?= $p['id'] ?>">Labot</a>
                <!-- dzēšanas poga tikai administratoram -->
                <?php if (is_admin()): ?>
                    <!-- Dzēšana ar POST formu uz product_delete.php un apstiprinajumu -->
                    <form method="post" action="product_delete.php" onsubmit="return confirm('Dzēst preci?');">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit">Dzēst</button>
                    </form>
                <?php endif; ?>
            </td>            
        </tr>
    <?php endforeach; ?>
    <!-- ja pēc filtriem nekas neatbilst, parāda paziņojumu -->
    <?php if (count($products) == 0): ?>
        <tr><td colspan="8">Nekas nav atrasts.</td></tr>
    <?php endif; ?>
</table>
<?php require 'includes/footer.php'; ?>