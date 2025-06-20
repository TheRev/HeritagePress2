<?php

/**
 * Source Management Main View
 *
 * @package HeritagePress
 * @subpackage Admin/Views
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$sources_per_page = 25;
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';

// Get sources data
$sources_data = $this->source_controller->get_sources_list($current_page, $sources_per_page, $search_term, $filter_type);
$sources = $sources_data['sources'];
$total_sources = $sources_data['total'];
$total_pages = ceil($total_sources / $sources_per_page);

// Get source types for filter dropdown
global $wpdb;
$source_types = $wpdb->get_col("SELECT DISTINCT sourcetype FROM {$wpdb->prefix}hp_sources WHERE sourcetype IS NOT NULL AND sourcetype != '' ORDER BY sourcetype");
?>

<div class="wrap">
  <h1 class="wp-heading-inline">
    <?php _e('Source Management', 'heritagepress'); ?>
    <a href="<?php echo admin_url('admin.php?page=hp-sources&action=add'); ?>" class="page-title-action">
      <?php _e('Add New Source', 'heritagepress'); ?>
    </a>
  </h1>

  <?php if (isset($_GET['message'])): ?>
    <div class="notice notice-success is-dismissible">
      <p>
        <?php
        switch ($_GET['message']) {
          case 'added':
            _e('Source added successfully.', 'heritagepress');
            break;
          case 'updated':
            _e('Source updated successfully.', 'heritagepress');
            break;
          case 'deleted':
            _e('Source deleted successfully.', 'heritagepress');
            break;
          case 'merged':
            _e('Sources merged successfully.', 'heritagepress');
            break;
          default:
            echo esc_html($_GET['message']);
        }
        ?>
      </p>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="notice notice-error is-dismissible">
      <p>
        <?php
        switch ($_GET['error']) {
          case 'not_found':
            _e('Source not found.', 'heritagepress');
            break;
          case 'delete_failed':
            _e('Failed to delete source.', 'heritagepress');
            break;
          case 'merge_failed':
            _e('Failed to merge sources.', 'heritagepress');
            break;
          default:
            echo esc_html($_GET['error']);
        }
        ?>
      </p>
    </div>
  <?php endif; ?>

  <!-- Search and Filter Form -->
  <div class="tablenav top">
    <form method="get" action="">
      <input type="hidden" name="page" value="hp-sources" />

      <div class="alignleft actions">
        <input type="search" name="search" value="<?php echo esc_attr($search_term); ?>"
          placeholder="<?php _e('Search sources...', 'heritagepress'); ?>" />

        <select name="filter_type">
          <option value=""><?php _e('All Types', 'heritagepress'); ?></option>
          <?php foreach ($source_types as $type): ?>
            <option value="<?php echo esc_attr($type); ?>" <?php selected($filter_type, $type); ?>>
              <?php echo esc_html($type); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <input type="submit" class="button" value="<?php _e('Filter', 'heritagepress'); ?>" />

        <?php if ($search_term || $filter_type): ?>
          <a href="<?php echo admin_url('admin.php?page=hp-sources'); ?>" class="button">
            <?php _e('Clear', 'heritagepress'); ?>
          </a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Bulk Actions -->
    <div class="alignleft actions bulkactions">
      <label for="bulk-action-selector-top" class="screen-reader-text">
        <?php _e('Select bulk action', 'heritagepress'); ?>
      </label>
      <select name="action" id="bulk-action-selector-top">
        <option value="-1"><?php _e('Bulk Actions', 'heritagepress'); ?></option>
        <option value="merge"><?php _e('Merge Sources', 'heritagepress'); ?></option>
        <option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>
      </select>
      <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'heritagepress'); ?>" />
    </div>

    <!-- Pagination Info -->
    <div class="tablenav-pages">
      <span class="displaying-num">
        <?php printf(_n('%s item', '%s items', $total_sources, 'heritagepress'), number_format_i18n($total_sources)); ?>
      </span>
    </div>
  </div>

  <!-- Sources Table -->
  <form id="sources-form" method="post">
    <?php wp_nonce_field('hp_sources_bulk_action', 'hp_sources_nonce'); ?>

    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <td id="cb" class="manage-column column-cb check-column">
            <input id="cb-select-all-1" type="checkbox" />
          </td>
          <th scope="col" class="manage-column column-title column-primary">
            <?php _e('Title', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column">
            <?php _e('Author', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column">
            <?php _e('Type', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column">
            <?php _e('Publication', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column">
            <?php _e('Repository', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column">
            <?php _e('Citations', 'heritagepress'); ?>
          </th>
          <th scope="col" class="manage-column">
            <?php _e('Actions', 'heritagepress'); ?>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sources)): ?>
          <tr class="no-items">
            <td class="colspanchange" colspan="8">
              <?php _e('No sources found.', 'heritagepress'); ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($sources as $source): ?>
            <tr>
              <th scope="row" class="check-column">
                <input type="checkbox" name="source_ids[]" value="<?php echo esc_attr($source->sourceID); ?>" />
              </th>

              <td class="title column-title column-primary" data-colname="Title">
                <strong>
                  <a href="<?php echo admin_url('admin.php?page=hp-sources&action=edit&id=' . $source->sourceID); ?>">
                    <?php echo esc_html($source->title ?: __('(No Title)', 'heritagepress')); ?>
                  </a>
                </strong>
                <div class="row-actions">
                  <span class="edit">
                    <a href="<?php echo admin_url('admin.php?page=hp-sources&action=edit&id=' . $source->sourceID); ?>">
                      <?php _e('Edit', 'heritagepress'); ?>
                    </a> |
                  </span>
                  <span class="view">
                    <a href="<?php echo home_url('/source/' . $source->sourceID); ?>" target="_blank">
                      <?php _e('View', 'heritagepress'); ?>
                    </a> |
                  </span>
                  <span class="delete">
                    <a href="#" class="delete-source" data-source-id="<?php echo esc_attr($source->sourceID); ?>"
                      data-source-title="<?php echo esc_attr($source->title); ?>">
                      <?php _e('Delete', 'heritagepress'); ?>
                    </a>
                  </span>
                </div>
              </td>

              <td class="author column-author" data-colname="Author">
                <?php echo esc_html($source->author ?: '-'); ?>
              </td>

              <td class="type column-type" data-colname="Type">
                <?php echo esc_html($source->sourcetype ?: '-'); ?>
              </td>

              <td class="publication column-publication" data-colname="Publication">
                <?php echo esc_html($source->publisherinfo ?: '-'); ?>
              </td>

              <td class="repository column-repository" data-colname="Repository">
                <?php if ($source->repository_name): ?>
                  <a href="<?php echo admin_url('admin.php?page=hp-repositories&action=edit&id=' . $source->repositoryID); ?>">
                    <?php echo esc_html($source->repository_name); ?>
                  </a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>

              <td class="citations column-citations" data-colname="Citations">
                <?php echo intval($source->citation_count); ?>
              </td>

              <td class="actions column-actions" data-colname="Actions">
                <a href="<?php echo admin_url('admin.php?page=hp-sources&action=edit&id=' . $source->sourceID); ?>"
                  class="button button-small">
                  <?php _e('Edit', 'heritagepress'); ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </form>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
      <div class="tablenav-pages">
        <?php
        $pagination_args = array(
          'base' => add_query_arg('paged', '%#%'),
          'format' => '',
          'prev_text' => '&laquo;',
          'next_text' => '&raquo;',
          'total' => $total_pages,
          'current' => $current_page,
          'type' => 'plain'
        );
        echo paginate_links($pagination_args);
        ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-source-modal" style="display: none;">
  <div class="modal-content">
    <h3><?php _e('Confirm Delete', 'heritagepress'); ?></h3>
    <p id="delete-source-message"></p>
    <div class="modal-actions">
      <button type="button" class="button button-secondary" id="cancel-delete">
        <?php _e('Cancel', 'heritagepress'); ?>
      </button>
      <button type="button" class="button button-primary" id="confirm-delete">
        <?php _e('Delete', 'heritagepress'); ?>
      </button>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Delete source confirmation
    $('.delete-source').on('click', function(e) {
      e.preventDefault();

      var sourceId = $(this).data('source-id');
      var sourceTitle = $(this).data('source-title');

      $('#delete-source-message').text(
        '<?php _e('Are you sure you want to delete the source "', 'heritagepress'); ?>' +
        sourceTitle +
        '"<?php _e('? This action cannot be undone.', 'heritagepress'); ?>'
      );

      $('#delete-source-modal').show();

      $('#confirm-delete').off('click').on('click', function() {
        window.location.href = '<?php echo admin_url('admin.php?page=hp-sources&action=delete&id='); ?>' +
          sourceId +
          '&_wpnonce=<?php echo wp_create_nonce('hp_delete_source'); ?>';
      });
    });

    $('#cancel-delete').on('click', function() {
      $('#delete-source-modal').hide();
    });

    // Bulk actions
    $('#doaction').on('click', function(e) {
      var action = $('#bulk-action-selector-top').val();
      var checkedBoxes = $('input[name="source_ids[]"]:checked');

      if (action === '-1') {
        e.preventDefault();
        alert('<?php _e('Please select an action.', 'heritagepress'); ?>');
        return;
      }

      if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('<?php _e('Please select at least one source.', 'heritagepress'); ?>');
        return;
      }

      if (action === 'delete') {
        e.preventDefault();
        if (confirm('<?php _e('Are you sure you want to delete the selected sources?', 'heritagepress'); ?>')) {
          $('#sources-form').attr('action', '<?php echo admin_url('admin.php?page=hp-sources&action=bulk_delete'); ?>').submit();
        }
      } else if (action === 'merge') {
        e.preventDefault();
        if (checkedBoxes.length < 2) {
          alert('<?php _e('Please select at least 2 sources to merge.', 'heritagepress'); ?>');
          return;
        }

        var sourceIds = [];
        checkedBoxes.each(function() {
          sourceIds.push($(this).val());
        });

        window.location.href = '<?php echo admin_url('admin.php?page=hp-sources&action=merge&ids='); ?>' + sourceIds.join(',');
      }
    });

    // Select all checkbox
    $('#cb-select-all-1').on('change', function() {
      $('input[name="source_ids[]"]').prop('checked', this.checked);
    });
  });
</script>

<style>
  #delete-source-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999999;
  }

  #delete-source-modal .modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 4px;
    max-width: 400px;
    width: 90%;
  }

  #delete-source-modal .modal-actions {
    text-align: right;
    margin-top: 20px;
  }

  #delete-source-modal .modal-actions .button {
    margin-left: 10px;
  }
</style>
