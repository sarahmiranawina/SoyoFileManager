<?php

function performFileSearch($rootPath, $filenameQuery, $contentQuery, $filenameType = 'contain', $contentType = 'contain') {
    $results = [];
    $searchCount = 0;
    $maxResults = 1000; // Limit results to prevent timeout
    
    // Determine search path from GET parameters
    $searchFolder = $_GET['search_folder'] ?? '';
    
    if (!empty($searchFolder)) {
        if ($searchFolder === '/') {
            $searchPath = '/';
        } elseif (strpos($searchFolder, '/') === 0) {
            // Absolute path
            $searchPath = $searchFolder;
        } else {
            // Relative path from root
            $searchPath = $rootPath . '/' . $searchFolder;
        }
    } else {
        $searchPath = $rootPath;
    }
    
    // Validate search path
    if (!is_dir($searchPath) || !is_readable($searchPath)) {
        throw new Exception("Search path is not accessible: " . $searchPath);
    }
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            // Limit search to prevent timeout
            if ($searchCount++ > $maxResults) {
                break;
            }
            
            if (!$file->isFile()) continue;
            
            $filename = $file->getFilename();
            $fullPath = $file->getPathname();
            
            // Calculate relative path from root
            $relativePath = str_replace($rootPath . DIRECTORY_SEPARATOR, '', $fullPath);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            
            $filenameMatch = false;
            $contentMatch = false;
            
            // Check filename match
            if (!empty($filenameQuery)) {
                $filenameMatch = matchFilename($filename, $filenameQuery, $filenameType);
            }
            
            // Check content match (only for text files)
            if (!empty($contentQuery) && isTextFile($filename)) {
                try {
                    // Only read files smaller than 10MB to prevent memory issues
                    if ($file->getSize() < 10 * 1024 * 1024) {
                        $content = file_get_contents($fullPath);
                        if ($content !== false) {
                            $contentMatch = matchContent($content, $contentQuery, $contentType);
                        }
                    }
                } catch (Exception $e) {
                    // Skip files that can't be read
                    continue;
                }
            }
            
            // Determine if file matches criteria
            $shouldInclude = false;
            $matchType = '';
            
            if (!empty($filenameQuery) && !empty($contentQuery)) {
                // Both criteria must match
                if ($filenameMatch && $contentMatch) {
                    $shouldInclude = true;
                    $matchType = 'both';
                }
            } elseif (!empty($filenameQuery)) {
                // Only filename criteria
                if ($filenameMatch) {
                    $shouldInclude = true;
                    $matchType = 'filename';
                }
            } elseif (!empty($contentQuery)) {
                // Only content criteria
                if ($contentMatch) {
                    $shouldInclude = true;
                    $matchType = 'content';
                }
            }
            
            // Add to results if matches
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
            // Convert wildcard pattern to regex
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
            // For content, wildcard works like regex
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
?>
