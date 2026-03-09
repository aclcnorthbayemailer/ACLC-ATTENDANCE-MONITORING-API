<?php
ini_set('display_errors', 0);
error_reporting(0);
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed', 405);
$user=requireAuth(); $b=getBody(); $action=$b['action']??''; $db=getDB();
if ($action==='update_name') {
    $name=trim($b['name']??''); if (!$name) respondError('Name required.');
    $ini=strtoupper(substr($name,0,1).(strpos($name,' ')!==false?substr(strrchr($name,' '),1,1):''));
    $s=$db->prepare("UPDATE users SET name=?,initials=? WHERE id=?");
    $s->bind_param('ssi',$name,$ini,$user['id']); $s->execute(); $s->close(); $db->close();
    respond(['success'=>true]);
}
if ($action==='change_password') {
    $old=$b['old_password']??''; $new=$b['new_password']??'';
    if (!$old||!$new) respondError('Both required.'); if (strlen($new)<6) respondError('Min 6 chars.');
    $s=$db->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
    $s->bind_param('i',$user['id']); $s->execute();
    $row=$s->get_result()->fetch_assoc(); $s->close();
    if (!password_verify($old,$row['password'])&&$old!==$row['password']) respondError('Wrong password.');
    $h=password_hash($new,PASSWORD_DEFAULT);
    $u=$db->prepare("UPDATE users SET password=? WHERE id=?");
    $u->bind_param('si',$h,$user['id']); $u->execute(); $u->close(); $db->close();
    respond(['success'=>true]);
}
respondError('Invalid action.');
