/**
 * Import/Export Constants and Global Variables
 * HeritagePress Plugin - Core Constants Module
 */

// Global message constants
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
var finished_msg = "Finished imadapting!";
var imadapting_msg = "Imadapting GEDCOM...";
var removeged_msg = "Remove GEDCOM";
var close_msg = "Close Window";
var more_options = "More Options";

// Global state variables
var branches = new Array();
var branchcounts = new Array();
var currentUploader = null; // Global variable for current upload instance

/**
 * Format file size in human readable format
 * @param {number} bytes - File size in bytes
 * @returns {string} - Formatted file size
 */
function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}

/**
 * Format upload speed
 * @param {number} bytesPerSecond - Upload speed in bytes per second
 * @returns {string} - Formatted speed
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

/**
 * Format time remaining
 * @param {number} seconds - Time in seconds
 * @returns {string} - Formatted time
 */
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
 * Show upload message to user
 * @param {string} message - Message to display
 * @param {string} type - Message type ('success' or 'error')
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
 * File picker placeholder function
 */
function FilePicker(inputId, type) {
  alert(
    "File picker functionality will be implemented. For now, please type the file path manually."
  );
}

/**
 * Run post-import utility
 * @param {string} utility - Utility name to run
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
 * Handle iframe load for import progress
 */
function iframeLoaded() {
  console.log("Import iframe loaded");
  // Additional progress handling can be added here
}
