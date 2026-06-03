<?php
// Central router for Portalia
require_once 'db.php';

if (isAuthenticated()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
    } else {
        header("Location: marketplace/index.php");
    }
} else {
    header("Location: marketplace/welcome.php");
}
exit;
?>
