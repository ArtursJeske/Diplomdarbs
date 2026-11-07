<?php

require_once 'includes/functions.php';
require_login();

$error = '';
$edit_id = (int)($_GET['edit'] ?? 0);
$nosaukums = '';
$apraksts = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];

    if ($_POST['action'] == 'delete') {
        
        require_admin();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM preces WHERE kategorija_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            flash('Kategorijā ir preces, tapēc to nevar dzēst!', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM kategorijas WHERE id = ?');
            $stmt->execute([$id]);
            flash('Kategorija dzēsta!');
        }
        header('Location: categories.php');
        exit;
    }    
