<?php

/**
 * Entity Transfer Integration
 *
 * Helper functions to integrate entity transfer functionality into existing forms
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Add "Change Tree" button to entity edit forms
 *
 * @param string $entity_type Entity type (person, source, repository)
 * @param string $entity_id Entity ID
 * @param string $current_tree Current tree ID
 */
function heritagepress_add_change_tree_button($entity_type, $entity_id, $current_tree)
{
  if (empty($entity_type) || empty($entity_id) || empty($current_tree)) {
    return;
  }

  // Check if user has permission to edit genealogy
  if (!current_user_can('edit_genealogy')) {
    return;
  }

  // Get available trees (exclude current tree)
  global $wpdb;
  $trees_table = $wpdb->prefix . 'hp_trees';
  $tree_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$trees_table} WHERE gedcom != %s",
    $current_tree
  ));

  // Only show if there are other trees available
  if ($tree_count == 0) {
    return;
  }

?>
  <div class="change-tree-section">
    <h3><?php _e('Tree Management', 'heritagepress'); ?></h3>
    <table class="form-table">
      <tr>
        <th scope="row"><?php _e('Change Tree', 'heritagepress'); ?></th>
        <td>
          <button type="button" id="change-tree-btn" class="button button-secondary"
            data-entity-type="<?php echo esc_attr($entity_type); ?>"
            data-entity-id="<?php echo esc_attr($entity_id); ?>"
            data-tree-id="<?php echo esc_attr($current_tree); ?>">
            <?php _e('Move to Different Tree', 'heritagepress'); ?>
          </button>
          <p class="description">
            <?php _e('Transfer this entity to a different tree. All associated data (events, notes, media) will be moved with it.', 'heritagepress'); ?>
          </p>
        </td>
      </tr>
    </table>
  </div>

  <script>
    jQuery(document).ready(function($) {
      $('#change-tree-btn').on('click', function() {
        var entityType = $(this).data('entity-type');
        var entityId = $(this).data('entity-id');
        var treeId = $(this).data('tree-id');

        // Trigger the transfer modal
        $(document).trigger('heritagepress:open-transfer-modal', {
          entityType: entityType,
          entityId: entityId,
          treeId: treeId
        });
      });
    });
  </script>
<?php
}

/**
 * Add quick transfer links to list tables
 *
 * @param string $entity_type Entity type
 * @param string $entity_id Entity ID
 * @param string $current_tree Current tree ID
 * @return string HTML for transfer link
 */
function heritagepress_get_transfer_link($entity_type, $entity_id, $current_tree)
{
  if (!current_user_can('edit_genealogy')) {
    return '';
  }

  return sprintf(
    '<a href="#" class="transfer-entity-link" data-entity-type="%s" data-entity-id="%s" data-tree-id="%s" title="%s">%s</a>',
    esc_attr($entity_type),
    esc_attr($entity_id),
    esc_attr($current_tree),
    esc_attr(__('Transfer to different tree', 'heritagepress')),
    __('Transfer', 'heritagepress')
  );
}

/**
 * Initialize transfer modal on admin pages
 */
function heritagepress_init_transfer_modal()
{
  // Only load on relevant admin pages
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;

  // Get current page and action from query string
  $page = isset($_GET['page']) ? $_GET['page'] : '';
  $action = isset($_GET['action']) ? $_GET['action'] : '';
  $section = isset($_GET['section']) ? $_GET['section'] : '';

  // Only show modal on edit/add/update for people or families (not on browse, not on other sections)
  $show_modal = false;
  if (
    ($page === 'heritagepress-people' || $page === 'heritagepress-families') &&
    (in_array($action, ['edit', 'add', 'update']) || empty($action)) &&
    empty($section) // section param must be empty (prevents albums/media/etc)
  ) {
    $show_modal = true;
  }

  // Debug: output a comment for troubleshooting
  echo "<!-- heritagepress_init_transfer_modal: page=$page, action=$action, section=$section, show_modal=" . ($show_modal ? 'yes' : 'no') . " -->\n";

  if (!$show_modal) {
    return;
  }

  // Use plugin_dir_path if available, otherwise fallback
  if (function_exists('plugin_dir_path')) {
    $modal_path = plugin_dir_path(__FILE__) . '../views/entity-transfer-modal.php';
  } else {
    $modal_path = dirname(__FILE__) . '/../views/entity-transfer-modal.php';
  }

  ob_start();
  include $modal_path;
  $modal_html = ob_get_clean();
  // Print after all content, just before </body>
  echo '<div style="display:none !important;">' . $modal_html . '</div>';

  // Add JavaScript for transfer links
?>
  <script>
    jQuery(document).ready(function($) {
      // Handle transfer links in list tables
      $(document).on('click', '.transfer-entity-link', function(e) {
        e.preventDefault();

        var entityType = $(this).data('entity-type');
        var entityId = $(this).data('entity-id');
        var treeId = $(this).data('tree-id');

        $(document).trigger('heritagepress:open-transfer-modal', {
          entityType: entityType,
          entityId: entityId,
          treeId: treeId
        });
      });
    });
  </script>
<?php
}

// Hook to initialize transfer modal on admin pages
add_action('admin_footer', 'heritagepress_init_transfer_modal');

/**
 * Enqueue styles for transfer functionality
 */
function heritagepress_enqueue_transfer_styles()
{
  $screen = get_current_screen();

  if (!$screen || strpos($screen->id, 'heritagepress') === false) {
    return;
  }

  // Add minimal CSS for transfer links
?>
  <style>
    .change-tree-section {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #ddd;
    }

    .transfer-entity-link {
      color: #0073aa;
      text-decoration: none;
      font-size: 12px;
    }

    .transfer-entity-link:hover {
      color: #005177;
      text-decoration: underline;
    }

    .transfer-entity-link:before {
      content: "⇄ ";
      font-weight: bold;
    }
  </style>
<?php
}

add_action('admin_head', 'heritagepress_enqueue_transfer_styles');
