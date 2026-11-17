<?php
// Kopīgā lapas augšdaļa - ielādē katra lapa pēc savas PHP loģijas.
// Pirms ielādes lapa iestata $title 
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <!-- Lapa pielāgojas telefona ekrāna platumam -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Noliktava') ?> | Noliktavas uzskaite</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
<header class='topbar'>
    <div class="logo">Noliktavas uzskaites sistēma</div>
    <!-- Izvēlne redzama tikai pieslēgušamies lietotājiem -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <nav>
            <a href="index.php">Sākums</a>
            <a href="products.php">Preces</a>
            <a href="movements.php">Kustības</a>
            <a href="categories.php">Kategorijas</a>
            <a href="suppliers.php">Piegādātāji</a>
            <a href="clients.php">Klienti</a>
            <!-- Lietotāju pārvaldība - tikai administratoram -->
            <?php if (is_admin()): ?>
                <a href="users.php">Lietotāji</a>
            <?php endif; ?>
        </nav>
        <div class="user">
            <?= e($_SESSION['vards']) ?> (<?= e($_SESSION['loma']) ?>)
            <a href="logout.php">Iziet</a>
        </div>
    <?php endif; ?>
</header>
<!-- Lapas saturs <main> tiek aizvērts -->
<main class="container">
    <!-- Vienreizējs paziņojums no flash() - parāda un uzreiz izdzēš -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert <?= e($_SESSION['flash_type']) ?>"><?= e($_SESSION['flash']) ?></div>
        <?php
        unset($_SESSION['flash'], $_SESSION['flash_type']);
        ?>
    <?php endif; ?>