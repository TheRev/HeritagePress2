/**
 * HeritagePress Public JavaScript
 */

(function ($) {
  "use strict";

  /**
   * Document ready
   */
  $(document).ready(function () {
    initSearch();
    initTimeline();
    initFamilyTree();
    initLazyLoading();
  });

  /**
   * Initialize search functionality
   */
  function initSearch() {
    var $searchForm = $(".hp-search-form form");
    var $searchResults = $(".hp-search-results");

    if ($searchForm.length) {
      $searchForm.on("submit", function (e) {
        e.preventDefault();
        performSearch();
      });

      // Auto-search on input with debounce
      var searchTimeout;
      $searchForm.find('input[type="text"]').on("input", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
          if ($searchForm.find('input[type="text"]').val().length >= 3) {
            performSearch();
          }
        }, 300);
      });
    }

    function performSearch() {
      var formData = $searchForm.serialize();

      $searchResults.html(
        '<div class="hp-loading"><span class="hp-spinner"></span> Searching...</div>'
      );

      $.ajax({
        url: heritagepress_public.ajax_url,
        type: "POST",
        data:
          formData +
          "&action=hp_search_genealogy&nonce=" +
          heritagepress_public.nonce,
        success: function (response) {
          if (response.success) {
            $searchResults.html(response.data.html);
            initPagination();
          } else {
            $searchResults.html(
              '<div class="hp-error">Search failed. Please try again.</div>'
            );
          }
        },
        error: function () {
          $searchResults.html(
            '<div class="hp-error">Search failed. Please try again.</div>'
          );
        },
      });
    }
  }

  /**
   * Initialize pagination
   */
  function initPagination() {
    $(".hp-pagination a").on("click", function (e) {
      e.preventDefault();

      var url = $(this).attr("href");
      var page = getUrlParameter(url, "paged") || 1;

      loadPage(page);
    });
  }

  /**
   * Load specific page
   */
  function loadPage(page) {
    var $searchForm = $(".hp-search-form form");
    var $searchResults = $(".hp-search-results");
    var formData = $searchForm.serialize();

    $searchResults.html(
      '<div class="hp-loading"><span class="hp-spinner"></span> Loading...</div>'
    );

    $.ajax({
      url: heritagepress_public.ajax_url,
      type: "POST",
      data:
        formData +
        "&action=hp_search_genealogy&paged=" +
        page +
        "&nonce=" +
        heritagepress_public.nonce,
      success: function (response) {
        if (response.success) {
          $searchResults.html(response.data.html);
          initPagination();

          // Scroll to results
          $("html, body").animate(
            {
              scrollTop: $searchResults.offset().top - 20,
            },
            500
          );
        }
      },
    });
  }

  /**
   * Initialize timeline functionality
   */
  function initTimeline() {
    var $timeline = $(".hp-timeline");

    if ($timeline.length) {
      // Animate timeline items on scroll
      $(window).on("scroll", function () {
        $timeline.find(".hp-timeline-item").each(function () {
          var $item = $(this);
          var itemTop = $item.offset().top;
          var itemBottom = itemTop + $item.outerHeight();
          var windowTop = $(window).scrollTop();
          var windowBottom = windowTop + $(window).height();

          if (itemBottom > windowTop && itemTop < windowBottom) {
            $item.addClass("hp-visible");
          }
        });
      });

      // Trigger scroll event on load
      $(window).trigger("scroll");
    }
  }

  /**
   * Initialize family tree functionality
   */
  function initFamilyTree() {
    $(".hp-person-card").on("click", function () {
      var personId = $(this).data("person-id");
      if (personId) {
        loadPersonDetails(personId);
      }
    });

    function loadPersonDetails(personId) {
      // Create modal or expand details
      var $modal = $('<div class="hp-modal">')
        .html(
          '<div class="hp-modal-content"><div class="hp-loading"><span class="hp-spinner"></span> Loading person details...</div></div>'
        )
        .appendTo("body");

      $.ajax({
        url: heritagepress_public.ajax_url,
        type: "POST",
        data: {
          action: "hp_get_person_details",
          person_id: personId,
          nonce: heritagepress_public.nonce,
        },
        success: function (response) {
          if (response.success) {
            $modal.find(".hp-modal-content").html(response.data.html);
          } else {
            $modal
              .find(".hp-modal-content")
              .html(
                '<div class="hp-error">Failed to load person details.</div>'
              );
          }
        },
        error: function () {
          $modal
            .find(".hp-modal-content")
            .html('<div class="hp-error">Failed to load person details.</div>');
        },
      });

      // Close modal on click outside or escape key
      $modal.on("click", function (e) {
        if (e.target === this) {
          $modal.remove();
        }
      });

      $(document).on("keydown.hp-modal", function (e) {
        if (e.keyCode === 27) {
          // Escape key
          $modal.remove();
          $(document).off("keydown.hp-modal");
        }
      });
    }
  }

  /**
   * Initialize lazy loading for images
   */
  function initLazyLoading() {
    var $lazyImages = $(".hp-lazy-image");

    if ($lazyImages.length && "IntersectionObserver" in window) {
      var imageObserver = new IntersectionObserver(function (
        entries,
        observer
      ) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var $img = $(entry.target);
            $img.attr("src", $img.data("src"));
            $img.removeClass("hp-lazy-image");
            observer.unobserve(entry.target);
          }
        });
      });

      $lazyImages.each(function () {
        imageObserver.observe(this);
      });
    } else {
      // Fallback for browsers without IntersectionObserver
      $lazyImages.each(function () {
        var $img = $(this);
        $img.attr("src", $img.data("src"));
        $img.removeClass("hp-lazy-image");
      });
    }
  }

  /**
   * Utility function to get URL parameter
   */
  function getUrlParameter(url, param) {
    var urlParams = new URLSearchParams(url.split("?")[1]);
    return urlParams.get(param);
  }

  /**
   * Global HeritagePress object
   */
  window.HeritagePress = window.HeritagePress || {};

  window.HeritagePress.public = {
    /**
     * Show notification
     */
    showNotification: function (message, type) {
      type = type || "info";

      var $notification = $(
        '<div class="hp-notification hp-notification-' + type + '">'
      )
        .text(message)
        .appendTo("body");

      setTimeout(function () {
        $notification.addClass("hp-notification-show");
      }, 100);

      setTimeout(function () {
        $notification.removeClass("hp-notification-show");
        setTimeout(function () {
          $notification.remove();
        }, 300);
      }, 3000);
    },

    /**
     * Refresh search results
     */
    refreshSearch: function () {
      var $searchForm = $(".hp-search-form form");
      if ($searchForm.length) {
        $searchForm.trigger("submit");
      }
    },
  };
})(jQuery);
