/**
 * Citation Modal JavaScript
 * Replicates HeritagePress admin_citations.php AJAX popup functionality
 * Based on HeritagePress citation management modal
 */

(function ($) {
  "use strict";

  // Global citation modal object
  window.HeritagePress = window.HeritagePress || {};
  window.HeritagePress.Citations = window.HeritagePress.Citations || {};

  // Citation modal functionality
  var CitationModal = {
    currentPersfamID: null,
    currentTree: null,
    currentEventID: null,
    currentNoteID: null,
    modalContainer: null,
    prevsection: null,
    subpage: false,

    // Initialize citation modal
    init: function () {
      this.createModalContainer();
      this.bindEvents();
    },

    // Create modal container
    createModalContainer: function () {
      if ($("#citation-modal-container").length === 0) {
        $("body").append(`
                    <div id="citation-modal-container" class="citation-modal-overlay" style="display:none;">
                        <div class="citation-modal-content">
                            <div class="citation-modal-header">
                                <h3 class="citation-modal-title">Citations</h3>
                                <button type="button" class="citation-modal-close">&times;</button>
                            </div>
                            <div class="citation-modal-body">
                                <!-- Content loaded dynamically -->
                            </div>
                        </div>
                    </div>
                `);
        this.modalContainer = $("#citation-modal-container");
      }
    },

    // Bind events
    bindEvents: function () {
      var self = this;

      // Close modal events
      $(document).on(
        "click",
        ".citation-modal-close, .citation-modal-overlay",
        function (e) {
          if (e.target === this) {
            self.closeModal();
          }
        }
      );

      // Escape key to close
      $(document).on("keydown", function (e) {
        if (
          e.keyCode === 27 &&
          self.modalContainer &&
          self.modalContainer.is(":visible")
        ) {
          self.closeModal();
        }
      });

      // Citation action buttons
      $(document).on("click", ".citation-finish-btn", function () {
        self.closeModal();
      });

      $(document).on("click", ".citation-add-btn", function () {
        self.showAddCitationForm();
      });
    },

    // Open citation modal for person/family/event
    openCitationModal: function (persfamID, tree, eventID, noteID) {
      this.currentPersfamID = persfamID;
      this.currentTree = tree;
      this.currentEventID = eventID || "";
      this.currentNoteID = noteID || "";

      this.loadCitations();
      this.showModal();
    },

    // Load citations for current context
    loadCitations: function () {
      var self = this;
      var data = {
        action: "hp_get_citations_modal",
        nonce: heritagepress_ajax.nonce,
        persfamID: this.currentPersfamID,
        tree: this.currentTree,
        eventID: this.currentEventID,
        noteID: this.currentNoteID,
      };

      $.post(ajaxurl, data, function (response) {
        if (response.success) {
          self.modalContainer
            .find(".citation-modal-body")
            .html(response.data.html);
          self.updateModalTitle(response.data.eventTypeDesc || "General");
          self.initializeSortable();
        } else {
          self.modalContainer
            .find(".citation-modal-body")
            .html("<p>Error loading citations: " + response.data + "</p>");
        }
      });
    },

    // Update modal title
    updateModalTitle: function (eventTypeDesc) {
      this.modalContainer
        .find(".citation-modal-title")
        .text("Citations: " + eventTypeDesc);
    },

    // Show modal
    showModal: function () {
      this.modalContainer.fadeIn(200);
      $("body").addClass("citation-modal-open");
    },

    // Close modal
    closeModal: function () {
      this.modalContainer.fadeOut(200);
      $("body").removeClass("citation-modal-open");
      this.resetModal();
    },

    // Reset modal state
    resetModal: function () {
      this.currentPersfamID = null;
      this.currentTree = null;
      this.currentEventID = null;
      this.currentNoteID = null;
      this.modalContainer.find(".citation-modal-body").empty();
      this.subpage = false;
    },

    // Show add citation form
    showAddCitationForm: function () {
      this.gotoSection("citations", "addcitation");
    },

    // Navigate between sections (replicates HeritagePress gotoSection)
    gotoSection: function (from, to) {
      var fromEl = $("#" + from);
      var toEl = $("#" + to);

      if (fromEl.length && toEl.length) {
        fromEl.hide();
        toEl.show();
        this.prevsection = from;
      } else if (to === "addcitation") {
        this.loadAddCitationForm();
      }
    },

    // Load add citation form
    loadAddCitationForm: function () {
      var self = this;
      var data = {
        action: "hp_load_add_citation_form",
        nonce: heritagepress_ajax.nonce,
        persfamID: this.currentPersfamID,
        tree: this.currentTree,
        eventID: this.currentEventID,
      };

      $.post(ajaxurl, data, function (response) {
        if (response.success) {
          self.modalContainer
            .find(".citation-modal-body")
            .html(response.data.html);
        } else {
          alert("Error loading citation form: " + response.data);
        }
      });
    },

    // Initialize sortable citations
    initializeSortable: function () {
      if (typeof $.fn.sortable !== "undefined") {
        $("#citationstblbody").sortable({
          items: ".sortrow",
          handle: ".dragarea",
          update: function (event, ui) {
            CitationModal.updateCitationOrder();
          },
        });
      }
    },

    // Update citation order after drag/drop
    updateCitationOrder: function () {
      var citationIds = [];
      $(".sortrow").each(function () {
        var id = $(this).attr("id").replace("citations_", "");
        citationIds.push(id);
      });

      $.post(ajaxurl, {
        action: "hp_update_citation_order",
        nonce: heritagepress_ajax.nonce,
        citationIds: citationIds,
      });
    },
  };

  // Citation management functions (global to match HeritagePress)
  window.editCitation = function (citationID) {
    var data = {
      action: "hp_load_edit_citation_form",
      nonce: heritagepress_ajax.nonce,
      citationID: citationID,
    };

    $.post(ajaxurl, data, function (response) {
      if (response.success) {
        CitationModal.modalContainer
          .find(".citation-modal-body")
          .html(response.data.html);
      } else {
        alert("Error loading citation form: " + response.data);
      }
    });
    return false;
  };

  window.deleteCitation = function (citationID, persfamID, tree, eventID) {
    if (!confirm("Are you sure you want to delete this citation?")) {
      return false;
    }

    $.post(
      ajaxurl,
      {
        action: "hp_delete_citation",
        nonce: heritagepress_ajax.nonce,
        citationID: citationID,
      },
      function (response) {
        if (response.success) {
          $("#citations_" + citationID).remove();
          // Reload citations if no more exist
          if ($(".sortrow").length === 0) {
            CitationModal.loadCitations();
          }
        } else {
          alert("Error deleting citation: " + response.data);
        }
      }
    );
    return false;
  };

  window.addCitation = function (form) {
    var formData = $(form).serialize();
    formData += "&action=hp_add_citation&nonce=" + heritagepress_ajax.nonce;

    $.post(ajaxurl, formData, function (response) {
      if (response.success) {
        // Return to citations list
        CitationModal.gotoSection("addcitation", "citations");
        CitationModal.loadCitations();
      } else {
        alert("Error adding citation: " + response.data);
      }
    });
    return false;
  };

  window.updateCitation = function (form) {
    var formData = $(form).serialize();
    formData += "&action=hp_update_citation&nonce=" + heritagepress_ajax.nonce;

    $.post(ajaxurl, formData, function (response) {
      if (response.success) {
        // Return to citations list
        CitationModal.gotoSection("editcitation", "citations");
        CitationModal.loadCitations();
      } else {
        alert("Error updating citation: " + response.data);
      }
    });
    return false;
  };

  // Source search functions
  window.initFilter = function (
    currentSection,
    targetSection,
    sourceIdField,
    sourceTitleField
  ) {
    CitationModal.gotoSection(currentSection, targetSection);
    return false;
  };

  window.applyFilter = function (params) {
    var searchTerm = $("#" + params.fieldId).val();
    var filterType = $('input[name="filter"]:checked').val() || "c";

    $.post(
      ajaxurl,
      {
        action: "hp_search_sources",
        nonce: heritagepress_ajax.nonce,
        search: searchTerm,
        filter: filterType,
        tree: params.tree,
      },
      function (response) {
        if (response.success) {
          $("#" + params.destdiv).html(response.data.html);
        }
      }
    );
    return false;
  };

  window.selectSource = function (sourceID, title) {
    $("#sourceID").val(sourceID);
    $("#sourceTitle").html(title);
    CitationModal.gotoSection("findsource", CitationModal.prevsection);
    return false;
  };

  // Copy last citation
  window.copylast = function (form, citationID) {
    $("#lastspinner").show();

    $.post(
      ajaxurl,
      {
        action: "hp_get_last_citation",
        nonce: heritagepress_ajax.nonce,
        citationID: citationID,
      },
      function (response) {
        $("#lastspinner").hide();
        if (response.success) {
          var data = response.data;
          $(form)
            .find("#sourceID")
            .val(data.sourceID || "");
          $(form)
            .find("#citepage")
            .val(data.page || "");
          $(form)
            .find("#quay")
            .val(data.quay || "");
          $(form)
            .find("#citedate")
            .val(data.citedate || "");
          $(form)
            .find("#citetext")
            .val(data.citetext || "");
          $(form)
            .find("#citenote")
            .val(data.note || "");
          if (data.sourceTitle) {
            $("#sourceTitle").html(data.sourceTitle);
          }
        } else {
          alert("Error copying citation: " + response.data);
        }
      }
    );
    return false;
  };

  // Initialize when document is ready
  $(document).ready(function () {
    CitationModal.init();

    // Make CitationModal globally available
    window.HeritagePress.Citations.Modal = CitationModal;
  });
})(jQuery);
