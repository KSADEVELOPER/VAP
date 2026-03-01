<?php
// admin/get_data.php
require_once '../config/database.php';
require_once '../classes/UserManager.php';
require_once '../classes/WebsiteManager.php';
require_once '../classes/PlatformManager.php';

$userManager = new UserManager($db);
$websiteManager = new WebsiteManager($db);
$platformManager = new PlatformManager($db);

// التحقق من تسجيل الدخول وصلاحيات الإدارة
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$user = $userManager->getUserById($_SESSION['user_id']);
if (!$user || !$userManager->isAdmin()) {
    die(json_encode(['error' => 'Unauthorized']));
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch ($type) {
    case 'pending_websites':
        $websites = $websiteManager->getAllWebsites('pending');
        echo json_encode($websites);
        break;
    
    case 'all_users':
        $users = $userManager->getAllUsers();
        echo json_encode($users);
        break;
    
    case 'all_websites':
        $websites = $websiteManager->getAllWebsites();
        echo json_encode($websites);
        break;
    
    case 'all_platforms':
        $platforms = $platformManager->getAllPlatforms();
        echo json_encode($platforms);
        break;
    
    default:
        echo json_encode(['error' => 'Invalid request type']);
        break;
}