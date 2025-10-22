<?php
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db = new Database();
    
    // Delete from main registrations table
    $db->query('DELETE FROM registrations WHERE id = ?', [$id]);
    
    // Note: We don't delete from registration_submissions to maintain the backup
}
header('Location: registrations.php');
exit; 