/**
 * Add New Tree Modal
 * HeritagePress Plugin - Tree Management Module
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
 * @returns {boolean} - True if validation passes
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
 * @param {string} fieldId - Field ID to show error for
 * @param {string} message - Error message to display
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
  // Debug: Check if hp_ajax is available
  if (typeof hp_ajax === "undefined") {
    console.error("hp_ajax is not defined! Check script localization.");
    showFieldError(
      "new-tree-id",
      "Configuration error. Please refresh the page."
    );
    submitButton.prop("disabled", false).text("Create Tree");
    return;
  }

  // AJAX request to add tree
  console.log("Submitting tree via AJAX:", {
    tree_id: treeId,
    tree_name: treeName,
    ajax_url: hp_ajax.ajax_url,
    nonce: hp_ajax.nonce,
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
      console.log("AJAX response received:", response);
      if (response.success) {
        console.log("Tree creation successful, updating UI...");

        // Success - add tree to dropdown and close modal
        console.log("Adding tree to dropdown:", treeId, treeName);
        addTreeToDropdown(treeId, treeName);

        console.log("Closing modal...");
        closeAddTreeModal();

        // Show success message
        console.log("Showing success message...");
        showSuccessMessage('Tree "' + treeName + '" added successfully!');

        // Auto-select the new tree
        console.log("Auto-selecting new tree:", treeId);
        jQuery("#tree1").val(treeId);

        // Trigger change event to update any dependent UI
        jQuery("#tree1").trigger("change");

        console.log("Tree creation process completed successfully");
      } else {
        console.error("Server returned error:", response);
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
      console.error("AJAX Error:", {
        status: status,
        error: error,
        response: xhr.responseText,
        statusCode: xhr.status,
      });

      let errorMessage =
        "Network error. Please check your connection and try again.";
      if (xhr.status === 403) {
        errorMessage =
          "Permission denied. You may not have sufficient privileges.";
      } else if (xhr.status === 404) {
        errorMessage =
          "AJAX endpoint not found. Please contact an administrator.";
      } else if (xhr.responseText) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response.data) {
            errorMessage = response.data;
          }
        } catch (e) {
          // Keep default error message
        }
      }

      showFieldError("new-tree-id", errorMessage);
    },
    complete: function () {
      // Re-enable submit button
      submitButton.prop("disabled", false).text("Create Tree");
    },
  });
}

/**
 * Add new tree to the dropdown
 * @param {string} treeId - Tree ID
 * @param {string} treeName - Tree name
 */
function addTreeToDropdown(treeId, treeName) {
  const treeSelect = jQuery("#tree1");
  const newOption = jQuery(
    '<option value="' + treeId + '">' + treeName + "</option>"
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
 * @param {string} message - Success message to display
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
  // Insert after the destination tree row
  jQuery("#desttree2").after(successDiv);

  // Auto-remove after 5 seconds
  setTimeout(function () {
    successDiv.fadeOut(500, function () {
      jQuery(this).remove();
    });
  }, 5000);
}

/**
 * Initialize Add Tree Modal functionality
 */
function initializeAddTreeModal() {
  console.log("Add Tree Modal initialization starting");

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
}

// Export functions for use in other modules
window.openAddTreeModal = openAddTreeModal;
window.closeAddTreeModal = closeAddTreeModal;
window.validateTreeForm = validateTreeForm;
window.showFieldError = showFieldError;
window.clearFieldErrors = clearFieldErrors;
window.submitNewTree = submitNewTree;
window.addTreeToDropdown = addTreeToDropdown;
window.showSuccessMessage = showSuccessMessage;
window.initializeAddTreeModal = initializeAddTreeModal;
