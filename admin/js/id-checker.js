/**
 * HeritagePress ID Checker Utilities
 *
 * JavaScript functions for AJAX ID validation across all entity types
 * Replicates HeritagePress admin_checkID.php functionality
 */

(function ($) {
  "use strict";

  // Global HP ID Checker object
  window.HP_IDChecker = {
    /**
     * Universal ID checker for all entity types
     *
     * @param {string} entityID - The ID to check
     * @param {string} entityType - Type: person, family, source, repo
     * @param {string} tree - Tree/gedcom identifier
     * @param {string} messageElementId - DOM element ID to display result
     * @param {function} callback - Optional callback function
     */
    checkID: function (entityID, entityType, tree, messageElementId, callback) {
      if (!entityID || !entityType || !tree) {
        console.warn("HP_IDChecker: Missing required parameters");
        return;
      }

      var messageElement = document.getElementById(messageElementId);
      if (messageElement) {
        messageElement.innerHTML =
          '<span style="color: #666;"><em>Checking...</em></span>';
      }

      // AJAX request
      $.post(
        hp_ajax_object.ajax_url,
        {
          action: "hp_check_entity_id",
          type: entityType,
          checkID: entityID,
          tree: tree,
          _wpnonce: hp_ajax_object.nonce || $("#hp_nonce").val(),
        },
        function (response) {
          var message = "";
          var cssClass = "";

          if (response.success) {
            message = response.data.message;
            cssClass = response.data.css_class;

            if (messageElement) {
              var color = response.data.available ? "green" : "red";
              messageElement.innerHTML =
                '<span style="color: ' + color + ';">' + message + "</span>";
            }

            // Execute callback if provided
            if (typeof callback === "function") {
              callback(response.data);
            }
          } else {
            message = response.data || "ID check failed";
            if (messageElement) {
              messageElement.innerHTML =
                '<span style="color: red;">' + message + "</span>";
            }

            if (typeof callback === "function") {
              callback({ available: false, message: message });
            }
          }
        }
      ).fail(function (xhr, status, error) {
        console.error("ID Check Failed:", xhr, status, error);
        var errorMsg = "Failed to check ID: " + error;

        if (messageElement) {
          messageElement.innerHTML =
            '<span style="color: red;">' + errorMsg + "</span>";
        }

        if (typeof callback === "function") {
          callback({ available: false, message: errorMsg });
        }
      });
    },

    /**
     * Check Person ID (backward compatibility)
     */
    checkPersonID: function (personID, tree, messageElementId, callback) {
      this.checkID(personID, "person", tree, messageElementId, callback);
    },

    /**
     * Check Family ID
     */
    checkFamilyID: function (familyID, tree, messageElementId, callback) {
      this.checkID(familyID, "family", tree, messageElementId, callback);
    },

    /**
     * Check Source ID
     */
    checkSourceID: function (sourceID, tree, messageElementId, callback) {
      this.checkID(sourceID, "source", tree, messageElementId, callback);
    },

    /**
     * Check Repository ID
     */
    checkRepositoryID: function (repoID, tree, messageElementId, callback) {
      this.checkID(repoID, "repo", tree, messageElementId, callback);
    },

    /**
     * Auto-bind ID checking to form elements
     * Call this function to automatically set up ID checking on forms
     */
    bindToForms: function () {
      // Person ID fields
      $('input[name="personID"], input[id*="personID"]').on(
        "blur",
        function () {
          var personID = $(this).val();
          var tree = $('select[name="tree"], select[name="gedcom"]').val();
          var messageId =
            $(this).attr("data-message-id") || "personid-check-message";

          if (personID && tree) {
            HP_IDChecker.checkPersonID(personID, tree, messageId);
          }
        }
      );

      // Family ID fields
      $('input[name="familyID"], input[id*="familyID"]').on(
        "blur",
        function () {
          var familyID = $(this).val();
          var tree = $('select[name="tree"], select[name="gedcom"]').val();
          var messageId =
            $(this).attr("data-message-id") || "familyid-check-message";

          if (familyID && tree) {
            HP_IDChecker.checkFamilyID(familyID, tree, messageId);
          }
        }
      );

      // Source ID fields
      $('input[name="sourceID"], input[id*="sourceID"]').on(
        "blur",
        function () {
          var sourceID = $(this).val();
          var tree = $('select[name="tree"], select[name="gedcom"]').val();
          var messageId =
            $(this).attr("data-message-id") || "sourceid-check-message";

          if (sourceID && tree) {
            HP_IDChecker.checkSourceID(sourceID, tree, messageId);
          }
        }
      );

      // Repository ID fields
      $('input[name="repoID"], input[id*="repoID"]').on("blur", function () {
        var repoID = $(this).val();
        var tree = $('select[name="tree"], select[name="gedcom"]').val();
        var messageId =
          $(this).attr("data-message-id") || "repoid-check-message";

        if (repoID && tree) {
          HP_IDChecker.checkRepositoryID(repoID, tree, messageId);
        }
      });
    },

    /**
     * Generate next available ID (if implemented on backend)
     */
    generateNextID: function (entityType, tree, callback) {
      $.post(
        hp_ajax_object.ajax_url,
        {
          action: "hp_generate_next_id",
          type: entityType,
          tree: tree,
          _wpnonce: hp_ajax_object.nonce || $("#hp_nonce").val(),
        },
        function (response) {
          if (response.success && typeof callback === "function") {
            callback(response.data.nextID);
          }
        }
      ).fail(function () {
        if (typeof callback === "function") {
          callback(null);
        }
      });
    },
  };

  // Auto-initialize when document is ready
  $(document).ready(function () {
    // Only auto-bind if hp_ajax_object is available
    if (typeof hp_ajax_object !== "undefined") {
      HP_IDChecker.bindToForms();
    }
  });
})(jQuery);

// Legacy function for backward compatibility with existing forms
function checkID(entityID, entityType, messageElementId, treeField) {
  var tree = treeField ? treeField.value : "";
  if (typeof HP_IDChecker !== "undefined") {
    HP_IDChecker.checkID(entityID, entityType, tree, messageElementId);
  }
}
