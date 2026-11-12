<?php

require_once 'includes/functions.php';
require_admin();

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT nosaukums, daudzums FROM preces WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    //flash('Prece nav atrasta.', 'error');   
} elseif ($product['daudzums'] > 0) {
    flash('Nevar dzēst preci, kurai atlikums nav 0', 'error');
} else {
    $stmt = $pdo->prepare('DELETE FROM preces WHERE id = ?');
    $stmt->execute([$id]);
    flash('Prece "' . $product['nosaukums'] . '" dzēsta.');
}

header('Location: products.php');
exit;