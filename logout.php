<?php
// logout.php
require_once 'config/database.php';
require_once 'classes/UserManager.php';

$userManager = new UserManager($db);
$userManager->logout();

redirect('login.php?logged_out=1');
?>