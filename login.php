<?php

require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM lietotaji WHERE lietotajvards =?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (user && password_verify($password, $user['parole_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['vards'] = $user['vards'];
        $_SESSION['loma'] = $user['loma'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Nepareizs lietotājvārds vai parole.';
    }
}

$title = 'Pieslēgšanās';
require 'includes/header.php';
?>

<div class="card login-box">
    <h1>Pieslēgšanās</h1>
    <?php if ($error !=''): ?>
        <div class="alert error"><? =e($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>lietotājvārds
            <input type="text" name="username" value="<?= e($username) ?>" required autofocus>
        </label>
        <label>Parole
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn">Pieslēgties</button>
    </form>
</div>               