/**
 * Enhanced Browse People Table Interactions
 * Provides better user feedback for sorting and table interactions
 */

jQuery(document).ready(function ($) {
  // Add loading state when sorting
  $(".manage-column.sortable a, .manage-column.sorted a").on(
    "click",
    function (e) {
      const $link = $(this);
      const $indicator = $link.find(".sorting-indicator");

      // Add loading state
      $link.addClass("sorting-loading");
      $indicator
        .removeClass(
          "dashicons-sort dashicons-arrow-up-alt2 dashicons-arrow-down-alt2"
        )
        .addClass("dashicons-update");

      // Show loading message
      const $loadingMsg = $(
        '<div class="sorting-loading-message">Sorting...</div>'
      );
      $("body").append($loadingMsg);

      setTimeout(function () {
        $loadingMsg.remove();
      }, 5000); // Fallback removal
    }
  );

  // Enhanced row hover effects with better date visibility
  $(".people-table tbody tr").hover(
    function () {
      $(this).addClass("row-hover");
      // Highlight date fields on hover
      $(this).find(".column-birth, .column-death").addClass("date-highlight");
    },
    function () {
      $(this).removeClass("row-hover");
      $(this)
        .find(".column-birth, .column-death")
        .removeClass("date-highlight");
    }
  );

  // Add keyboard navigation for sortable headers
  $(".manage-column.sortable a, .manage-column.sorted a").on(
    "keydown",
    function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        $(this)[0].click();
      }
    }
  );

  // Show sort direction in a more prominent way
  $(".manage-column.sorted").each(function () {
    const $col = $(this);
    const $indicator = $col.find(".sorting-indicator");

    if ($indicator.hasClass("dashicons-arrow-up-alt2")) {
      $col.attr("title", "Sorted ascending - click to sort descending");
    } else if ($indicator.hasClass("dashicons-arrow-down-alt2")) {
      $col.attr("title", "Sorted descending - click to sort ascending");
    }
  });

  // Add visual feedback for date range filtering
  $(".date-range-picker").on("input", function () {
    const $input = $(this);
    const value = $input.val();

    if (value.length > 0) {
      $input.addClass("has-filter");
    } else {
      $input.removeClass("has-filter");
    }
  });

  // Initialize filter state on page load
  $(".date-range-picker").each(function () {
    const $input = $(this);
    if ($input.val().length > 0) {
      $input.addClass("has-filter");
    }
  });
});
