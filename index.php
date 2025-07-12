<?php
session_start();

// Configuration - Set root path to current directory where index.php is located
$config = [
    'root_path' => __DIR__, // Changed to current directory instead of deep root
    'allowed_extensions' => ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip'],
    'max_upload_size' => 50 * 1024 * 1024, // 50MB
    'show_hidden' => false,
    // Password hash for "P@ssw0rd123234!@#@#$" - HIDDEN FROM VIEW
    'password_hash' => '$2y$10$YourHashWillBeGeneratedHere',
    'allow_root_access' => true // Allow access to parent directories
];

// Generate correct hash if needed
if ($config['password_hash'] === '$2y$10$YourHashWillBeGeneratedHere') {
    $config['password_hash'] = password_hash('P@ssw0rd123234!@#@#$', PASSWORD_DEFAULT);
}

// Include all functions
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/file-operations.php';
require_once 'includes/search.php';
require_once 'includes/templates.php';

// Authentication
if (!isset($_SESSION['authenticated'])) {
    if (isset($_POST['password']) && verifyPassword($_POST['password'], $config['password_hash'])) {
        $_SESSION['authenticated'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    showLoginForm();
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle actions
$action = $_GET['action'] ?? '';
$path = $_GET['path'] ?? '';

// Enhanced path handling - allow going up but start from current directory
if (empty($path)) {
    $fullPath = $config['root_path'];
} else {
    // Allow absolute paths for better navigation
    if (strpos($path, '/') === 0) {
        // Absolute path - use as is if it exists and is accessible
        $fullPath = realpath($path);
        if (!$fullPath || !is_dir($fullPath) || !is_readable($fullPath)) {
            $fullPath = $config['root_path'];
            $path = '';
        }
    } else {
        // Relative path from current root
        $fullPath = realpath($config['root_path'] . '/' . $path);
        if (!$fullPath || !is_dir($fullPath)) {
            $fullPath = $config['root_path'];
            $path = '';
        }
    }
}

switch ($action) {
    case 'view':
        viewFile($fullPath);
        break;
    case 'download':
        downloadFile($fullPath);
        break;
    case 'delete':
        deleteFile($fullPath);
        break;
    case 'upload':
        uploadFiles($fullPath);
        break;
    case 'create_folder':
        createFolder($fullPath, $_POST['folder_name'] ?? '');
        break;
    case 'edit':
        if ($_POST) {
            saveFile($fullPath, $_POST['content']);
        } else {
            showEditor($fullPath);
            exit;
        }
        break;
    case 'edit_permissions':
        if ($_POST) {
            changePermissions($fullPath, $_POST['permissions']);
        } else {
            showPermissionEditor($fullPath);
            exit;
        }
        break;
    case 'compress':
        compressFiles($fullPath, $_POST['files'] ?? [], $_POST['archive_name'] ?? '');
        break;
    case 'extract':
        extractFile($fullPath);
        break;
    case 'create_file':
        if ($_POST) {
            createFile($fullPath, $_POST['file_name'] ?? '', $_POST['file_content'] ?? '');
        } else {
            showFileCreator($fullPath);
            exit;
        }
        break;
    case 'copy':
        copyToClipboard($fullPath);
        break;
    case 'paste':
        pasteFromClipboard($fullPath);
        break;
    case 'search':
        showSearchResults();
        exit;
        break;
    case 'bulk_delete':
        bulkDeleteFiles($_POST['files'] ?? [], $fullPath);
        break;
    case 'bulk_copy':
        bulkCopyFiles($_POST['files'] ?? [], $fullPath);
        break;
    case 'bulk_delete_search':
        bulkDeleteSearchResults($_POST['files'] ?? []);
        break;
    case 'debug_search':
        debugSearch();
        exit;
        break;
    case 'generate_hash':
        if (isset($_POST['new_password'])) {
            echo '<pre>New password hash: ' . generatePasswordHash($_POST['new_password']) . '</pre>';
            exit;
        }
        break;
    case 'clear_clipboard':
        unset($_SESSION['clipboard']);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
        break;
    case 'terminal':
        runTerminalCommand($fullPath);
        exit;
        break;
    case 'get_folders':
        getFolderList();
        exit;
        break;
}

// Main interface
showFileManager($fullPath, $path);
?>
