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
});

/**
 * Initialize chunked upload interface
 */
function initChunkedUpload() {
  // Upload method selection
  $('input[name="upload_method"]').change(function () {
    if ($(this).val() === "computer") {
      $("#computer-upload").show();
      $("#server-upload").hide();
    } else {
      $("#computer-upload").hide();
      $("#server-upload").show();
    }
  });

  // Browse button click
  $("#browse-button").click(function () {
    $("#gedcom-file-input").click();
  });
  // File input functionality
  const fileInput = document.getElementById("gedcom-file-input");
  const selectedFileInfo = document.getElementById("selected-file-info");
  const selectedFileName = document.getElementById("selected-file-name");
  const selectedFileSize = document.getElementById("selected-file-size");

  if (fileInput) {
    fileInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        // Show file information
        selectedFileName.textContent = file.name;
        selectedFileSize.textContent = formatFileSize(file.size);
        selectedFileInfo.style.display = "flex";

        // Process the file for upload        handleFileSelection(file);
      } else {
        // Hide file information if no file selected
        selectedFileInfo.style.display = "none";
      }
    });
  }

  // Utility function to format file size
  function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
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
  // Make "Upload from Computer" tab button trigger file selection
  const computerTabButton = document.getElementById("computer-tab-button");
  if (computerTabButton) {
    computerTabButton.addEventListener("click", function (e) {
      // Always trigger file selection when this tab is clicked
      setTimeout(() => {
        if (fileInput) {
          fileInput.click();
        }
      }, 100); // Small delay to ensure tab switching completes first
    });
  }
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

  // Reset interface
  resetUploadInterface();

  // Show upload progress
  $("#upload-progress").show();
  $(".file-input-section").hide();

  // Create uploader
  currentUploader = new ChunkedGedcomUploader({
    chunkSize: 2 * 1024 * 1024, // 2MB chunks
    onProgress: function (progress) {
      $("#upload-progress-bar").css("width", progress + "%");
      $("#upload-progress-text").text(Math.round(progress) + "%");
    },
    onSpeed: function (stats) {
      const speedMBps = (stats.speed / 1024 / 1024).toFixed(1);
      const remainingMin = Math.round(stats.remaining / 60);
      const uploadedMB = (stats.uploaded / 1024 / 1024).toFixed(1);
      const totalMB = (stats.total / 1024 / 1024).toFixed(1);

      $("#upload-speed").text(
        `${speedMBps} MB/s - ${uploadedMB}/${totalMB} MB - ${remainingMin}min remaining`
      );
    },
    onComplete: function (data) {
      $("#upload-progress").hide();

      $("#file-name").text(data.filename);
      $("#file-stats").html(`Size: ${(data.size / 1024 / 1024).toFixed(2)} MB`);
      $("#selected-file-info").show();
      $("#uploaded-file-path").val(data.file_path);

      // Show success message
      showUploadMessage("File uploaded successfully!", "success");
    },
    onError: function (error) {
      $("#upload-progress").hide();
      $(".file-input-section").show();
      showUploadMessage("Upload failed: " + error, "error");
      currentUploader = null;
    },
  });

  // Start upload
  currentUploader.upload(file);
}

/**
 * Reset upload interface
 */
function resetUploadInterface() {
  $("#upload-progress").hide();
  $("#selected-file-info").hide();
  $(".file-input-section").show();
  $("#upload-progress-bar").css("width", "0%");
  $("#upload-progress-text").text("0%");
  $("#upload-speed").text("Calculating...");
  $("#gedcom-file-input").val("");

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

  $(".wrap h1").after(messageHtml);

  // Auto-dismiss after 5 seconds
  setTimeout(function () {
    $(".notice.is-dismissible").fadeOut();
  }, 5000);
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
  jQuery("#selected-file-info").hide();
  hideFileInfo();
  hideUploadProgress();
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
  // Initialize tab functionality
  initializeUploadTabs();

  // Initialize keyboard navigation
  initializeTabKeyboardNavigation();

  // Initialize drag and drop
  // Initialize components

  // Initialize dropzone keyboard accessibility
  // Initialize components

  // Set default active tab
  switchUploadMethod("computer");
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
    const uploadMethod = jQuery('input[name="upload_method"]:checked').val();

    if (uploadMethod === "computer") {
      const uploadedFile = jQuery("#uploaded-file-path").val();
      if (!uploadedFile) {
        e.preventDefault();
        alert("Please select and upload a GEDCOM file first.");
        return false;
      }
    } else if (uploadMethod === "server") {
      const serverFile = jQuery("#server-file-select").val();
      if (!serverFile) {
        e.preventDefault();
        alert("Please select a file from the server.");
        return false;
      }
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
// Initialize keyboard navigation
initializeTabKeyboardNavigation();
