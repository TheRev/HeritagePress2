/**
 * File Selection Interface
 * HeritagePress Plugin - Modern File Selection Module
 */

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
 * Handle file selection for upload
 * @param {File} file - Selected file object
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
 * Switch between upload methods (computer vs server)
 * @param {string} method - Upload method ('computer' or 'server')
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
 * @param {string} filename - Selected filename
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
 * Hide file information display
 */
function hideFileInfo() {
  jQuery("#selected-file-display").hide();
}

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

// Export functions for use in other modules
window.initializeFileSelectionButtons = initializeFileSelectionButtons;
window.handleFileSelection = handleFileSelection;
window.clearSelectedFile = clearSelectedFile;
window.switchUploadMethod = switchUploadMethod;
window.initializeTabKeyboardNavigation = initializeTabKeyboardNavigation;
window.initializeUploadTabs = initializeUploadTabs;
window.refreshServerFiles = refreshServerFiles;
