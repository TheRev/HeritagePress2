<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package    HeritagePress
 * @subpackage HeritagePress/uninstall
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

/**
 * The code that runs during plugin uninstall.
 * This action is documented in includes/class-heritagepress-uninstaller.php
 */
function heritagepress_uninstall()
{
  // Remove plugin options
  delete_option('heritagepress_version');
  delete_option('heritagepress_db_version');

  // Note: Database tables are NOT dropped on uninstall to preserve user data
  // Users must manually remove tables if they want to completely remove all data
}

heritagepress_uninstall();
