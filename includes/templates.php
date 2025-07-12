<?php

function showLoginForm() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Manager - Login</title>
        <link rel="stylesheet" href="assets/css/login.css">
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
    
    // Get sorting parameters
    $sortBy = $_GET['sort'] ?? 'name';
    $sortOrder = $_GET['order'] ?? 'asc';
    
    // Sort files
    $files = sortFiles($files, $currentPath, $sortBy, $sortOrder);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Manager</title>
        <link rel="stylesheet" href="assets/css/main.css">
        <link rel="stylesheet" href="assets/css/forms.css">
        <link rel="stylesheet" href="assets/css/file-list.css">
        <link rel="stylesheet" href="assets/css/dropdown.css">
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
            <button type="button" onclick="bulkCompress()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none  style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 500;">📦 Compress Selected Files</button>
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
            // Enhanced parent directory navigation
            $parentPath = dirname($currentPath);
            if ($parentPath !== $currentPath && $parentPath !== '.' && $parentPath !== $currentPath): 
                // For absolute paths, use the parent path directly
                if (strpos($currentPath, '/') === 0) {
                    $relativeParentPath = $parentPath;
                } else {
                    // Calculate relative path for parent
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
                
                // Get file information
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
        
        <script src="assets/js/main.js"></script>
    </body>
    </html>
    <?php
}

// Rest of the template functions remain the same...
function showEditor($filePath) {
    $content = file_get_contents($filePath);
    $fileName = basename($filePath);
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    // Determine language for syntax highlighting
    $language = getLanguageFromExtension($extension);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit: <?php echo htmlspecialchars($fileName); ?></title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
        <link rel="stylesheet" href="assets/css/editor.css">
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

        <script src="assets/js/editor.js"></script>
        <script>
            // Initialize editor when page loads
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
    
    // Perform search with error handling
    try {
        $searchResults = performFileSearch($config['root_path'], $filenameQuery, $contentQuery, $filenameType, $contentType);
    } catch (Exception $e) {
        // If search fails, show debug info
        showSearchError($e->getMessage(), $filenameQuery, $contentQuery);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Search Results - Soyo File Manager</title>
        <link rel="stylesheet" href="assets/css/search-results.css">
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
        
        <script src="assets/js/search.js"></script>
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

function showFileCreator($targetDirectory) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create File - Soyo File Manager</title>
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
            textarea {
                width: 100%;
                height: 300px;
                margin-bottom: 10px;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-family: monospace;
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
        <h1>Create New File</h1>
        <form method="post">
            <label>File Name:</label><br>
            <input type="text" name="file_name" required><br><br>
            
            <label>File Content (optional):</label><br>
            <textarea name="file_content"></textarea><br>
            
            <input type="submit" value="Create File">
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
?>
