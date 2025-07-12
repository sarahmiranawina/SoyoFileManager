<?php

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
        // Copy directory recursively
        if (recurseCopy($sourcePath, $destinationPath)) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($_GET['path']));
        } else {
            echo "Error copying directory.";
        }
    } else {
        // Copy file
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

// New bulk operations
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
?>
