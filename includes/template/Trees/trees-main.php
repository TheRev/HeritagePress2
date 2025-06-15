<?php

/**
 * Trees Main Interface
 * Complete facsimile of TNG Trees section with tabbed navigation
 */

if (!defined('ABSPATH')) {
  exit;
}

// Determine current tab and tree ID
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';
$tree_id = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

// Define tabs
$trees_tabs = array(
  'browse' => __('Browse Trees', 'heritagepress'),
  'add' => __('Add Tree', 'heritagepress')
);

// Add edit tab if we're editing a specific tree
if (!empty($tree_id) && $action === 'edit') {
  $trees_tabs['edit'] = __('Edit Tree', 'heritagepress');
  $current_tab = 'edit';
}

?>

<div class="wrap heritagepress-admin trees-admin">
  <h1><?php _e('Family Trees Management', 'heritagepress'); ?></h1>

  <nav class="nav-tab-wrapper hp-nav-tabs">
    <?php foreach ($trees_tabs as $tab_id => $tab_name): ?>
      <a href="?page=heritagepress-trees&tab=<?php echo $tab_id; ?><?php echo ($tab_id === 'edit' && !empty($tree_id)) ? '&tree=' . urlencode($tree_id) . '&action=edit' : ''; ?>"
        class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
        <?php echo $tab_name; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="hp-tab-content">
    <?php    // Include the appropriate tab content based on current tab
    switch ($current_tab) {
      case 'browse':
        include_once 'browse-trees.php';
        break;
      case 'add':
        include_once 'add-tree.php';
        break;
      case 'edit':
        include_once 'edit-tree.php';
        break;
      default:
        include_once 'browse-trees.php';
        break;
    }
    ?>
  </div>
</div>

<style>
  /* Trees-specific styling extending Import section styles */
  .trees-admin .form-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .trees-admin .form-card-header {
    background: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    padding: 15px 20px;
    border-radius: 4px 4px 0 0;
  }

  .trees-admin .form-card-title {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #1d2327;
  }

  .trees-admin .form-card-body {
    padding: 20px;
  }

  .trees-admin .hp-form-table {
    width: 100%;
    border-collapse: collapse;
  }

  .trees-admin .hp-form-table td {
    padding: 8px 12px;
    vertical-align: top;
    border: none;
  }

  .trees-admin .hp-form-table td:first-child {
    width: 150px;
    font-weight: 500;
    color: #1d2327;
  }

  .trees-admin .tree-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
  }

  .trees-admin .stat-box {
    background: #f0f6fc;
    border: 1px solid #c6e9ff;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
  }

  .trees-admin .stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
    display: block;
  }

  .trees-admin .stat-label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
    margin-top: 5px;
  }

  .trees-admin .tree-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #dcdcde;
  }

  .trees-admin .tree-list-table {
    margin-top: 20px;
  }

  .trees-admin .tree-list-table th,
  .trees-admin .tree-list-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #c3c4c7;
  }

  .trees-admin .tree-list-table th {
    background: #f6f7f7;
    font-weight: 600;
  }

  .trees-admin .tree-row:hover {
    background: #f6f7f7;
  }
</style>
