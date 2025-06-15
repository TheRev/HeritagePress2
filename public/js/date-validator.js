/**
 * HeritagePress Date Validator JavaScript
 *
 * Provides real-time date validation and user feedback
 */

(function ($) {
  "use strict";

  var DateValidator = {
    /**
     * Initialize the date validator
     */
    init: function () {
      this.bindEvents();
      this.validateExistingFields();
    },

    /**
     * Bind validation events
     */
    bindEvents: function () {
      var self = this;

      // Real-time validation on input
      $(document).on(
        "input keyup paste",
        '[data-hp-date-field="true"]',
        function () {
          var $field = $(this);
          self.debounce(function () {
            self.validateField($field);
          }, 300)();
        }
      );

      // Example click handlers
      $(document).on("click", ".hp-date-example", function (e) {
        e.preventDefault();
        var example = $(this).data("example");
        var $field = $(this)
          .closest(".hp-date-field-wrapper")
          .find(".hp-date-input");
        $field.val(example).trigger("input");
      });

      // Clear validation on focus
      $(document).on("focus", '[data-hp-date-field="true"]', function () {
        var $field = $(this);
        self.clearValidation($field);
      });
    },

    /**
     * Validate existing fields on page load
     */
    validateExistingFields: function () {
      var self = this;
      $('[data-hp-date-field="true"]').each(function () {
        var $field = $(this);
        if ($field.val().trim()) {
          self.validateField($field);
        }
      });
    },

    /**
     * Validate a specific field
     */
    validateField: function ($field) {
      var self = this;
      var dateString = $field.val().trim();
      var $feedback = $("#" + $field.attr("id") + "-feedback");

      // Clear previous state
      self.clearValidation($field);

      if (!dateString) {
        return;
      }

      // Show loading state
      $feedback.addClass("hp-date-validating");
      $field.addClass("hp-date-validating");

      // AJAX validation
      $.ajax({
        url: hp_date_ajax.ajax_url,
        type: "POST",
        data: {
          action: "hp_validate_date",
          date_string: dateString,
          nonce: hp_date_ajax.nonce,
        },
        success: function (response) {
          if (response.success) {
            self.showValidationResult($field, response.data);
          } else {
            self.showError($field, "Validation failed");
          }
        },
        error: function () {
          self.showError($field, "Connection error");
        },
        complete: function () {
          $feedback.removeClass("hp-date-validating");
          $field.removeClass("hp-date-validating");
        },
      });
    },

    /**
     * Show validation result
     */
    showValidationResult: function ($field, data) {
      var $feedback = $("#" + $field.attr("id") + "-feedback");
      var $status = $feedback.find(".hp-date-status");
      var $suggestions = $feedback.find(".hp-date-suggestions");
      var $warnings = $feedback.find(".hp-date-warnings");

      // Update field classes
      $field.removeClass("hp-date-valid hp-date-invalid hp-date-warning");

      if (data.is_valid) {
        $field.addClass("hp-date-valid");
        $status.html('<i class="hp-icon-check"></i> ' + data.message);
        $status.removeClass("error warning").addClass("success");

        // Show formatted date if different
        if (data.formatted && data.formatted !== $field.val()) {
          $status.append(
            ' <span class="hp-formatted">(' + data.formatted + ")</span>"
          );
        }

        // Show precision info
        if (data.precision) {
          $status.append(
            ' <span class="hp-precision">[' +
              data.precision +
              " precision]</span>"
          );
        }
      } else {
        $field.addClass("hp-date-invalid");
        $status.html('<i class="hp-icon-times"></i> ' + data.message);
        $status.removeClass("success warning").addClass("error");
      }

      // Show suggestions
      if (data.suggestions && data.suggestions.length > 0) {
        var suggestionsHtml =
          '<div class="hp-suggestions-title">Suggestions:</div>';
        data.suggestions.forEach(function (suggestion) {
          suggestionsHtml +=
            '<div class="hp-suggestion">' + suggestion + "</div>";
        });
        $suggestions.html(suggestionsHtml).show();
      } else {
        $suggestions.hide();
      }

      // Show warnings
      if (data.warnings && data.warnings.length > 0) {
        $field.addClass("hp-date-warning");
        var warningsHtml = '<div class="hp-warnings-title">Warnings:</div>';
        data.warnings.forEach(function (warning) {
          warningsHtml +=
            '<div class="hp-warning"><i class="hp-icon-warning"></i> ' +
            warning +
            "</div>";
        });
        $warnings.html(warningsHtml).show();
      } else {
        $warnings.hide();
      }

      $feedback.show();
    },

    /**
     * Show error message
     */
    showError: function ($field, message) {
      var $feedback = $("#" + $field.attr("id") + "-feedback");
      var $status = $feedback.find(".hp-date-status");

      $field.removeClass("hp-date-valid hp-date-invalid hp-date-warning");
      $field.addClass("hp-date-invalid");

      $status.html('<i class="hp-icon-times"></i> ' + message);
      $status.removeClass("success warning").addClass("error");

      $feedback.show();
    },

    /**
     * Clear validation state
     */
    clearValidation: function ($field) {
      var $feedback = $("#" + $field.attr("id") + "-feedback");

      $field.removeClass(
        "hp-date-valid hp-date-invalid hp-date-warning hp-date-validating"
      );
      $feedback.removeClass("hp-date-validating").hide();
      $feedback
        .find(".hp-date-status, .hp-date-suggestions, .hp-date-warnings")
        .empty();
    },

    /**
     * Simple debounce function
     */
    debounce: function (func, wait) {
      var timeout;
      return function () {
        var context = this,
          args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function () {
          func.apply(context, args);
        }, wait);
      };
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    if (typeof hp_date_ajax !== "undefined") {
      DateValidator.init();
    }
  });

  // Make DateValidator available globally
  window.HPDateValidator = DateValidator;
})(jQuery);
