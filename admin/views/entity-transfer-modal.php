<?php

/**
 * Entity Transfer Modal Interface
 *
 * Modal interface for transferring entities between trees.
 * Provides UI for the entity transfer functionality.
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get available trees for transfer
global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$trees = $wpdb->get_results("SELECT gedcom, treename FROM {$trees_table} ORDER BY treename", ARRAY_A);

?>

<div id="entity-transfer-modal" class="entity-transfer-modal" style="display: none;">
  <div class="entity-transfer-modal-content">
    <div class="entity-transfer-modal-header">
      <h3><?php _e('Transfer Entity Between Trees', 'heritagepress'); ?></h3>
      <button type="button" class="entity-transfer-modal-close">&times;</button>
    </div>

    <div class="entity-transfer-modal-body">
      <form id="entity-transfer-form">
        <?php wp_nonce_field('hp_transfer_entity', 'transfer_nonce'); ?>

        <!-- Hidden fields -->
        <input type="hidden" id="transfer-entity-type" name="entity_type" value="">
        <input type="hidden" id="transfer-entity-id" name="entity_id" value="">
        <input type="hidden" id="transfer-old-tree" name="old_tree" value="">

        <!-- Entity Information Display -->
        <div class="entity-info-section">
          <h4><?php _e('Entity Information', 'heritagepress'); ?></h4>
          <div id="entity-info-display">
            <p><?php _e('Loading entity information...', 'heritagepress'); ?></p>
          </div>
        </div>

        <!-- Transfer Options -->
        <div class="transfer-options-section">
          <h4><?php _e('Transfer Options', 'heritagepress'); ?></h4>

          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="new-tree"><?php _e('Destination Tree', 'heritagepress'); ?></label>
              </th>
              <td> <select id="new-tree" name="new_tree" required>
                  <option value=""><?php _e('Select destination tree...', 'heritagepress'); ?></option>
                  <!-- Trees will be populated by JavaScript, excluding current tree -->
                </select>
                <script>
                  // Populate tree dropdown excluding current tree
                  window.HP_AVAILABLE_TREES = <?php echo json_encode($trees); ?>;
                </script>
                </select>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="new-id"><?php _e('New ID (Optional)', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="new-id" name="new_id" value="" placeholder="<?php _e('Leave blank to keep current ID', 'heritagepress'); ?>">
                <button type="button" id="generate-id" class="button button-secondary">
                  <?php _e('Generate', 'heritagepress'); ?>
                </button>
                <div id="id-check-status" class="id-check-status"></div>
                <p class="description">
                  <?php _e('If you want to change the entity ID during transfer, enter the new ID here or click Generate to create a new one automatically.', 'heritagepress'); ?>
                </p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="operation"><?php _e('Operation', 'heritagepress'); ?></label>
              </th>
              <td>
                <label>
                  <input type="radio" name="operation" value="0" checked>
                  <?php _e('Move (remove from current tree)', 'heritagepress'); ?>
                </label>
                <br>
                <label>
                  <input type="radio" name="operation" value="1">
                  <?php _e('Copy (keep in current tree)', 'heritagepress'); ?>
                </label>
                <p class="description">
                  <?php _e('Move will transfer the entity completely. Copy will create a duplicate in the new tree.', 'heritagepress'); ?>
                </p>
              </td>
            </tr>
          </table>
        </div>

        <!-- Transfer Impact Warning -->
        <div class="transfer-warning-section">
          <div class="notice notice-warning">
            <p>
              <strong><?php _e('Warning:', 'heritagepress'); ?></strong>
              <?php _e('This operation will transfer the entity and all associated data (events, notes, media links). This action cannot be easily undone.', 'heritagepress'); ?>
            </p>
          </div>
        </div>
      </form>
    </div>

    <div class="entity-transfer-modal-footer">
      <button type="button" class="button entity-transfer-modal-cancel"><?php _e('Cancel', 'heritagepress'); ?></button>
      <button type="button" id="confirm-transfer" class="button button-primary" disabled>
        <?php _e('Transfer Entity', 'heritagepress'); ?>
      </button>
    </div>
  </div>
</div>

<style>
  .entity-transfer-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .entity-transfer-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 90%;
    max-width: 600px;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .entity-transfer-modal-header {
    padding: 20px 20px 10px;
    border-bottom: 1px solid #ddd;
    position: relative;
  }

  .entity-transfer-modal-header h3 {
    margin: 0;
    font-size: 18px;
  }

  .entity-transfer-modal-close {
    position: absolute;
    right: 15px;
    top: 15px;
    background: none;
    border: none;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .entity-transfer-modal-close:hover {
    color: #000;
  }

  .entity-transfer-modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
  }

  .entity-transfer-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
  }

  .entity-transfer-modal-footer .button {
    margin-left: 10px;
  }

  .entity-info-section,
  .transfer-options-section,
  .transfer-warning-section {
    margin-bottom: 20px;
  }

  .entity-info-section h4,
  .transfer-options-section h4 {
    margin-top: 10px;
    margin-bottom: 15px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    color: #646970;
  }

  #entity-info-display {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #e1e1e1;
  }

  .entity-info-item {
    margin-bottom: 8px;
  }

  .entity-info-label {
    font-weight: 600;
    color: #646970;
    min-width: 80px;
    display: inline-block;
  }

  .id-check-status {
    margin-top: 5px;
    font-size: 13px;
  }

  .id-check-status.available {
    color: #00a32a;
  }

  .id-check-status.exists {
    color: #d63384;
  }

  .id-check-status.checking {
    color: #646970;
  }

  .transfer-warning-section .notice {
    margin: 0;
  }

  /* Responsive adjustments */
  @media (max-width: 600px) {
    .entity-transfer-modal-content {
      width: 95%;
      margin: 2% auto;
    }

    .entity-transfer-modal-body {
      max-height: 80vh;
    }
  }
</style>

<script>
  jQuery(document).ready(function($) {
    var EntityTransfer = {
      modal: null,
      currentEntityType: '',
      currentEntityId: '',
      currentTreeId: '',

      init: function() {
        this.modal = $('#entity-transfer-modal');
        this.bindEvents();
      },

      bindEvents: function() {
        var self = this;

        // Close modal events
        $('.entity-transfer-modal-close, .entity-transfer-modal-cancel').on('click', function() {
          self.closeModal();
        });

        // Close on background click
        this.modal.on('click', function(e) {
          if (e.target === this) {
            self.closeModal();
          }
        });

        // Tree selection change
        $('#new-tree').on('change', function() {
          self.onTreeChange();
        }); // New ID input change
        $('#new-id').on('input', function() {
          self.checkNewId();
        });

        // Convert ID to uppercase on blur (matching HeritagePress behavior)
        $('#new-id').on('blur', function() {
          this.value = this.value.toUpperCase();
        });

        // Generate ID button
        $('#generate-id').on('click', function() {
          self.generateId();
        });

        // Confirm transfer
        $('#confirm-transfer').on('click', function() {
          self.confirmTransfer();
        });

        // Global trigger for opening modal
        $(document).on('heritagepress:open-transfer-modal', function(e, data) {
          self.openModal(data.entityType, data.entityId, data.treeId);
        });
      },
      openModal: function(entityType, entityId, treeId) {
        this.currentEntityType = entityType;
        this.currentEntityId = entityId;
        this.currentTreeId = treeId;

        // Set hidden form fields
        $('#transfer-entity-type').val(entityType);
        $('#transfer-entity-id').val(entityId);
        $('#transfer-old-tree').val(treeId);

        // Reset form
        this.resetForm();

        // Populate tree dropdown excluding current tree
        this.populateTreeDropdown(treeId);

        // Load entity information
        this.loadEntityInfo();

        // Show modal
        this.modal.show();
      },

      closeModal: function() {
        this.modal.hide();
        this.resetForm();
      },

      resetForm: function() {
        $('#entity-transfer-form')[0].reset();
        $('#entity-info-display').html('<p>Loading entity information...</p>');
        $('#id-check-status').html('').removeClass('available exists checking');
        $('#confirm-transfer').prop('disabled', true);
      },

      populateTreeDropdown: function(currentTreeId) {
        var $dropdown = $('#new-tree');
        $dropdown.empty();

        // Add default option
        $dropdown.append('<option value="">Select destination tree...</option>');

        // Add trees excluding the current one
        if (window.HP_AVAILABLE_TREES) {
          for (var i = 0; i < window.HP_AVAILABLE_TREES.length; i++) {
            var tree = window.HP_AVAILABLE_TREES[i];
            if (tree.gedcom !== currentTreeId) {
              $dropdown.append('<option value="' + tree.gedcom + '">' + tree.treename + '</option>');
            }
          }
        }
      },

      loadEntityInfo: function() {
        var self = this;

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_get_entity_info',
            entity_type: this.currentEntityType,
            entity_id: this.currentEntityId,
            tree_id: this.currentTreeId,
            nonce: $('#transfer_nonce').val()
          },
          success: function(response) {
            if (response.success) {
              self.displayEntityInfo(response.data);
            } else {
              $('#entity-info-display').html('<p class="error">Failed to load entity information.</p>');
            }
          },
          error: function() {
            $('#entity-info-display').html('<p class="error">Error loading entity information.</p>');
          }
        });
      },

      displayEntityInfo: function(data) {
        var html = '<div class="entity-info-items">';

        html += '<div class="entity-info-item">';
        html += '<span class="entity-info-label">Type:</span> ';
        html += this.capitalize(data.type);
        html += '</div>';

        html += '<div class="entity-info-item">';
        html += '<span class="entity-info-label">ID:</span> ';
        html += data.id;
        html += '</div>';

        if (data.name) {
          html += '<div class="entity-info-item">';
          html += '<span class="entity-info-label">Name:</span> ';
          html += data.name;
          html += '</div>';
        }

        if (data.title) {
          html += '<div class="entity-info-item">';
          html += '<span class="entity-info-label">Title:</span> ';
          html += data.title;
          html += '</div>';
        }

        if (data.birth) {
          html += '<div class="entity-info-item">';
          html += '<span class="entity-info-label">Birth:</span> ';
          html += data.birth;
          html += '</div>';
        }

        if (data.death) {
          html += '<div class="entity-info-item">';
          html += '<span class="entity-info-label">Death:</span> ';
          html += data.death;
          html += '</div>';
        }

        if (data.author) {
          html += '<div class="entity-info-item">';
          html += '<span class="entity-info-label">Author:</span> ';
          html += data.author;
          html += '</div>';
        }

        html += '</div>';

        $('#entity-info-display').html(html);
      },

      onTreeChange: function() {
        this.updateTransferButton();
        this.checkNewId();
      },

      checkNewId: function() {
        var newId = $('#new-id').val().trim();
        var newTree = $('#new-tree').val();
        var $status = $('#id-check-status');

        if (!newId || !newTree) {
          $status.html('').removeClass('available exists checking');
          this.updateTransferButton();
          return;
        }

        // Don't check if it's the same as current ID
        if (newId === this.currentEntityId) {
          $status.html('').removeClass('available exists checking');
          this.updateTransferButton();
          return;
        }

        $status.html('Checking availability...').removeClass('available exists').addClass('checking');

        var self = this;
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_check_entity_id',
            entity_type: this.currentEntityType,
            entity_id: newId,
            tree_id: newTree,
            nonce: $('#transfer_nonce').val()
          },
          success: function(response) {
            if (response.success) {
              if (response.data.exists) {
                $status.html('âœ— ID already exists in destination tree').removeClass('checking available').addClass('exists');
              } else {
                $status.html('âœ“ ID available').removeClass('checking exists').addClass('available');
              }
            } else {
              $status.html('Error checking ID').removeClass('checking available exists');
            }
            self.updateTransferButton();
          },
          error: function() {
            $status.html('Error checking ID').removeClass('checking available exists');
            self.updateTransferButton();
          }
        });
      },

      generateId: function() {
        var newTree = $('#new-tree').val();

        if (!newTree) {
          alert('<?php echo esc_js(__('Please select a destination tree first.', 'heritagepress')); ?>');
          return;
        }

        var self = this;
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_generate_entity_id',
            entity_type: this.currentEntityType,
            tree_id: newTree,
            nonce: $('#transfer_nonce').val()
          },
          success: function(response) {
            if (response.success) {
              $('#new-id').val(response.data.id).trigger('input');
            } else {
              alert('Failed to generate ID: ' + (response.data || 'Unknown error'));
            }
          },
          error: function() {
            alert('Error generating ID');
          }
        });
      },

      updateTransferButton: function() {
        var newTree = $('#new-tree').val();
        var newId = $('#new-id').val().trim();
        var $status = $('#id-check-status');

        // Disable if no destination tree selected
        if (!newTree) {
          $('#confirm-transfer').prop('disabled', true);
          return;
        }

        // Disable if new ID exists in destination tree
        if (newId && $status.hasClass('exists')) {
          $('#confirm-transfer').prop('disabled', true);
          return;
        }

        // Enable if all conditions are met
        $('#confirm-transfer').prop('disabled', false);
      },

      confirmTransfer: function() {
        var formData = {
          action: 'hp_transfer_entity',
          entity_type: $('#transfer-entity-type').val(),
          entity_id: $('#transfer-entity-id').val(),
          old_tree: $('#transfer-old-tree').val(),
          new_tree: $('#new-tree').val(),
          new_id: $('#new-id').val() || $('#transfer-entity-id').val(),
          operation: $('input[name="operation"]:checked').val(),
          nonce: $('#transfer_nonce').val()
        };

        // Disable button and show loading
        $('#confirm-transfer').prop('disabled', true).text('Transferring...');

        var self = this;
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: formData,
          success: function(response) {
            if (response.success) {
              alert('Entity transferred successfully!');
              self.closeModal();

              // Redirect to the edit page for the transferred entity
              if (response.data.redirect_url) {
                window.location.href = response.data.redirect_url;
              } else {
                // Refresh current page
                window.location.reload();
              }
            } else {
              alert('Transfer failed: ' + (response.data || 'Unknown error'));
              $('#confirm-transfer').prop('disabled', false).text('Transfer Entity');
            }
          },
          error: function() {
            alert('Transfer failed: Network error');
            $('#confirm-transfer').prop('disabled', false).text('Transfer Entity');
          }
        });
      },

      capitalize: function(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
      }
    };

    // Initialize
    EntityTransfer.init();

    // Make available globally
    window.HeritagePress = window.HeritagePress || {};
    window.HeritagePress.EntityTransfer = EntityTransfer;
  });
</script>
