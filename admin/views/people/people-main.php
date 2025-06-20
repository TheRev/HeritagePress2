<?php

/**
 * People Main Admin Interface
 * Handles the main interface for managing people in HeritagePress.
 * Provides tabs for browsing, adding, editing, and reporting on people.
 * * @package HeritagePress
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

// Check if editing a person
$person_id = isset($_GET['personID']) ? sanitize_text_field($_GET['personID']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$is_editing = !empty($person_id) && !empty($tree);

// If editing, show edit tab
if ($is_editing) {
  $current_tab = 'edit';
}

// Get person counts for each tree
$people_table = $wpdb->prefix . 'hp_people';
$tree_counts = array();
foreach ($trees_result as $tree_row) {
  $count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $people_table WHERE gedcom = %s",
    $tree_row['gedcom']
  ));
  $tree_counts[$tree_row['gedcom']] = $count;
}

$total_people = array_sum($tree_counts);
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php _e('People Management', 'heritagepress'); ?></h1>

  <?php if ($current_tab === 'browse'): ?>
    <a href="?page=heritagepress-people&tab=add" class="page-title-action"><?php _e('Add New Person', 'heritagepress'); ?></a>
  <?php endif; ?>

  <hr class="wp-header-end">

  <?php
  settings_errors('heritagepress_people');
  ?>

  <!-- Tab Navigation -->
  <h2 class="nav-tab-wrapper">
    <a href="?page=heritagepress-people&tab=browse" class="nav-tab <?php echo $current_tab === 'browse' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-groups"></span>
      <?php _e('Browse People', 'heritagepress'); ?>
      <span class="count">(<?php echo number_format($total_people); ?>)</span>
    </a> <a href="?page=heritagepress-people&tab=add" class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-plus-alt"></span>
      <?php _e('Add New', 'heritagepress'); ?>
    </a>
    <a href="?page=heritagepress-people&tab=reports" class="nav-tab <?php echo $current_tab === 'reports' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-chart-bar"></span>
      <?php _e('Reports', 'heritagepress'); ?>
    </a>
    <a href="?page=heritagepress-people&tab=utilities" class="nav-tab <?php echo $current_tab === 'utilities' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-admin-tools"></span>
      <?php _e('Utilities', 'heritagepress'); ?>
    </a>
    <?php if ($is_editing): ?>
      <a href="?page=heritagepress-people&tab=edit&personID=<?php echo esc_attr($person_id); ?>&tree=<?php echo esc_attr($tree); ?>" class="nav-tab nav-tab-active">
        <span class="dashicons dashicons-edit"></span>
        <?php _e('Edit Person', 'heritagepress'); ?>
        <span class="person-id"><?php echo esc_html($person_id); ?></span>
      </a>
    <?php endif; ?>
  </h2>

  <div class="tab-content">
    <?php
    switch ($current_tab) {
      case 'browse':
        include __DIR__ . '/browse-people.php';
        break;

      case 'add':
        include __DIR__ . '/add-person.php';
        break;
      case 'edit':
        include __DIR__ . '/edit-person.php';
        break;

      case 'reports':
        include __DIR__ . '/reports-people.php';
        break;

      case 'utilities':
        include __DIR__ . '/utilities-people.php';
        break;

      default:
        include __DIR__ . '/browse-people.php';
        break;
    }
    ?>
  </div>
</div>
