function toggleUpload() {
  document.getElementById("upload-form").classList.toggle("hidden")
}

function toggleFolder() {
  document.getElementById("folder-form").classList.toggle("hidden")
}

function toggleCompress() {
  document.getElementById("compress-form").classList.toggle("hidden")
}

function toggleCreateFile() {
  document.getElementById("create-file-form").classList.toggle("hidden")
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

// Search functionality
function toggleSearch() {
  const searchForm = document.getElementById("search-form")
  const isVisible = searchForm.classList.contains("show")

  if (isVisible) {
    searchForm.classList.remove("show")
  } else {
    searchForm.classList.add("show")
    // Focus on search input when opened
    const searchInput = document.getElementById("search-query")
    if (searchInput) {
      setTimeout(() => searchInput.focus(), 100)
    }
  }
}

function performSearch() {
  const form = document.getElementById("search-form-element")
  const searchQuery = document.getElementById("search-query").value.trim()

  if (searchQuery.length < 2) {
    alert("Please enter at least 2 characters to search")
    return
  }

  form.submit()
}

function clearSearch() {
  // Clear search and return to normal view
  const currentPath = getPathFromUrl()
  window.location.href = "?path=" + encodeURIComponent(currentPath)
}

function highlightSearchTerm(text, searchTerm) {
  if (!searchTerm || searchTerm.length < 2) return text

  const regex = new RegExp(`(${escapeRegex(searchTerm)})`, "gi")
  return text.replace(regex, '<span class="search-highlight">$1</span>')
}

function escapeRegex(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
}

function getSearchTermFromUrl() {
  const urlParams = new URLSearchParams(window.location.search)
  return urlParams.get("search") || ""
}

// Initialize search form with current values
document.addEventListener("DOMContentLoaded", () => {
  const searchTerm = getSearchTermFromUrl()
  if (searchTerm) {
    const searchInput = document.getElementById("search-query")
    if (searchInput) {
      searchInput.value = searchTerm
      document.getElementById("search-form").classList.add("show")

      // Show search results info if exists
      const searchResultsInfo = document.getElementById("search-results-info")
      if (searchResultsInfo) {
        searchResultsInfo.classList.add("show")
      }
    }
  }
})
