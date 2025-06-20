<?php

/**
 * HeritagePress Places Management Interface
 *
 * Main places listing and search interface
 * Replicates admin_places.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

// Initialize place controller if not already loaded
if (!class_exists('HP_Place_Controller')) {
  require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-place-controller.php';
}

$place_controller = new HP_Place_Controller();

// Handle search parameters
$search_params = array(
  'search_string' => sanitize_text_field($_GET['search_string'] ?? ''),
  'exact_match' => !empty($_GET['exact_match']),
  'no_coords' => !empty($_GET['no_coords']),
  'no_events' => !empty($_GET['no_events']),
  'no_level' => !empty($_GET['no_level']),
  'temples' => !empty($_GET['temples']),
  'tree' => sanitize_text_field($_GET['tree'] ?? ''),
  'order' => sanitize_text_field($_GET['order'] ?? 'name'),
  'limit' => 50,
  'offset' => intval($_GET['offset'] ?? 0)
);

// Get current page for pagination
$current_page = intval($_GET['paged'] ?? 1);
$search_params['offset'] = ($current_page - 1) * $search_params['limit'];

// Perform search
$places = $place_controller->search_places($search_params);
$total_count = $place_controller->get_places_count($search_params);
$total_pages = ceil($total_count / $search_params['limit']);

// Get available trees
global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$trees = $wpdb->get_results("SELECT gedcom, treename FROM {$trees_table} ORDER BY treename", ARRAY_A);

?>

<div class="wrap">
  <h1><?php _e('Places Management', 'heritagepress'); ?></h1>

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper">
    <a href="?page=hp-places" class="nav-tab nav-tab-active"><?php _e('Search Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-add" class="nav-tab"><?php _e('Add New', 'heritagepress'); ?></a>
    <a href="?page=hp-places-merge" class="nav-tab"><?php _e('Merge Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-geocode" class="nav-tab"><?php _e('Geocode', 'heritagepress'); ?></a>
  </nav>

  <!-- Search Form -->
  <div class="hp-search-section">
    <form method="get" id="places-search-form">
      <input type="hidden" name="page" value="hp-places">

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="search_string"><?php _e('Search Places:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" id="search_string" name="search_string"
              value="<?php echo esc_attr($search_params['search_string']); ?>"
              class="regular-text" />
            <label>
              <input type="checkbox" name="exact_match" value="1"
                <?php checked($search_params['exact_match']); ?>>
              <?php _e('Exact match', 'heritagepress'); ?>
            </label>
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
                    <?php selected($search_params['tree'], $tree['gedcom']); ?>>
                    <?php echo esc_html($tree['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        <?php endif; ?>

        <tr>
          <th scope="row"><?php _e('Filters:', 'heritagepress'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="no_coords" value="1"
                <?php checked($search_params['no_coords']); ?>>
              <?php _e('Places without coordinates', 'heritagepress'); ?>
            </label><br>

            <label>
              <input type="checkbox" name="no_events" value="1"
                <?php checked($search_params['no_events']); ?>>
              <?php _e('Places not used in events', 'heritagepress'); ?>
            </label><br>

            <label>
              <input type="checkbox" name="no_level" value="1"
                <?php checked($search_params['no_level']); ?>>
              <?php _e('Places without level designation', 'heritagepress'); ?>
            </label><br>

            <label>
              <input type="checkbox" name="temples" value="1"
                <?php checked($search_params['temples']); ?>>
              <?php _e('LDS Temples only', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" name="search" class="button-primary"
          value="<?php _e('Search Places', 'heritagepress'); ?>">
        <a href="?page=hp-places" class="button"><?php _e('Clear Search', 'heritagepress'); ?></a>
      </p>
    </form>
  </div>

  <!-- Results Section -->
  <div class="hp-results-section">
    <div class="tablenav top">
      <div class="alignleft actions">
        <span class="displaying-num">
          <?php
          printf(_n(
            '%s place found',
            '%s places found',
            $total_count,
            'heritagepress'
          ), number_format_i18n($total_count));
          ?>
        </span>
      </div>

      <?php if ($total_pages > 1): ?>
        <div class="tablenav-pages">
          <?php
          $page_links = paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;'),
            'next_text' => __('&raquo;'),
            'total' => $total_pages,
            'current' => $current_page,
            'type' => 'plain'
          ));
          echo $page_links;
          ?>
        </div>
      <?php endif; ?>
    </div>

    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th scope="col" class="manage-column column-actions" style="width: 80px;">
            <?php _e('Actions', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column column-place sortable">
            <?php
            $order_url = add_query_arg(array_merge($_GET, array('order' => ($search_params['order'] == 'name') ? 'nameup' : 'name')));
            $sort_icon = ($search_params['order'] == 'name') ? ' ▼' : (($search_params['order'] == 'nameup') ? ' ▲' : '');
            ?>
            <a href="<?php echo esc_url($order_url); ?>">
              <?php _e('Place', 'heritagepress'); ?><?php echo $sort_icon; ?>
            </a>
          </th>
          <th scope="col" class="manage-column column-level">
            <?php _e('Level', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column column-coordinates">
            <?php _e('Coordinates', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column column-temple">
            <?php _e('Temple', 'heritagepress'); ?>
          </th>
          <?php if (!empty($trees)): ?>
            <th scope="col" class="manage-column column-tree">
              <?php _e('Tree', 'heritagepress'); ?>
            </th>
          <?php endif; ?>
          <th scope="col" class="manage-column column-modified sortable">
            <?php
            $change_url = add_query_arg(array_merge($_GET, array('order' => ($search_params['order'] == 'change') ? 'changeup' : 'change')));
            $change_icon = ($search_params['order'] == 'change') ? ' ▼' : (($search_params['order'] == 'changeup') ? ' ▲' : '');
            ?>
            <a href="<?php echo esc_url($change_url); ?>">
              <?php _e('Last Modified', 'heritagepress'); ?><?php echo $change_icon; ?>
            </a>
          </th>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($places)): ?>
          <tr>
            <td colspan="7" class="no-items">
              <?php _e('No places found matching your criteria.', 'heritagepress'); ?>
            </td>
          </tr>
        <?php else: ?>

          <?php foreach ($places as $place): ?>
            <tr>
              <td class="column-actions">
                <a href="?page=hp-places-edit&id=<?php echo esc_attr($place['ID']); ?>"
                  class="button button-small" title="<?php _e('Edit Place', 'heritagepress'); ?>">
                  <?php _e('Edit', 'heritagepress'); ?>
                </a>
                <a href="#" class="button button-small delete-place"
                  data-place-id="<?php echo esc_attr($place['ID']); ?>"
                  data-place-name="<?php echo esc_attr($place['place']); ?>"
                  title="<?php _e('Delete Place', 'heritagepress'); ?>">
                  <?php _e('Delete', 'heritagepress'); ?>
                </a>
              </td>

              <td class="column-place">
                <strong>
                  <a href="?page=hp-places-edit&id=<?php echo esc_attr($place['ID']); ?>">
                    <?php echo esc_html($place['place']); ?>
                  </a>
                </strong>
                <?php if (!empty($place['notes'])): ?>
                  <br><small class="description">
                    <?php echo esc_html(wp_trim_words($place['notes'], 10)); ?>
                  </small>
                <?php endif; ?>
              </td>

              <td class="column-level">
                <?php
                if ($place['placelevel'] > 0) {
                  $levels = array(
                    1 => __('Country', 'heritagepress'),
                    2 => __('State/Province', 'heritagepress'),
                    3 => __('County', 'heritagepress'),
                    4 => __('City/Town', 'heritagepress'),
                    5 => __('Locality', 'heritagepress'),
                    6 => __('Address', 'heritagepress')
                  );
                  echo esc_html($levels[$place['placelevel']] ?? $place['placelevel']);
                } elseif ($place['placelevel'] == -1) {
                  echo '<em>' . __('Do not geocode', 'heritagepress') . '</em>';
                } else {
                  echo '<span class="description">' . __('No level', 'heritagepress') . '</span>';
                }
                ?>
              </td>

              <td class="column-coordinates">
                <?php if (!empty($place['latitude']) && !empty($place['longitude'])): ?>
                  <?php echo esc_html($place['latitude']); ?>, <?php echo esc_html($place['longitude']); ?>
                  <?php if (!empty($place['zoom'])): ?>
                    <br><small><?php echo sprintf(__('Zoom: %d', 'heritagepress'), $place['zoom']); ?></small>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="description"><?php _e('No coordinates', 'heritagepress'); ?></span>
                <?php endif; ?>
              </td>

              <td class="column-temple">
                <?php if ($place['temple']): ?>
                  <span class="dashicons dashicons-yes-alt" title="<?php _e('LDS Temple', 'heritagepress'); ?>"></span>
                <?php else: ?>
                  <span class="description">—</span>
                <?php endif; ?>
              </td>

              <?php if (!empty($trees)): ?>
                <td class="column-tree">
                  <?php
                  if (!empty($place['gedcom'])) {
                    $tree_name = '';
                    foreach ($trees as $tree) {
                      if ($tree['gedcom'] === $place['gedcom']) {
                        $tree_name = $tree['treename'];
                        break;
                      }
                    }
                    echo esc_html($tree_name ?: $place['gedcom']);
                  } else {
                    echo '<span class="description">' . __('All trees', 'heritagepress') . '</span>';
                  }
                  ?>
                </td>
              <?php endif; ?>

              <td class="column-modified">
                <?php echo esc_html($place['changedatef']); ?>
                <?php if (!empty($place['changedby'])): ?>
                  <br><small><?php echo esc_html($place['changedby']); ?></small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>

        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
      <div class="tablenav bottom">
        <div class="tablenav-pages">
          <?php echo $page_links; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
  .hp-search-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
  }

  .hp-results-section {
    margin-top: 20px;
  }

  .column-actions {
    width: 120px;
  }

  .column-level {
    width: 120px;
  }

  .column-coordinates {
    width: 150px;
  }

  .column-temple {
    width: 80px;
    text-align: center;
  }

  .column-tree {
    width: 120px;
  }

  .column-modified {
    width: 120px;
  }

  .delete-place {
    color: #d63638;
  }

  .delete-place:hover {
    background-color: #d63638;
    color: #fff;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Handle place deletion
    $('.delete-place').on('click', function(e) {
      e.preventDefault();

      var placeId = $(this).data('place-id');
      var placeName = $(this).data('place-name');

      if (confirm('<?php _e('Are you sure you want to delete this place?', 'heritagepress'); ?>\n\n' + placeName)) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_place_delete',
            place_id: placeId,
            nonce: '<?php echo wp_create_nonce('heritagepress_admin'); ?>'
          },
          success: function(response) {
            if (response.success) {
              location.reload();
            } else {
              alert('<?php _e('Error deleting place:', 'heritagepress'); ?> ' + response.data);
            }
          },
          error: function() {
            alert('<?php _e('Error communicating with server.', 'heritagepress'); ?>');
          }
        });
      }
    });
  });
</script>
