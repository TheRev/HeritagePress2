<?php

/**
 * Browse Trees Tab - Admin Interface
 * Complete facsimile of admin trees with all administrative functions
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get trees with full statistics
$trees_table = $wpdb->prefix . 'hp_trees';
$people_table = $wpdb->prefix . 'hp_people';
$families_table = $wpdb->prefix . 'hp_families';
$sources_table = $wpdb->prefix . 'hp_sources';
$media_table = $wpdb->prefix . 'hp_media';

// Handle bulk actions
if (isset($_POST['action']) && $_POST['action'] !== '-1' && !empty($_POST['tree_ids'])) {
  if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_bulk_trees')) {
    wp_die('Security check failed');
  }

  $tree_ids = array_map('sanitize_text_field', $_POST['tree_ids']);
  $action = sanitize_text_field($_POST['action']);

  switch ($action) {
    case 'delete':
      foreach ($tree_ids as $tree_id) {
        // Delete tree logic here
      }
      break;
    case 'clear_data':
      foreach ($tree_ids as $tree_id) {
        // Clear tree data logic here
      }
      break;
  }
}

// Search functionality
$search_term = isset($_GET['searchstring']) ? sanitize_text_field($_GET['searchstring']) : '';
$where_clause = '';
$where_params = array();

if (!empty($search_term)) {
  $where_clause = "WHERE t.gedcom LIKE %s OR t.treename LIKE %s OR t.description LIKE %s OR t.owner LIKE %s";
  $where_params = array("%{$search_term}%", "%{$search_term}%", "%{$search_term}%", "%{$search_term}%");
}

// Get all trees with comprehensive statistics
$trees_query = "
  SELECT
    t.gedcom,
    t.treename,
    t.description,
    t.owner,
    t.email,
    t.secret,    t.disallowgedcreate,
    t.disallowpdf,
    t.lastimportdate,
    t.importfilename,
    t.created,
    COUNT(DISTINCT p.personID) as people_count,
    COUNT(DISTINCT f.familyID) as families_count,
    COUNT(DISTINCT s.sourceID) as sources_count,
    COUNT(DISTINCT m.mediaID) as media_count
  FROM {$trees_table} t
  LEFT JOIN {$people_table} p ON t.gedcom = p.gedcom
  LEFT JOIN {$families_table} f ON t.gedcom = f.gedcom
  LEFT JOIN {$sources_table} s ON t.gedcom = s.gedcom
  LEFT JOIN {$media_table} m ON t.gedcom = m.gedcom
  {$where_clause}
  GROUP BY t.gedcom
  ORDER BY t.treename
";

if (empty($where_params)) {
  $trees = $wpdb->get_results($trees_query, ARRAY_A);
} else {
  $trees = $wpdb->get_results($wpdb->prepare($trees_query, $where_params), ARRAY_A);
}

?>

<div class="browse-trees-admin-section">
  <!-- Compact Header with Actions and Search -->
  <div class="admin-header-compact">
    <div class="header-top">
      <div class="admin-actions">
        <a href="?page=heritagepress-trees&tab=add" class="button button-primary">
          <span class="dashicons dashicons-plus"></span> <?php _e('Add New Tree', 'heritagepress'); ?>
        </a>
        <button type="button" onclick="location.reload()" class="button">
          <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'heritagepress'); ?>
        </button>
      </div>

      <div class="search-section-compact">
        <form method="get" action="" class="tree-search-form">
          <input type="hidden" name="page" value="heritagepress-trees">
          <input type="hidden" name="tab" value="browse">
          <div class="search-controls">
            <input type="text"
              name="searchstring"
              value="<?php echo esc_attr($search_term); ?>"
              placeholder="<?php _e('Search trees...', 'heritagepress'); ?>"
              class="search-input">
            <input type="submit" value="<?php _e('Search', 'heritagepress'); ?>" class="button">
            <?php if (!empty($search_term)): ?>
              <a href="?page=heritagepress-trees&tab=browse" class="button"><?php _e('Clear', 'heritagepress'); ?></a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <?php if (!empty($search_term)): ?>
      <div class="search-results-info">
        <?php printf(
          _n('Found %d tree matching "%s"', 'Found %d trees matching "%s"', count($trees), 'heritagepress'),
          count($trees),
          esc_html($search_term)
        ); ?>
      </div>
    <?php endif; ?>
  </div>

  <form method="post" id="trees-admin-form">
    <?php wp_nonce_field('heritagepress_bulk_trees'); ?>

    <!-- Bulk Actions Bar -->
    <div class="tablenav top">
      <div class="alignleft actions bulkactions">
        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'heritagepress'); ?></label>
        <select name="action" id="bulk-action-selector-top">
          <option value="-1"><?php _e('Bulk Actions', 'heritagepress'); ?></option>
          <option value="delete"><?php _e('Delete Selected Trees', 'heritagepress'); ?></option>
          <option value="clear_data"><?php _e('Clear Tree Data Only', 'heritagepress'); ?></option>
        </select>
        <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'heritagepress'); ?>">
      </div>

      <div class="alignright actions">
        <span class="displaying-num">
          <?php printf(_n('%s tree', '%s trees', count($trees), 'heritagepress'), number_format(count($trees))); ?>
        </span>
      </div>
    </div>

    <!-- Trees Administration Table -->
    <table class="wp-list-table widefat fixed striped trees-admin-table">
      <thead>
        <tr>
          <td id="cb" class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'heritagepress'); ?></label>
            <input id="cb-select-all-1" type="checkbox">
          </td>
          <th scope="col" class="manage-column column-treename"><?php _e('Tree Name', 'heritagepress'); ?></th>
          <th scope="col" class="manage-column column-description"><?php _e('Description', 'heritagepress'); ?></th>
          <th scope="col" class="manage-column column-owner"><?php _e('Owner', 'heritagepress'); ?></th>
          <th scope="col" class="manage-column column-stats"><?php _e('Statistics', 'heritagepress'); ?></th>
          <th scope="col" class="manage-column column-settings"><?php _e('Settings', 'heritagepress'); ?></th>
          <th scope="col" class="manage-column column-created"><?php _e('Tree Created', 'heritagepress'); ?></th>
          <th scope="col" class="manage-column column-lastimport"><?php _e('Last Import', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($trees)): ?>
          <?php foreach ($trees as $tree): ?>
            <tr class="tree-row">
              <th scope="row" class="check-column">
                <input type="checkbox" name="tree_ids[]" value="<?php echo esc_attr($tree['gedcom']); ?>" id="tree_<?php echo esc_attr($tree['gedcom']); ?>">
                <label for="tree_<?php echo esc_attr($tree['gedcom']); ?>" class="screen-reader-text">
                  <?php printf(__('Select %s', 'heritagepress'), esc_html($tree['treename'])); ?>
                </label>
              </th>
              <td class="treename column-treename">
                <strong>
                  <a href="?page=heritagepress-trees&tree=<?php echo urlencode($tree['gedcom']); ?>&action=edit" class="row-title">
                    <?php echo esc_html($tree['treename']); ?>
                  </a>
                  <?php if ($tree['secret']): ?>
                    <span class="dashicons dashicons-lock" title="<?php _e('Private Tree', 'heritagepress'); ?>"></span>
                  <?php endif; ?>
                </strong>
                <div class="tree-id">ID: <?php echo esc_html($tree['gedcom']); ?></div>
                <div class="row-actions">
                  <span class="edit">
                    <a href="?page=heritagepress-trees&tree=<?php echo urlencode($tree['gedcom']); ?>&action=edit">
                      <?php _e('Edit', 'heritagepress'); ?>
                    </a> |
                  </span> <span class="clear">
                    <a href="<?php echo HP_Clear_Tree_Handler::get_clear_tree_url($tree['gedcom'], $tree['treename']); ?>" class="submitdelete">
                      <?php _e('Clear', 'heritagepress'); ?>
                    </a> |
                  </span>
                  <span class="delete">
                    <a href="#" onclick="confirmDeleteTree('<?php echo esc_js($tree['gedcom']); ?>', '<?php echo esc_js($tree['treename']); ?>')" class="submitdelete">
                      <?php _e('Delete', 'heritagepress'); ?>
                    </a>
                  </span>
                </div>
              </td>
              <td class="description column-description">
                <?php echo esc_html($tree['description']); ?>
              </td>
              <td class="owner column-owner">
                <?php echo esc_html($tree['owner']); ?>
                <?php if (!empty($tree['email'])): ?>
                  <br><a href="mailto:<?php echo esc_attr($tree['email']); ?>"><?php echo esc_html($tree['email']); ?></a>
                <?php endif; ?>
              </td>
              <td class="stats column-stats">
                <div class="tree-stats-grid">
                  <div class="stat-row">
                    <span class="stat-compact"><?php echo number_format((int)$tree['people_count']); ?> <?php _e('people', 'heritagepress'); ?></span>
                    <span class="stat-compact"><?php echo number_format((int)$tree['families_count']); ?> <?php _e('families', 'heritagepress'); ?></span>
                  </div>
                  <div class="stat-row">
                    <span class="stat-compact"><?php echo number_format((int)$tree['sources_count']); ?> <?php _e('sources', 'heritagepress'); ?></span>
                    <span class="stat-compact"><?php echo number_format((int)$tree['media_count']); ?> <?php _e('media', 'heritagepress'); ?></span>
                  </div>
                </div>
              </td>
              <td class="settings column-settings">
                <div class="tree-settings">
                  <?php
                  $restrictions = array();
                  if ($tree['disallowgedcreate']) $restrictions[] = 'G';
                  if ($tree['disallowpdf']) $restrictions[] = 'P';
                  $restriction_text = !empty($restrictions) ? ' (' . implode(',', $restrictions) . ')' : '';
                  ?>

                  <?php if ($tree['secret']): ?>
                    <span class="setting-badge private">
                      <span class="dashicons dashicons-lock"></span> Private<?php echo $restriction_text; ?>
                    </span>
                  <?php else: ?>
                    <span class="setting-badge public">
                      <span class="dashicons dashicons-unlock"></span> Public<?php echo $restriction_text; ?>
                    </span>
                  <?php endif; ?>

                  <?php if (!empty($restrictions)): ?>
                    <div class="restrictions-legend">
                      <small>
                        <?php if (in_array('G', $restrictions)): ?>G=No GEDCOM <?php endif; ?>
                      <?php if (in_array('P', $restrictions)): ?>P=No PDF<?php endif; ?>
                      </small>
                    </div>
                  <?php endif; ?>
                </div>
              </td>
              <td class="created column-created">
                <div class="created-info">
                  <?php if (!empty($tree['created']) && $tree['created'] !== '0000-00-00 00:00:00' && $tree['created'] !== '1970-01-01 00:00:00'): ?>
                    <div class="created-date"><?php echo esc_html(mysql2date('M j, Y', $tree['created'])); ?></div>
                    <div class="created-time"><?php echo esc_html(mysql2date('g:i a', $tree['created'])); ?></div>
                  <?php else: ?>
                    <div class="no-created"><?php _e('Unknown', 'heritagepress'); ?></div>
                  <?php endif; ?>
                </div>
              </td>
              <td class="lastimport column-lastimport">
                <div class="import-info">
                  <?php if (!empty($tree['lastimportdate']) && $tree['lastimportdate'] !== '0000-00-00 00:00:00'): ?>
                    <div class="import-date"><?php echo esc_html(mysql2date('M j, Y', $tree['lastimportdate'])); ?></div>
                    <div class="import-time"><?php echo esc_html(mysql2date('g:i a', $tree['lastimportdate'])); ?></div>
                  <?php else: ?>
                    <div class="no-import"><?php _e('Never', 'heritagepress'); ?></div>
                  <?php endif; ?>
                  <?php if (!empty($tree['importfilename'])): ?>
                    <div class="import-file" title="<?php echo esc_attr($tree['importfilename']); ?>">
                      <?php echo esc_html(wp_trim_words($tree['importfilename'], 3, '...')); ?>
                    </div>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?> <?php else: ?> <tr class="no-items">
            <td class="colspanchange" colspan="8">
              <div class="no-trees-message">
                <p><?php _e('No family trees have been created yet.', 'heritagepress'); ?></p>
                <p><a href="?page=heritagepress-trees&tab=add" class="button button-primary"><?php _e('Add Your First Tree', 'heritagepress'); ?></a></p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Bottom Bulk Actions -->
    <div class="tablenav bottom">
      <div class="alignleft actions bulkactions">
        <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action', 'heritagepress'); ?></label>
        <select name="action2" id="bulk-action-selector-bottom">
          <option value="-1"><?php _e('Bulk Actions', 'heritagepress'); ?></option>
          <option value="delete"><?php _e('Delete Selected Trees', 'heritagepress'); ?></option>
          <option value="clear_data"><?php _e('Clear Tree Data Only', 'heritagepress'); ?></option>
        </select>
        <input type="submit" id="doaction2" class="button action" value="<?php _e('Apply', 'heritagepress'); ?>">
      </div>
    </div>
  </form>
</div>

<!-- Admin JavaScript -->
<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Select all checkboxes functionality
    $('#cb-select-all-1, #cb-select-all-2').change(function() {
      var checked = $(this).prop('checked');
      $('input[name="tree_ids[]"]').prop('checked', checked);
    });

    // Update select all when individual checkboxes change
    $('input[name="tree_ids[]"]').change(function() {
      var total = $('input[name="tree_ids[]"]').length;
      var checked = $('input[name="tree_ids[]"]:checked').length;
      $('#cb-select-all-1, #cb-select-all-2').prop('checked', total === checked);
    });

    // Bulk actions form submission
    $('#trees-admin-form').submit(function(e) {
      var action = $('#bulk-action-selector-top').val();
      if (action === '-1') {
        action = $('#bulk-action-selector-bottom').val();
      }

      if (action === '-1') {
        e.preventDefault();
        alert('<?php esc_js(_e('Please select an action to perform.', 'heritagepress')); ?>');
        return false;
      }

      var checkedBoxes = $('input[name="tree_ids[]"]:checked');
      if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('<?php esc_js(_e('Please select at least one tree to perform this action on.', 'heritagepress')); ?>');
        return false;
      }

      if (action === 'delete') {
        var treeNames = [];
        checkedBoxes.each(function() {
          var row = $(this).closest('tr');
          treeNames.push(row.find('.row-title').text());
        });

        if (!confirm('<?php esc_js(_e('Are you sure you want to delete the following trees?', 'heritagepress')); ?>\n\n' + treeNames.join('\n'))) {
          e.preventDefault();
          return false;
        }
      }
    });
  });

  function confirmDeleteTree(treeId, treeName) {
    if (confirm('<?php esc_js(_e('Are you sure you want to delete the tree:', 'heritagepress')); ?> "' + treeName + '"?\n\n<?php esc_js(_e('This action cannot be undone.', 'heritagepress')); ?>')) {
      var form = document.createElement('form');
      form.method = 'post';
      form.action = '';

      var actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'delete_tree';

      var treeInput = document.createElement('input');
      treeInput.type = 'hidden';
      treeInput.name = 'tree_id';
      treeInput.value = treeId;

      var nonceInput = document.createElement('input');
      nonceInput.type = 'hidden';
      nonceInput.name = '_wpnonce';
      nonceInput.value = '<?php echo wp_create_nonce('heritagepress_delete_tree'); ?>';

      form.appendChild(actionInput);
      form.appendChild(treeInput);
      form.appendChild(nonceInput);

      document.body.appendChild(form);
      form.submit();
    }
  }

  function clearTreeData(treeId, treeName) {
    if (confirm('<?php esc_js(_e('Are you sure you want to clear all data from the tree:', 'heritagepress')); ?> "' + treeName + '"?\n\n<?php esc_js(_e('This will remove all people, families, sources, and media but keep the tree configuration.', 'heritagepress')); ?>')) {
      var form = document.createElement('form');
      form.method = 'post';
      form.action = '';

      var actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'clear_tree';

      var treeInput = document.createElement('input');
      treeInput.type = 'hidden';
      treeInput.name = 'tree_id';
      treeInput.value = treeId;

      var nonceInput = document.createElement('input');
      nonceInput.type = 'hidden';
      nonceInput.name = '_wpnonce';
      nonceInput.value = '<?php echo wp_create_nonce('heritagepress_clear_tree'); ?>';

      document.body.appendChild(form);
      form.submit();
    }
  }
</script>
