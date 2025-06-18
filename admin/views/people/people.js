/**
 * People Management JavaScript
 * Handles all interactive functionality for the People section
 */

(function ($) {
  "use strict"; // Main People object
  window.HeritagePressPeople = {
    initialized: false,
    isToggling: false,
    /**
     * Initialize people management
     */ init: function () {
      console.log("HeritagePressPeople.init() called");

      // Prevent double initialization
      if (this.initialized) {
        console.log("Already initialized, skipping");
        return;
      }

      console.log("Initializing HeritagePressPeople components...");

      this.bindEvents();
      this.initAdvancedSearch();
      this.initBulkActions();
      this.initPersonForms();
      this.initPersonIDHandling();

      this.initialized = true;
      console.log("HeritagePressPeople initialization complete");
    }
    /**
     * Bind event handlers
     */,
    bindEvents: function () {
      var self = this;

      console.log("Binding events for HeritagePressPeople");

      // Unbind ALL existing events to prevent duplicates
      $(document).off("click.hp-people", "#toggle-advanced");
      $("#toggle-advanced").off("click");
      $(document).off("change.hp-people", "#cb-select-all-1, #cb-select-all-2");
      $(document).off("change.hp-people", 'input[name="selected_people[]"]');

      // Advanced search toggle - single handler only
      $(document).on("click.hp-people", "#toggle-advanced", function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        console.log("Advanced search toggle clicked (single handler)");

        // Call toggle function directly without setTimeout to avoid conflicts
        self.toggleAdvancedSearch();
      }); // Select all checkboxes
      $(document).on(
        "change.hp-people",
        "#cb-select-all-1, #cb-select-all-2",
        function () {
          self.toggleSelectAll($(this));
        }
      );

      // Individual checkbox handling
      $(document).on(
        "change.hp-people",
        'input[name="selected_people[]"]',
        function () {
          self.updateSelectAll();
        }
      );

      // Delete person button
      $(document).on("click", ".delete-person", function (e) {
        e.preventDefault();
        self.deletePerson($(this));
      });

      // Form validation
      $(document).on("submit", "#person-form", function (e) {
        return self.validatePersonForm($(this));
      });

      // Bulk actions
      $(document).on("submit", "#bulk-action-form", function (e) {
        return self.handleBulkActions($(this));
      });

      // Auto-generate person ID
      $(document).on("click", ".generate-person-id", function (e) {
        e.preventDefault();
        self.generatePersonID();
      });

      // Check person ID availability
      $(document).on("blur", "#personID", function () {
        self.checkPersonID($(this).val());
      });

      // Tab navigation
      $(document).on("click", ".hp-tabs-nav a", function (e) {
        e.preventDefault();
        self.switchTab($(this));
      });
    },

    /**
     * Initialize advanced search functionality     */
    initAdvancedSearch: function () {
      console.log("Initializing advanced search state");

      // Show advanced options if any are selected
      var hasAdvancedSelected =
        $(
          'input[name="exactmatch"]:checked, input[name="living"]:checked, input[name="private"]:checked, input[name="noparents"]:checked, input[name="nospouse"]:checked, input[name="nokids"]:checked'
        ).length > 0;

      console.log("Advanced options already selected:", hasAdvancedSelected);

      if (hasAdvancedSelected) {
        console.log("Showing advanced options on load");
        $("#advanced-options").show();
        $("#toggle-advanced .dashicons")
          .removeClass("dashicons-arrow-down-alt2")
          .addClass("dashicons-arrow-up-alt2");
      } else {
        console.log("Hiding advanced options on load");
        $("#advanced-options").hide();
        $("#toggle-advanced .dashicons")
          .removeClass("dashicons-arrow-up-alt2")
          .addClass("dashicons-arrow-down-alt2");
      }
    },
    /**
     * Toggle advanced search options
     */ toggleAdvancedSearch: function () {
      console.log("Toggle advanced search called");

      // Prevent multiple rapid calls
      if (this.isToggling) {
        console.log("Already toggling, ignoring call");
        return;
      }
      this.isToggling = true;

      var self = this;
      var $options = $("#advanced-options");
      var $button = $("#toggle-advanced .dashicons");

      console.log("Options element found:", $options.length);
      console.log("Button element found:", $button.length);
      console.log("Current options display:", $options.css("display"));
      console.log("Current options visibility:", $options.is(":visible"));

      if ($options.length === 0) {
        console.error("Advanced options element not found!");
        this.isToggling = false;
        return;
      }

      if ($button.length === 0) {
        console.error("Toggle button dashicon not found!");
        this.isToggling = false;
        return;
      }

      // Check current visibility state more reliably
      var isVisible =
        $options.is(":visible") && $options.css("display") !== "none";

      console.log("Is currently visible:", isVisible);

      if (isVisible) {
        console.log("Hiding advanced options");
        $options.slideUp(300, function () {
          console.log("Hide animation complete");
          self.isToggling = false;
        });
        $button
          .removeClass("dashicons-arrow-up-alt2")
          .addClass("dashicons-arrow-down-alt2");
      } else {
        console.log("Showing advanced options");
        $options.css("display", "flex").slideDown(300, function () {
          console.log("Show animation complete");
          self.isToggling = false;
        });
        $button
          .removeClass("dashicons-arrow-down-alt2")
          .addClass("dashicons-arrow-up-alt2");
      }
    },

    /**
     * Initialize bulk actions
     */
    initBulkActions: function () {
      // Sync bulk action selectors
      $(document).on("change", "#bulk-action-selector-top", function () {
        $("#bulk-action-selector-bottom").val($(this).val());
      });

      $(document).on("change", "#bulk-action-selector-bottom", function () {
        $("#bulk-action-selector-top").val($(this).val());
      });
    },

    /**
     * Handle select all functionality
     */
    toggleSelectAll: function ($checkbox) {
      var checked = $checkbox.is(":checked");
      $('input[name="selected_people[]"]').prop("checked", checked);
      $("#cb-select-all-1, #cb-select-all-2").prop("checked", checked);
    },

    /**
     * Update select all checkboxes based on individual selections
     */
    updateSelectAll: function () {
      var total = $('input[name="selected_people[]"]').length;
      var checked = $('input[name="selected_people[]"]:checked').length;
      var allChecked = total > 0 && total === checked;

      $("#cb-select-all-1, #cb-select-all-2").prop("checked", allChecked);
    },

    /**
     * Handle bulk actions form submission
     */
    handleBulkActions: function ($form) {
      var action = $("#bulk-action-selector-top").val();
      if (action === "-1") {
        action = $("#bulk-action-selector-bottom").val();
      }

      if (action === "-1") {
        alert(hp_people.strings.select_action || "Please select an action.");
        return false;
      }

      var selected = $('input[name="selected_people[]"]:checked').length;
      if (selected === 0) {
        alert(
          hp_people.strings.select_people ||
            "Please select at least one person."
        );
        return false;
      }

      if (action === "delete") {
        return confirm(
          hp_people.strings.confirm_bulk_delete ||
            "Are you sure you want to delete the selected people? This action cannot be undone."
        );
      }

      return true;
    },

    /**
     * Delete individual person
     */
    deletePerson: function ($button) {
      if (
        !confirm(
          hp_people.strings.confirm_delete ||
            "Are you sure you want to delete this person?"
        )
      ) {
        return;
      }

      var personId = $button.data("person-id");
      var tree = $button.data("tree");

      // Show loading
      this.showLoading();

      // Create and submit delete form
      var $form = $('<form method="post">')
        .append($('<input type="hidden" name="action" value="delete_person">'))
        .append($('<input type="hidden" name="personID">').val(personId))
        .append($('<input type="hidden" name="gedcom">').val(tree))
        .append(
          $('<input type="hidden" name="_wpnonce">').val(
            this.getNonce("delete_person")
          )
        );

      $("body").append($form);
      $form.submit();
    },

    /**
     * Initialize person forms
     */
    initPersonForms: function () {
      // Auto-format dates
      $(document).on("blur", 'input[name*="date"]', function () {
        // Basic date formatting can be added here
      });

      // Name formatting helpers
      $(document).on("blur", "#lastname", function () {
        // Auto-capitalize surname if option is set
        var value = $(this).val();
        if (value && $("#capitalize_surnames").is(":checked")) {
          $(this).val(value.toUpperCase());
        }
      });
    },

    /**
     * Person ID generation and checking
     */
    initPersonIDHandling: function () {
      var self = this;

      // Generate Person ID button
      $(document).on("click", "#generate-person-id", function (e) {
        e.preventDefault();
        self.generatePersonID();
      });

      // Check Person ID availability
      $(document).on("click", "#check-person-id", function (e) {
        e.preventDefault();
        self.checkPersonIDAvailability();
      });

      // Auto-check ID when typing (with debounce)
      var idCheckTimeout;
      $(document).on("input", "#personID", function () {
        clearTimeout(idCheckTimeout);
        var personID = $(this).val().trim();
        var originalID = $('input[name="original_personID"]').val();

        if (personID && personID !== originalID && personID.length > 2) {
          idCheckTimeout = setTimeout(function () {
            self.checkPersonIDAvailability(true); // Silent check
          }, 1000);
        }
      });
    },
    /**
     * Generate a new Person ID
     */ generatePersonID: function () {
      var gedcom = $("#gedcom").val();
      if (!gedcom) {
        this.showMessage("Please select a tree first.", "warning");
        return;
      }

      $.post(
        hp_people.ajax_url,
        {
          action: "hp_generate_person_id",
          gedcom: gedcom,
          _wpnonce: hp_people.nonce,
        },
        function (response) {
          if (response.success) {
            $("#personID").val(response.data.personID);
            HeritagePressPeople.showMessage(
              "Person ID generated: " + response.data.personID,
              "success"
            );
          } else {
            HeritagePressPeople.showMessage(
              "Failed to generate Person ID: " +
                (response.data || "Unknown error"),
              "error"
            );
          }
        }
      ).fail(function () {
        HeritagePressPeople.showMessage(
          "Failed to generate Person ID. Please try again.",
          "error"
        );
      });
    },

    /**
     * Check Person ID availability
     */
    checkPersonIDAvailability: function (silent) {
      var personID = $("#personID").val().trim();
      var gedcom = $("#gedcom").val();
      var originalID = $('input[name="original_personID"]').val();

      if (!personID) {
        if (!silent)
          this.showMessage("Please enter a Person ID to check.", "warning");
        return;
      }

      if (personID === originalID) {
        if (!silent)
          this.showMessage(
            "This is the current Person ID for this person.",
            "info"
          );
        return;
      }

      $.post(
        hp_people.ajax_url,
        {
          action: "hp_check_person_id",
          personID: personID,
          gedcom: gedcom,
          _wpnonce: hp_people.nonce,
        },
        function (response) {
          if (response.success) {
            if (response.data.available) {
              if (!silent)
                HeritagePressPeople.showMessage(
                  "Person ID is available.",
                  "success"
                );
              $("#personID")
                .removeClass("id-unavailable")
                .addClass("id-available");
            } else {
              if (!silent)
                HeritagePressPeople.showMessage(
                  "Person ID is already in use. Please choose a different ID.",
                  "error"
                );
              $("#personID")
                .removeClass("id-available")
                .addClass("id-unavailable");
            }
          } else {
            if (!silent)
              HeritagePressPeople.showMessage(
                "Failed to check Person ID availability.",
                "error"
              );
          }
        }
      ).fail(function () {
        if (!silent)
          HeritagePressPeople.showMessage(
            "Failed to check Person ID availability.",
            "error"
          );
      });
    },

    /**
     * Enhanced form validation for add/edit person
     */
    validatePersonForm: function (form) {
      var errors = [];

      // Required fields
      var requiredFields = {
        gedcom: "Tree",
        firstname: "First Name",
        lastname: "Last Name",
        personID: "Person ID",
      };

      $.each(requiredFields, function (field, label) {
        var value = form.find('[name="' + field + '"]').val();
        if (!value || value.trim() === "") {
          errors.push(label + " is required.");
        }
      });

      // Person ID format validation
      var personID = form.find('[name="personID"]').val();
      if (personID && !/^[A-Za-z0-9_-]+$/.test(personID)) {
        errors.push(
          "Person ID can only contain letters, numbers, hyphens, and underscores."
        );
      }

      // Date validation
      var birthDate = form.find('[name="birthdate"]').val();
      var deathDate = form.find('[name="deathdate"]').val();

      if (birthDate && deathDate) {
        // Basic date comparison (simplified)
        var birthYear = this.extractYear(birthDate);
        var deathYear = this.extractYear(deathDate);

        if (birthYear && deathYear && birthYear > deathYear) {
          errors.push("Birth date cannot be after death date.");
        }
      }

      // Living person validation
      var living = form.find('[name="living"]').is(":checked");
      if (living && deathDate) {
        errors.push("Living persons cannot have a death date.");
      }

      return errors;
    },

    /**
     * Extract year from date string (basic implementation)
     */
    extractYear: function (dateStr) {
      var matches = dateStr.match(/\b(19|20)\d{2}\b/);
      return matches ? parseInt(matches[0]) : null;
    },

    /**
     * Duplicate person functionality
     */
    duplicatePerson: function () {
      if (
        confirm(
          "This will create a copy of this person with a new Person ID. Continue?"
        )
      ) {
        var form = $("#edit-person-form");

        // Change action to add_person
        form.find('input[name="action"]').val("add_person");
        form.find('input[name="original_personID"]').remove();
        form.find('input[name="original_gedcom"]').remove();

        // Clear person ID for duplication
        $("#personID").val("");

        form.submit();
      }
    },

    /**
     * Advanced search functionality
     */
    initAdvancedSearch: function () {
      var self = this;

      // Date range validation
      $(document).on("change", 'input[type="date"]', function () {
        self.validateDateRange($(this));
      });

      // Search form validation
      $(document).on("submit", "#advanced-search-form", function (e) {
        return self.validateSearchForm($(this));
      });

      // Save/load search functionality
      $(document).on("click", "#save-search", function (e) {
        e.preventDefault();
        self.saveSearch();
      });

      $(document).on("click", "#load-search", function (e) {
        e.preventDefault();
        self.loadSearch();
      });
    },

    /**
     * Validate date ranges in search form
     */
    validateDateRange: function (input) {
      var fieldName = input.attr("name");
      var value = input.val();

      if (fieldName.includes("_from")) {
        var toField = fieldName.replace("_from", "_to");
        var toValue = $('[name="' + toField + '"]').val();

        if (value && toValue && value > toValue) {
          this.showMessage("From date cannot be later than to date.", "error");
          input.val("");
        }
      } else if (fieldName.includes("_to")) {
        var fromField = fieldName.replace("_to", "_from");
        var fromValue = $('[name="' + fromField + '"]').val();

        if (value && fromValue && fromValue > value) {
          this.showMessage(
            "To date cannot be earlier than from date.",
            "error"
          );
          input.val("");
        }
      }
    },

    /**
     * Validate search form
     */
    validateSearchForm: function (form) {
      var hasValue = false;

      form
        .find('input[type="text"], input[type="date"], select')
        .each(function () {
          if ($(this).val().trim() !== "") {
            hasValue = true;
            return false; // break loop
          }
        });

      if (!hasValue) {
        this.showMessage(
          "Please enter at least one search criterion.",
          "warning"
        );
        return false;
      }

      return true;
    },

    /**
     * Save search functionality (placeholder)
     */
    saveSearch: function () {
      var searchName = prompt("Enter a name for this search:");
      if (searchName) {
        // TODO: Implement save search functionality
        this.showMessage(
          "Search saving functionality will be available in a future update.",
          "info"
        );
      }
    },

    /**
     * Load search functionality (placeholder)
     */
    loadSearch: function () {
      // TODO: Implement load search functionality
      this.showMessage(
        "Saved search loading functionality will be available in a future update.",
        "info"
      );
    },

    /**
     * Report functionality
     */
    initReports: function () {
      var self = this;

      // Export report
      $(document).on("click", "#export-report", function (e) {
        e.preventDefault();
        self.exportReport();
      });

      // Print report
      $(document).on("click", "#print-report", function (e) {
        e.preventDefault();
        self.printReport();
      });
    },
    /**
     * Export report functionality
     */ exportReport: function () {
      var reportType = $("#report").val();
      var tree = $("#tree").val();

      var exportUrl = hp_people.ajax_url + "?action=hp_export_people_report";
      exportUrl += "&report=" + encodeURIComponent(reportType);
      if (tree) {
        exportUrl += "&tree=" + encodeURIComponent(tree);
      }
      exportUrl += "&_wpnonce=" + encodeURIComponent(hp_people.nonce);

      window.open(exportUrl, "_blank");
    },

    /**
     * Print report functionality
     */
    printReport: function () {
      var printContent = $(".report-content-section").html();
      var printWindow = window.open("", "print", "width=800,height=600");

      printWindow.document.write("<html><head><title>People Report</title>");
      printWindow.document.write("<style>");
      printWindow.document.write(
        "body { font-family: Arial, sans-serif; margin: 20px; }"
      );
      printWindow.document.write(
        "table { border-collapse: collapse; width: 100%; }"
      );
      printWindow.document.write(
        "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }"
      );
      printWindow.document.write("th { background-color: #f2f2f2; }");
      printWindow.document.write(
        ".stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }"
      );
      printWindow.document.write(
        ".stat-item { border: 1px solid #ddd; padding: 15px; text-align: center; }"
      );
      printWindow.document.write("</style>");
      printWindow.document.write("</head><body>");
      printWindow.document.write(printContent);
      printWindow.document.write("</body></html>");

      printWindow.document.close();
      printWindow.print();
    },

    /**
     * Utilities functionality
     */
    initUtilities: function () {
      var self = this;

      // Utility execution
      $(document).on("click", ".run-utility", function (e) {
        e.preventDefault();
        var utility = $(this).data("utility");
        self.showUtilityModal(utility);
      });

      // Utility info
      $(document).on("click", ".utility-info", function (e) {
        e.preventDefault();
        var utility = $(this).data("utility");
        self.showUtilityInfo(utility);
      });

      // Modal actions
      $(document).on("click", "#confirm-utility", function (e) {
        e.preventDefault();
        self.runUtility();
      });

      $(document).on("click", ".cancel-utility, .close-modal", function (e) {
        e.preventDefault();
        $(".utility-modal").hide();
      });
    },

    /**
     * Show utility confirmation modal
     */
    showUtilityModal: function (utility) {
      var utilityCard = $('[data-utility="' + utility + '"]');
      var title = utilityCard.find("h5").text();
      var description = utilityCard.find("p").text();

      $("#modal-title").text(title);
      $("#modal-description").html("<p>" + description + "</p>");

      // Add warnings for destructive utilities
      var destructiveUtilities = [
        "merge_people",
        "cleanup_orphans",
        "fix_dates",
      ];
      if (destructiveUtilities.includes(utility)) {
        $("#modal-description").append(
          '<div class="warning"><strong>Warning:</strong> This utility may make permanent changes to your data.</div>'
        );
      }

      $("#utility-modal").data("utility", utility).show();
    },
    /**
     * Run utility
     */ runUtility: function () {
      if (!$("#confirm-backup").is(":checked")) {
        this.showMessage(
          "Please confirm that you have created a backup before proceeding.",
          "warning"
        );
        return;
      }

      var utility = $("#utility-modal").data("utility");
      var tree = $("#modal-tree").val();

      $("#utility-modal").hide();
      $("#progress-modal").show();

      $.post(
        hp_people.ajax_url,
        {
          action: "hp_run_people_utility",
          utility: utility,
          tree: tree,
          _wpnonce: hp_people.nonce,
        },
        function (response) {
          $("#progress-modal").hide();

          if (response.success) {
            $("#results-content").html(response.data.report);
            $("#utility-results").show();
          } else {
            HeritagePressPeople.showMessage(
              "Utility execution failed: " + (response.data || "Unknown error"),
              "error"
            );
          }
        }
      ).fail(function () {
        $("#progress-modal").hide();
        HeritagePressPeople.showMessage(
          "Failed to run utility. Please try again.",
          "error"
        );
      });
    },

    /**
     * Show utility information
     */
    showUtilityInfo: function (utility) {
      var info = this.getUtilityInfo(utility);
      $("#results-content").html(info);
      $("#utility-results").show();
    },

    /**
     * Get utility information content
     */
    getUtilityInfo: function (utility) {
      var infoContent = {
        reindex_names:
          "<h5>Reindex Names</h5><p>This utility rebuilds the search indexes for person names, improving search performance and accuracy. It processes all name fields including soundex codes.</p>",
        check_duplicates:
          "<h5>Find Duplicates</h5><p>Scans for potential duplicate person records based on name similarity, dates, and other criteria. Results are provided for manual review.</p>",
        fix_dates:
          "<h5>Standardize Dates</h5><p>Converts dates to standard format and fixes common issues like invalid dates, inconsistent formatting, and missing date components.</p>",
        update_soundex:
          "<h5>Update Soundex</h5><p>Generates Soundex codes for all names to improve phonetic name searching. Useful for finding variant spellings of names.</p>",
        merge_people:
          "<h5>Merge People</h5><p>Provides tools to merge duplicate or related person records while preserving all associated data like events, sources, and media.</p>",
        bulk_privacy:
          "<h5>Bulk Privacy Update</h5><p>Updates privacy settings for multiple people based on criteria like living status, birth year, or other factors.</p>",
        cleanup_orphans:
          "<h5>Clean Orphaned Data</h5><p>Removes orphaned records and fixes data consistency issues. This includes unused media references, broken family links, and invalid relationships.</p>",
        export_people:
          "<h5>Export People Data</h5><p>Exports people data in various formats including CSV for spreadsheets, Excel files, or GEDCOM for genealogy programs.</p>",
        import_corrections:
          "<h5>Import Corrections</h5><p>Imports bulk corrections from properly formatted spreadsheet files. Useful for making many changes at once.</p>",
        verify_relationships:
          "<h5>Verify Relationships</h5><p>Checks family relationships for logical consistency, such as impossible birth dates, circular relationships, and missing connections.</p>",
      };

      return (
        infoContent[utility] ||
        "<p>Information not available for this utility.</p>"
      );
    },

    /**
     * Show message to user
     */
    showMessage: function (message, type) {
      type = type || "info";

      // Remove existing messages
      $(".hp-message").remove();

      var messageClass = "notice notice-" + type;
      if (type === "error") messageClass = "notice notice-error";
      if (type === "success") messageClass = "notice notice-success";
      if (type === "warning") messageClass = "notice notice-warning";
      if (type === "info") messageClass = "notice notice-info";

      var messageHtml =
        '<div class="hp-message ' +
        messageClass +
        ' is-dismissible"><p>' +
        message +
        "</p></div>";

      // Add to top of current tab content
      $(".tab-content").prepend(messageHtml);

      // Auto-dismiss after 5 seconds
      setTimeout(function () {
        $(".hp-message").fadeOut(function () {
          $(this).remove();
        });
      }, 5000);
    },

    /**
     * Unsaved changes warning
     */
    initUnsavedChangesWarning: function () {
      var formChanged = false;

      $(document).on(
        "change",
        "#add-person-form input, #add-person-form select, #add-person-form textarea, #edit-person-form input, #edit-person-form select, #edit-person-form textarea",
        function () {
          formChanged = true;
        }
      );

      $(window).on("beforeunload", function () {
        if (formChanged) {
          return "You have unsaved changes. Are you sure you want to leave?";
        }
      });
      $(document).on(
        "submit",
        "#add-person-form, #edit-person-form",
        function () {
          formChanged = false; // Don't warn when actually submitting
        }
      );
    },

    /**
     * Get utility functions for generating nonces
     */
    getNonce: function (action) {
      return hp_people.nonce;
    },

    /**
     * Show loading indicator
     */
    showLoading: function () {
      // Add loading class or spinner
      $("body").addClass("hp-loading");
    },

    /**
     * Hide loading indicator
     */
    hideLoading: function () {
      $("body").removeClass("hp-loading");
    },

    /**
     * Switch tabs
     */
    switchTab: function ($link) {
      var tab = $link.data("tab");
      window.location.href =
        window.location.pathname + "?page=heritagepress-people&tab=" + tab;
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    HeritagePressPeople.init();
    HeritagePressPeople.initUnsavedChangesWarning();
  });
})(jQuery);

// Person ID generation patterns (utility object)
window.PersonIDGenerator = {
  patterns: {
    standard: function (num) {
      return "I" + num;
    },
    padded: function (num) {
      return "I" + num.toString().padStart(4, "0");
    },
    custom: function (num, prefix) {
      return (prefix || "I") + num;
    },
  },

  generate: function (pattern, num, prefix) {
    if (this.patterns[pattern]) {
      return this.patterns[pattern](num, prefix);
    }
    return this.patterns.standard(num);
  },
};

// Date formatting utilities
window.DateFormatter = {
  /**
   * Format date for display
   */
  format: function (dateString, format) {
    if (!dateString) return "";

    format = format || "display";

    switch (format) {
      case "display":
        return this.formatForDisplay(dateString);
      case "sort":
        return this.formatForSorting(dateString);
      default:
        return dateString;
    }
  },

  /**
   * Format date for display
   */
  formatForDisplay: function (dateString) {
    // Handle common genealogy date formats
    var patterns = {
      abt: "About",
      bef: "Before",
      aft: "After",
      bet: "Between",
    };

    var formatted = dateString.toLowerCase();
    Object.keys(patterns).forEach(function (key) {
      formatted = formatted.replace(
        new RegExp("^" + key + " "),
        patterns[key] + " "
      );
    });

    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
  },

  /**
   * Format date for sorting
   */
  formatForSorting: function (dateString) {
    // Convert to sortable format (YYYY-MM-DD)
    // This is a simplified version - full implementation would handle various formats
    var year = dateString.match(/\b(\d{4})\b/);
    return year ? year[1] + "-01-01" : "0000-01-01";
  },
};

// Name formatting utilities
window.NameFormatter = {
  /**
   * Format person name for display
   */
  format: function (person, format) {
    format = format || "full";

    switch (format) {
      case "full":
        return this.formatFull(person);
      case "last_first":
        return this.formatLastFirst(person);
      case "first_last":
        return this.formatFirstLast(person);
      default:
        return this.formatFull(person);
    }
  },

  /**
   * Format full name with all parts
   */
  formatFull: function (person) {
    var parts = [];

    if (person.prefix) parts.push(person.prefix);
    if (person.firstname) parts.push(person.firstname);
    if (person.lnprefix) parts.push(person.lnprefix);
    if (person.lastname) parts.push(person.lastname);
    if (person.suffix) parts.push(person.suffix);

    var name = parts.join(" ");

    if (person.nickname) {
      name += ' "' + person.nickname + '"';
    }

    return name;
  },

  /**
   * Format as "Last, First"
   */
  formatLastFirst: function (person) {
    var lastName = [person.lnprefix, person.lastname].filter(Boolean).join(" ");
    var firstName = [person.prefix, person.firstname, person.suffix]
      .filter(Boolean)
      .join(" ");

    if (lastName && firstName) {
      return lastName + ", " + firstName;
    } else if (lastName) {
      return lastName;
    } else if (firstName) {
      return firstName;
    }

    return "Unknown";
  },

  /**
   * Format as "First Last"
   */
  formatFirstLast: function (person) {
    var parts = [];

    if (person.prefix) parts.push(person.prefix);
    if (person.firstname) parts.push(person.firstname);
    if (person.lnprefix) parts.push(person.lnprefix);
    if (person.lastname) parts.push(person.lastname);
    if (person.suffix) parts.push(person.suffix);

    return parts.join(" ") || "Unknown";
  },
};
