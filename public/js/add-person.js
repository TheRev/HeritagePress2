/**
 * Add Person Form JavaScript - Compact and Professional
 * Modern, efficient functionality for HeritagePress Add Person form
 */

(function ($) {
  "use strict";

  // Initialize when DOM is ready
  $(document).ready(function () {
    initializeAddPersonForm();
  });

  function initializeAddPersonForm() {
    // Initialize form validation
    initFormValidation();

    // Initialize AJAX handlers
    initAjaxHandlers();

    // Initialize field interactions
    initFieldInteractions();

    // Initialize date validation
    initDateValidation();

    // Initialize collapsible sections
    initCollapsibleSections();
  }

  /**
   * Form Validation
   */
  function initFormValidation() {
    const form = $("#add_person_form");

    form.on("submit", function (e) {
      if (!validateForm()) {
        e.preventDefault();
        showValidationErrors();
      }
    });
  }

  function validateForm() {
    const errors = [];

    // Required fields validation
    const firstname = $("#firstname").val().trim();
    const lastname = $("#lastname").val().trim();

    if (!firstname && !lastname) {
      errors.push("Either First Name or Last Name is required.");
    }

    // Store errors for display
    window.validationErrors = errors;
    return errors.length === 0;
  }

  function showValidationErrors() {
    const errors = window.validationErrors || [];

    // Remove existing error notices
    $(".validation-error-notice").remove();

    if (errors.length > 0) {
      const errorHtml = `
        <div class="validation-error-notice">
          <p>Please correct the following errors:</p>
          <ul>
            ${errors.map((error) => `<li>${error}</li>`).join("")}
          </ul>
        </div>
      `;

      $(".person-form-card").first().before(errorHtml);

      // Scroll to error notice
      $("html, body").animate(
        {
          scrollTop: $(".validation-error-notice").offset().top - 20,
        },
        300
      );
    }
  }

  /**
   * AJAX Handlers
   */
  function initAjaxHandlers() {
    // Tree selection handler
    $("#gedcom").on("change", function () {
      const selectedTree = $(this).val();
      loadBranches(selectedTree);
    });

    // Person ID generation
    $("#generate_id").on("click", function (e) {
      e.preventDefault();
      generatePersonID();
    });
  }

  function loadBranches(treeId) {
    if (!treeId) {
      $("#branch").empty().append('<option value="">Select Branch...</option>');
      return;
    }

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "hp_get_branches",
        tree_id: treeId,
        nonce: hp_ajax_nonce,
      },
      success: function (response) {
        if (response.success) {
          const branchSelect = $("#branch");
          branchSelect.empty();
          branchSelect.append('<option value="">Select Branch...</option>');

          response.data.forEach(function (branch) {
            branchSelect.append(
              `<option value="${branch.branch}">${branch.description}</option>`
            );
          });
        }
      },
      error: function () {
        console.warn("Failed to load branches");
      },
    });
  }

  function generatePersonID() {
    const gedcom = $("#gedcom").val();

    if (!gedcom) {
      alert("Please select a tree first.");
      return;
    }

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "hp_generate_person_id",
        tree_id: gedcom,
        nonce: hp_ajax_nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#personID").val(response.data.personID);
        } else {
          alert(
            "Failed to generate Person ID: " +
              (response.data || "Unknown error")
          );
        }
      },
      error: function () {
        alert("Failed to generate Person ID. Please try again.");
      },
    });
  }

  /**
   * Field Interactions
   */
  function initFieldInteractions() {
    // Gender field logic
    $("#sex").on("change", function () {
      toggleGenderSpecificFields($(this).val());
    });

    // Field focus highlighting
    $("input, select, textarea")
      .on("focus", function () {
        $(this).addClass("field-focused");
      })
      .on("blur", function () {
        $(this).removeClass("field-focused");
      });

    // Name field auto-capitalize
    $("#firstname, #lastname").on("input", function () {
      capitalizeNames($(this));
    });
  }

  function toggleGenderSpecificFields(gender) {
    // Future implementation for gender-specific field visibility
    // Currently no specific gender-based field hiding implemented
  }

  function capitalizeNames(field) {
    const value = field.val();
    if (value) {
      const capitalized = value
        .split(" ")
        .map(
          (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        )
        .join(" ");
      field.val(capitalized);
    }
  }

  /**
   * Date Validation
   */
  function initDateValidation() {
    $(".datefield").on("input blur", function () {
      validateDateField($(this));
    });
  }

  function validateDateField(field) {
    const value = field.val().trim();
    const container = field.closest(".event-fields, td");

    // Remove existing validation classes
    container.removeClass("date-valid date-invalid");

    if (!value) {
      return; // Empty dates are allowed
    }

    // Basic date format validation
    const datePattern =
      /^\d{1,2}\s+(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)\s+\d{4}$/i;
    const yearOnlyPattern = /^\d{4}$/;
    const monthYearPattern =
      /^(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)\s+\d{4}$/i;

    if (
      datePattern.test(value) ||
      yearOnlyPattern.test(value) ||
      monthYearPattern.test(value)
    ) {
      container.addClass("date-valid");
    } else {
      container.addClass("date-invalid");
    }
  }

  /**
   * Collapsible Section Toggle
   */
  function initCollapsibleSections() {
    // Handle collapsible header clicks
    $(".collapsible-header").on("click", function () {
      const $header = $(this);
      const targetId = $header.data("target");
      const $content = $("#" + targetId);
      const $icon = $header.find(".toggle-icon");

      // Toggle collapsed state
      $header.toggleClass("collapsed");
      $content.toggleClass("collapsed");

      // Update icon rotation
      if ($header.hasClass("collapsed")) {
        $icon
          .removeClass("dashicons-arrow-down")
          .addClass("dashicons-arrow-right");
      } else {
        $icon
          .removeClass("dashicons-arrow-right")
          .addClass("dashicons-arrow-down");
      }

      // Store preference in localStorage
      const isCollapsed = $header.hasClass("collapsed");
      localStorage.setItem("hp_lds_section_collapsed", isCollapsed);
    });

    // Restore saved state on page load
    const savedState = localStorage.getItem("hp_lds_section_collapsed");
    if (savedState === "true") {
      const $ldsHeader = $(".lds-events-card .collapsible-header");
      const $ldsContent = $("#lds-content");
      const $icon = $ldsHeader.find(".toggle-icon");

      $ldsHeader.addClass("collapsed");
      $ldsContent.addClass("collapsed");
      $icon
        .removeClass("dashicons-arrow-down")
        .addClass("dashicons-arrow-right");
    }
  }
})(jQuery);
