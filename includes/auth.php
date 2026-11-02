<?php

session_start();


function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function is_admin()
{
    return isset($_SESSION['loma']) && $_SESSION['loma'] == 'admin';
}

function require_admin()
{
    require_login();
    if (!is_admin()) {
        die('Šī darbība pieejama tikai administratoram');
    }
}