/**
 * HeritagePress Admin JavaScript
 */

(function ($) {
  "use strict";

  /**
   * Document ready
   */
  $(document).ready(function () {
    initTableManagement();
    initProgressBars();
    initConfirmDialogs();
    initTooltips();
  });

  /**
   * Initialize table management functionality
   */
  function initTableManagement() {
    // Handle table creation
    $("#create-tables-btn").on("click", function (e) {
      e.preventDefault();

      if (
        !confirm(
          "Are you sure you want to create all database tables? This may take a few moments."
        )
      ) {
        return;
      }

      var $btn = $(this);
      var originalText = $btn.text();

      $btn
        .prop("disabled", true)
        .html('<span class="hp-loading"></span> Creating Tables...');

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "hp_create_tables",
          nonce: heritagepress_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            showNotice("Tables created successfully!", "success");
            location.reload();
          } else {
            showNotice("Error creating tables: " + response.data, "error");
          }
        },
        error: function () {
          showNotice("Error creating tables. Please try again.", "error");
        },
        complete: function () {
          $btn.prop("disabled", false).text(originalText);
        },
      });
    });

    // Handle table updates
    $("#update-tables-btn").on("click", function (e) {
      e.preventDefault();

      if (!confirm("Are you sure you want to update the database tables?")) {
        return;
      }

      var $btn = $(this);
      var originalText = $btn.text();

      $btn
        .prop("disabled", true)
        .html('<span class="hp-loading"></span> Updating Tables...');

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "hp_update_tables",
          nonce: heritagepress_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            showNotice("Tables updated successfully!", "success");
            location.reload();
          } else {
            showNotice("Error updating tables: " + response.data, "error");
          }
        },
        error: function () {
          showNotice("Error updating tables. Please try again.", "error");
        },
        complete: function () {
          $btn.prop("disabled", false).text(originalText);
        },
      });
    });

    // Handle table deletion
    $("#drop-tables-btn").on("click", function (e) {
      e.preventDefault();

      if (
        !confirm(
          "WARNING: This will permanently delete ALL genealogy data! Are you absolutely sure?"
        )
      ) {
        return;
      }

      if (!confirm('This action cannot be undone. Type "DELETE" to confirm.')) {
        return;
      }

      var $btn = $(this);
      var originalText = $btn.text();

      $btn
        .prop("disabled", true)
        .html('<span class="hp-loading"></span> Dropping Tables...');

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "hp_drop_tables",
          nonce: heritagepress_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            showNotice("Tables dropped successfully!", "success");
            location.reload();
          } else {
            showNotice("Error dropping tables: " + response.data, "error");
          }
        },
        error: function () {
          showNotice("Error dropping tables. Please try again.", "error");
        },
        complete: function () {
          $btn.prop("disabled", false).text(originalText);
        },
      });
    });
  }

  /**
   * Initialize progress bars
   */
  function initProgressBars() {
    $(".hp-progress-bar").each(function () {
      var $bar = $(this);
      var targetWidth = $bar.data("width") || 0;

      setTimeout(function () {
        $bar.css("width", targetWidth + "%");
      }, 100);
    });
  }

  /**
   * Initialize confirm dialogs
   */
  function initConfirmDialogs() {
    $("[data-confirm]").on("click", function (e) {
      var message = $(this).data("confirm");
      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }
    });
  }

  /**
   * Initialize tooltips
   */
  function initTooltips() {
    $("[data-tooltip]").each(function () {
      $(this).attr("title", $(this).data("tooltip"));
    });
  }

  /**
   * Show admin notice
   */
  function showNotice(message, type) {
    type = type || "info";

    var $notice = $(
      '<div class="notice notice-' +
        type +
        " is-dismissible hp-notice " +
        type +
        '">'
    )
      .append("<p>" + message + "</p>")
      .append(
        '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
      );

    $(".wrap.heritagepress").prepend($notice);

    // Auto-dismiss after 5 seconds for success messages
    if (type === "success") {
      setTimeout(function () {
        $notice.fadeOut(function () {
          $(this).remove();
        });
      }, 5000);
    }

    // Handle dismiss button
    $notice.find(".notice-dismiss").on("click", function () {
      $notice.fadeOut(function () {
        $(this).remove();
      });
    });
  }

  /**
   * Utility functions
   */
  window.HeritagePress = window.HeritagePress || {};

  window.HeritagePress.admin = {
    showNotice: showNotice,

    /**
     * Refresh table stats
     */
    refreshStats: function () {
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "hp_get_table_stats",
          nonce: heritagepress_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            updateStatsDisplay(response.data);
          }
        },
      });
    },
  };

  /**
   * Update stats display
   */
  function updateStatsDisplay(stats) {
    $.each(stats, function (table, count) {
      var $statElement = $(
        '.hp-stat-item[data-table="' + table + '"] .hp-stat-number'
      );
      if ($statElement.length) {
        $statElement.text(count);
      }
    });
  }
})(jQuery);
