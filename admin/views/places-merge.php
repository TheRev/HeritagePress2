<?php

/**
 * HeritagePress Merge Places Interface
 *
 * Merge duplicate places functionality
 * Replicates admin_mergeplaces.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

// Initialize place controller if not already loaded
if (!class_exists('HP_Place_Controller')) {
  require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-place-controller.php';
}

$place_controller = new HP_Place_Controller();

// Handle merge submission
$message = '';
$error = '';

if (isset($_POST['merge_places']) && !empty($_POST['source_place']) && !empty($_POST['target_place'])) {
  // Verify nonce
  if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_merge_places')) {
    $error = __('Security check failed.', 'heritagepress');
  } else {
    // TODO: Implement place merging functionality
    $message = __('Place merging functionality will be implemented.', 'heritagepress');
  }
}

// Get available trees
global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$trees = $wpdb->get_results("SELECT gedcom, treename FROM {$trees_table} ORDER BY treename", ARRAY_A);

?>

<div class="wrap">
  <h1><?php _e('Merge Places', 'heritagepress'); ?></h1>

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper">
    <a href="?page=hp-places" class="nav-tab"><?php _e('Search Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-add" class="nav-tab"><?php _e('Add New', 'heritagepress'); ?></a>
    <a href="?page=hp-places-merge" class="nav-tab nav-tab-active"><?php _e('Merge Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-geocode" class="nav-tab"><?php _e('Geocode', 'heritagepress'); ?></a>
  </nav>

  <?php if ($message): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo esc_html($message); ?></p>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="notice notice-error is-dismissible">
      <p><?php echo esc_html($error); ?></p>
    </div>
  <?php endif; ?>

  <div class="hp-merge-section">
    <div class="hp-info-box">
      <h3><?php _e('About Place Merging', 'heritagepress'); ?></h3>
      <p><?php _e('Use this tool to merge duplicate place names in your database. This is useful when you have variations of the same place name (e.g., "Chicago, Illinois" and "Chicago, IL") that should be standardized.', 'heritagepress'); ?></p>
      <p><strong><?php _e('Warning:', 'heritagepress'); ?></strong> <?php _e('This action cannot be undone. All references to the source place will be changed to the target place, and the source place will be deleted.', 'heritagepress'); ?></p>
    </div>

    <form method="post" id="merge-places-form">
      <?php wp_nonce_field('hp_merge_places'); ?>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="source_place"><?php _e('Source Place (to be merged):', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="source_place" id="source_place"
              value="<?php echo esc_attr($_POST['source_place'] ?? ''); ?>"
              class="large-text" />
            <button type="button" class="button find-place-btn" data-target="source_place">
              <?php _e('Find Place', 'heritagepress'); ?>
            </button>
            <p class="description">
              <?php _e('Enter the place name that you want to merge into another place. This place will be deleted.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="target_place"><?php _e('Target Place (keep this one):', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="target_place" id="target_place"
              value="<?php echo esc_attr($_POST['target_place'] ?? ''); ?>"
              class="large-text" />
            <button type="button" class="button find-place-btn" data-target="target_place">
              <?php _e('Find Place', 'heritagepress'); ?>
            </button>
            <p class="description">
              <?php _e('Enter the place name that should be kept. All references will be updated to this name.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <?php if (!empty($trees)): ?>
          <tr>
            <th scope="row">
              <label for="tree"><?php _e('Tree:', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="tree" id="tree">
                <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>"
                    <?php selected($_POST['tree'] ?? '', $tree['gedcom']); ?>>
                    <?php echo esc_html($tree['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="description">
                <?php _e('Limit the merge to a specific tree, or leave blank to merge across all trees.', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
        <?php endif; ?>
      </table>

      <div id="merge-preview" style="display: none;">
        <h3><?php _e('Merge Preview', 'heritagepress'); ?></h3>
        <div id="preview-content"></div>
      </div>

      <p class="submit">
        <input type="button" id="preview-merge" class="button"
          value="<?php _e('Preview Merge', 'heritagepress'); ?>">
        <input type="submit" name="merge_places" class="button-primary"
          value="<?php _e('Merge Places', 'heritagepress'); ?>"
          onclick="return confirm('<?php _e('Are you sure you want to merge these places? This action cannot be undone!', 'heritagepress'); ?>')">
        <a href="?page=hp-places" class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
      </p>
    </form>
  </div>

  <!-- Duplicate Places Finder -->
  <div class="hp-duplicates-section">
    <h3><?php _e('Find Potential Duplicates', 'heritagepress'); ?></h3>
    <p><?php _e('Click the button below to search for potential duplicate place names in your database.', 'heritagepress'); ?></p>
    <p>
      <button type="button" id="find-duplicates" class="button">
        <?php _e('Find Potential Duplicates', 'heritagepress'); ?>
      </button>
    </p>

    <div id="duplicates-results" style="display: none;">
      <h4><?php _e('Potential Duplicate Places', 'heritagepress'); ?></h4>
      <div id="duplicates-list"></div>
    </div>
  </div>
</div>

<!-- Place Finder Modal -->
<div id="place-finder-modal" class="hp-modal" style="display: none;">
  <div class="hp-modal-content">
    <div class="hp-modal-header">
      <h3><?php _e('Find Place', 'heritagepress'); ?></h3>
      <span class="hp-modal-close">&times;</span>
    </div>
    <div class="hp-modal-body">
      <input type="text" id="place-search-input" placeholder="<?php _e('Type to search places...', 'heritagepress'); ?>" class="large-text">
      <div id="place-search-results"></div>
    </div>
  </div>
</div>

<style>
  .hp-merge-section,
  .hp-duplicates-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
  }

  .hp-info-box {
    background: #e7f3ff;
    border: 1px solid #72aee6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
  }

  .hp-info-box h3 {
    margin-top: 0;
  }

  .find-place-btn {
    margin-left: 10px;
  }

  .hp-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .hp-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #ddd;
    width: 80%;
    max-width: 600px;
    border-radius: 4px;
  }

  .hp-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .hp-modal-header h3 {
    margin: 0;
  }

  .hp-modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .hp-modal-close:hover {
    color: #d63638;
  }

  .hp-modal-body {
    padding: 20px;
  }

  #place-search-results {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 10px;
  }

  .place-result-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
  }

  .place-result-item:hover {
    background-color: #f0f0f1;
  }

  .place-result-item:last-child {
    border-bottom: none;
  }

  .duplicate-pair {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
    background: #f9f9f9;
  }

  .duplicate-pair .places {
    display: flex;
    gap: 20px;
    margin-bottom: 10px;
  }

  .duplicate-pair .place-info {
    flex: 1;
    padding: 10px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    var currentTarget = '';

    // Find place buttons
    $('.find-place-btn').on('click', function() {
      currentTarget = $(this).data('target');
      $('#place-finder-modal').show();
      $('#place-search-input').focus();
    });

    // Close modal
    $('.hp-modal-close, .hp-modal').on('click', function(e) {
      if (e.target === this) {
        $('#place-finder-modal').hide();
        $('#place-search-input').val('');
        $('#place-search-results').empty();
      }
    });

    // Search places
    var searchTimeout;
    $('#place-search-input').on('input', function() {
      var searchTerm = $(this).val().trim();

      clearTimeout(searchTimeout);

      if (searchTerm.length < 2) {
        $('#place-search-results').empty();
        return;
      }

      searchTimeout = setTimeout(function() {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_place_search',
            search_string: searchTerm,
            limit: 20,
            nonce: '<?php echo wp_create_nonce('heritagepress_admin'); ?>'
          },
          success: function(response) {
            if (response.success) {
              displaySearchResults(response.data.places);
            }
          }
        });
      }, 300);
    });

    function displaySearchResults(places) {
      var html = '';

      if (places.length === 0) {
        html = '<div class="place-result-item"><?php _e('No places found.', 'heritagepress'); ?></div>';
      } else {
        places.forEach(function(place) {
          html += '<div class="place-result-item" data-place="' + place.place + '">';
          html += '<strong>' + place.place + '</strong>';
          if (place.gedcom) {
            html += ' <em>(' + place.gedcom + ')</em>';
          }
          html += '</div>';
        });
      }

      $('#place-search-results').html(html);
    }

    // Select place from results
    $(document).on('click', '.place-result-item', function() {
      var placeName = $(this).data('place');
      $('#' + currentTarget).val(placeName);
      $('#place-finder-modal').hide();
      $('#place-search-input').val('');
      $('#place-search-results').empty();
    });

    // Preview merge
    $('#preview-merge').on('click', function() {
      var sourcePlace = $('#source_place').val().trim();
      var targetPlace = $('#target_place').val().trim();

      if (!sourcePlace || !targetPlace) {
        alert('<?php _e('Please enter both source and target place names.', 'heritagepress'); ?>');
        return;
      }

      if (sourcePlace === targetPlace) {
        alert('<?php _e('Source and target places cannot be the same.', 'heritagepress'); ?>');
        return;
      }

      // TODO: Implement merge preview
      $('#merge-preview').show();
      $('#preview-content').html('<p><?php _e('Merge preview functionality will be implemented.', 'heritagepress'); ?></p>');
    });

    // Find duplicates
    $('#find-duplicates').on('click', function() {
      $(this).prop('disabled', true).text('<?php _e('Searching...', 'heritagepress'); ?>');

      // TODO: Implement duplicate finder
      setTimeout(function() {
        $('#duplicates-results').show();
        $('#duplicates-list').html('<p><?php _e('Duplicate finder functionality will be implemented.', 'heritagepress'); ?></p>');
        $('#find-duplicates').prop('disabled', false).text('<?php _e('Find Potential Duplicates', 'heritagepress'); ?>');
      }, 1000);
    });

    // Form validation
    $('#merge-places-form').on('submit', function(e) {
      var sourcePlace = $('#source_place').val().trim();
      var targetPlace = $('#target_place').val().trim();

      if (!sourcePlace || !targetPlace) {
        alert('<?php _e('Please enter both source and target place names.', 'heritagepress'); ?>');
        e.preventDefault();
        return false;
      }

      if (sourcePlace === targetPlace) {
        alert('<?php _e('Source and target places cannot be the same.', 'heritagepress'); ?>');
        e.preventDefault();
        return false;
      }
    });
  });
</script>
