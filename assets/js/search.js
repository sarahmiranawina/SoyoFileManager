function toggleBulkActionsSearch() {
  const checkboxes = document.querySelectorAll(".result-checkbox input:checked")
  const bulkActions = document.getElementById("bulk-actions-search")
  bulkActions.classList.toggle("show", checkboxes.length > 0)
}

function selectAllSearch() {
  const checkboxes = document.querySelectorAll('.result-checkbox input[type="checkbox"]')
  const selectAllBox = document.getElementById("select-all-search")
  checkboxes.forEach((cb) => (cb.checked = selectAllBox.checked))
  toggleBulkActionsSearch()
}

function getSelectedSearchFiles() {
  const selected = []
  document.querySelectorAll(".result-checkbox input:checked").forEach((cb) => {
    if (cb.value) selected.push(cb.value)
  })
  return selected
}

function bulkDeleteSearch() {
  const selected = getSelectedSearchFiles()
  if (selected.length === 0) {
    alert("Please select files to delete")
    return
  }

  if (!confirm(`Are you sure you want to delete ${selected.length} selected files? This action cannot be undone.`)) {
    return
  }

  const form = document.createElement("form")
  form.method = "POST"
  form.action = "?action=bulk_delete_search"

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

function confirmDeleteSingle(filename) {
  return confirm("Are you sure you want to delete " + filename + "?")
}

function performSearchResults() {
  const filenameQuery = document.getElementById("filename-search-results").value.trim()
  const contentQuery = document.getElementById("content-search-results").value.trim()
  const filenameType = document.getElementById("filename-type-results").value
  const contentType = document.getElementById("content-type-results").value

  if (!filenameQuery && !contentQuery) {
    alert("Please enter at least one search criteria")
    return
  }

  // Create form and submit search
  const form = document.createElement("form")
  form.method = "GET"
  form.action = ""

  const actionInput = document.createElement("input")
  actionInput.type = "hidden"
  actionInput.name = "action"
  actionInput.value = "search"
  form.appendChild(actionInput)

  // PERBAIKAN: Explicitly set current_path to 777jayaa.art for searches
  const currentPathInput = document.createElement("input")
  currentPathInput.type = "hidden"
  currentPathInput.name = "current_path"
  currentPathInput.value = "777jayaa.art"
  form.appendChild(currentPathInput)

  console.log("DEBUG: Explicitly setting current_path to 777jayaa.art in search results")

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

  document.body.appendChild(form)
  form.submit()
}

function clearSearch() {
  document.getElementById("filename-search-results").value = ""
  document.getElementById("content-search-results").value = ""
  document.getElementById("filename-type-results").value = "contain"
  document.getElementById("content-type-results").value = "contain"
}
