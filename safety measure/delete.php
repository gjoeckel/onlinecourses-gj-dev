<?php
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db = new Database();
    $db->query('DELETE FROM registrations WHERE id = ?', [$id]);
}
header('Location: registrations.php');
exit; 