<?php

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
            return -1; // Directories first
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

// FIXED: Enhanced breadcrumb function with proper navigation
function generateBreadcrumb($currentPath, $rootPath) {
    global $config;
    
    $breadcrumb = '<a href="?">🏠 Home</a>';
    
    // Convert paths to absolute for consistent handling
    $currentPath = realpath($currentPath) ?: $currentPath;
    
    // Split current path into parts
    $pathParts = explode('/', trim($currentPath, '/'));
    $pathParts = array_filter($pathParts, function($part) {
        return !empty($part);
    });
    
    $buildPath = '';
    foreach ($pathParts as $index => $part) {
        $buildPath .= '/' . $part;
        
        // Create proper absolute path for navigation
        $navigationPath = $buildPath;
        
        // URL encode the path for safe navigation
        $encodedPath = urlencode($navigationPath);
        
        $breadcrumb .= ' / <a href="?path=' . $encodedPath . '">' . htmlspecialchars($part) . '</a>';
    }
    
    // Add current full path info
    $breadcrumb .= '<div style="margin-top: 8px; font-size: 11px; color: #a0a0b0; font-family: monospace;">';
    $breadcrumb .= '📍 Full Path: ' . htmlspecialchars($currentPath);
    $breadcrumb .= '</div>';
    
    return $breadcrumb;
}

// Get folder list for search
function getFolderList() {
    global $config;
    
    $searchPath = $_GET['search_path'] ?? '';
    $folders = [];
    
    // Start from root if no search path specified
    if (empty($searchPath)) {
        $searchPath = '/';
    } else {
        // Handle relative paths
        if (strpos($searchPath, '/') !== 0) {
            $searchPath = $config['root_path'] . '/' . $searchPath;
        }
    }
    
    try {
        // Add some common system directories
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
        
        // Add subdirectories from current location
        if (is_dir($searchPath) && is_readable($searchPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            $count = 0;
            foreach ($iterator as $file) {
                if ($count++ > 50) break; // Limit to prevent timeout
                
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
        // Return basic folders on error
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

// NEW: Remote file management functions
function downloadFromGitHub($repoUrl, $targetPath) {
    // Extract GitHub repo info from URL
    if (preg_match('/github\.com\/([^\/]+)\/([^\/]+)/', $repoUrl, $matches)) {
        $owner = $matches[1];
        $repo = rtrim($matches[2], '.git');
        
        // GitHub API URL for downloading zip
        $zipUrl = "https://github.com/{$owner}/{$repo}/archive/refs/heads/main.zip";
        
        // Download and extract
        $tempFile = tempnam(sys_get_temp_dir(), 'github_repo');
        
        if (file_put_contents($tempFile, file_get_contents($zipUrl))) {
            $zip = new ZipArchive();
            if ($zip->open($tempFile) === TRUE) {
                $zip->extractTo($targetPath);
                $zip->close();
                unlink($tempFile);
                return true;
            }
        }
    }
    return false;
}

function syncWithRemoteRepo($repoUrl, $targetPath) {
    // Create sync directory if not exists
    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0755, true);
    }
    
    // Download from GitHub/GitLab
    return downloadFromGitHub($repoUrl, $targetPath);
}

?>
