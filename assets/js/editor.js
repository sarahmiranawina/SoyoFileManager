let editor
let originalContent = ""

function initializeEditor(content, language) {
  originalContent = content

  // Configure Monaco Editor
  require.config({
    paths: {
      vs: "https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs",
    },
  })

  require(["vs/editor/editor.main"], (monaco) => {
    // Hide loading indicator
    document.getElementById("loading").style.display = "none"
    document.getElementById("monaco-editor").style.display = "block"

    // Create editor
    editor = monaco.editor.create(document.getElementById("monaco-editor"), {
      value: originalContent,
      language: language,
      theme: "vs-dark",
      automaticLayout: true,
      fontSize: 14,
      fontFamily: 'Consolas, "Courier New", monospace',
      lineNumbers: "on",
      roundedSelection: false,
      scrollBeyondLastLine: false,
      minimap: { enabled: true },
      folding: true,
      wordWrap: "on",
      contextmenu: true,
      selectOnLineNumbers: true,
      lineDecorationsWidth: 10,
      lineNumbersMinChars: 3,
      glyphMargin: true,
      formatOnPaste: true,
      formatOnType: true,
      suggestOnTriggerCharacters: true,
      acceptSuggestionOnEnter: "on",
      tabCompletion: "on",
      wordBasedSuggestions: true,
      parameterHints: { enabled: true },
      quickSuggestions: {
        other: true,
        comments: false,
        strings: false,
      },
    })

    // Update cursor position
    editor.onDidChangeCursorPosition((e) => {
      document.getElementById("cursor-position").textContent =
        `Line ${e.position.lineNumber}, Column ${e.position.column}`
    })

    // Update selection info
    editor.onDidChangeCursorSelection((e) => {
      const selection = editor.getSelection()
      if (selection.isEmpty()) {
        document.getElementById("selection-info").textContent = ""
      } else {
        const selectedText = editor.getModel().getValueInRange(selection)
        const lines = selectedText.split("\n").length
        const chars = selectedText.length
        document.getElementById("selection-info").textContent = `Selected: ${chars} chars, ${lines} lines`
      }
    })

    // Monitor for errors
    monaco.editor.onDidChangeMarkers(([resource]) => {
      const markers = monaco.editor.getModelMarkers({ resource })
      const errors = markers.filter((m) => m.severity === monaco.MarkerSeverity.Error)
      const warnings = markers.filter((m) => m.severity === monaco.MarkerSeverity.Warning)

      let errorText = ""
      if (errors.length > 0) {
        errorText = `<span class="error-indicator">${errors.length} error(s)</span>`
      } else if (warnings.length > 0) {
        errorText = `<span style="color: #f59e0b">${warnings.length} warning(s)</span>`
      } else {
        errorText = '<span class="success-indicator">No errors</span>'
      }

      document.getElementById("error-count").innerHTML = errorText
    })

    // Keyboard shortcuts
    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => {
      saveFile()
    })
  })
}

function saveFile() {
  if (editor) {
    const content = editor.getValue()
    document.getElementById("content-input").value = content
    document.getElementById("save-form").submit()
  }
}

// Auto-save on Ctrl+S
document.addEventListener("keydown", (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === "s") {
    e.preventDefault()
    saveFile()
  }
})

// Warn about unsaved changes
window.addEventListener("beforeunload", (e) => {
  if (editor && editor.getValue() !== originalContent) {
    e.preventDefault()
    e.returnValue = "You have unsaved changes. Are you sure you want to leave?"
    return e.returnValue
  }
})
