/**
 * Import/Export JavaScript functionality
 * Based on TNG admin.js and dataimport.js
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
var currentUploader = null; // Global variable for current upload instance

/**
 * Chunked GEDCOM Uploader Class
 */
class ChunkedGedcomUploader {
  constructor(options = {}) {
    this.file = null;
    this.chunkSize = options.chunkSize || 2 * 1024 * 1024; // 2MB chunks
    this.maxRetries = options.maxRetries || 3;
    this.progressCallback = options.onProgress || function () {};
    this.completeCallback = options.onComplete || function () {};
    this.errorCallback = options.onError || function () {};
    this.speedCallback = options.onSpeed || function () {};

    this.totalChunks = 0;
    this.currentChunk = 0;
    this.uploadId = "";
    this.startTime = 0;
    this.uploadedBytes = 0;
    this.cancelled = false;
    this.retryCount = 0;
  }

  upload(file) {
    this.file = file;
    this.totalChunks = Math.ceil(file.size / this.chunkSize);
    this.currentChunk = 0;
    this.uploadId = this.generateUploadId();
    this.startTime = Date.now();
    this.uploadedBytes = 0;
    this.cancelled = false;
    this.retryCount = 0;

    this.uploadNextChunk();
  }

  cancel() {
    this.cancelled = true;

    // Send cancel request to server
    jQuery.ajax({
      url: hp_ajax.ajax_url,
      type: "POST",
      data: {
        action: "hp_cancel_upload",
        nonce: hp_ajax.nonce,
        upload_id: this.uploadId,
      },
    });
  }

  uploadNextChunk() {
    if (this.cancelled) {
      this.errorCallback("Upload cancelled");
      return;
    }

    if (this.currentChunk >= this.totalChunks) {
      this.finalizeUpload();
      return;
    }

    const start = this.currentChunk * this.chunkSize;
    const end = Math.min(start + this.chunkSize, this.file.size);
    const chunk = this.file.slice(start, end);

    const formData = new FormData();
    formData.append("action", "hp_upload_gedcom_chunk");
    formData.append("nonce", hp_ajax.nonce);
    formData.append("chunk", chunk);
    formData.append("chunk_number", this.currentChunk);
    formData.append("total_chunks", this.totalChunks);
    formData.append("upload_id", this.uploadId);
    formData.append("filename", this.file.name);

    const chunkStartTime = Date.now();

    jQuery.ajax({
      url: hp_ajax.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      timeout: 30000, // 30 second timeout
      success: (response) => {
        if (this.cancelled) return;

        if (response.success) {
          this.currentChunk++;
          this.uploadedBytes += chunk.size;
          this.retryCount = 0;

          // Calculate progress and speed
          const progress = (this.currentChunk / this.totalChunks) * 100;
          const elapsed = (Date.now() - this.startTime) / 1000;
          const speed = this.uploadedBytes / elapsed; // bytes per second
          const remaining = (this.file.size - this.uploadedBytes) / speed;

          this.progressCallback(progress);
          this.speedCallback({
            speed: speed,
            remaining: remaining,
            uploaded: this.uploadedBytes,
            total: this.file.size,
          });

          this.uploadNextChunk();
        } else {
          this.handleUploadError(response.data || "Upload failed");
        }
      },
      error: (xhr, status, error) => {
        if (this.cancelled) return;
        this.handleUploadError(`Upload error: ${error}`);
      },
    });
  }

  handleUploadError(error) {
    this.retryCount++;

    if (this.retryCount <= this.maxRetries) {
      // Retry after a delay
      setTimeout(() => {
        this.uploadNextChunk();
      }, 1000 * this.retryCount);
    } else {
      this.errorCallback(error);
    }
  }

  finalizeUpload() {
    jQuery.ajax({
      url: hp_ajax.ajax_url,
      type: "POST",
      data: {
        action: "hp_finalize_gedcom_upload",
        nonce: hp_ajax.nonce,
        upload_id: this.uploadId,
        filename: this.file.name,
      },
      success: (response) => {
        if (response.success) {
          this.completeCallback(response.data);
        } else {
          this.errorCallback(response.data || "Finalization failed");
        }
      },
      error: (xhr, status, error) => {
        this.errorCallback(`Finalization error: ${error}`);
      },
    });
  }

  generateUploadId() {
    return (
      "upload_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9)
    );
  }
}

// Global uploader instance
let currentUploader = null;

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

  // Check if tree is selected (unless creating new)
  var tree = form.tree1;
  if (tree && !tree.value) {
    alert(selectdesttree);
    return false;
  }

  return true;
}

/**
 * Toggle import/export sections
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
 */
function toggleNorecalcdiv(show) {
  var div = document.getElementById("norecalcdiv");
  if (div) {
    div.style.display = show ? "" : "none";
  }
}

/**
 * Toggle append options
 */
function toggleAppenddiv(show) {
  var div = document.getElementById("appenddiv");
  if (div) {
    div.style.display = show ? "" : "none";
  }
}

/**
 * Toggle form target for legacy import
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
 * Handle iframe load for import progress
 */
function iframeLoaded() {
  console.log("Import iframe loaded");
  // Additional progress handling can be added here
}

/**
 * Run post-import utility
 */
function runPostImportUtility(utility) {
  if (confirm("Run post-import utility: " + utility + "?")) {
    var form = document.createElement("form");
    form.method = "POST";
    form.action = window.location.href;

    // Add nonce
    var nonceField = document.createElement("input");
    nonceField.type = "hidden";
    nonceField.name = "_wpnonce";
    nonceField.value = hp_admin.nonce;
    form.appendChild(nonceField);

    // Add action
    var actionField = document.createElement("input");
    actionField.type = "hidden";
    actionField.name = "secaction";
    actionField.value = utility;
    form.appendChild(actionField);

    // Add tree
    var treeSelect = document.getElementById("treequeryselect");
    if (treeSelect) {
      var treeField = document.createElement("input");
      treeField.type = "hidden";
      treeField.name = "tree";
      treeField.value = treeSelect.value;
      form.appendChild(treeField);
    }

    document.body.appendChild(form);
    form.submit();
  }
}

/**
 * File picker placeholder function
 */
function FilePicker(inputId, type) {
  alert(
    "File picker functionality will be implemented. For now, please type the file path manually."
  );
}

/**
 * Initialize import/export interface
 */
jQuery(document).ready(function ($) {
  // Initialize form validation
  $("#gedcom-import-form").on("submit", function (e) {
    if (!checkFile(this)) {
      e.preventDefault();
      return false;
    }
  });

  // Initialize branch loading for single tree
  var treeSelect = $("#tree1");
  if (treeSelect.length && treeSelect.find("option").length === 2) {
    // Auto-select if only one tree
    treeSelect.prop("selectedIndex", 1);
    getBranches(treeSelect[0], 1);
  }

  // Initialize export tree selection
  var exportTreeSelect = $("#treeselect");
  if (exportTreeSelect.length && exportTreeSelect.find("option").length === 1) {
    getBranches(exportTreeSelect[0], 0);
  }

  // Post-import utility links
  $(".secutil").on("click", function (e) {
    e.preventDefault();
    var utility = $(this).text();
    runPostImportUtility(utility);
  });
  // Initialize toggle states
  var defimpopt = 0; // Default import option
  toggleNorecalcdiv(defimpopt);
  toggleAppenddiv(defimpopt == 3);

  // Initialize chunked upload functionality
  initChunkedUpload();
  // Initialize the new two-button file selection interface
  initializeFileSelectionButtons();
});

/**
 * Initialize chunked upload interface
 */
function initChunkedUpload() {
  // Tab switching functionality
  const tabButtons = document.querySelectorAll(".method-tab-button");
  const tabContents = document.querySelectorAll(".upload-tab-content");
  const radioInputs = document.querySelectorAll('input[name="upload_method"]');

  tabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const method = this.dataset.method;

      // Update tab appearances
      tabButtons.forEach((btn) => {
        btn.classList.remove("active");
        btn.setAttribute("aria-selected", "false");
        btn.setAttribute("tabindex", "-1");
      });

      this.classList.add("active");
      this.setAttribute("aria-selected", "true");
      this.setAttribute("tabindex", "0");

      // Update content visibility
      tabContents.forEach((content) => {
        content.style.display = "none";
      });

      const targetContent = document.getElementById(method + "-upload-tab");
      if (targetContent) {
        targetContent.style.display = "block";
      }

      // Update radio button
      radioInputs.forEach((radio) => {
        radio.checked = radio.value === method;
      });
    });
  });
  // File input and upload button functionality
  const fileInput = document.getElementById("gedcom-file-input");
  const browseButton = document.getElementById("browse-button");
  const selectedFileDisplay = document.getElementById("selected-file-display");

  // Browse button click
  if (browseButton && fileInput) {
    browseButton.addEventListener("click", function (e) {
      e.preventDefault();
      fileInput.click();
    });
  }

  // File input change
  if (fileInput) {
    fileInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        handleFileSelection(file);
      } else {
        // Hide file information if no file selected
        if (selectedFileDisplay) {
          selectedFileDisplay.style.display = "none";
        }
      }
    });
  }

  // Cancel upload
  $("#cancel-upload").click(function () {
    if (currentUploader) {
      currentUploader.cancel();
      resetUploadInterface();
    }
  });

  // Remove uploaded file
  $("#remove-file").click(function () {
    resetUploadInterface();
    $("#uploaded-file-path").val("");
  });

  // Server file selection
  $("#server-file-select").change(function () {
    const selectedFile = $(this).val();
    const selectedOption = $(this).find("option:selected");

    if (selectedFile) {
      const size = selectedOption.data("size");
      const sizeMB = (size / 1024 / 1024).toFixed(2);

      $("#server-file-name").text(selectedFile);
      $("#server-file-stats").html(`Size: ${sizeMB} MB`);
      $("#server-file-info").show();
    } else {
      $("#server-file-info").hide();
    }
  });

  // Refresh server files
  $("#refresh-server-files").click(function () {
    refreshServerFiles();
  });
}

/**
 * Initialize the enhanced two-button file selection interface
 */
function initializeFileSelectionButtons() {
  // From Computer button - triggers file input with enhanced feedback
  jQuery("#computer-button").on("click", function () {
    // Add loading state
    const $this = jQuery(this);
    $this.addClass("btn-loading");

    setTimeout(() => {
      jQuery("#gedcom-file-input").click();
      jQuery("#selected-upload-method").val("computer");
      $this.removeClass("btn-loading");
    }, 150);
  });

  // From Server button - opens server file modal with enhanced animation
  jQuery("#server-button").on("click", function () {
    const $this = jQuery(this);
    $this.addClass("btn-loading");

    setTimeout(() => {
      jQuery("#server-file-modal").fadeIn(300);
      jQuery("#selected-upload-method").val("server");
      $this.removeClass("btn-loading");

      // Focus on the select element for accessibility
      setTimeout(() => {
        jQuery("#server-file-select").focus();
      }, 300);
    }, 150);
  });

  // Enhanced button hover effects
  jQuery(".selection-btn")
    .on("mouseenter", function () {
      jQuery(this).addClass("btn-hover");
    })
    .on("mouseleave", function () {
      jQuery(this).removeClass("btn-hover");
    });

  // Server file modal handlers
  initializeServerFileModal();
}

/**
 * Enhanced server file selection modal
 */
function initializeServerFileModal() {
  // Close modal buttons with fade effect
  jQuery("#close-modal, #cancel-server-selection").on("click", function () {
    jQuery("#server-file-modal").fadeOut(250);
  });

  // Enhanced file selection with immediate feedback
  jQuery("#server-file-select").on("change", function () {
    const selectedFile = jQuery(this).val();
    const $selectButton = jQuery("#select-server-file");

    if (selectedFile) {
      $selectButton.prop("disabled", false).addClass("button-ready");

      // Add subtle animation to draw attention
      $selectButton.addClass("pulse-once");
      setTimeout(() => {
        $selectButton.removeClass("pulse-once");
      }, 600);
    } else {
      $selectButton.prop("disabled", true).removeClass("button-ready");
    }
  });

  // Enhanced server file selection with loading feedback
  jQuery("#select-server-file").on("click", function () {
    const $this = jQuery(this);
    const selectedFile = jQuery("#server-file-select").val();
    const selectedOption = jQuery("#server-file-select option:selected");

    if (selectedFile) {
      // Add loading state
      $this.addClass("button-loading").prop("disabled", true);
      $this.html(
        '<span class="dashicons dashicons-update-alt spinning"></span> Selecting...'
      );

      setTimeout(() => {
        // Display selected file info
        displayServerFileInfo(selectedFile, selectedOption);

        // Set the hidden form field
        jQuery("#uploaded-file-path").val(selectedFile);

        // Show success feedback
        showSelectionSuccess();

        // Close modal with delay
        setTimeout(() => {
          jQuery("#server-file-modal").fadeOut(250);

          // Reset button state
          $this.removeClass("button-loading").prop("disabled", false);
          $this.html("Select File");
        }, 800);
      }, 1000);
    }
  });

  // Enhanced refresh functionality
  jQuery("#refresh-server-files").on("click", function () {
    refreshServerFileList();
  });

  // Close modal on overlay click with fade
  jQuery("#server-file-modal").on("click", function (e) {
    if (e.target === this) {
      jQuery(this).fadeOut(250);
    }
  });

  // Keyboard navigation for modal
  jQuery("#server-file-modal").on("keydown", function (e) {
    if (e.key === "Escape") {
      jQuery(this).fadeOut(250);
    }
  });
}

/**
 * Enhanced display for selected server file information
 */
function displayServerFileInfo(filename, optionElement) {
  const fileSize = optionElement.attr("data-size");
  const fileSizeMB = fileSize
    ? (parseInt(fileSize) / (1024 * 1024)).toFixed(2)
    : "Unknown";

  // Update file display with enhanced styling
  jQuery("#file-name").text(filename);
  jQuery("#file-stats").html(
    '<span class="file-size">' +
      fileSizeMB +
      " MB</span> • " +
      '<span class="file-source">Server file</span>'
  );
  jQuery("#file-status-text").text("Ready for import");

  // Show the selected file display with animation
  const $display = jQuery("#selected-file-display");
  $display.fadeIn(300);
}

/**
 * Refresh the server file list
 */
function refreshServerFileList() {
  const refreshButton = jQuery("#refresh-server-files");
  const originalText = refreshButton.html();

  // Show loading state
  refreshButton.html(
    '<span class="dashicons dashicons-update-alt spinning"></span> Refreshing...'
  );
  refreshButton.prop("disabled", true);

  // AJAX call to refresh file list
  jQuery.ajax({
    url: hp_ajax.ajax_url,
    type: "POST",
    data: {
      action: "hp_refresh_server_files",
      nonce: hp_ajax.nonce,
    },
    success: function (response) {
      if (response.success) {
        // Update the select options
        const select = jQuery("#server-file-select");
        select.empty();
        select.append('<option value="">Select a file from server...</option>');

        if (response.data && response.data.length > 0) {
          response.data.forEach(function (file) {
            select.append(
              '<option value="' +
                file.filename +
                '" data-size="' +
                file.size +
                '">' +
                file.filename +
                " (" +
                file.size_mb +
                " MB - " +
                file.modified +
                ")</option>"
            );
          });
        } else {
          select.append(
            '<option value="" disabled>No GEDCOM files found on server</option>'
          );
        }
      }
    },
    error: function () {
      alert("Error refreshing file list. Please try again.");
    },
    complete: function () {
      // Restore button state
      refreshButton.html(originalText);
      refreshButton.prop("disabled", false);
    },
  });
}

/**
 * Show success feedback notification
 */
function showSelectionSuccess() {
  // Remove any existing notifications
  jQuery(".success-feedback").remove();

  // Create and show success notification
  const notification = jQuery(
    '<div class="success-feedback">' +
      '<span class="dashicons dashicons-yes-alt" style="margin-right: 8px;"></span>' +
      "File selected successfully!" +
      "</div>"
  );

  jQuery("body").append(notification);

  // Auto remove after animation
  setTimeout(() => {
    notification.remove();
  }, 3000);
}

/**
 * Handle file selection for upload
 */
function handleFileSelection(file) {
  // Validate file type
  const ext = file.name.split(".").pop().toLowerCase();
  if (ext !== "ged" && ext !== "gedcom") {
    alert("Please select a valid GEDCOM file (.ged or .gedcom)");
    return;
  }

  // Validate file size (max 500MB)
  const maxSize = 500 * 1024 * 1024;
  if (file.size > maxSize) {
    alert("File size too large. Please select a file smaller than 500MB.");
    return;
  }

  // Get UI elements
  const selectedFileDisplay = document.getElementById("selected-file-display");
  const fileNameEl = document.getElementById("file-name");
  const fileStatsEl = document.getElementById("file-stats");

  // Show file information with enhanced styling
  if (fileNameEl) {
    fileNameEl.textContent = file.name;
  }
  if (fileStatsEl) {
    fileStatsEl.innerHTML = `<span class="file-size">${formatFileSize(
      file.size
    )}</span> • <span class="file-source">Uploaded from computer</span>`;
  }

  // Show the file display with animation
  if (selectedFileDisplay) {
    selectedFileDisplay.style.display = "block";
    // Trigger reflow for animation
    selectedFileDisplay.offsetHeight;
  }

  // Set upload method and file info
  jQuery("#selected-upload-method").val("computer");

  // Simulate upload process with better feedback
  setTimeout(() => {
    // Set the uploaded file path
    const uploadedFilePathInput = document.getElementById("uploaded-file-path");
    if (uploadedFilePathInput) {
      uploadedFilePathInput.value = file.name;
    }

    // Show success notification
    showSelectionSuccess();

    // Update file status
    const statusEl = document.getElementById("file-status-text");
    if (statusEl) {
      statusEl.textContent = "Ready for import";
    }
  }, 500);
}

/**
 * Format file size in human readable format
 */
function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}

/**
 * Reset upload interface
 */
function resetUploadInterface() {
  const selectedFileDisplay = document.getElementById("selected-file-display");
  const fileInput = document.getElementById("gedcom-file-input");
  const uploadedFilePathInput = document.getElementById("uploaded-file-path");
  const selectedUploadMethod = document.getElementById(
    "selected-upload-method"
  );
  const serverFileSelect = document.getElementById("server-file-select");

  if (selectedFileDisplay) {
    selectedFileDisplay.style.display = "none";
  }
  if (fileInput) {
    fileInput.value = "";
  }
  if (uploadedFilePathInput) {
    uploadedFilePathInput.value = "";
  }
  if (selectedUploadMethod) {
    selectedUploadMethod.value = "";
  }
  if (serverFileSelect) {
    serverFileSelect.value = "";
  }

  // Cancel any active uploader
  if (currentUploader) {
    currentUploader.cancel();
    currentUploader = null;
  }
}

/**
 * Show upload message
 */
function showUploadMessage(message, type) {
  const alertClass = type === "success" ? "notice-success" : "notice-error";
  const messageHtml = `<div class="notice ${alertClass} is-dismissible"><p>${message}</p></div>`;

  // Try to find existing wrap element
  const wrapElement = document.querySelector(".wrap h1");
  if (wrapElement) {
    wrapElement.insertAdjacentHTML("afterend", messageHtml);
  } else {
    // Fallback: prepend to the main content area
    const mainContent = document.querySelector(".import-section");
    if (mainContent) {
      mainContent.insertAdjacentHTML("afterbegin", messageHtml);
    }
  }

  // Auto-dismiss after 5 seconds
  setTimeout(function () {
    const notices = document.querySelectorAll(".notice.is-dismissible");
    notices.forEach((notice) => {
      notice.style.opacity = "0";
      setTimeout(() => notice.remove(), 300);
    });
  }, 5000);
}

/**
 * Refresh server files list
 */
function refreshServerFiles() {
  const refreshButton = document.getElementById("refresh-server-files");
  const select = document.getElementById("server-file-select");

  if (refreshButton) {
    refreshButton.disabled = true;
    refreshButton.innerHTML =
      '<span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span> Refreshing...';
  }

  // Make AJAX request to refresh server files
  if (typeof hp_ajax !== "undefined") {
    jQuery.ajax({
      url: hp_ajax.ajax_url,
      type: "POST",
      data: {
        action: "hp_refresh_server_files",
        nonce: hp_ajax.nonce,
      },
      success: function (response) {
        if (response.success && select) {
          // Update the select options
          select.innerHTML = response.data.options;
        }
      },
      error: function () {
        showUploadMessage("Failed to refresh server files list", "error");
      },
      complete: function () {
        if (refreshButton) {
          refreshButton.disabled = false;
          refreshButton.innerHTML =
            '<span class="dashicons dashicons-update-alt"></span> Refresh List';
        }
      },
    });
  } else {
    // Fallback: just reload the page
    window.location.reload();
  }
}

/**
 * Upload Method Tab Functionality
 */
function initializeUploadTabs() {
  // Tab button click handlers
  jQuery(".method-tab-button").on("click", function () {
    const method = jQuery(this).data("method");
    switchUploadMethod(method);
  });

  // Server file selection change handler
  jQuery("#server-file-select").on("change", function () {
    const selectedFile = jQuery(this).val();
    if (selectedFile) {
      showServerFileInfo(selectedFile);
    } else {
      hideServerFileInfo();
    }
  });

  // Refresh server files button
  jQuery("#refresh-server-files").on("click", function () {
    refreshServerFiles();
  });
}

/**
 * Switch between upload methods (computer vs server)
 */
function switchUploadMethod(method) {
  // Update tab buttons
  jQuery(".method-tab-button")
    .removeClass("active")
    .attr("aria-selected", "false")
    .attr("tabindex", "-1");

  const activeButton = jQuery(`.method-tab-button[data-method="${method}"]`);
  activeButton
    .addClass("active")
    .attr("aria-selected", "true")
    .attr("tabindex", "0");

  // Update radio buttons
  jQuery('input[name="upload_method"]').prop("checked", false);
  jQuery(`#method-${method}`).prop("checked", true);

  // Show/hide tab content with proper ARIA
  jQuery(".upload-tab-content").hide().attr("aria-hidden", "true");
  const activePanel = jQuery(`#${method}-upload-tab`);
  activePanel.show().attr("aria-hidden", "false");

  // Focus management for accessibility
  activePanel.focus();

  // Clear any previous selections
  if (method === "computer") {
    hideServerFileInfo();
    jQuery("#server-file-select").val("");
  } else {
    hideFileInfo();
    clearSelectedFile();
  }
}

/**
 * Show server file information
 */
function showServerFileInfo(filename) {
  const select = jQuery("#server-file-select");
  const selectedOption = select.find(`option[value="${filename}"]`);

  if (selectedOption.length) {
    const size = selectedOption.data("size");
    const sizeFormatted = formatFileSize(size);
    const optionText = selectedOption.text();

    // Extract date from option text (format: filename (size - date))
    const dateMatch = optionText.match(/\(([\d.]+\s*MB\s*-\s*(.+))\)/);
    const dateText = dateMatch ? dateMatch[2] : "Unknown";

    jQuery("#server-file-name").text(filename);
    jQuery("#server-file-stats").html(`
      <strong>Size:</strong> ${sizeFormatted}<br>
      <strong>Modified:</strong> ${dateText}
    `);
    jQuery("#server-file-info").show();
  }
}

/**
 * Hide server file information
 */
function hideServerFileInfo() {
  jQuery("#server-file-info").hide();
}

/**
 * Refresh server files list
 */
function refreshServerFiles() {
  const button = jQuery("#refresh-server-files");
  const originalText = button.html();

  button
    .prop("disabled", true)
    .html('<span class="dashicons dashicons-update-alt"></span> Refreshing...');

  jQuery.ajax({
    url: hp_ajax.ajax_url,
    type: "POST",
    data: {
      action: "hp_refresh_server_files",
      nonce: hp_ajax.nonce,
    },
    success: function (response) {
      if (response.success) {
        // Update the select options
        const select = jQuery("#server-file-select");
        const currentValue = select.val();

        select
          .empty()
          .append('<option value="">Select a file from server...</option>');

        if (response.data.files && response.data.files.length > 0) {
          response.data.files.forEach(function (file) {
            const option = jQuery("<option></option>")
              .attr("value", file.filename)
              .attr("data-size", file.size)
              .text(
                `${file.filename} (${file.size_formatted} - ${file.modified})`
              );
            select.append(option);
          });
        } else {
          select.append(
            '<option value="" disabled>No GEDCOM files found on server</option>'
          );
        }

        // Restore previous selection if it still exists
        if (
          currentValue &&
          select.find(`option[value="${currentValue}"]`).length
        ) {
          select.val(currentValue);
          showServerFileInfo(currentValue);
        } else {
          hideServerFileInfo();
        }
      } else {
        alert(
          "Failed to refresh server files: " +
            (response.data || "Unknown error")
        );
      }
    },
    error: function () {
      alert("Error refreshing server files. Please try again.");
    },
    complete: function () {
      button.prop("disabled", false).html(originalText);
    },
  });
}

/**
 * Format file size in human readable format
 */
function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";

  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}

/**
 * Format upload speed and time remaining
 */
function formatSpeed(bytesPerSecond) {
  if (bytesPerSecond < 1024) {
    return Math.round(bytesPerSecond) + " B/s";
  } else if (bytesPerSecond < 1024 * 1024) {
    return (bytesPerSecond / 1024).toFixed(1) + " KB/s";
  } else {
    return (bytesPerSecond / (1024 * 1024)).toFixed(1) + " MB/s";
  }
}

function formatTimeRemaining(seconds) {
  if (seconds < 60) {
    return Math.round(seconds) + " seconds";
  } else if (seconds < 3600) {
    return Math.round(seconds / 60) + " minutes";
  } else {
    return Math.round(seconds / 3600) + " hours";
  }
}

/**
 * Clear selected file and reset UI
 */
/**
 * Clear selected file and reset UI
 */
function clearSelectedFile() {
  jQuery("#gedcom-file-input").val("");
  jQuery("#uploaded-file-path").val("");
  jQuery("#selected-upload-method").val("");
  jQuery("#selected-file-display").fadeOut(250);
  jQuery("#server-file-select").val("");

  // Remove any success notifications
  jQuery(".success-feedback").remove();

  // Reset button states
  jQuery(".selection-btn").removeClass("btn-loading btn-hover");
  jQuery("#select-server-file")
    .removeClass("button-ready button-loading")
    .prop("disabled", true);

  // Cancel any active upload
  if (currentUploader) {
    currentUploader.cancel();
    currentUploader = null;
  }
}

/**
 * Initialize upload interface on document ready
 */
jQuery(document).ready(function () {
  // Initialize the new two-button interface handlers
  initializeFileSelectionButtons();

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
});

/**
 * Handle keyboard navigation for tabs
 */
function initializeTabKeyboardNavigation() {
  jQuery(".method-tab-button").on("keydown", function (e) {
    const currentButton = jQuery(this);
    const allButtons = jQuery(".method-tab-button");
    const currentIndex = allButtons.index(currentButton);

    switch (e.key) {
      case "ArrowLeft":
      case "ArrowUp":
        e.preventDefault();
        const prevIndex =
          (currentIndex - 1 + allButtons.length) % allButtons.length;
        const prevButton = allButtons.eq(prevIndex);
        prevButton.focus().click();
        break;

      case "ArrowRight":
      case "ArrowDown":
        e.preventDefault();
        const nextIndex = (currentIndex + 1) % allButtons.length;
        const nextButton = allButtons.eq(nextIndex);
        nextButton.focus().click();
        break;

      case "Home":
        e.preventDefault();
        allButtons.first().focus().click();
        break;

      case "End":
        e.preventDefault();
        allButtons.last().focus().click();
        break;

      case "Enter":
      case " ":
        e.preventDefault();
        currentButton.click();
        break;
    }
  });
}

/**
 * Handle dropzone keyboard accessibility
 */
// Initialize keyboard navigation when document is ready
jQuery(document).ready(function () {
  initializeTabKeyboardNavigation();
});

/**
 * Add New Tree Modal Functionality
 */

/**
 * Open the Add New Tree modal
 */
function openAddTreeModal() {
  console.log("openAddTreeModal function called");

  // Check if modal element exists
  var modal = jQuery("#add-tree-modal");
  if (modal.length === 0) {
    console.error("Modal element #add-tree-modal not found!");
    alert("Modal not found. Please check the page setup.");
    return;
  }

  // Clear previous values and errors
  jQuery("#new-tree-id").val("");
  jQuery("#new-tree-name").val("");
  jQuery(".hp-error-message").remove();

  // Show modal with fade effect
  modal.fadeIn(300, function () {
    console.log("Modal should now be visible");
    // Focus on the first input
    jQuery("#new-tree-id").focus();
  });
}

// Make sure function is available globally
window.openAddTreeModal = openAddTreeModal;

/**
 * Close the Add New Tree modal
 */
function closeAddTreeModal() {
  jQuery("#add-tree-modal").fadeOut(250);
}

/**
 * Validate tree form data
 */
function validateTreeForm() {
  const treeId = jQuery("#new-tree-id").val().trim();
  const treeName = jQuery("#new-tree-name").val().trim();

  // Remove previous error messages
  jQuery(".hp-error-message").remove();

  let isValid = true;

  // Validate Tree ID
  if (!treeId) {
    showFieldError("new-tree-id", entertreeid);
    isValid = false;
  } else if (!/^[a-zA-Z0-9_-]+$/.test(treeId)) {
    showFieldError("new-tree-id", alphanum);
    isValid = false;
  }

  // Validate Tree Name
  if (!treeName) {
    showFieldError("new-tree-name", entertreename);
    isValid = false;
  }

  return isValid;
}

/**
 * Show field error message
 */
function showFieldError(fieldId, message) {
  const field = jQuery("#" + fieldId);
  const errorDiv = jQuery(
    '<div class="hp-error-message" style="color: #d63638; font-size: 14px; margin-top: 5px;">' +
      message +
      "</div>"
  );
  field.after(errorDiv);
  field.addClass("hp-error-field").css("border-color", "#d63638");
}

/**
 * Clear field errors
 */
function clearFieldErrors() {
  jQuery(".hp-error-message").remove();
  jQuery(".hp-error-field")
    .removeClass("hp-error-field")
    .css("border-color", "");
}

/**
 * Submit new tree via AJAX
 */
function submitNewTree() {
  // Validate form first
  if (!validateTreeForm()) {
    return;
  }

  const treeId = jQuery("#new-tree-id").val().trim();
  const treeName = jQuery("#new-tree-name").val().trim();
  const submitButton = jQuery("#create-tree-btn");

  // Disable submit button and show loading
  submitButton.prop("disabled", true).val("Adding Tree...");

  // Clear any previous errors
  clearFieldErrors();
  // AJAX request to add tree
  console.log("Submitting tree via AJAX:", {
    tree_id: treeId,
    tree_name: treeName,
  });

  jQuery.ajax({
    url: hp_ajax.ajax_url,
    type: "POST",
    data: {
      action: "hp_add_tree",
      nonce: hp_ajax.nonce,
      tree_id: treeId,
      tree_name: treeName,
    },
    success: function (response) {
      if (response.success) {
        // Success - add tree to dropdown and close modal
        addTreeToDropdown(treeId, treeName);
        closeAddTreeModal();

        // Show success message
        showSuccessMessage('Tree "' + treeName + '" added successfully!');

        // Auto-select the new tree
        jQuery("#tree").val(treeId);
      } else {
        // Handle server-side validation errors
        if (response.data && response.data.field) {
          showFieldError(response.data.field, response.data.message);
        } else {
          showFieldError(
            "new-tree-id",
            response.data || "Failed to add tree. Please try again."
          );
        }
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      showFieldError(
        "new-tree-id",
        "Network error. Please check your connection and try again."
      );
    },
    complete: function () {
      // Re-enable submit button
      submitButton.prop("disabled", false).text("Create Tree");
    },
  });
}

/**
 * Add new tree to the dropdown
 */
function addTreeToDropdown(treeId, treeName) {
  const treeSelect = jQuery("#tree");
  const newOption = jQuery(
    '<option value="' + treeId + '">' + treeName + " (" + treeId + ")</option>"
  );

  // Insert in alphabetical order
  let inserted = false;
  treeSelect.find("option").each(function () {
    if (jQuery(this).val() === "") return; // Skip the "Select Tree" option

    if (treeName.toLowerCase() < jQuery(this).text().toLowerCase()) {
      jQuery(this).before(newOption);
      inserted = true;
      return false; // Break the loop
    }
  });

  // If not inserted, append to the end
  if (!inserted) {
    treeSelect.append(newOption);
  }
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
  // Remove any existing messages
  jQuery(".hp-success-message").remove();

  // Create success message
  const successDiv = jQuery(
    '<div class="hp-success-message notice notice-success is-dismissible" style="margin: 10px 0;"><p>' +
      message +
      "</p></div>"
  );

  // Insert after the tree selection row
  jQuery(".tree-selection-row").after(successDiv);

  // Auto-remove after 5 seconds
  setTimeout(function () {
    successDiv.fadeOut(500, function () {
      jQuery(this).remove();
    });
  }, 5000);
}

/**
 * Initialize Add Tree Modal when document is ready
 */
jQuery(document).ready(function () {
  console.log(
    "Import-export.js document ready - Add Tree Modal initialization starting"
  );

  // Verify modal exists
  var modal = jQuery("#add-tree-modal");
  console.log("Modal element found:", modal.length > 0);
  // Verify button exists
  var button = jQuery("input[onclick*='openAddTreeModal']");
  console.log("Add Tree button found:", button.length > 0);

  // Bind click event directly to Add New Tree button as backup
  jQuery("input[name='newtree'], input[value*='Add New Tree']").on(
    "click",
    function (e) {
      e.preventDefault();
      console.log("Add New Tree button clicked via jQuery event");
      openAddTreeModal();
    }
  );

  // Close modal button handlers
  jQuery("#close-add-tree-modal, #cancel-add-tree").on("click", function (e) {
    e.preventDefault();
    closeAddTreeModal();
  });
  // Submit tree button handler
  jQuery("#create-tree-btn").on("click", function (e) {
    console.log("Create tree button clicked");
    e.preventDefault();
    submitNewTree();
  });

  // Close modal on overlay click
  jQuery("#add-tree-modal").on("click", function (e) {
    if (e.target === this) {
      closeAddTreeModal();
    }
  });

  // Handle Enter key in form fields
  jQuery("#new-tree-id, #new-tree-name").on("keypress", function (e) {
    if (e.which === 13) {
      // Enter key
      e.preventDefault();
      submitNewTree();
    }
  });

  // Handle Escape key to close modal
  jQuery(document).on("keydown", function (e) {
    if (e.key === "Escape" && jQuery("#add-tree-modal").is(":visible")) {
      closeAddTreeModal();
    }
  });

  // Clear errors when user starts typing
  jQuery("#new-tree-id, #new-tree-name").on("input", function () {
    const fieldId = jQuery(this).attr("id");
    jQuery("#" + fieldId)
      .removeClass("hp-error-field")
      .css("border-color", "");
    jQuery("#" + fieldId)
      .next(".hp-error-message")
      .remove();
  });

  // Auto-format tree ID (remove spaces, convert to lowercase with underscores)
  jQuery("#new-tree-id").on("input", function () {
    let value = jQuery(this).val();
    // Remove invalid characters and convert spaces to underscores
    value = value
      .replace(/[^a-zA-Z0-9_-\s]/g, "")
      .replace(/\s+/g, "_")
      .toLowerCase();
    jQuery(this).val(value);
  });
});
