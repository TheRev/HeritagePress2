<?php

/**
 * Edit Tree Tab
 * Tree editing form based on admin tree editing
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get tree ID from URL
$tree_id = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';

if (empty($tree_id)) {
  echo '<div class="notice notice-error"><p>' . __('No tree specified for editing.', 'heritagepress') . '</p></div>';
  return;
}

// Get tree data
$trees_table = $wpdb->prefix . 'hp_trees';
$tree = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$trees_table} WHERE gedcom = %s", $tree_id), ARRAY_A);

if (!$tree) {
  echo '<div class="notice notice-error"><p>' . __('Tree not found.', 'heritagepress') . '</p></div>';
  return;
}

// Get statistics
$people_table = $wpdb->prefix . 'hp_people';
$families_table = $wpdb->prefix . 'hp_families';
$sources_table = $wpdb->prefix . 'hp_sources';
$media_table = $wpdb->prefix . 'hp_media';
$repositories_table = $wpdb->prefix . 'hp_repositories';
$xnotes_table = $wpdb->prefix . 'hp_xnotes';

$stats = array(
  'people' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$people_table} WHERE gedcom = %s", $tree_id)),
  'families' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$families_table} WHERE gedcom = %s", $tree_id)),
  'sources' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sources_table} WHERE gedcom = %s", $tree_id)),
  'media' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$media_table} WHERE gedcom = %s", $tree_id)),
  'repositories' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$repositories_table} WHERE gedcom = %s", $tree_id)),
  'notes' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$xnotes_table} WHERE gedcom = %s", $tree_id))
);

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'update_tree') {
  // This is handled in the main admin class
}

?>

<div class="edit-tree-section">
  <!-- Tree Header -->
  <div class="tree-header">
    <h2><?php printf(__('Edit Tree: %s', 'heritagepress'), esc_html($tree['treename'])); ?></h2>
    <p class="tree-id">Tree ID: <code><?php echo esc_html($tree['gedcom']); ?></code></p>
  </div>

  <!-- Tree Statistics -->
  <div class="form-card">
    <div class="form-card-header">
      <h3 class="form-card-title"><?php _e('Tree Statistics', 'heritagepress'); ?></h3>
    </div>
    <div class="form-card-body">
      <div class="tree-stats-detailed">
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($stats['people']); ?></span>
          <span class="stat-label"><?php _e('People', 'heritagepress'); ?></span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($stats['families']); ?></span>
          <span class="stat-label"><?php _e('Families', 'heritagepress'); ?></span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($stats['sources']); ?></span>
          <span class="stat-label"><?php _e('Sources', 'heritagepress'); ?></span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($stats['media']); ?></span>
          <span class="stat-label"><?php _e('Media Files', 'heritagepress'); ?></span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($stats['repositories']); ?></span>
          <span class="stat-label"><?php _e('Repositories', 'heritagepress'); ?></span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($stats['notes']); ?></span>
          <span class="stat-label"><?php _e('Notes', 'heritagepress'); ?></span>
        </div>
      </div>

      <!-- Import History -->
      <?php if (!empty($tree['lastimportdate']) && $tree['lastimportdate'] !== '0000-00-00 00:00:00'): ?>
        <div class="import-history">
          <h4><?php _e('Last Import', 'heritagepress'); ?></h4>
          <p>
            <strong><?php _e('Date:', 'heritagepress'); ?></strong>
            <?php echo esc_html(mysql2date('F j, Y \a\t g:i a', $tree['lastimportdate'])); ?>
          </p>
          <?php if (!empty($tree['importfilename'])): ?>
            <p>
              <strong><?php _e('File:', 'heritagepress'); ?></strong>
              <?php echo esc_html($tree['importfilename']); ?>
            </p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <form method="post" action="" class="tree-edit-form">
    <?php wp_nonce_field('heritagepress_update_tree'); ?>
    <input type="hidden" name="action" value="update_tree">
    <input type="hidden" name="tree_id" value="<?php echo esc_attr($tree['gedcom']); ?>">

    <!-- Basic Tree Information -->
    <div class="form-card">
      <div class="form-card-header">
        <h3 class="form-card-title"><?php _e('Basic Information', 'heritagepress'); ?></h3>
      </div>
      <div class="form-card-body">
        <table class="hp-form-table">
          <tr>
            <td>
              <label for="treename"><?php _e('Tree Name', 'heritagepress'); ?> <span class="required">*</span></label>
            </td>
            <td>
              <input type="text"
                name="treename"
                id="treename"
                value="<?php echo esc_attr($tree['treename']); ?>"
                required
                maxlength="100"
                class="regular-text">
            </td>
          </tr>
          <tr>
            <td>
              <label for="description"><?php _e('Description', 'heritagepress'); ?></label>
            </td>
            <td>
              <textarea name="description"
                id="description"
                rows="3"
                cols="50"
                class="large-text"><?php echo esc_textarea($tree['description']); ?></textarea>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Owner Information -->
    <div class="form-card">
      <div class="form-card-header">
        <h3 class="form-card-title"><?php _e('Owner Information', 'heritagepress'); ?></h3>
      </div>
      <div class="form-card-body">
        <table class="hp-form-table">
          <tr>
            <td>
              <label for="owner"><?php _e('Owner Name', 'heritagepress'); ?></label>
            </td>
            <td>
              <input type="text"
                name="owner"
                id="owner"
                value="<?php echo esc_attr($tree['owner']); ?>"
                maxlength="100"
                class="regular-text">
            </td>
          </tr>
          <tr>
            <td>
              <label for="email"><?php _e('Email Address', 'heritagepress'); ?></label>
            </td>
            <td>
              <input type="email"
                name="email"
                id="email"
                value="<?php echo esc_attr($tree['email']); ?>"
                maxlength="100"
                class="regular-text">
            </td>
          </tr>
          <tr>
            <td>
              <label for="address"><?php _e('Address', 'heritagepress'); ?></label>
            </td>
            <td>
              <textarea name="address"
                id="address"
                rows="3"
                cols="40"
                class="large-text"><?php echo esc_textarea($tree['address']); ?></textarea>
            </td>
          </tr>
          <tr>
            <td>
              <label for="city"><?php _e('City', 'heritagepress'); ?></label>
            </td>
            <td>
              <input type="text"
                name="city"
                id="city"
                value="<?php echo esc_attr($tree['city']); ?>"
                maxlength="50"
                class="regular-text">
            </td>
          </tr>
          <tr>
            <td>
              <label for="state"><?php _e('State/Province', 'heritagepress'); ?></label>
            </td>
            <td>
              <input type="text"
                name="state"
                id="state"
                value="<?php echo esc_attr($tree['state']); ?>"
                maxlength="50"
                class="regular-text">
            </td>
          </tr>
          <tr>
            <td>
              <label for="country"><?php _e('Country', 'heritagepress'); ?></label>
            </td>
            <td>
              <input type="text"
                name="country"
                id="country"
                value="<?php echo esc_attr($tree['country']); ?>"
                maxlength="50"
                class="regular-text">
            </td>
          </tr>
          <tr>
            <td>
              <label for="zip"><?php _e('ZIP/Postal Code', 'heritagepress'); ?></label>
            </td>
            <td>
              <input type="text"
                name="zip"
                id="zip"
                value="<?php echo esc_attr($tree['zip']); ?>"
                maxlength="10"
                class="regular-text">
            </td>
          </tr>
          <tr>
            <td>
              <label for="phone"><?php _e('Phone', 'heritagepress'); ?></label>
            </td>
            <td>
              <input type="text"
                name="phone"
                id="phone"
                value="<?php echo esc_attr($tree['phone']); ?>"
                maxlength="20"
                class="regular-text">
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Tree Settings -->
    <div class="form-card">
      <div class="form-card-header">
        <h3 class="form-card-title"><?php _e('Tree Settings', 'heritagepress'); ?></h3>
      </div>
      <div class="form-card-body">
        <table class="hp-form-table">
          <tr>
            <td colspan="2">
              <label>
                <input type="checkbox"
                  name="private"
                  id="private"
                  value="1"
                  <?php checked($tree['secret'], 1); ?>>
                <?php _e('Private Tree', 'heritagepress'); ?>
              </label>
              <p class="description">
                <?php _e('Private trees are only visible to authorized users.', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <label>
                <input type="checkbox"
                  name="disallowgedcreate"
                  id="disallowgedcreate"
                  value="1"
                  <?php checked($tree['disallowgedcreate'], 1); ?>>
                <?php _e('Disallow GEDCOM Creation', 'heritagepress'); ?>
              </label>
              <p class="description">
                <?php _e('Prevent users from creating GEDCOM files from this tree.', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <label>
                <input type="checkbox"
                  name="disallowpdf"
                  id="disallowpdf"
                  value="1"
                  <?php checked($tree['disallowpdf'], 1); ?>>
                <?php _e('Disallow PDF Creation', 'heritagepress'); ?>
              </label>
              <p class="description">
                <?php _e('Prevent users from creating PDF reports from this tree.', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Submit and Actions -->
    <div class="form-card">
      <div class="form-card-body">
        <div class="submit-row"> <input type="submit"
            value="<?php _e('Update Tree', 'heritagepress'); ?>"
            class="button button-primary button-large">
          <a href="?page=heritagepress-trees&tab=browse"
            class="button button-large"><?php _e('Cancel', 'heritagepress'); ?></a>
        </div>

        <!-- Dangerous Actions -->
        <div class="dangerous-actions">
          <h4><?php _e('Dangerous Actions', 'heritagepress'); ?></h4>
          <p class="description"><?php _e('These actions cannot be undone.', 'heritagepress'); ?></p>

          <button type="button"
            class="button button-secondary clear-tree-btn"
            data-tree-id="<?php echo esc_attr($tree['gedcom']); ?>"
            data-tree-name="<?php echo esc_attr($tree['treename']); ?>">
            <?php _e('Clear Tree Data', 'heritagepress'); ?>
          </button>

          <button type="button"
            class="button button-delete delete-tree-btn"
            data-tree-id="<?php echo esc_attr($tree['gedcom']); ?>"
            data-tree-name="<?php echo esc_attr($tree['treename']); ?>">
            <?php _e('Delete Tree', 'heritagepress'); ?>
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<style>
  .tree-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
  }

  .tree-header h2 {
    margin: 0 0 5px 0;
  }

  .tree-id {
    color: #646970;
    font-family: monospace;
    margin: 0;
  }

  .tree-stats-detailed {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
  }

  .tree-stats-detailed .stat-item {
    background: #f0f6fc;
    border: 1px solid #c6e9ff;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
  }

  .tree-stats-detailed .stat-number {
    display: block;
    font-size: 20px;
    font-weight: bold;
    color: #0073aa;
  }

  .tree-stats-detailed .stat-label {
    display: block;
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
    margin-top: 5px;
  }

  .import-history {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
  }

  .import-history h4 {
    margin: 0 0 10px 0;
    color: #1d2327;
  }

  .import-history p {
    margin: 5px 0;
    font-size: 13px;
  }

  .tree-edit-form .form-card {
    margin-bottom: 20px;
  }

  .tree-edit-form .hp-form-table {
    width: 100%;
  }

  .tree-edit-form .hp-form-table td {
    padding: 12px 8px;
    vertical-align: top;
  }

  .tree-edit-form .hp-form-table td:first-child {
    width: 180px;
    font-weight: 500;
  }

  .tree-edit-form .required {
    color: #d63638;
  }

  .tree-edit-form .description {
    margin-top: 5px;
    font-style: italic;
    color: #646970;
  }

  .tree-edit-form .regular-text,
  .tree-edit-form .large-text {
    width: 400px;
    max-width: 100%;
  }

  .tree-edit-form .large-text {
    width: 500px;
  }

  .submit-row {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #ddd;
  }

  .dangerous-actions {
    padding-top: 20px;
  }

  .dangerous-actions h4 {
    color: #d63638;
    margin-bottom: 10px;
  }

  .dangerous-actions .button {
    margin-right: 10px;
  }

  .button-delete {
    background: #d63638 !important;
    border-color: #d63638 !important;
    color: white !important;
  }

  .button-delete:hover {
    background: #b32d2e !important;
    border-color: #b32d2e !important;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Clear tree data
    $('.clear-tree-btn').on('click', function() {
      var treeId = $(this).data('tree-id');
      var treeName = $(this).data('tree-name');

      if (confirm('<?php _e('Are you sure you want to clear all data from this tree? This will delete all people, families, sources, and media, but keep the tree configuration.', 'heritagepress'); ?>')) {
        $(this).text('Clearing...').prop('disabled', true);

        $.post(ajaxurl, {
          action: 'hp_clear_tree',
          tree_id: treeId,
          nonce: '<?php echo wp_create_nonce("heritagepress_clear_tree"); ?>'
        }, function(response) {
          if (response.success) {
            location.reload();
          } else {
            alert('Error clearing tree: ' + response.data);
            $('.clear-tree-btn').text('Clear Tree Data').prop('disabled', false);
          }
        });
      }
    });

    // Delete tree
    $('.delete-tree-btn').on('click', function() {
      var treeId = $(this).data('tree-id');
      var treeName = $(this).data('tree-name');

      if (confirm('<?php _e('Are you sure you want to delete this tree? This will permanently delete the tree configuration and all associated data.', 'heritagepress'); ?>')) {
        if (confirm('<?php _e('This action cannot be undone. Are you absolutely sure?', 'heritagepress'); ?>')) {
          $(this).text('Deleting...').prop('disabled', true);

          $.post(ajaxurl, {
            action: 'hp_delete_tree',
            tree_id: treeId,
            data_only: 0,
            nonce: '<?php echo wp_create_nonce("heritagepress_delete_tree"); ?>'
          }, function(response) {
            if (response.success) {
              window.location.href = '?page=heritagepress-trees&tab=browse';
            } else {
              alert('Error deleting tree: ' + response.data);
              $('.delete-tree-btn').text('Delete Tree').prop('disabled', false);
            }
          });
        }
      }
    });
  });
</script>
