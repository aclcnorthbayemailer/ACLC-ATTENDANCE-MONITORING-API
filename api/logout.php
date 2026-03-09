<?php
ini_set('display_errors', 0);
error_reporting(0);
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/db.php';
$token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
if ($token) {
    $db = getDB();
    $s  = $db->prepare("UPDATE users SET auth_token=NULL WHERE auth_token=?");
    $s->bind_param('s', $token); $s->execute(); $s->close(); $db->close();
}
respond(['success' => true]);
