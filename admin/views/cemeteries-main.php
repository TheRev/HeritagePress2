<?php

/**
 * Cemetery Management Main View
 *
 * @package HeritagePress
 * @subpackage Admin/Views
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get search parameters
$search_string = sanitize_text_field($_GET['search'] ?? '');
$current_page = intval($_GET['paged'] ?? 1);
$per_page = 25;
$offset = ($current_page - 1) * $per_page;

global $wpdb;
$table_name = $wpdb->prefix . 'hp_cemeteries';

// Build search query
$where_clause = '';
$params = array();

if (!empty($search_string)) {
  $where_clause = "WHERE (cemname LIKE %s OR city LIKE %s OR county LIKE %s OR state LIKE %s OR country LIKE %s OR cemeteryID LIKE %s)";
  $search_term = '%' . $wpdb->esc_like($search_string) . '%';
  $params = array($search_term, $search_term, $search_term, $search_term, $search_term, $search_term);
}

// Get total count
$total_query = "SELECT COUNT(*) FROM $table_name $where_clause";
$total_items = $total_query ? $wpdb->get_var($wpdb->prepare($total_query, $params)) : 0;

// Get cemeteries for current page
$query = "SELECT cemeteryID, cemname, city, county, state, country, latitude, longitude, zoom FROM $table_name $where_clause ORDER BY cemname, city, county, state, country LIMIT %d, %d";
$params[] = $offset;
$params[] = $per_page;

$cemeteries = $wpdb->get_results($wpdb->prepare($query, $params));

// Calculate pagination
$total_pages = ceil($total_items / $per_page);
$showing_start = $offset + 1;
$showing_end = min($offset + $per_page, $total_items);

// Show messages
if (isset($_GET['message'])) {
  echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(urldecode($_GET['message'])) . '</p></div>';
}
if (isset($_GET['error'])) {
  echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
}
?>

<div class="wrap">
  <h1 class="wp-heading-inline">
    <?php _e('Cemetery Management', 'heritagepress'); ?>
  </h1>

  <a href="<?php echo admin_url('admin.php?page=hp-cemeteries&action=add'); ?>" class="page-title-action">
    <?php _e('Add New Cemetery', 'heritagepress'); ?>
  </a>

  <hr class="wp-header-end">

  <!-- Search Form -->
  <div class="tablenav top">
    <div class="alignleft actions">
      <form method="get" action="">
        <input type="hidden" name="page" value="hp-cemeteries">
        <input type="search" name="search" value="<?php echo esc_attr($search_string); ?>"
          placeholder="<?php _e('Search cemeteries...', 'heritagepress'); ?>" class="search-input">
        <input type="submit" class="button" value="<?php _e('Search', 'heritagepress'); ?>">
        <?php if (!empty($search_string)): ?>
          <a href="<?php echo admin_url('admin.php?page=hp-cemeteries'); ?>" class="button">
            <?php _e('Reset', 'heritagepress'); ?>
          </a>
        <?php endif; ?>
      </form>
    </div>

    <?php if ($total_items > 0): ?>
      <!-- Bulk Actions -->
      <div class="alignleft actions bulkactions">
        <select name="action" id="bulk-action-selector-top">
          <option value="-1"><?php _e('Bulk Actions', 'heritagepress'); ?></option>
          <option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>
        </select>
        <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'heritagepress'); ?>">
      </div>

      <!-- Pagination Info -->
      <div class="tablenav-pages">
        <span class="displaying-num">
          <?php printf(_n('%s item', '%s items', $total_items, 'heritagepress'), number_format_i18n($total_items)); ?>
        </span>
        <?php if ($total_pages > 1): ?>
          <span class="pagination-links">
            <?php
            $pagination_args = array(
              'base' => add_query_arg('paged', '%#%'),
              'format' => '',
              'prev_text' => '&laquo;',
              'next_text' => '&raquo;',
              'total' => $total_pages,
              'current' => $current_page
            );
            echo paginate_links($pagination_args);
            ?>
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Results Info -->
  <?php if ($total_items > 0): ?>
    <p class="search-results-info">
      <?php printf(__('Showing %d-%d of %d cemeteries', 'heritagepress'), $showing_start, $showing_end, $total_items); ?>
    </p>
  <?php endif; ?>

  <!-- Cemetery Table -->
  <form id="cemetery-form" method="post">
    <?php wp_nonce_field('hp_cemetery_action', 'hp_cemetery_nonce'); ?>
    <input type="hidden" name="hp_cemetery_action" value="delete_selected">

    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <td class="manage-column column-cb check-column">
            <input type="checkbox" id="cb-select-all-1">
          </td>
          <th scope="col" class="manage-column column-id">
            <?php _e('ID', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column column-name column-primary">
            <?php _e('Cemetery Name', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column column-location">
            <?php _e('Location', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column column-coordinates">
            <?php _e('Coordinates', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column column-actions">
            <?php _e('Actions', 'heritagepress'); ?>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($cemeteries)): ?>
          <tr class="no-items">
            <td colspan="6">
              <?php _e('No cemeteries found.', 'heritagepress'); ?>
              <?php if (!empty($search_string)): ?>
                <a href="<?php echo admin_url('admin.php?page=hp-cemeteries'); ?>">
                  <?php _e('View all cemeteries', 'heritagepress'); ?>
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($cemeteries as $cemetery): ?>
            <tr>
              <th scope="row" class="check-column">
                <input type="checkbox" name="cemetery_ids[]" value="<?php echo esc_attr($cemetery->cemeteryID); ?>">
              </th>
              <td class="column-id">
                <?php echo esc_html($cemetery->cemeteryID); ?>
              </td>
              <td class="column-name column-primary">
                <strong>
                  <a href="<?php echo admin_url('admin.php?page=hp-cemeteries&action=edit&id=' . $cemetery->cemeteryID); ?>">
                    <?php echo esc_html($cemetery->cemname ?: __('(Unnamed)', 'heritagepress')); ?>
                  </a>
                </strong>
                <div class="row-actions">
                  <span class="edit">
                    <a href="<?php echo admin_url('admin.php?page=hp-cemeteries&action=edit&id=' . $cemetery->cemeteryID); ?>">
                      <?php _e('Edit', 'heritagepress'); ?>
                    </a>
                  </span>
                  <span class="trash"> |
                    <a href="#" class="delete-cemetery" data-cemetery-id="<?php echo esc_attr($cemetery->cemeteryID); ?>"
                      data-cemetery-name="<?php echo esc_attr($cemetery->cemname); ?>">
                      <?php _e('Delete', 'heritagepress'); ?>
                    </a>
                  </span>
                  <?php if (!empty($cemetery->latitude) && !empty($cemetery->longitude)): ?>
                    <span class="view"> |
                      <a href="#" class="view-map" data-lat="<?php echo esc_attr($cemetery->latitude); ?>"
                        data-lng="<?php echo esc_attr($cemetery->longitude); ?>"
                        data-zoom="<?php echo esc_attr($cemetery->zoom ?: 13); ?>">
                        <?php _e('View Map', 'heritagepress'); ?>
                      </a>
                    </span>
                  <?php endif; ?>
                </div>
                <button type="button" class="toggle-row">
                  <span class="screen-reader-text"><?php _e('Show more details', 'heritagepress'); ?></span>
                </button>
              </td>
              <td class="column-location" data-colname="<?php _e('Location', 'heritagepress'); ?>">
                <?php
                $location_parts = array_filter(array(
                  $cemetery->city,
                  $cemetery->county,
                  $cemetery->state,
                  $cemetery->country
                ));
                echo esc_html(implode(', ', $location_parts));
                ?>
              </td>
              <td class="column-coordinates" data-colname="<?php _e('Coordinates', 'heritagepress'); ?>">
                <?php if (!empty($cemetery->latitude) && !empty($cemetery->longitude)): ?>
                  <span class="coordinates">
                    <?php echo esc_html($cemetery->latitude . ', ' . $cemetery->longitude); ?>
                  </span>
                  <?php if ($cemetery->zoom): ?>
                    <br><small><?php printf(__('Zoom: %d', 'heritagepress'), $cemetery->zoom); ?></small>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="no-coordinates"><?php _e('No coordinates', 'heritagepress'); ?></span>
                <?php endif; ?>
              </td>
              <td class="column-actions" data-colname="<?php _e('Actions', 'heritagepress'); ?>">
                <div class="button-group">
                  <a href="<?php echo admin_url('admin.php?page=hp-cemeteries&action=edit&id=' . $cemetery->cemeteryID); ?>"
                    class="button button-small">
                    <?php _e('Edit', 'heritagepress'); ?>
                  </a>
                  <button type="button" class="button button-small delete-cemetery"
                    data-cemetery-id="<?php echo esc_attr($cemetery->cemeteryID); ?>"
                    data-cemetery-name="<?php echo esc_attr($cemetery->cemname); ?>">
                    <?php _e('Delete', 'heritagepress'); ?>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </form>

  <!-- Bottom Pagination -->
  <?php if ($total_items > 0 && $total_pages > 1): ?>
    <div class="tablenav bottom">
      <div class="tablenav-pages">
        <span class="displaying-num">
          <?php printf(_n('%s item', '%s items', $total_items, 'heritagepress'), number_format_i18n($total_items)); ?>
        </span>
        <span class="pagination-links">
          <?php echo paginate_links($pagination_args); ?>
        </span>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Cemetery Deletion Form -->
<form id="delete-cemetery-form" method="post" style="display: none;">
  <?php wp_nonce_field('hp_cemetery_action', 'hp_cemetery_nonce'); ?>
  <input type="hidden" name="hp_cemetery_action" value="delete">
  <input type="hidden" name="cemetery_id" id="delete-cemetery-id">
</form>

<style>
  .search-input {
    width: 300px;
  }

  .search-results-info {
    margin: 10px 0;
    font-style: italic;
    color: #666;
  }

  .button-group {
    display: flex;
    gap: 5px;
  }

  .coordinates {
    font-family: monospace;
    font-size: 11px;
  }

  .no-coordinates {
    color: #999;
    font-style: italic;
  }

  .column-id {
    width: 60px;
  }

  .column-coordinates {
    width: 150px;
  }

  .column-actions {
    width: 120px;
  }

  @media (max-width: 782px) {
    .button-group {
      flex-direction: column;
    }

    .search-input {
      width: 100%;
      margin-bottom: 5px;
    }
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Handle select all checkbox
    $('#cb-select-all-1').change(function() {
      $('input[name="cemetery_ids[]"]').prop('checked', this.checked);
    });

    // Handle individual checkboxes
    $('input[name="cemetery_ids[]"]').change(function() {
      var total = $('input[name="cemetery_ids[]"]').length;
      var checked = $('input[name="cemetery_ids[]"]:checked').length;
      $('#cb-select-all-1').prop('checked', total === checked);
    });

    // Handle bulk actions
    $('#doaction').click(function(e) {
      var action = $('#bulk-action-selector-top').val();
      if (action === 'delete') {
        var selected = $('input[name="cemetery_ids[]"]:checked').length;
        if (selected === 0) {
          alert('<?php _e('Please select at least one cemetery to delete.', 'heritagepress'); ?>');
          e.preventDefault();
          return false;
        }
        if (!confirm('<?php _e('Are you sure you want to delete the selected cemeteries?', 'heritagepress'); ?>')) {
          e.preventDefault();
          return false;
        }
        // Submit the main form
        $('#cemetery-form').submit();
      }
      e.preventDefault();
    });

    // Handle individual cemetery deletion
    $('.delete-cemetery').click(function(e) {
      e.preventDefault();
      var cemeteryId = $(this).data('cemetery-id');
      var cemeteryName = $(this).data('cemetery-name');

      if (confirm('<?php _e('Are you sure you want to delete cemetery', 'heritagepress'); ?> "' + cemeteryName + '"?')) {
        $('#delete-cemetery-id').val(cemeteryId);
        $('#delete-cemetery-form').submit();
      }
    });

    // Handle map viewing (placeholder - would integrate with mapping service)
    $('.view-map').click(function(e) {
      e.preventDefault();
      var lat = $(this).data('lat');
      var lng = $(this).data('lng');
      var zoom = $(this).data('zoom');

      // Simple implementation - opens Google Maps
      var url = 'https://www.google.com/maps/@' + lat + ',' + lng + ',' + zoom + 'z';
      window.open(url, '_blank');
    });
  });
</script>
