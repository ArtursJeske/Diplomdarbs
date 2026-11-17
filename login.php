<?php
// Pieslēgšanās lapa - vienīgā lapa, kas pieejama bez pieslēgšanās.

require_once 'includes/functions.php';

//Ja lietotājs jau ir pieslēdzies - uzreiz aizved uz sākumlapu
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = ''; //Lai pēc kļūdas lietotājvārds paliktu formā.

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';  //parolei neliekam trim, jo atstarpe var būt paroles daļa

    // Meklē lietotāju (sagatavotais vaicājums)
    $stmt = $pdo->prepare('SELECT * FROM lietotaji WHERE lietotajvards =?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // password_verify salīdzina ievadīto paroli ar saglabāto hash
    if ($user && password_verify($password, $user['parole_hash'])) {
        // Saglabā lietotāja datus sesijā - tos izmanto auth.php un header.php
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['vards'] = $user['vards'];
        $_SESSION['loma'] = $user['loma'];
        header('Location: index.php');
        exit;
    } else {
        // Vienāds ziņojums abos gadījumos - neatklāj, vai lietotājvārds eksistē
        $error = 'Nepareizs lietotājvārds vai parole.';
    }
}

$title = 'Pieslēgšanās';
require 'includes/header.php';
?>

<div class="card login-box">
    <h1>Pieslēgšanās</h1>
    <?php if ($error != ''): ?>
        <div class="alert error"><?=e($error) ?></div>
    <?php endif; ?>
    <form method="post" class="form">
        <label>Lietotājvārds
            <input type="text" name="username" value="<?= e($username) ?>" required autofocus>
        </label>
        <label>Parole
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn">Pieslēgties</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>
