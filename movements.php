<?php
// Noliktavas kustības: preču saņemšana un izsniegšana + pēdējo kustību saraksts.
// Šī ir vienīgā vieta, kur mainās preču atlikums. Katra izmaiņa tiek ierakstīta vēsturē.
// Saņemt var tikai no piegādātāja, izsniegt - tikai klientam.
// movements.php?tips=izsniegta&prece=5 - atver izsniegšanas formu ar jau izvēlētu preci 5.

require_once 'includes/functions.php';   // DB savienojums ($pdo) un palīgfunkcijas
require_login();                          // kustības drīkst reģistrēt jebkurš pieteicies lietotājs

// Sākuma vērtības formai
$error = '';
$selected_product = (int)($_GET['prece'] ?? 0);  // prece no adreses (movements.php?prece=1), saite no product_edit.php
// Kustības tips no adreses (?tips=izsniegta). Atļautas tikai divas vērtības, viss pārējais = saņemšana.
$form_type = ($_GET['tips'] ?? '') == 'izsniegta' ? 'izsniegta' : 'sanemta';
$selected_supplier = 0;
$selected_client = 0;
$qty = '';    // daudzums un piezīme - kļūdas gadījumā paliek formā
$note = '';

// Ja lapa atvērta no preces (movements.php?prece=id), piedāvā preces piegādātāju
// (int) - ja precei nav piegādātāja (NULL) vai prece neeksistē (false), iegūst 0 = nekas nav izvēlēts
if ($selected_product > 0) {
    $stmt = $pdo->prepare('SELECT piegadatajs_id FROM preces WHERE id = ?');
    $stmt->execute([$selected_product]);
    $selected_supplier = (int)$stmt->fetchColumn();
}

// --- Forma iesniegta ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['prece_id'];
    $type = $_POST['tips'];                    // 'sanemta' vai 'izsniegta' no slēptā lauka, pārbauda zemāk
    $qty = (int)$_POST['daudzums'];            // (int) - teksts kļūst 0 un neiziet pārbaudi
    $note = trim($_POST['piezime']);
    $supplier_id = (int)($_POST['piegadatajs_id'] ?? 0);   // ?? 0 - formā ir tikai viens no abiem sarakstiem
    $client_id = (int)($_POST['klients_id'] ?? 0);

    // Ielādē preci: vai eksistē un kāds ir pašreizējais atlikums (vajag izsniegšanas pārbaudei)
    $stmt = $pdo->prepare('SELECT daudzums FROM preces WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    // Saņemt var tikai no piegādātāja, izsniegt - tikai klientam.
    // Formā ir tikai vajadzīgais saraksts, bet pieprasījumu var viltot, tāpēc otru partneri notīra (= 0).
    // COUNT(*) - pārbauda, vai partneris tiešām eksistē DB (id var būt viltots)
    $partner_ok = false;
    if ($type == 'sanemta') {
        $client_id = 0;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM piegadataji WHERE id = ?');
        $stmt->execute([$supplier_id]);
        $partner_ok = $stmt->fetchColumn() > 0;
    } elseif ($type == 'izsniegta') {
        $supplier_id = 0;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM klienti WHERE id = ?');
        $stmt->execute([$client_id]);
        $partner_ok = $stmt->fetchColumn() > 0;
    }

    // Pārbaudes (if/elseif - parāda pirmo atrasto kļūdu; secība ir svarīga)
    if (!$product) {
        $error = 'Izvēlies preci.';
    } elseif ($type != 'sanemta' && $type != 'izsniegta') {
        // Tips var būt viltots - pieņem tikai divas atļautās vērtības
        $error = 'Nezināms kustības tips.';
    } elseif ($qty <= 0) {
        $error = 'Daudzumam jābūt lielākam par 0.';
    } elseif ($type == 'sanemta' && !$partner_ok) {
        $error = 'Izvēlies piegādātāju, no kura prece saņemta.';
    } elseif ($type == 'izsniegta' && !$partner_ok) {
        $error = 'Izvēlies klientu, kuram prece izsniegta.';
    } elseif ($type == 'izsniegta' && $qty > $product['daudzums']) {
        // Nevar izsniegt vairāk, nekā ir noliktavā
        $error = 'Nepietiekams atlikums. Noliktavā ir tikai ' . $product['daudzums'] . '.';
    }

    if ($error == '') {

        // Kustībā daudzums vienmēr pozitīvs; atlikumam saņemšana pieskaita (+), izsniegšana atņem (-)
        if ($type == 'sanemta') {
            $change = $qty;
        } else {
            $change = -$qty;
        }

        // --- Transakcija: atlikuma maiņa un ieraksts vēsturē notiek ABI vai NEVIENS ---
        // Kamēr nav izsaukts commit(), izmaiņas nav galīgas un citiem lietotājiem nav redzamas.
        $pdo->beginTransaction();

        // try/catch - ja kāds no vaicājumiem izmet kļūdu (izņēmumu), izpilde pāriet uz catch bloku,
        // nevis apstājas ar PHP kļūdas lapu. (db.php ieslēdz ERRMODE_EXCEPTION - SQL kļūdas izmet izņēmumu.)
        try {
            // Atlikumu maina relatīvi pašā SQL (daudzums + ?), nevis aprēķina PHP.
            // Ja vairāki lietotāji maina vienlaikus, visas izmaiņas tiek ieskaitītas.
            // Ja atlikums kļūtu negatīvs, DB CHECK (daudzums >= 0) šo vaicājumu noraida -> catch.
            $stmt = $pdo->prepare('UPDATE preces SET daudzums = daudzums + ? WHERE id = ?');
            $stmt->execute([$change, $product_id]);

            // Ieraksts vēsturē
            $stmt = $pdo->prepare(
                'INSERT INTO kustibas (prece_id, tips, daudzums, piegadatajs_id, klients_id, piezime, lietotajs_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $product_id, $type, $qty,
                $supplier_id > 0 ? $supplier_id : null,   // 0 -> NULL: saņemšanai ir piegādātājs,
                $client_id > 0 ? $client_id : null,       // izsniegšanai - klients; DB CHECK to pieprasa
                $note, $_SESSION['user_id'],              // lietotājs no sesijas, nevis no formas - nevar viltot
            ]);

            // Abi vaicājumi izdevās - apstiprina abas izmaiņas vienlaikus
            $pdo->commit();

        } catch (PDOException $ex) {
            // Kāds vaicājums neizdevās - atceļ VISU, kas transakcijā paspēja izpildīties
            // (piem., ja UPDATE izdevās, bet INSERT nē, atlikums atgriežas iepriekšējā vērtībā).
            $pdo->rollBack();

            // Tehnisko kļūdas tekstu ieraksta servera žurnālā (XAMPP: apache/logs/error.log),
            // bet lietotājam to nerāda - tas var atklāt DB uzbūvi (tāpat kā db.php).
            error_log('Kustības saglabāšanas kļūda: ' . $ex->getMessage());

            // Biežākais iemesls: divi lietotāji vienlaikus izsniedza to pašu preci - pārbaude augstāk
            // abiem izgāja, bet otrajam atlikums kļūtu negatīvs, un DB to neļāva.
            $error = 'Kustību neizdevās saglabāt. Iespējams, atlikums pa to laiku mainījās - pārbaudi un mēģini vēlreiz.';
        }

        // Ja transakcija izdevās - paziņojums un pāradresācija.
        // Ja nē - $error ir aizpildīts, un izpilde turpinās zemāk: forma ar kļūdu un ievadītajām vērtībām.
        if ($error == '') {
            flash('Kustība reģistrēta.');
            // Pāradresācija, lai F5 neiesniegtu formu atkārtoti (citādi kustība tiktu reģistrēta divreiz).
            // ?tips= - forma paliek tajā pašā režīmā nākamajai kustībai ($type šeit jau ir pārbaudīts).
            header('Location: movements.php?tips=' . $type);
            exit;
        }
    }

    // Kļūdas gadījumā izvēlētās vērtības paliek formā
    $selected_product = $product_id;
    $form_type = $type == 'izsniegta' ? 'izsniegta' : 'sanemta';   // tikai atļautās vērtības
    $selected_supplier = $supplier_id;
    $selected_client = $client_id;
}

// Pēdējās 50 kustības (jaunākās augšā)
// JOIN - prece un lietotājs ir obligāti; LEFT JOIN - katrai kustībai ir TIKAI piegādātājs VAI klients,
// ar parasto JOIN pazustu visas kustības. Datumam ir indekss (schema.sql) - kārtošana ātra.
$movements = $pdo->query(
    'SELECT k.*, p.kods, p.nosaukums, p.vieniba, l.vards,
            s.nosaukums AS piegadatajs, c.nosaukums AS klients
     FROM kustibas k
     JOIN preces p ON p.id = k.prece_id
     JOIN lietotaji l ON l.id = k.lietotajs_id
     LEFT JOIN piegadataji s ON s.id = k.piegadatajs_id
     LEFT JOIN klienti c ON c.id = k.klients_id
     ORDER BY k.datums DESC
     LIMIT 50'
)->fetchAll();

// Saraksti formas izvēlnēm (precēm - arī atlikums, lai to redzētu pirms izsniegšanas)
$products = $pdo->query('SELECT id, kods, nosaukums, daudzums, vieniba FROM preces ORDER BY nosaukums')->fetchAll();
$suppliers = $pdo->query('SELECT id, nosaukums FROM piegadataji ORDER BY nosaukums')->fetchAll();
$clients = $pdo->query('SELECT id, nosaukums FROM klienti ORDER BY nosaukums')->fetchAll();

$title = 'Kustības';              // lapas virsraksts pārlūka cilnē (izmanto header.php)
require 'includes/header.php';    // lapas augšdaļa: <head>, izvēlne, flash paziņojumi
?>
<h1>Noliktavas kustības</h1>

<!-- Kļūdas paziņojums -->
<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<!-- Kustības tipa izvēle: saite pārlādē lapu ar ?tips=...; izvēlētā prece saglabājas adresē.
     Klase "active" iezīmē pašreizējo režīmu (style.css). -->
<div class="tabs">
    <a href="movements.php?tips=sanemta&prece=<?= $selected_product ?>" class="<?= $form_type == 'sanemta' ? 'active' : '' ?>">Saņemt</a>
    <a href="movements.php?tips=izsniegta&prece=<?= $selected_product ?>" class="<?= $form_type == 'izsniegta' ? 'active' : '' ?>">Izsniegt</a>
</div>

<!-- Jaunas kustības forma -->
<form method="post" class="card form-inline">
    <!-- Virsraksts atkarīgs no režīma -->
    <h2><?= $form_type == 'sanemta' ? 'Saņemt preci' : 'Izsniegt preci' ?></h2>
    <!-- Tips tiek nosūtīts slēptā laukā; PHP to tik un tā pārbauda -->
    <input type="hidden" name="tips" value="<?= $form_type ?>">
    <!-- Preces izvēle; tekstā redzams arī atlikums, piem. "K-001 — Skrūve (120 gab)" -->
    <select name="prece_id" required>
        <option value="">— prece —</option>
        <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id'] == $selected_product ? 'selected' : '' ?>>
                <?= e($p['kods'] . ' — ' . $p['nosaukums'] . ' (' . $p['daudzums'] . ' ' . $p['vieniba'] . ')') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- PHP izvada tikai vajadzīgo sarakstu: saņemšanai - piegādātājus, izsniegšanai - klientus -->
    <?php if ($form_type == 'sanemta'): ?>
        <select name="piegadatajs_id" required>
            <option value="">— no piegādātāja —</option>
            <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $selected_supplier ? 'selected' : '' ?>><?= e($s['nosaukums']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <select name="klients_id" required>
            <option value="">— klientam —</option>
            <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $selected_client ? 'selected' : '' ?>><?= e($c['nosaukums']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <!-- min="1", required - pārbaude pārlūkā; PHP to pašu pārbauda vēlreiz -->
    <input type="number" name="daudzums" min="1" placeholder="Daudzums" value="<?= e($qty) ?>" required>
    <input type="text" name="piezime" maxlength="255" placeholder="Piezīme (nav obligāta)" value="<?= e($note) ?>">
    <button type="submit" class="btn">Reģistrēt</button>
</form>

<!-- Pēdējo kustību saraksts -->
<h2>Pēdējās kustības</h2>
<table>
    <tr><th>Datums</th><th>Kods</th><th>Prece</th><th>Tips</th><th>Daudzums</th><th>Piegādātājs / klients</th><th>Piezīme</th><th>Lietotājs</th></tr>
    <?php foreach ($movements as $m): ?>
        <tr>
            <!-- format_date() - datums latviešu formātā (dd.mm.gggg hh:mm) -->
            <td><?= format_date($m['datums']) ?></td>
            <td><?= e($m['kods']) ?></td>
            <td><?= e($m['nosaukums']) ?></td>
            <!-- Saņemšanai: zaļā zīme, "+", piegādātājs; izsniegšanai: sarkanā zīme, "−", klients -->
            <?php if ($m['tips'] == 'sanemta'): ?>
                <td><span class="badge sanemts">Saņemts</span></td>
                <td>+<?= $m['daudzums'] ?> <?= e($m['vieniba']) ?></td>
                <td><?= e($m['piegadatajs'] ?? '—') ?></td>
            <?php else: ?>
                <td><span class="badge izsniegts">Izsniegts</span></td>
                <td>−<?= $m['daudzums'] ?> <?= e($m['vieniba']) ?></td>
                <td><?= e($m['klients'] ?? '—') ?></td>
            <?php endif; ?>
            <td><?= e($m['piezime']) ?></td>
            <td><?= e($m['vards']) ?></td>
        </tr>
    <?php endforeach; ?>
    <!-- Ja kustību vēl nav -->
    <?php if (count($movements) == 0): ?>
        <tr><td colspan="8">Kustību nav.</td></tr>
    <?php endif; ?>
</table>
<?php require 'includes/footer.php'; // lapas apakšdaļa ?>
