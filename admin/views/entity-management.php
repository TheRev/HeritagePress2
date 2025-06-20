<?php

/**
 * Entity Management Admin View
 * Provides interface for managing states and countries for place standardization
 * Based on admin_newentity.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'states';

// Get entity data for current tab
$entity_type = $current_tab === 'countries' ? 'country' : 'state';
$table_name = $entity_type === 'state' ? $wpdb->prefix . 'hp_states' : $wpdb->prefix . 'hp_countries';
$column_name = $entity_type === 'state' ? 'state_name' : 'country_name';

// Check if tables exist
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
  // Get entities for current tab
  $entities = $wpdb->get_results("SELECT $column_name as name FROM $table_name ORDER BY $column_name", ARRAY_A);
}
?>

<div class="wrap heritagepress-entities">
  <h1><?php _e('Geographic Entities', 'heritagepress'); ?></h1>
  <p><?php _e('Manage standardized lists of states and countries for consistent place entry.', 'heritagepress'); ?></p>

  <!-- Tab Navigation -->
  <h2 class="nav-tab-wrapper">
    <a href="<?php echo admin_url('admin.php?page=hp-entity-management&tab=states'); ?>"
      class="nav-tab <?php echo $current_tab === 'states' ? 'nav-tab-active' : ''; ?>">
      <?php _e('States/Provinces', 'heritagepress'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=hp-entity-management&tab=countries'); ?>"
      class="nav-tab <?php echo $current_tab === 'countries' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Countries', 'heritagepress'); ?>
    </a>
  </h2>

  <div class="entity-management-content">
    <?php if (!$table_exists): ?>
      <div class="notice notice-warning">
        <p><?php _e('Database tables for entity management are not yet created. Please create the HeritagePress database tables first.', 'heritagepress'); ?></p>
        <p><a href="<?php echo admin_url('admin.php?page=heritagepress&tab=tables'); ?>" class="button"><?php _e('Go to Table Management', 'heritagepress'); ?></a></p>
      </div>
    <?php else: ?>

      <!-- Add Entity Form -->
      <div class="entity-form-container">
        <h2><?php echo sprintf(__('Add New %s', 'heritagepress'), ucfirst($entity_type)); ?></h2>

        <form id="add-entity-form" class="entity-form">
          <?php wp_nonce_field('hp_entity_nonce', 'nonce'); ?>
          <input type="hidden" name="entity_type" value="<?php echo esc_attr($entity_type); ?>">

          <table class="form-table">
            <tbody>
              <tr>
                <th scope="row">
                  <label for="entity_name"><?php echo sprintf(__('%s Name', 'heritagepress'), ucfirst($entity_type)); ?></label>
                </th>
                <td>
                  <input type="text" id="entity_name" name="entity_name" class="regular-text" required>
                  <p class="description">
                    <?php
                    if ($entity_type === 'state') {
                      _e('Enter the full name of the state or province (e.g., "California", "Ontario").', 'heritagepress');
                    } else {
                      _e('Enter the full name of the country (e.g., "United States", "Canada").', 'heritagepress');
                    }
                    ?>
                  </p>
                  <div id="entity-validation-message" class="validation-message"></div>
                </td>
              </tr>
            </tbody>
          </table>

          <p class="submit">
            <button type="submit" class="button button-primary" id="add-entity-btn">
              <?php echo sprintf(__('Add %s', 'heritagepress'), ucfirst($entity_type)); ?>
            </button>
            <button type="button" id="reset-form" class="button">
              <?php _e('Reset Form', 'heritagepress'); ?>
            </button>
          </p>
        </form>
      </div>

      <!-- Entity List -->
      <div class="entity-list-container">
        <h2><?php echo sprintf(__('Existing %s', 'heritagepress'), ucfirst($entity_type) . 's'); ?></h2>

        <?php if (!empty($entities)): ?>
          <div class="entity-list-actions">
            <p class="description">
              <?php echo sprintf(__('Total %s: %d', 'heritagepress'), strtolower($entity_type) . 's', count($entities)); ?>
            </p>
            <div class="tablenav">
              <div class="alignleft actions">
                <select id="bulk-action-selector">
                  <option value=""><?php _e('Bulk Actions', 'heritagepress'); ?></option>
                  <option value="delete"><?php _e('Delete Selected', 'heritagepress'); ?></option>
                </select>
                <button type="button" id="bulk-action-btn" class="button"><?php _e('Apply', 'heritagepress'); ?></button>
              </div>
              <div class="alignright">
                <button type="button" id="export-entities" class="button">
                  <?php echo sprintf(__('Export %s', 'heritagepress'), ucfirst($entity_type) . 's'); ?>
                </button>
              </div>
            </div>
          </div>

          <div class="entity-grid">
            <?php foreach ($entities as $entity): ?>
              <div class="entity-item" data-name="<?php echo esc_attr($entity['name']); ?>">
                <label>
                  <input type="checkbox" class="entity-checkbox" value="<?php echo esc_attr($entity['name']); ?>">
                  <span class="entity-name"><?php echo esc_html($entity['name']); ?></span>
                </label>
                <div class="entity-actions">
                  <button type="button" class="button-link delete-entity"
                    data-name="<?php echo esc_attr($entity['name']); ?>"
                    data-type="<?php echo esc_attr($entity_type); ?>"
                    title="<?php _e('Delete', 'heritagepress'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Pagination (if needed for large lists) -->
          <?php if (count($entities) > 100): ?>
            <div class="tablenav">
              <div class="tablenav-pages">
                <span class="pagination-links">
                  <!-- Pagination controls would go here -->
                  <span class="paging-input">
                    <span class="screen-reader-text"><?php _e('Current Page', 'heritagepress'); ?></span>
                    <span class="total-pages"><?php echo sprintf(__('Showing all %d items', 'heritagepress'), count($entities)); ?></span>
                  </span>
                </span>
              </div>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <div class="no-entities-message">
            <p class="description">
              <?php echo sprintf(__('No %s have been added yet.', 'heritagepress'), strtolower($entity_type) . 's'); ?>
            </p>
            <p>
              <?php
              if ($entity_type === 'state') {
                _e('Add states and provinces to provide standardized options for place entries. This helps ensure consistent geographical data in your genealogy records.', 'heritagepress');
              } else {
                _e('Add countries to provide standardized options for place entries. This helps ensure consistent geographical data in your genealogy records.', 'heritagepress');
              }
              ?>
            </p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Quick Add Presets -->
      <div class="entity-presets-container">
        <h3><?php _e('Quick Add Presets', 'heritagepress'); ?></h3>
        <p class="description">
          <?php _e('Click to quickly add common geographic entities.', 'heritagepress'); ?>
        </p>

        <?php if ($entity_type === 'state'): ?>
          <div class="preset-buttons">
            <button type="button" class="button preset-btn" data-preset="us-states">
              <?php _e('US States', 'heritagepress'); ?>
            </button>
            <button type="button" class="button preset-btn" data-preset="canadian-provinces">
              <?php _e('Canadian Provinces', 'heritagepress'); ?>
            </button>
            <button type="button" class="button preset-btn" data-preset="uk-counties">
              <?php _e('UK Counties', 'heritagepress'); ?>
            </button>
          </div>
        <?php else: ?>
          <div class="preset-buttons">
            <button type="button" class="button preset-btn" data-preset="common-countries">
              <?php _e('Common Countries', 'heritagepress'); ?>
            </button>
            <button type="button" class="button preset-btn" data-preset="all-countries">
              <?php _e('All World Countries', 'heritagepress'); ?>
            </button>
          </div>
        <?php endif; ?>
      </div>

    <?php endif; ?>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Add entity form submission
    $('#add-entity-form').on('submit', function(e) {
      e.preventDefault();

      var $form = $(this);
      var $btn = $('#add-entity-btn');
      var originalText = $btn.text();

      // Validate input
      var entityName = $('#entity_name').val().trim();
      if (!entityName) {
        showValidationMessage('<?php _e('Name is required.', 'heritagepress'); ?>', 'error');
        return;
      }

      // Disable button and show loading
      $btn.prop('disabled', true).text('<?php _e('Adding...', 'heritagepress'); ?>');

      var formData = {
        action: 'hp_add_entity',
        nonce: $('#nonce').val(),
        entity_type: $('input[name="entity_type"]').val(),
        entity_name: entityName
      };

      $.post(ajaxurl, formData, function(response) {
        if (response.success) {
          showValidationMessage(response.data.message, 'success');
          $('#entity_name').val('');
          // Reload page to show new entity
          setTimeout(function() {
            location.reload();
          }, 1500);
        } else {
          showValidationMessage(response.data, 'error');
        }
      }).fail(function() {
        showValidationMessage('<?php _e('Network error. Please try again.', 'heritagepress'); ?>', 'error');
      }).always(function() {
        $btn.prop('disabled', false).text(originalText);
      });
    });

    // Reset form
    $('#reset-form').on('click', function() {
      $('#add-entity-form')[0].reset();
      clearValidationMessage();
    });

    // Delete entity
    $('.delete-entity').on('click', function() {
      var entityName = $(this).data('name');
      var entityType = $(this).data('type');

      if (!confirm('<?php _e('Are you sure you want to delete this', 'heritagepress'); ?> ' + entityType + '?\n\n"' + entityName + '"')) {
        return;
      }

      var data = {
        action: 'hp_delete_entity',
        nonce: '<?php echo wp_create_nonce('hp_entity_nonce'); ?>',
        entity_type: entityType,
        entity_name: entityName
      };

      $.post(ajaxurl, data, function(response) {
        if (response.success) {
          // Remove entity from list
          $('[data-name="' + entityName + '"]').fadeOut(300, function() {
            $(this).remove();
            // Check if list is now empty
            if ($('.entity-item').length === 0) {
              location.reload();
            }
          });
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    });

    // Bulk actions
    $('#bulk-action-btn').on('click', function() {
      var action = $('#bulk-action-selector').val();
      var selected = $('.entity-checkbox:checked');

      if (!action) {
        alert('<?php _e('Please select an action.', 'heritagepress'); ?>');
        return;
      }

      if (selected.length === 0) {
        alert('<?php _e('Please select entities to process.', 'heritagepress'); ?>');
        return;
      }

      if (action === 'delete') {
        if (!confirm('<?php _e('Are you sure you want to delete the selected entities?', 'heritagepress'); ?>')) {
          return;
        }

        // Process deletions one by one
        var entityType = $('input[name="entity_type"]').val();
        var processed = 0;
        var total = selected.length;

        selected.each(function() {
          var entityName = $(this).val();
          var $item = $(this).closest('.entity-item');

          var data = {
            action: 'hp_delete_entity',
            nonce: '<?php echo wp_create_nonce('hp_entity_nonce'); ?>',
            entity_type: entityType,
            entity_name: entityName
          };

          $.post(ajaxurl, data, function(response) {
            processed++;
            if (response.success) {
              $item.fadeOut(300);
            }

            if (processed === total) {
              setTimeout(function() {
                location.reload();
              }, 500);
            }
          });
        });
      }
    });

    // Select all checkbox
    $('#select-all-entities').on('change', function() {
      $('.entity-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Preset buttons
    $('.preset-btn').on('click', function() {
      var preset = $(this).data('preset');

      if (!confirm('<?php _e('This will add multiple entities. Continue?', 'heritagepress'); ?>')) {
        return;
      }

      // Show loading state
      $(this).prop('disabled', true).text('<?php _e('Adding...', 'heritagepress'); ?>');

      // Add preset entities
      addPresetEntities(preset);
    });

    // Export entities
    $('#export-entities').on('click', function() {
      var entityType = $('input[name="entity_type"]').val();
      var entities = [];

      $('.entity-item').each(function() {
        entities.push($(this).data('name'));
      });

      if (entities.length === 0) {
        alert('<?php _e('No entities to export.', 'heritagepress'); ?>');
        return;
      }

      // Create CSV content
      var csv = entityType + '_name\n';
      entities.forEach(function(entity) {
        csv += '"' + entity.replace(/"/g, '""') + '"\n';
      });

      // Download CSV
      var blob = new Blob([csv], {
        type: 'text/csv'
      });
      var url = window.URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = entityType + 's_export.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    });

    // Validation helpers
    function showValidationMessage(message, type) {
      var $msg = $('#entity-validation-message');
      $msg.removeClass('error success').addClass(type).text(message).show();
    }

    function clearValidationMessage() {
      $('#entity-validation-message').hide().removeClass('error success').text('');
    }

    // Add preset entities function
    function addPresetEntities(preset) {
      var entities = getPresetData(preset);
      var entityType = $('input[name="entity_type"]').val();
      var added = 0;
      var total = entities.length;

      entities.forEach(function(entityName) {
        var data = {
          action: 'hp_add_entity',
          nonce: $('#nonce').val(),
          entity_type: entityType,
          entity_name: entityName
        };

        $.post(ajaxurl, data, function(response) {
          added++;
          if (added === total) {
            alert('<?php _e('Preset entities added successfully!', 'heritagepress'); ?>');
            location.reload();
          }
        });
      });
    }

    // Get preset data
    function getPresetData(preset) {
      var presets = {
        'us-states': [
          'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware',
          'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky',
          'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi',
          'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico',
          'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania',
          'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont',
          'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming', 'District of Columbia'
        ],
        'canadian-provinces': [
          'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
          'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario', 'Prince Edward Island',
          'Quebec', 'Saskatchewan', 'Yukon'
        ],
        'uk-counties': [
          'England', 'Scotland', 'Wales', 'Northern Ireland', 'Bedfordshire', 'Berkshire',
          'Buckinghamshire', 'Cambridgeshire', 'Cheshire', 'Cornwall', 'Cumberland', 'Derbyshire',
          'Devon', 'Dorset', 'Durham', 'Essex', 'Gloucestershire', 'Hampshire', 'Hertfordshire',
          'Kent', 'Lancashire', 'Leicestershire', 'Lincolnshire', 'Norfolk', 'Northamptonshire',
          'Northumberland', 'Nottinghamshire', 'Oxfordshire', 'Somerset', 'Staffordshire',
          'Suffolk', 'Surrey', 'Sussex', 'Warwickshire', 'Westmorland', 'Wiltshire',
          'Worcestershire', 'Yorkshire'
        ],
        'common-countries': [
          'Australia', 'Austria', 'Belgium', 'Canada', 'Denmark', 'England', 'France', 'Germany',
          'Ireland', 'Italy', 'Netherlands', 'New Zealand', 'Norway', 'Poland', 'Scotland',
          'Spain', 'Sweden', 'Switzerland', 'United Kingdom', 'United States', 'Wales'
        ],
        'all-countries': [
          // This would include a comprehensive list of world countries
          // For brevity, showing just a few examples
          'Afghanistan', 'Albania', 'Algeria', 'Argentina', 'Australia', 'Austria', 'Bangladesh',
          'Belgium', 'Brazil', 'Canada', 'China', 'Denmark', 'Egypt', 'France', 'Germany',
          'India', 'Italy', 'Japan', 'Mexico', 'Netherlands', 'Norway', 'Poland', 'Russia',
          'Spain', 'Sweden', 'Switzerland', 'United Kingdom', 'United States'
          // ... complete list would go here
        ]
      };

      return presets[preset] || [];
    }
  });
</script>

<style>
  .heritagepress-entities {
    max-width: 1200px;
  }

  .entity-management-content {
    margin-top: 20px;
  }

  .entity-form-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
  }

  .entity-form-container h2 {
    margin-top: 0;
  }

  .entity-list-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
  }

  .entity-list-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e1e1e1;
  }

  .entity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 20px;
  }

  .entity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
  }

  .entity-item:hover {
    background: #f0f0f0;
  }

  .entity-item label {
    display: flex;
    align-items: center;
    margin: 0;
    flex-grow: 1;
  }

  .entity-item .entity-checkbox {
    margin-right: 8px;
  }

  .entity-item .entity-actions {
    margin-left: 10px;
  }

  .entity-item .delete-entity {
    color: #d63638;
    padding: 2px;
  }

  .entity-item .delete-entity:hover {
    color: #d63638;
  }

  .validation-message {
    margin-top: 5px;
    padding: 4px 8px;
    border-radius: 3px;
    display: none;
  }

  .validation-message.error {
    background: #fef7f7;
    color: #d63638;
    border: 1px solid #d63638;
  }

  .validation-message.success {
    background: #f7fff7;
    color: #00a32a;
    border: 1px solid #00a32a;
  }

  .no-entities-message {
    text-align: center;
    padding: 40px 20px;
    color: #666;
  }

  .entity-presets-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
  }

  .preset-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .preset-btn {
    margin: 0;
  }

  @media (max-width: 768px) {
    .entity-grid {
      grid-template-columns: 1fr;
    }

    .entity-list-actions {
      flex-direction: column;
      align-items: stretch;
      gap: 10px;
    }

    .preset-buttons {
      flex-direction: column;
    }
  }
</style>
