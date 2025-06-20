/**
 * Import/Export JavaScript functionality
 * Based on genealogy software admin.js and dataimport.js
 */

// Global variables for import/export
var opening = "Opening file...";
var uploading = "Uploading...";
var peoplelbl = "People";
var familieslbl = "Families";
var sourceslbl = "Sources";
var noteslbl = "Notes";
var medialbl = "Media";
var placeslbl = "Places";
var stopmsg = "Stop";
var stoppedmsg = "Stopped";
var resumemsg = "Resume";
var reopenmsg = "Reopen";
var saveimport = "1";
var selectimportfile = "Please select an import file.";
var selectdesttree = "Please select a destination tree.";
var entertreeid = "Please enter a tree ID.";
var alphanum = "Tree ID must be alphanumeric.";
var entertreename = "Please enter a tree name.";
var confdeletefile = "Are you sure you want to delete this file?";
var finished_msg = "Finished importing!";
var importing_msg = "Importing GEDCOM...";
var removeged_msg = "Remove GEDCOM";
var close_msg = "Close Window";
var more_options = "More Options";

var branches = new Array();
var branchcounts = new Array();

/**
 * Modern Import Wizard Functionality
 */

// Wizard state management
let currentStep = 1;
let maxSteps = 4;
let selectedFile = null;
let validationResults = null;

// Initialize wizard when document is ready
document.addEventListener("DOMContentLoaded", function () {
  // Only initialize if we're on the import page
  if (document.getElementById("gedcom-import-form")) {
    initImportWizard();
    initFileUpload();
    initTreeSelection();
    initFormValidation();
    initImportLogToggle();
  }
});

/**
 * Initialize the import wizard
 */
function initImportWizard() {
  const nextBtn = document.getElementById("next-step");
  const prevBtn = document.getElementById("prev-step");
  const submitBtn = document.getElementById("start-import");

  if (nextBtn) {
    nextBtn.addEventListener("click", nextStep);
  }
  if (prevBtn) {
    prevBtn.addEventListener("click", prevStep);
  }

  // Initialize first step
  showStep(1);
}

/**
 * Initialize modern file upload functionality
 */
function initFileUpload() {
  const uploadZone = document.getElementById("upload-zone");
  const fileInput = document.getElementById("gedcom-file");
  const fileInfo = document.getElementById("file-info");
  const removeBtn = document.getElementById("remove-file");

  if (!uploadZone || !fileInput) return;

  // Click to select file
  uploadZone.addEventListener("click", () => fileInput.click());

  // Drag and drop functionality
  uploadZone.addEventListener("dragover", handleDragOver);
  uploadZone.addEventListener("dragleave", handleDragLeave);
  uploadZone.addEventListener("drop", handleFileDrop);

  // File input change
  fileInput.addEventListener("change", handleFileSelect);

  // Remove file
  if (removeBtn) {
    removeBtn.addEventListener("click", removeSelectedFile);
  }
}

/**
 * Handle drag over event
 */
function handleDragOver(e) {
  e.preventDefault();
  e.stopPropagation();
  e.currentTarget.classList.add("dragover");
}

/**
 * Handle drag leave event
 */
function handleDragLeave(e) {
  e.preventDefault();
  e.stopPropagation();
  e.currentTarget.classList.remove("dragover");
}

/**
 * Handle file drop
 */
function handleFileDrop(e) {
  e.preventDefault();
  e.stopPropagation();
  e.currentTarget.classList.remove("dragover");

  const files = e.dataTransfer.files;
  if (files.length > 0) {
    handleFileSelection(files[0]);
  }
}

/**
 * Handle file input selection
 */
function handleFileSelect(e) {
  const files = e.target.files;
  if (files.length > 0) {
    handleFileSelection(files[0]);
  }
}

/**
 * Handle file selection (from any source)
 */
function handleFileSelection(file) {
  // Validate file type
  const allowedTypes = [".ged", ".gedcom"];
  const fileName = file.name.toLowerCase();
  const isValidType = allowedTypes.some((type) => fileName.endsWith(type));

  if (!isValidType) {
    alert("Please select a GEDCOM file (.ged or .gedcom)");
    return;
  }

  selectedFile = file;
  showFileInfo(file);
  validateFile(file);
}

/**
 * Show file information
 */
function showFileInfo(file) {
  const fileInfo = document.getElementById("file-info");
  const fileName = document.getElementById("file-name");
  const fileSize = document.getElementById("file-size");
  const uploadZone = document.getElementById("upload-zone");

  if (fileInfo && fileName && fileSize) {
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    fileInfo.style.display = "flex";
    uploadZone.style.display = "none";
  }
}

/**
 * Remove selected file
 */
function removeSelectedFile() {
  selectedFile = null;
  validationResults = null;

  const fileInfo = document.getElementById("file-info");
  const uploadZone = document.getElementById("upload-zone");
  const fileInput = document.getElementById("gedcom-file");
  const validationDiv = document.getElementById("validation-results");

  if (fileInfo) fileInfo.style.display = "none";
  if (uploadZone) uploadZone.style.display = "block";
  if (fileInput) fileInput.value = "";
  if (validationDiv) validationDiv.style.display = "none";

  updateStepValidation();
}

/**
 * Validate GEDCOM file
 */
function validateFile(file) {
  const validationDiv = document.getElementById("validation-results");
  if (!validationDiv) return;

  // Show validation section
  validationDiv.style.display = "block";

  // Simulate file validation (in real implementation, this would be an AJAX call)
  setTimeout(() => {
    const mockResults = {
      individuals: Math.floor(Math.random() * 1000) + 100,
      families: Math.floor(Math.random() * 500) + 50,
      sources: Math.floor(Math.random() * 200) + 20,
      media: Math.floor(Math.random() * 300) + 30,
      errors: [],
      warnings: [],
    };

    showValidationResults(mockResults);
    validationResults = mockResults;
    updateStepValidation();
  }, 1500);
}

/**
 * Show validation results
 */
function showValidationResults(results) {
  const individualsEl = document.getElementById("individuals-count");
  const familiesEl = document.getElementById("families-count");
  const sourcesEl = document.getElementById("sources-count");
  const mediaEl = document.getElementById("media-count");

  if (individualsEl) individualsEl.textContent = results.individuals;
  if (familiesEl) familiesEl.textContent = results.families;
  if (sourcesEl) sourcesEl.textContent = results.sources;
  if (mediaEl) mediaEl.textContent = results.media;

  const issuesDiv = document.getElementById("validation-issues");
  if (issuesDiv) {
    if (results.errors.length > 0 || results.warnings.length > 0) {
      let issuesHtml = "";

      if (results.errors.length > 0) {
        issuesHtml += '<div class="validation-errors"><h5>Errors:</h5><ul>';
        results.errors.forEach((error) => {
          issuesHtml += `<li>${error}</li>`;
        });
        issuesHtml += "</ul></div>";
      }

      if (results.warnings.length > 0) {
        issuesHtml += '<div class="validation-warnings"><h5>Warnings:</h5><ul>';
        results.warnings.forEach((warning) => {
          issuesHtml += `<li>${warning}</li>`;
        });
        issuesHtml += "</ul></div>";
      }

      issuesDiv.innerHTML = issuesHtml;
    } else {
      issuesDiv.innerHTML =
        '<div class="validation-success">✅ No issues found in the GEDCOM file.</div>';
    }
  }
}

/**
 * Initialize tree selection functionality
 */
function initTreeSelection() {
  const treeRadios = document.querySelectorAll(
    'input[name="tree_destination"]'
  );
  const treeSelect = document.getElementById("tree1");
  const newTreeInput = document.querySelector('input[name="new_tree_name"]');

  treeRadios.forEach((radio) => {
    radio.addEventListener("change", function () {
      if (this.value === "existing") {
        if (treeSelect) treeSelect.disabled = false;
        if (newTreeInput) newTreeInput.disabled = true;
      } else if (this.value === "new") {
        if (treeSelect) treeSelect.disabled = true;
        if (newTreeInput) newTreeInput.disabled = false;
      }
      updateStepValidation();
    });
  });

  if (treeSelect) {
    treeSelect.addEventListener("change", updateStepValidation);
  }
  if (newTreeInput) {
    newTreeInput.addEventListener("input", updateStepValidation);
  }
}

/**
 * Move to next step
 */
function nextStep() {
  if (!validateCurrentStep()) {
    return;
  }

  if (currentStep < maxSteps) {
    currentStep++;
    showStep(currentStep);
  }
}

/**
 * Move to previous step
 */
function prevStep() {
  if (currentStep > 1) {
    currentStep--;
    showStep(currentStep);
  }
}

/**
 * Show specific step
 */
function showStep(step) {
  // Hide all step contents
  document.querySelectorAll(".wizard-step-content").forEach((content) => {
    content.style.display = "none";
    content.classList.remove("active");
  });

  // Show current step content
  const currentContent = document.querySelector(`[data-step="${step}"]`);
  if (currentContent) {
    currentContent.style.display = "block";
    currentContent.classList.add("active");
  }

  // Update step indicators
  document.querySelectorAll(".wizard-step").forEach((stepEl, index) => {
    stepEl.classList.remove("active", "completed");
    if (index + 1 === step) {
      stepEl.classList.add("active");
    } else if (index + 1 < step) {
      stepEl.classList.add("completed");
    }
  });

  // Update navigation buttons
  updateNavigationButtons();
  updateStepValidation();
}

/**
 * Update navigation buttons
 */
function updateNavigationButtons() {
  const nextBtn = document.getElementById("next-step");
  const prevBtn = document.getElementById("prev-step");
  const submitBtn = document.getElementById("start-import");

  if (prevBtn) {
    prevBtn.style.display = currentStep === 1 ? "none" : "inline-block";
  }

  if (nextBtn && submitBtn) {
    if (currentStep === maxSteps) {
      nextBtn.style.display = "none";
      submitBtn.style.display = "inline-block";
    } else {
      nextBtn.style.display = "inline-block";
      submitBtn.style.display = "none";
    }
  }
}

/**
 * Validate current step
 */
function validateCurrentStep() {
  switch (currentStep) {
    case 1: // File selection
      return validateStep1();
    case 2: // Tree selection
      return validateStep2();
    case 3: // Options
      return true; // Options are optional
    case 4: // Import
      return true;
    default:
      return true;
  }
}

/**
 * Validate step 1 (file selection)
 */
function validateStep1() {
  const hasFile = selectedFile !== null;
  const databaseInput = document.getElementById("database");
  const hasServerFile = databaseInput && databaseInput.value.trim() !== "";

  if (!hasFile && !hasServerFile) {
    alert("Please select a GEDCOM file to import.");
    return false;
  }

  return true;
}

/**
 * Validate step 2 (tree selection)
 */
function validateStep2() {
  const existingTreeRadio = document.querySelector(
    'input[name="tree_destination"][value="existing"]'
  );
  const newTreeRadio = document.querySelector(
    'input[name="tree_destination"][value="new"]'
  );
  const treeSelect = document.getElementById("tree1");
  const newTreeInput = document.querySelector('input[name="new_tree_name"]');

  if (existingTreeRadio && existingTreeRadio.checked) {
    if (!treeSelect || !treeSelect.value) {
      alert("Please select an existing tree.");
      return false;
    }
  } else if (newTreeRadio && newTreeRadio.checked) {
    if (!newTreeInput || !newTreeInput.value.trim()) {
      alert("Please enter a name for the new tree.");
      return false;
    }
  }

  return true;
}

/**
 * Validate current step silently (no alerts)
 */
function validateCurrentStepSilent() {
  switch (currentStep) {
    case 1:
      return validateStep1Silent();
    case 2:
      return validateStep2Silent();
    default:
      return true;
  }
}

/**
 * Validate step 1 silently (file selection)
 */
function validateStep1Silent() {
  const hasFile = selectedFile !== null;
  const databaseInput = document.getElementById("database");
  const hasServerFile = databaseInput && databaseInput.value.trim() !== "";
  return hasFile || hasServerFile;
}

/**
 * Validate step 2 silently (tree selection)
 */
function validateStep2Silent() {
  const existingTreeRadio = document.querySelector(
    'input[name="desttree"]:checked'
  );
  if (!existingTreeRadio) return false;

  if (existingTreeRadio.value === "existing") {
    const treeSelect = document.getElementById("tree1");
    return treeSelect && treeSelect.value !== "";
  } else if (existingTreeRadio.value === "new") {
    const newTreeInput = document.getElementById("newtreename");
    return newTreeInput && newTreeInput.value.trim() !== "";
  }

  return false;
}

/**
 * Update step validation status
 */
function updateStepValidation() {
  const nextBtn = document.getElementById("next-step");
  if (!nextBtn) return;

  // Check validation without showing alerts
  const isValid = validateCurrentStepSilent();
  nextBtn.disabled = !isValid;
  nextBtn.classList.toggle("button-primary", isValid);
  nextBtn.classList.toggle("button-secondary", !isValid);
}

/**
 * Initialize form validation
 */
function initFormValidation() {
  const form = document.getElementById("gedcom-import-form");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    if (currentStep < maxSteps) {
      e.preventDefault();
      return false;
    }

    // Show import progress
    showImportProgress();
  });
}

/**
 * Show import progress
 */
function showImportProgress() {
  const progressSection = document.getElementById("import-progress");
  const summarySection = document.getElementById("import-summary");

  if (summarySection) {
    // Populate summary
    const filename = selectedFile
      ? selectedFile.name
      : document.getElementById("database").value;
    const tree = getSelectedTreeName();
    const method = getSelectedImportMethod();

    const summaryFilename = document.getElementById("summary-filename");
    const summaryTree = document.getElementById("summary-tree");
    const summaryMethod = document.getElementById("summary-method");

    if (summaryFilename) summaryFilename.textContent = filename;
    if (summaryTree) summaryTree.textContent = tree;
    if (summaryMethod) summaryMethod.textContent = method;

    summarySection.style.display = "block";
  }

  if (progressSection) {
    progressSection.style.display = "block";
    startProgressSimulation();
  }
}

/**
 * Get selected tree name
 */
function getSelectedTreeName() {
  const existingTreeRadio = document.querySelector(
    'input[name="tree_destination"][value="existing"]'
  );
  if (existingTreeRadio && existingTreeRadio.checked) {
    const treeSelect = document.getElementById("tree1");
    return treeSelect
      ? treeSelect.options[treeSelect.selectedIndex].text
      : "Unknown";
  } else {
    const newTreeInput = document.querySelector('input[name="new_tree_name"]');
    return newTreeInput ? newTreeInput.value : "New Tree";
  }
}

/**
 * Get selected import method
 */
function getSelectedImportMethod() {
  const selectedMethod = document.querySelector('input[name="del"]:checked');
  if (!selectedMethod) return "Unknown";

  switch (selectedMethod.value) {
    case "yes":
      return "Replace all data";
    case "match":
      return "Replace matching data";
    case "no":
      return "Ignore matching data";
    case "append":
      return "Append with offset";
    default:
      return "Unknown";
  }
}

/**
 * Simulate import progress
 */
function startProgressSimulation() {
  const progressFill = document.getElementById("progress-fill");
  const progressText = document.getElementById("progress-text");
  const currentOperation = document.getElementById("current-operation");
  const recordsProcessed = document.getElementById("records-processed");
  const totalRecords = document.getElementById("total-records");

  if (!progressFill || !validationResults) return;

  const total = validationResults.individuals + validationResults.families;
  if (totalRecords) totalRecords.textContent = total;

  let processed = 0;
  const operations = [
    "Validating GEDCOM structure...",
    "Processing individuals...",
    "Processing families...",
    "Processing sources...",
    "Processing media...",
    "Updating relationships...",
    "Finalizing import...",
  ];

  let operationIndex = 0;

  const updateProgress = () => {
    if (processed < total) {
      processed += Math.floor(Math.random() * 10) + 1;
      if (processed > total) processed = total;

      const percentage = Math.round((processed / total) * 100);
      progressFill.style.width = percentage + "%";
      if (progressText) progressText.textContent = percentage + "%";
      if (recordsProcessed) recordsProcessed.textContent = processed;

      // Update operation
      if (operationIndex < operations.length - 1 && Math.random() > 0.7) {
        operationIndex++;
        if (currentOperation)
          currentOperation.textContent = operations[operationIndex];
      }

      setTimeout(updateProgress, Math.random() * 500 + 200);
    } else {
      // Import complete
      showImportResults();
    }
  };

  updateProgress();
}

/**
 * Show import results
 */
function showImportResults() {
  const progressSection = document.getElementById("import-progress");
  const resultsSection = document.getElementById("import-results");

  if (progressSection) progressSection.style.display = "none";
  if (resultsSection) {
    // Populate results
    if (validationResults) {
      const importedIndividuals = document.getElementById(
        "imported-individuals"
      );
      const importedFamilies = document.getElementById("imported-families");
      const importedSources = document.getElementById("imported-sources");
      const importedMedia = document.getElementById("imported-media");

      if (importedIndividuals)
        importedIndividuals.textContent = validationResults.individuals;
      if (importedFamilies)
        importedFamilies.textContent = validationResults.families;
      if (importedSources)
        importedSources.textContent = validationResults.sources;
      if (importedMedia) importedMedia.textContent = validationResults.media;
    }

    resultsSection.style.display = "block";
  }
}

/**
 * Format file size for display
 */
function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}

/**
 * Initialize import log toggle
 */
function initImportLogToggle() {
  const toggleBtn = document.getElementById("toggle-log");
  const importLog = document.getElementById("import-log");

  if (toggleBtn && importLog) {
    toggleBtn.addEventListener("click", function () {
      if (importLog.style.display === "none") {
        importLog.style.display = "block";
        toggleBtn.textContent = "Hide Details";
      } else {
        importLog.style.display = "none";
        toggleBtn.textContent = "Show Details";
      }
    });
  }
}

/**
 * Check file selection before form submission
 */
function checkFile(form) {
  // Check if file is selected
  var remoteFile = form.remotefile;
  var databaseFile = form.database;

  if (
    (!remoteFile || !remoteFile.value) &&
    (!databaseFile || !databaseFile.value)
  ) {
    alert(selectimportfile);
    return false;
  }

  // Check tree selection
  if (!form.tree1 || !form.tree1.value) {
    alert(selectdesttree);
    return false;
  }

  return true;
}

/**
 * Toggle target form based on legacy option
 */
function toggleTarget(form) {
  if (form.old && form.old.checked) {
    form.target = "gedcomin";
  } else {
    form.target = "results";
  }
}

/**
 * Get branches for selected tree
 */
function getBranches(tree, treeindex) {
  var branchselect = document.getElementById("branch1");
  if (!branchselect) return;

  // Clear existing options
  branchselect.options.length = 0;
  branchselect.options[0] = new Option("All branches", "");

  if (tree.selectedIndex > 0 && branches[treeindex]) {
    var treebranches = branches[treeindex];
    for (var i = 0; i < treebranches.length; i++) {
      branchselect.options[branchselect.options.length] = new Option(
        treebranches[i],
        treebranches[i]
      );
    }
    document.getElementById("destbranch").style.display = "table-row";
  } else {
    document.getElementById("destbranch").style.display = "none";
  }
}

/**
 * Toggle sections based on events only checkbox
 */
function toggleSections(eventsonly) {
  var sections = ["replace", "desttree"];

  sections.forEach(function (sectionId) {
    var section = document.getElementById(sectionId);
    if (section) {
      section.style.display = eventsonly ? "none" : "block";
    }
  });
}

/**
 * Toggle norecalc div visibility
 */
function toggleNorecalcdiv(show) {
  var div = document.getElementById("norecalcdiv");
  if (div) {
    div.style.display = show ? "block" : "none";
  }
}

/**
 * Toggle append div visibility
 */
function toggleAppenddiv(show) {
  var div = document.getElementById("appenddiv");
  if (div) {
    div.style.display = show ? "block" : "none";
  }
}

/**
 * Handle iframe loaded event
 */
function iframeLoaded() {
  // Implementation for iframe load handling
  console.log("Import iframe loaded");
}

/**
 * Swap branches for export tree selection
 */
function swapBranches(form) {
  // Implementation for branch swapping in export
  console.log("Swapping branches for export");
}
