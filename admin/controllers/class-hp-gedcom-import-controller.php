<?php

/**
 * HeritagePress GEDCOM Import Admin Controller
 * Provides the GEDCOM Import admin page and handles import actions.
 */
if (!defined('ABSPATH')) exit;

class HeritagePress_Gedcom_Import_Controller
{
  public function display_page()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
    }
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/gedcom-import.php';
  }
}
