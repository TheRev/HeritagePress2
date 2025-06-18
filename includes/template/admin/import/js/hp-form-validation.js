/**
 * Form Validation and Import Controls
 * HeritagePress Plugin - Form Validation Module
 */

/**
 * Check file selection before form submission
 * @param {HTMLFormElement} form - Form element to validate
 * @returns {boolean} - True if validation passes
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

  // Check if tree is selected (unless creating new)
  var tree = form.tree1;
  if (tree && !tree.value) {
    alert(selectdesttree);
    return false;
  }

  return true;
}

/**
 * Validate import form
 * @returns {boolean} - True if form is valid
 */
function validateForm() {
  const uploadMethod = jQuery("#selected-upload-method").val();
  const uploadedFile = jQuery("#uploaded-file-path").val();

  if (!uploadMethod || !uploadedFile) {
    alert(
      "Please select a GEDCOM file using either 'From Computer' or 'From Server' option."
    );
    return false;
  }

  // Additional validation can be added here
  return true;
}

/**
 * Toggle import/export sections
 * @param {boolean} eventsOnly - Whether to show events only
 */
function toggleSections(eventsOnly) {
  var sections = ["desttree", "replace", "ioptions"];
  for (var i = 0; i < sections.length; i++) {
    var element = document.getElementById(sections[i]);
    if (element) {
      element.style.display = eventsOnly ? "none" : "";
    }
  }
}

/**
 * Toggle no recalculation options
 * @param {boolean} show - Whether to show the options
 */
function toggleNorecalcdiv(show) {
  var div = document.getElementById("norecalcdiv");
  if (div) {
    div.style.display = show ? "" : "none";
  }
}

/**
 * Toggle append options
 * @param {boolean} show - Whether to show the options
 */
function toggleAppenddiv(show) {
  var div = document.getElementById("appenddiv");
  if (div) {
    div.style.display = show ? "" : "none";
  }
}

/**
 * Toggle form target for legacy import
 * @param {HTMLFormElement} form - Form element
 */
function toggleTarget(form) {
  if (form.old && form.old.checked) {
    form.target = "results";
    var iframe = document.getElementById("results");
    if (iframe) {
      iframe.style.display = "block";
      iframe.height = "300";
      iframe.width = "100%";
    }
  } else {
    form.target = "";
    var iframe = document.getElementById("results");
    if (iframe) {
      iframe.style.display = "none";
    }
  }
}

/**
 * Get branches for selected tree
 * @param {HTMLSelectElement} selectElement - Tree select element
 * @param {number} selectedIndex - Selected index
 */
function getBranches(selectElement, selectedIndex) {
  var tree = selectElement.value;
  var branchSelect =
    document.getElementById("branch1") || document.getElementById("branch");

  if (branchSelect) {
    // Clear existing options
    branchSelect.innerHTML = '<option value="">All branches</option>';

    // Show/hide branch selection
    var branchRow = document.getElementById("destbranch");
    if (branchRow) {
      branchRow.style.display = tree ? "" : "none";
    }

    // Load branches via AJAX if tree is selected
    if (tree && typeof jQuery !== "undefined") {
      jQuery.post(
        ajaxurl,
        {
          action: "hp_get_branches",
          tree: tree,
          nonce: hp_admin.nonce,
        },
        function (response) {
          if (response.success && response.data) {
            var options = '<option value="">All branches</option>';
            for (var i = 0; i < response.data.length; i++) {
              var branch = response.data[i];
              options +=
                '<option value="' +
                branch.branch +
                '">' +
                branch.description +
                "</option>";
            }
            branchSelect.innerHTML = options;
          }
        }
      );
    }
  }
}

/**
 * Swap branches for export tree selection
 * @param {HTMLFormElement} form - Form element
 */
function swapBranches(form) {
  if (form.tree) {
    getBranches(form.tree, form.tree.selectedIndex);
  }
}

/**
 * Toggle media export options
 */
function toggleStuff() {
  var exportMedia = document.getElementById("exportmedia");
  var exportMediaFiles = document.getElementById("exportmediafiles");
  var expRows = document.getElementById("exprows");

  if (exportMedia && exportMediaFiles && expRows) {
    if (exportMedia.checked) {
      exportMediaFiles.disabled = false;
      expRows.style.display = "block";
    } else {
      exportMediaFiles.disabled = true;
      exportMediaFiles.checked = false;
      expRows.style.display = "none";
    }
  }
}

/**
 * Initialize form validation and controls
 */
function initializeFormValidation() {
  // Initialize form validation
  jQuery("#gedcom-import-form").on("submit", function (e) {
    if (!checkFile(this)) {
      e.preventDefault();
      return false;
    }
  });

  // Initialize branch loading for single tree
  var treeSelect = jQuery("#tree1");
  if (treeSelect.length && treeSelect.find("option").length === 2) {
    // Auto-select if only one tree
    treeSelect.prop("selectedIndex", 1);
    getBranches(treeSelect[0], 1);
  }

  // Initialize export tree selection
  var exportTreeSelect = jQuery("#treeselect");
  if (exportTreeSelect.length && exportTreeSelect.find("option").length === 1) {
    getBranches(exportTreeSelect[0], 0);
  }

  // Post-import utility links
  jQuery(".secutil").on("click", function (e) {
    e.preventDefault();
    var utility = jQuery(this).text();
    runPostImportUtility(utility);
  });

  // Initialize toggle states
  var defimpopt = 0; // Default import option
  toggleNorecalcdiv(defimpopt);
  toggleAppenddiv(defimpopt == 3);

  // Enhanced file input handling
  jQuery("#gedcom-file-input").on("change", function () {
    const files = this.files;
    if (files.length > 0) {
      handleFileSelection(files[0]);
    }
  });

  // Remove file button
  jQuery(document).on("click", "#remove-file", function () {
    clearSelectedFile();
  });

  // Cancel upload button
  jQuery(document).on("click", "#cancel-upload", function () {
    if (currentUploader) {
      currentUploader.cancel();
      currentUploader = null;
    }
    hideUploadProgress();
  });

  // Form validation enhancement
  jQuery("#gedcom-import-form").on("submit", function (e) {
    const uploadMethod = jQuery("#selected-upload-method").val();
    const uploadedFile = jQuery("#uploaded-file-path").val();

    if (!uploadMethod || !uploadedFile) {
      e.preventDefault();
      alert(
        "Please select a GEDCOM file using either 'From Computer' or 'From Server' option."
      );
      return false;
    }

    // Existing validation
    return validateForm();
  });
}

/**
 * Hide upload progress interface
 */
function hideUploadProgress() {
  jQuery("#upload-progress").hide();
}

/**
 * Show upload progress interface
 */
function showUploadProgress() {
  jQuery("#upload-progress").show();
}

/**
 * Update upload progress
 * @param {number} percent - Progress percentage
 * @param {Object} stats - Upload statistics
 */
function updateUploadProgress(percent, stats) {
  jQuery("#upload-progress-bar").css("width", percent + "%");
  jQuery("#upload-progress-text").text(Math.round(percent) + "%");

  if (stats) {
    if (stats.speed) {
      jQuery("#upload-speed").text(formatSpeed(stats.speed));
    }
    if (stats.remaining) {
      jQuery("#upload-remaining").text(formatTimeRemaining(stats.remaining));
    }
  }
}

// Export functions for use in other modules
window.checkFile = checkFile;
window.validateForm = validateForm;
window.toggleSections = toggleSections;
window.toggleNorecalcdiv = toggleNorecalcdiv;
window.toggleAppenddiv = toggleAppenddiv;
window.toggleTarget = toggleTarget;
window.getBranches = getBranches;
window.swapBranches = swapBranches;
window.toggleStuff = toggleStuff;
window.initializeFormValidation = initializeFormValidation;
window.hideUploadProgress = hideUploadProgress;
window.showUploadProgress = showUploadProgress;
window.updateUploadProgress = updateUploadProgress;
