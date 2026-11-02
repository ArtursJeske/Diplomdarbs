<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function money($amount)
{
    return number_format((float)$amount, 2, ',', ' ') . '€';
}

function format_date($date)
{
    return date('d.m.Y H:i', strtotime($date));
}

