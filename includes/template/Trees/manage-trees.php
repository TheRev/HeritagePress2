<?php

/**
 * Manage Trees Tab
 * Administrative tree management based on TNG admin_trees.php
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get trees with detailed statistics
$trees_table = $wpdb->prefix . 'hp_trees';
$people_table = $wpdb->prefix . 'hp_people';
$families_table = $wpdb->prefix . 'hp_families';
$sources_table = $wpdb->prefix . 'hp_sources';
$media_table = $wpdb->prefix . 'hp_media';
$repositories_table = $wpdb->prefix . 'hp_repositories';
$xnotes_table = $wpdb->prefix . 'hp_xnotes';

// Pagination
$items_per_page = 15;
$current_page = max(1, isset($_GET['paged']) ? (int)$_GET['paged'] : 1);
$offset = ($current_page - 1) * $items_per_page;

// Get trees with comprehensive statistics
$trees_query = "
  SELECT
    t.*,
    COUNT(DISTINCT p.personID) as people_count,
    COUNT(DISTINCT f.familyID) as families_count,
    COUNT(DISTINCT s.sourceID) as sources_count,
    COUNT(DISTINCT m.mediaID) as media_count,
    COUNT(DISTINCT r.repoID) as repos_count,
    COUNT(DISTINCT n.noteID) as notes_count
  FROM {$trees_table} t
  LEFT JOIN {$people_table} p ON t.gedcom = p.gedcom
  LEFT JOIN {$families_table} f ON t.gedcom = f.gedcom
  LEFT JOIN {$sources_table} s ON t.gedcom = s.gedcom
  LEFT JOIN {$media_table} m ON t.gedcom = m.gedcom
  LEFT JOIN {$repositories_table} r ON t.gedcom = r.gedcom
  LEFT JOIN {$xnotes_table} n ON t.gedcom = n.gedcom
  GROUP BY t.gedcom
  ORDER BY t.treename
  LIMIT %d OFFSET %d
";

$trees = $wpdb->get_results($wpdb->prepare($trees_query, $items_per_page, $offset), ARRAY_A);

// Get total count for pagination
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$trees_table}");
$total_pages = ceil($total_items / $items_per_page);

?>

<div class="manage-trees-section">
  <div class="form-card">
    <div class="form-card-header">
      <h2 class="form-card-title"><?php _e('Tree Management', 'heritagepress'); ?></h2>
    </div>
    <div class="form-card-body">
      <!-- Actions Bar -->
      <div class="trees-toolbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <!-- Quick Actions -->
        <div class="quick-actions">
          <a href="?page=heritagepress-trees&tab=add" class="button button-primary"><?php _e('Add New Tree', 'heritagepress'); ?></a>
        </div>
      </div>

      <!-- Trees Management Table -->
      <?php if (!empty($trees)): ?>
        <table class="wp-list-table widefat fixed striped tree-management-table">
          <thead>
            <tr>
              <th style="width: 25%;"><?php _e('Tree Information', 'heritagepress'); ?></th>
              <th style="width: 30%;"><?php _e('Details', 'heritagepress'); ?></th>
              <th style="width: 35%;"><?php _e('Statistics', 'heritagepress'); ?></th>
              <th style="width: 10%;"><?php _e('Actions', 'heritagepress'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trees as $tree): ?>
              <tr class="tree-row">
                <td>
                  <div class="tree-info">
                    <strong class="tree-name"><?php echo esc_html($tree['treename']); ?></strong>
                    <?php if ($tree['secret']): ?>
                      <span class="dashicons dashicons-lock" title="<?php _e('Private Tree', 'heritagepress'); ?>"></span>
                    <?php endif; ?>
                    <div class="tree-id">ID: <?php echo esc_html($tree['gedcom']); ?></div>
                    <?php if (!empty($tree['description'])): ?>
                      <div class="tree-description"><?php echo esc_html(wp_trim_words($tree['description'], 15)); ?></div>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div class="tree-details">
                    <?php if (!empty($tree['owner'])): ?>
                      <div><strong><?php _e('Owner:', 'heritagepress'); ?></strong> <?php echo esc_html($tree['owner']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($tree['email'])): ?>
                      <div><strong><?php _e('Email:', 'heritagepress'); ?></strong>
                        <a href="mailto:<?php echo esc_attr($tree['email']); ?>"><?php echo esc_html($tree['email']); ?></a>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($tree['city']) || !empty($tree['state']) || !empty($tree['country'])): ?>
                      <div><strong><?php _e('Location:', 'heritagepress'); ?></strong>
                        <?php
                        $location_parts = array_filter(array($tree['city'], $tree['state'], $tree['country']));
                        echo esc_html(implode(', ', $location_parts));
                        ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div class="tree-stats-grid">
                    <div class="stat-item">
                      <span class="stat-number"><?php echo number_format((int)$tree['people_count']); ?></span>
                      <span class="stat-label"><?php _e('People', 'heritagepress'); ?></span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number"><?php echo number_format((int)$tree['families_count']); ?></span>
                      <span class="stat-label"><?php _e('Families', 'heritagepress'); ?></span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number"><?php echo number_format((int)$tree['sources_count']); ?></span>
                      <span class="stat-label"><?php _e('Sources', 'heritagepress'); ?></span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number"><?php echo number_format((int)$tree['media_count']); ?></span>
                      <span class="stat-label"><?php _e('Media', 'heritagepress'); ?></span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number"><?php echo number_format((int)$tree['repos_count']); ?></span>
                      <span class="stat-label"><?php _e('Repos', 'heritagepress'); ?></span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number"><?php echo number_format((int)$tree['notes_count']); ?></span>
                      <span class="stat-label"><?php _e('Notes', 'heritagepress'); ?></span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="tree-actions-menu">
                    <a href="?page=heritagepress-trees&tree=<?php echo urlencode($tree['gedcom']); ?>&action=edit"
                      class="button button-small" title="<?php _e('Edit Tree', 'heritagepress'); ?>">
                      <span class="dashicons dashicons-edit"></span>
                    </a>
                    <button type="button"
                      class="button button-small tree-delete-btn"
                      data-tree-id="<?php echo esc_attr($tree['gedcom']); ?>"
                      data-tree-name="<?php echo esc_attr($tree['treename']); ?>"
                      title="<?php _e('Delete Tree', 'heritagepress'); ?>">
                      <span class="dashicons dashicons-trash"></span>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="tablenav bottom">
            <div class="tablenav-pages">
              <span class="displaying-num">
                <?php printf(_n('%s item', '%s items', $total_items, 'heritagepress'), number_format($total_items)); ?>
              </span>
              <?php
              $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $current_page,
                'type' => 'array'
              ));

              if ($page_links) {
                echo '<span class="pagination-links">';
                foreach ($page_links as $link) {
                  echo $link;
                }
                echo '</span>';
              }
              ?>
            </div>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="no-trees-message">
          <?php if (!empty($search_term)): ?>
            <p><?php _e('No trees found matching your search criteria.', 'heritagepress'); ?></p>
          <?php else: ?>
            <p><?php _e('No family trees have been created yet.', 'heritagepress'); ?></p>
            <p><a href="?page=heritagepress-trees&tab=add" class="button button-primary"><?php _e('Add Your First Tree', 'heritagepress'); ?></a></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="tree-delete-modal" class="tree-modal" style="display: none;">
  <div class="tree-modal-content">
    <div class="tree-modal-header">
      <h3><?php _e('Delete Tree', 'heritagepress'); ?></h3>
      <button type="button" class="tree-modal-close">&times;</button>
    </div>
    <div class="tree-modal-body">
      <p><?php _e('Are you sure you want to delete this tree?', 'heritagepress'); ?></p>
      <p><strong id="delete-tree-name"></strong></p>
      <p class="warning"><?php _e('This action cannot be undone. All people, families, sources, and media associated with this tree will be permanently deleted.', 'heritagepress'); ?></p>

      <div class="delete-options">
        <label>
          <input type="checkbox" id="delete-data-only">
          <?php _e('Delete only genealogy data (keep tree configuration)', 'heritagepress'); ?>
        </label>
      </div>
    </div>
    <div class="tree-modal-footer">
      <button type="button" class="button tree-modal-close"><?php _e('Cancel', 'heritagepress'); ?></button>
      <button type="button" id="confirm-tree-delete" class="button button-primary button-delete"><?php _e('Delete Tree', 'heritagepress'); ?></button>
    </div>
  </div>
</div>

<style>
  .tree-management-table .tree-info {
    line-height: 1.4;
  }

  .tree-management-table .tree-name {
    display: block;
    font-size: 14px;
    margin-bottom: 4px;
  }

  .tree-management-table .tree-id {
    font-size: 11px;
    color: #646970;
    font-family: monospace;
  }

  .tree-management-table .tree-description {
    font-size: 12px;
    color: #646970;
    margin-top: 4px;
  }

  .tree-details {
    font-size: 12px;
    line-height: 1.4;
  }

  .tree-details>div {
    margin-bottom: 3px;
  }

  .tree-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    font-size: 11px;
  }

  .stat-item {
    text-align: center;
    padding: 4px;
    background: #f0f6fc;
    border-radius: 3px;
  }

  .stat-item .stat-number {
    display: block;
    font-weight: bold;
    color: #0073aa;
    font-size: 12px;
  }

  .stat-item .stat-label {
    display: block;
    color: #646970;
    text-transform: uppercase;
    font-size: 9px;
    margin-top: 2px;
  }

  .import-info {
    font-size: 11px;
    text-align: center;
  }

  .import-date {
    font-weight: 500;
  }

  .import-time {
    color: #646970;
  }

  .import-file {
    color: #646970;
    margin-top: 4px;
    font-style: italic;
  }

  .no-import {
    color: #d63638;
    font-style: italic;
  }

  .tree-actions-menu {
    display: flex;
    gap: 5px;
    justify-content: center;
  }

  .tree-actions-menu .button {
    padding: 4px 8px;
    min-height: auto;
  }

  .tree-actions-menu .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
  }

  /* Modal Styles */
  .tree-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .tree-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 0;
    border: 1px solid #888;
    border-radius: 4px;
    width: 500px;
    max-width: 90%;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }

  .tree-modal-header {
    padding: 15px 20px;
    background: #f1f1f1;
    border-bottom: 1px solid #ddd;
    border-radius: 4px 4px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .tree-modal-header h3 {
    margin: 0;
  }

  .tree-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .tree-modal-body {
    padding: 20px;
  }

  .tree-modal-body .warning {
    color: #d63638;
    font-weight: 500;
  }

  .delete-options {
    margin-top: 15px;
    padding: 10px;
    background: #f0f6fc;
    border-radius: 4px;
  }

  .tree-modal-footer {
    padding: 15px 20px;
    background: #f1f1f1;
    border-top: 1px solid #ddd;
    border-radius: 0 0 4px 4px;
    text-align: right;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
  }

  .button-delete {
    background: #d63638 !important;
    border-color: #d63638 !important;
  }

  .button-delete:hover {
    background: #b32d2e !important;
    border-color: #b32d2e !important;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Tree delete functionality
    $('.tree-delete-btn').on('click', function() {
      var treeId = $(this).data('tree-id');
      var treeName = $(this).data('tree-name');

      $('#delete-tree-name').text(treeName);
      $('#tree-delete-modal').data('tree-id', treeId).show();
    });

    $('.tree-modal-close').on('click', function() {
      $('#tree-delete-modal').hide();
    });

    $('#confirm-tree-delete').on('click', function() {
      var treeId = $('#tree-delete-modal').data('tree-id');
      var dataOnly = $('#delete-data-only').is(':checked');

      // Show loading state
      $(this).text('Deleting...').prop('disabled', true);

      $.post(ajaxurl, {
        action: 'hp_delete_tree',
        tree_id: treeId,
        data_only: dataOnly ? 1 : 0,
        nonce: '<?php echo wp_create_nonce("heritagepress_delete_tree"); ?>'
      }, function(response) {
        if (response.success) {
          location.reload();
        } else {
          alert('Error deleting tree: ' + response.data);
          $('#confirm-tree-delete').text('Delete Tree').prop('disabled', false);
        }
      });
    });

    $(window).on('click', function(event) {
      if ($(event.target).is('#tree-delete-modal')) {
        $('#tree-delete-modal').hide();
      }
    });
  });
</script>
