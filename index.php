<?php

require_once 'includes/functions.php';
require_login();

$product_count = $pdo->query('SELECT COUNT(*) FROM preces')->fetchColumn();

$title = 'Sākums';

require 'includes/header.php';

?>

<h1>Sākums</h1>

<div class="stats">
    <div><?= (int)$product_count ?></div>
</div>

<?php require 'includes/footer.php'; ?>