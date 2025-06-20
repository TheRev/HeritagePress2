<?php

/**
 * HeritagePress Admin Utilities Main View
 *
 * @package    HeritagePress
 * @subpackage Admin\Views
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get current tab or default to 'tables'
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'tables';

// Available tabs
$tabs = [
  'tables' => __('Tables', 'heritagepress'),
  'structure' => __('Table Structure', 'heritagepress'),
  'maintenance' => __('Maintenance', 'heritagepress'),
  'tools' => __('Tools', 'heritagepress'),
];

// Get message if any
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>

<div class="wrap heritagepress-admin">
  <h1><?php _e('Database Utilities & Backup', 'heritagepress'); ?></h1>

  <?php if ($message): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo esc_html($message); ?></p>
    </div>
  <?php endif; ?>

  <div class="heritagepress-tabs-wrapper">
    <h2 class="nav-tab-wrapper">
      <?php foreach ($tabs as $tab_key => $tab_label): ?>
        <a href="?page=heritagepress-utilities&tab=<?php echo esc_attr($tab_key); ?>"
          class="nav-tab <?php echo ($current_tab === $tab_key) ? 'nav-tab-active' : ''; ?>">
          <?php echo esc_html($tab_label); ?>
        </a>
      <?php endforeach; ?>
    </h2>

    <div class="heritagepress-tab-content">
      <?php
      switch ($current_tab) {
        case 'tables':
          include_once HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-backup.php';
          break;

        case 'structure':
          include_once HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-structure.php';
          break;

        case 'maintenance':
          include_once HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-maintenance.php';
          break;

        case 'tools':
          include_once HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-tools.php';
          break;
      }
      ?>
    </div>
  </div>
</div>
