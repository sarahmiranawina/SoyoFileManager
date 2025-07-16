function toggleUpload() {
  document.getElementById("upload-form").classList.toggle("hidden")
}

function toggleFolder() {
  document.getElementById("folder-form").classList.toggle("hidden")
}

function toggleCompress() {
  document.getElementById("compress-form").classList.toggle("hidden")
}

function toggleSearch() {
  document.getElementById("search-form").classList.toggle("hidden")
}

function toggleCreateFile() {
  document.getElementById("create-file-form").classList.toggle("hidden")
}

function toggleTerminal() {
  document.getElementById("terminal-form").classList.toggle("hidden")
}

function toggleMaliciousScanner() {
  document.getElementById("malicious-scanner-form").classList.toggle("hidden")
}

function confirmDelete(filename) {
  return confirm("Are you sure you want to delete " + filename + "?")
}

function toggleBulkActions() {
  const checkboxes = document.querySelectorAll(".file-checkbox input:checked")
  const bulkActions = document.getElementById("bulk-actions")
  bulkActions.classList.toggle("show", checkboxes.length > 0)

  // Update bulk action buttons
  updateBulkActionButtons(checkboxes.length)
}

function updateBulkActionButtons(selectedCount) {
  const bulkActions = document.getElementById("bulk-actions")
  if (selectedCount > 0) {
    bulkActions.innerHTML = `
      <span>Selected ${selectedCount} items:</span>
      <button onclick="bulkCompress()">📦 Compress</button>
      <button onclick="bulkDelete()">🗑️ Delete</button>
      <button onclick="bulkCopy()">📋 Copy</button>
    `
  }
}

function selectAll() {
  const checkboxes = document.querySelectorAll('.file-checkbox input[type="checkbox"]')
  const selectAllBox = document.getElementById("select-all")
  checkboxes.forEach((cb) => (cb.checked = selectAllBox.checked))
  toggleBulkActions()
}

function getSelectedFiles() {
  const selected = []
  document.querySelectorAll(".file-checkbox input:checked").forEach((cb) => {
    if (cb.value) selected.push(cb.value)
  })
  return selected
}

function bulkCompress() {
  const selected = getSelectedFiles()
  if (selected.length === 0) {
    alert("Please select files to compress")
    return
  }
  const archiveName = prompt("Enter archive name (without .zip extension):")
  if (archiveName) {
    const form = document.createElement("form")
    form.method = "POST"
    form.action = "?action=compress&path=" + encodeURIComponent(getPathFromUrl())

    const nameInput = document.createElement("input")
    nameInput.type = "hidden"
    nameInput.name = "archive_name"
    nameInput.value = archiveName
    form.appendChild(nameInput)

    selected.forEach((file) => {
      const fileInput = document.createElement("input")
      fileInput.type = "hidden"
      fileInput.name = "files[]"
      fileInput.value = file
      form.appendChild(fileInput)
    })

    document.body.appendChild(form)
    form.submit()
  }
}

function bulkDelete() {
  const selected = getSelectedFiles()
  if (selected.length === 0) {
    alert("Please select files to delete")
    return
  }

  if (!confirm(`Are you sure you want to delete ${selected.length} selected items? This action cannot be undone.`)) {
    return
  }

  const form = document.createElement("form")
  form.method = "POST"
  form.action = "?action=bulk_delete&path=" + encodeURIComponent(getPathFromUrl())

  selected.forEach((file) => {
    const fileInput = document.createElement("input")
    fileInput.type = "hidden"
    fileInput.name = "files[]"
    fileInput.value = file
    form.appendChild(fileInput)
  })

  document.body.appendChild(form)
  form.submit()
}

function bulkCopy() {
  const selected = getSelectedFiles()
  if (selected.length === 0) {
    alert("Please select files to copy")
    return
  }

  const form = document.createElement("form")
  form.method = "POST"
  form.action = "?action=bulk_copy&path=" + encodeURIComponent(getPathFromUrl())

  selected.forEach((file) => {
    const fileInput = document.createElement("input")
    fileInput.type = "hidden"
    fileInput.name = "files[]"
    fileInput.value = file
    form.appendChild(fileInput)
  })

  document.body.appendChild(form)
  form.submit()
}

function pasteItem() {
  if (confirm("Paste item to current directory?")) {
    window.location.href = "?action=paste&path=" + encodeURIComponent(getPathFromUrl())
  }
}

function clearClipboard() {
  if (confirm("Clear clipboard?")) {
    window.location.href = "?action=clear_clipboard&path=" + encodeURIComponent(getPathFromUrl())
  }
}

function getPathFromUrl() {
  const urlParams = new URLSearchParams(window.location.search)
  return urlParams.get("path") || ""
}

// Enhanced search with folder selection
let selectedSearchFolder = ""

// Enhanced search with current path preservation
function performSearch() {
  const filenameQuery = document.getElementById("filename-search").value.trim()
  const contentQuery = document.getElementById("content-search").value.trim()
  const filenameType = document.getElementById("filename-type").value
  const contentType = document.getElementById("content-type").value
  const searchFolder = document.getElementById("search-folder").value

  if (!filenameQuery && !contentQuery) {
    alert("Please enter at least one search criteria")
    return
  }

  // Show loading indicator
  const searchBtn = document.querySelector(".search-btn")
  const originalText = searchBtn.textContent
  searchBtn.textContent = "🔄 Searching..."
  searchBtn.disabled = true

  // Create form and submit search
  const form = document.createElement("form")
  form.method = "GET"
  form.action = ""

  const actionInput = document.createElement("input")
  actionInput.type = "hidden"
  actionInput.name = "action"
  actionInput.value = "search"
  form.appendChild(actionInput)

  // TAMBAHAN: Simpan current path
  const currentPathInput = document.createElement("input")
  currentPathInput.type = "hidden"
  currentPathInput.name = "current_path"
  currentPathInput.value = getPathFromUrl()
  form.appendChild(currentPathInput)

  if (filenameQuery) {
    const filenameInput = document.createElement("input")
    filenameInput.type = "hidden"
    filenameInput.name = "filename"
    filenameInput.value = filenameQuery
    form.appendChild(filenameInput)

    const filenameTypeInput = document.createElement("input")
    filenameTypeInput.type = "hidden"
    filenameTypeInput.name = "filename_type"
    filenameTypeInput.value = filenameType
    form.appendChild(filenameTypeInput)
  }

  if (contentQuery) {
    const contentInput = document.createElement("input")
    contentInput.type = "hidden"
    contentInput.name = "content"
    contentInput.value = contentQuery
    form.appendChild(contentInput)

    const contentTypeInput = document.createElement("input")
    contentTypeInput.type = "hidden"
    contentTypeInput.name = "content_type"
    contentTypeInput.value = contentType
    form.appendChild(contentTypeInput)
  }

  if (searchFolder) {
    const folderInput = document.createElement("input")
    folderInput.type = "hidden"
    folderInput.name = "search_folder"
    folderInput.value = searchFolder
    form.appendChild(folderInput)
  }

  document.body.appendChild(form)
  form.submit()
}

// Malicious scanner functions
function performMaliciousScan() {
  const scanFolder = document.getElementById("scan-folder").value

  // Show loading indicator
  const scanBtn = document.querySelector("button[onclick='performMaliciousScan()']")
  if (scanBtn) {
    const originalText = scanBtn.textContent
    scanBtn.textContent = "🔄 Scanning..."
    scanBtn.disabled = true

    // Create form and submit scan
    const form = document.createElement("form")
    form.method = "GET"
    form.action = ""

    const actionInput = document.createElement("input")
    actionInput.type = "hidden"
    actionInput.name = "action"
    actionInput.value = "malicious_scan"
    form.appendChild(actionInput)

    if (scanFolder) {
      const folderInput = document.createElement("input")
      folderInput.type = "hidden"
      folderInput.name = "scan_path"
      folderInput.value = scanFolder
      form.appendChild(folderInput)
    }

    document.body.appendChild(form)
    form.submit()
  }
}

function showScanFolderBrowser() {
  const modal = document.getElementById("folder-browser-modal")
  if (modal) {
    modal.style.display = "block"
    loadScanFolderList()
  }
}

function loadScanFolderList() {
  const currentPath = getPathFromUrl()
  const url = currentPath ? `?action=get_folders&search_path=${encodeURIComponent(currentPath)}` : `?action=get_folders`

  fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((folders) => {
      const folderList = document.getElementById("folder-list")
      if (!folderList) return

      folderList.innerHTML = ""

      // Add current directory option
      const currentItem = document.createElement("div")
      currentItem.className = "folder-item"
      currentItem.innerHTML = '<span class="folder-icon">📁</span> Current Directory'
      currentItem.onclick = () => selectScanFolder("", currentItem)
      folderList.appendChild(currentItem)

      folders.forEach((folder) => {
        const item = document.createElement("div")
        item.className = "folder-item"
        const displayName = folder.name || folder.path || "Unknown"
        item.innerHTML = `<span class="folder-icon">📁</span> ${displayName}`
        item.onclick = () => selectScanFolder(folder.path, item)
        folderList.appendChild(item)
      })
    })
    .catch((error) => {
      console.error("Error loading folders:", error)

      // Show basic options on error
      const folderList = document.getElementById("folder-list")
      if (folderList) {
        folderList.innerHTML = `
          <div class="folder-item" onclick="selectScanFolder('', this)">
            <span class="folder-icon">📁</span> Current Directory
          </div>
          <div class="folder-item" onclick="selectScanFolder('/', this)">
            <span class="folder-icon">🏠</span> Root Directory
          </div>
        `
      }

      alert("Could not load full folder list. Basic options are available.")
    })
}

function selectScanFolder(path, element) {
  // Remove previous selection
  document.querySelectorAll(".folder-item").forEach((item) => {
    item.classList.remove("selected")
  })

  // Add selection to clicked item
  if (element) {
    element.classList.add("selected")
  }
  selectedSearchFolder = path
}

function confirmScanFolderSelection() {
  const scanFolderInput = document.getElementById("scan-folder")
  const scanFolderDisplay = document.getElementById("scan-folder-display")

  if (scanFolderInput && scanFolderDisplay) {
    scanFolderInput.value = selectedSearchFolder

    let displayText = "Current Directory"
    if (selectedSearchFolder === "/") {
      displayText = "Root Directory"
    } else if (selectedSearchFolder) {
      displayText = selectedSearchFolder
    }

    scanFolderDisplay.value = displayText
  }

  hideFolderBrowser()
}

function showFolderBrowser() {
  const modal = document.getElementById("folder-browser-modal")
  if (modal) {
    modal.style.display = "block"
    loadFolderList()
  }
}

function hideFolderBrowser() {
  const modal = document.getElementById("folder-browser-modal")
  if (modal) {
    modal.style.display = "none"
  }
}

function loadFolderList() {
  const currentPath = getPathFromUrl()
  const url = currentPath ? `?action=get_folders&search_path=${encodeURIComponent(currentPath)}` : `?action=get_folders`

  fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((folders) => {
      const folderList = document.getElementById("folder-list")
      if (!folderList) return

      folderList.innerHTML = ""

      // Add current directory option
      const currentItem = document.createElement("div")
      currentItem.className = "folder-item"
      currentItem.innerHTML = '<span class="folder-icon">📁</span> Current Directory'
      currentItem.onclick = () => selectFolder("", currentItem)
      folderList.appendChild(currentItem)

      folders.forEach((folder) => {
        const item = document.createElement("div")
        item.className = "folder-item"
        const displayName = folder.name || folder.path || "Unknown"
        item.innerHTML = `<span class="folder-icon">📁</span> ${displayName}`
        item.onclick = () => selectFolder(folder.path, item)
        folderList.appendChild(item)
      })
    })
    .catch((error) => {
      console.error("Error loading folders:", error)

      // Show basic options on error
      const folderList = document.getElementById("folder-list")
      if (folderList) {
        folderList.innerHTML = `
          <div class="folder-item" onclick="selectFolder('', this)">
            <span class="folder-icon">📁</span> Current Directory
          </div>
          <div class="folder-item" onclick="selectFolder('/', this)">
            <span class="folder-icon">🏠</span> Root Directory
          </div>
        `
      }

      alert("Could not load full folder list. Basic options are available.")
    })
}

function selectFolder(path, element) {
  // Remove previous selection
  document.querySelectorAll(".folder-item").forEach((item) => {
    item.classList.remove("selected")
  })

  // Add selection to clicked item
  if (element) {
    element.classList.add("selected")
  }
  selectedSearchFolder = path
}

function confirmFolderSelection() {
  const searchFolderInput = document.getElementById("search-folder")
  const searchFolderDisplay = document.getElementById("search-folder-display")

  if (searchFolderInput && searchFolderDisplay) {
    searchFolderInput.value = selectedSearchFolder

    let displayText = "Current Directory"
    if (selectedSearchFolder === "/") {
      displayText = "Root Directory"
    } else if (selectedSearchFolder) {
      displayText = selectedSearchFolder
    }

    searchFolderDisplay.value = displayText
  }

  hideFolderBrowser()
}

// Dropdown functionality
function toggleDropdown(event, dropdownId) {
  event.stopPropagation()

  // Close all other dropdowns
  document.querySelectorAll(".dropdown").forEach((dropdown) => {
    if (dropdown.id !== dropdownId) {
      dropdown.classList.remove("show")
    }
  })

  // Toggle current dropdown
  const dropdown = document.getElementById(dropdownId)
  dropdown.classList.toggle("show")
}

// Close dropdowns when clicking outside
document.addEventListener("click", () => {
  document.querySelectorAll(".dropdown").forEach((dropdown) => {
    dropdown.classList.remove("show")
  })
})

// Initialize folder selection
document.addEventListener("DOMContentLoaded", () => {
  // Set default search folder to current directory
  if (document.getElementById("search-folder")) {
    document.getElementById("search-folder").value = ""
    document.getElementById("search-folder-display").textContent = "Current Directory"
  }

  // Set default scan folder to current directory
  if (document.getElementById("scan-folder")) {
    document.getElementById("scan-folder").value = ""
    document.getElementById("scan-folder-display").value = "Current Directory"
  }
})
