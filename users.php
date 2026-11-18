<?php
// Lietotāju pārvaldība (tikai administratoram): jauna lietotāja izveide, paroles maiņa, dzēšana.
// Paroles netiek glabātas - DB glabā tikai password_hash() rezultātu.
// Vienā lapā trīs darbības; kuru izpildīt, nosaka slēptais lauks "action".

require_once 'includes/functions.php';   // DB savienojums ($pdo) un palīgfunkcijas
require_admin();                          // visa lapa tikai administratoram (iekļauj arī require_login)

$error = '';

// --- Forma iesniegta ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];          // 'create', 'password' vai 'delete'
    $id = (int)($_POST['id'] ?? 0);      // lietotāja id (paroles maiņai un dzēšanai); izveidei nav

    // --- Jauna lietotāja izveide ---
    if ($action == 'create') {
        $username = trim($_POST['lietotajvards']);
        $name = trim($_POST['vards']);
        $password = $_POST['parole'];    // bez trim() - atstarpes var būt paroles daļa
        // Loma: ja nav tieši 'admin', tad 'darbinieks' - viltotu vērtību nevar ierakstīt
        $role = $_POST['loma'] == 'admin' ? 'admin' : 'darbinieks';


        // Vai šāds lietotājvārds jau ir aizņemts (rezultātu nolasa zemāk ar fetchColumn)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM lietotaji WHERE lietotajvards = ?');
        $stmt->execute([$username]);

        // Pārbaudes (if/elseif - parāda pirmo atrasto kļūdu)
        if (strlen($username) < 3) {
            $error = 'Lietotājvārdam jābūt vismaz 3 simbolus garam.';
        } elseif ($stmt->fetchColumn() > 0) {
            $error = 'Šāds lietotājvārds jau ir aizņemts.';
        } elseif ($name == '') {
            $error = 'Vārds ir obligāts.';
        } elseif (strlen($password) < 6) {
            $error = 'Parolei jābūt vismaz 6 simbolus garai.';
        }

        if ($error == '') {

            // password_hash() - vienvirziena jaucējfunkcija (bcrypt) ar nejaušu "sāli".
            // No hash paroli atjaunot nevar; pieteicoties login.php to pārbauda ar password_verify().
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO lietotaji (lietotajvards, parole_hash, vards, loma) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $hash, $name, $role]);
            flash('Lietotājs izveidots.');
            // Pāradresācija, lai F5 neiesniegtu formu atkārtoti
            header('Location: users.php');
            exit;
        }
    }

    // --- Paroles maiņa (administrators var nomainīt jebkura lietotāja paroli) ---
    if ($action == 'password') {
        $password = $_POST['parole'];
        if (strlen($password) < 6) {
            flash('Parolei jābūt vismaz 6 simbolus garai.', 'error');
        } else {
            // Jaunā parole arī tiek saglabāta tikai kā hash
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE lietotaji SET parole_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $id]);
            flash('Parole nomainīta.');
        }
        header('Location: users.php');
        exit;
    }

    // --- Lietotāja dzēšana ---
    if ($action == 'delete') {

        // Vai lietotājs ir reģistrējis kustības (rezultātu nolasa zemāk)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kustibas WHERE lietotajs_id = ?');
        $stmt->execute([$id]);

        if ($id == $_SESSION['user_id']) {
            // Pats sevi dzēst nedrīkst - tā sistēmā vienmēr paliek vismaz viens administrators
            flash('Nevar dzēst pats sevi.', 'error');
        } elseif ($stmt->fetchColumn() > 0) {
            // Lietotāju ar kustībām dzēst nedrīkst - vēsturē jāredz, kas darbību veica
            // (DB to bloķē ar ārējo atslēgu, bet lietotājs redzētu kļūdu, nevis saprotamu ziņu)
            flash('Lietotājam ir reģistrētas kustības, tāpēc to nevar dzēst.', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM lietotaji WHERE id = ?');
            $stmt->execute([$id]);
            flash('Lietotājs dzēsts.');
        }
        header('Location: users.php');
        exit;
    }
}

// Visi lietotāji sarakstam (bez parole_hash - tas lapā nav vajadzīgs)
$users = $pdo->query('SELECT id, lietotajvards, vards, loma, izveidots FROM lietotaji ORDER BY lietotajvards')->fetchAll();

$title = 'Lietotāji';             // lapas virsraksts pārlūka cilnē (izmanto header.php)
require 'includes/header.php';    // lapas augšdaļa: <head>, izvēlne, flash paziņojumi
?>
<h1>Lietotāji</h1>

<!-- Kļūdas paziņojums (tikai jauna lietotāja izveidei; pārējās darbības izmanto flash) -->
<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<!-- Jauna lietotāja forma -->
<form method="post" class="card form-inline">
    <h2>Jauns lietotājs</h2>
    <input type="hidden" name="action" value="create">
    <!-- maxlength atbilst kolonnu garumam datubāzē -->
    <input type="text" name="lietotajvards" placeholder="Lietotājvārds" maxlength="50" required>
    <input type="text" name="vards" placeholder="Vārds, uzvārds" maxlength="100" required>
    <!-- type="password" - ievadītie simboli netiek rādīti -->
    <input type="password" name="parole" placeholder="Parole (min. 6)" required>
    <!-- Loma: darbinieks - ikdienas darbs; administrators - arī dzēšana un lietotāju pārvaldība -->
    <select name="loma">
        <option value="darbinieks">Darbinieks</option>
        <option value="admin">Administrators</option>
    </select>
    <button type="submit" class="btn">Izveidot</button>
</form>

<!-- Lietotāju saraksts -->
<table>
    <tr><th>Lietotājvārds</th><th>Vārds</th><th>Loma</th><th>Izveidots</th><th>Jauna parole</th><th></th></tr>
    <!-- Katram lietotājam viena rinda; visu tekstu izvada caur e() (aizsardzība pret XSS) -->
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['lietotajvards']) ?></td>
            <td><?= e($u['vards']) ?></td>
            <td><?= e($u['loma']) ?></td>
            <!-- format_date() - datums latviešu formātā (dd.mm.gggg hh:mm) -->
            <td><?= format_date($u['izveidots']) ?></td>
            <td>
                <!-- Paroles maiņa: maza forma katrā rindā (inline-form - vienā rindā ar tekstu) -->
                <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="password">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <input type="password" name="parole" placeholder="Jauna parole" required>
                    <button type="submit" class="link">Mainīt</button>
                </form>
            </td>
            <td>
                <!-- Dzēšana ar POST formu (nevis saiti) un apstiprinājumu -->
                <form method="post" onsubmit="return confirm('Dzēst lietotāju?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="link danger">Dzēst</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require 'includes/footer.php'; // lapas apakšdaļa ?>
