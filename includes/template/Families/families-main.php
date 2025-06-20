<?php

/**
 * Families Main Admin Interface
 * Complete facsimile of families management with tabbed navigation
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Check if editing a family
$family_id = isset($_GET['familyID']) ? sanitize_text_field($_GET['familyID']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$is_editing = !empty($family_id) && !empty($tree);

// If editing, show edit tab
if ($is_editing) {
  $current_tab = 'edit';
}

// Get family counts for each tree
$families_table = $wpdb->prefix . 'hp_families';
$tree_counts = array();
foreach ($trees_result as $tree_row) {
  $count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $families_table WHERE gedcom = %s",
    $tree_row['gedcom']
  ));
  $tree_counts[$tree_row['gedcom']] = $count;
}

$total_families = array_sum($tree_counts);
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php _e('Families Management', 'heritagepress'); ?></h1>

  <?php if ($current_tab === 'browse'): ?>
    <a href="?page=heritagepress-families&tab=add" class="page-title-action"><?php _e('Add New Family', 'heritagepress'); ?></a>
  <?php endif; ?>

  <hr class="wp-header-end">

  <?php
  // Display any messages
  if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
  }
  if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
  }
  ?>

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper wp-clearfix">
    <a href="?page=heritagepress-families&tab=browse"
      class="nav-tab <?php echo $current_tab === 'browse' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Browse Families', 'heritagepress'); ?>
      <span class="count">(<?php echo number_format($total_families); ?>)</span>
    </a>

    <a href="?page=heritagepress-families&tab=add"
      class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Add New Family', 'heritagepress'); ?>
    </a>

    <?php if ($is_editing): ?>
      <a href="?page=heritagepress-families&tab=edit&familyID=<?php echo esc_attr($family_id); ?>&tree=<?php echo esc_attr($tree); ?>"
        class="nav-tab <?php echo $current_tab === 'edit' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Edit Family', 'heritagepress'); ?> (<?php echo esc_html($family_id); ?>)
      </a>
    <?php endif; ?>

    <a href="?page=heritagepress-families&tab=utilities"
      class="nav-tab <?php echo $current_tab === 'utilities' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Utilities', 'heritagepress'); ?>
    </a>

    <a href="?page=heritagepress-families&tab=reports"
      class="nav-tab <?php echo $current_tab === 'reports' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Reports', 'heritagepress'); ?>
    </a>
  </nav>

  <!-- Tab Content -->
  <div class="tab-content">
    <?php
    switch ($current_tab) {
      case 'browse':
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Families/browse-families.php';
        break;

      case 'add':
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Families/add-family.php';
        break;

      case 'edit':
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Families/edit-family.php';
        break;

      case 'utilities':
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Families/utilities-families.php';
        break;

      case 'reports':
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Families/reports-families.php';
        break;

      default:
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Families/browse-families.php';
        break;
    }
    ?>
  </div>
</div>
