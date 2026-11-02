<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

