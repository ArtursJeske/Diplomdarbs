<?php

require_once 'includes/functions.php';
require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);

    if ($action == 'create') {
        $username = trim($_POST['lietotajvards']);
        $name = trim($_POST['vards']);
        $password = $_POST['parole'];
        $role = $_POST['loma'] == 'admin' ? 'admin' : 'darbinieks';

     
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM lietotaji WHERE lietotajvards = ?');
        $stmt->execute([$username]);

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
            
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO lietotaji (lietotajvards, parole_hash, vards, loma) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $hash, $name, $role]);
            flash('Lietotājs izveidots.');
            header('Location: users.php');
            exit;
        }
    }

    if ($action == 'password') {
        $password = $_POST['parole'];
        if (strlen($password) < 6) {
            flash('Parolei jābūt vismaz 6 simbolus garai.', 'error');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE lietotaji SET parole_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $id]);
            flash('Parole nomainīta.');
        }
        header('Location: users.php');
        exit;
    }

    if ($action == 'delete') {
        
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kustibas WHERE lietotajs_id = ?');
        $stmt->execute([$id]);

        if ($id == $_SESSION['user_id']) {
            flash('Nevar dzēst pats sevi.', 'error');
        } elseif ($stmt->fetchColumn() > 0) {
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

$users = $pdo->query('SELECT id, lietotajvards, vards, loma, izveidots FROM lietotaji ORDER BY lietotajvards')->fetchAll();

$title = 'Lietotāji';
require 'includes/header.php';
?>
<h1>Lietotāji</h1>

<?php if ($error != ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="card form-inline">
    <h2>Jauns lietotājs</h2>
    <input type="hidden" name="action" value="create">
    <input type="text" name="lietotajvards" placeholder="Lietotājvārds" maxlength="50" required>
    <input type="text" name="vards" placeholder="Vārds, uzvārds" maxlength="100" required>
    <input type="password" name="parole" placeholder="Parole (min. 6)" required>
    <select name="loma">
        <option value="darbinieks">Darbinieks</option>
        <option value="admin">Administrators</option>
    </select>
    <button type="submit" class="btn">Izveidot</button>
</form>

<table>
    <tr><th>Lietotājvārds</th><th>Vārds</th><th>Loma</th><th>Izveidots</th><th>Jauna parole</th><th></th></tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['lietotajvards']) ?></td>
            <td><?= e($u['vards']) ?></td>
            <td><?= e($u['loma']) ?></td>
            <td><?= format_date($u['izveidots']) ?></td>
            <td>
                <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="password">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <input type="password" name="parole" placeholder="Jauna parole" required>
                    <button type="submit" class="link">Mainīt</button>
                </form>
            </td>
            <td>
                <form method="post" onsubmit="return confirm('Dzēst lietotāju?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="link danger">Dzēst</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require 'includes/footer.php'; ?>
