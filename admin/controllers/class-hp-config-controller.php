<?php

/**
 * HeritagePress Global Configuration Controller
 *
 * Handles the admin page for global plugin configuration/settings.
 * Provides comprehensive genealogy configuration options using WordPress standards.
 */

if (!defined('ABSPATH')) {
  exit;
}

class HeritagePress_Config_Controller
{
  public function display_page()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    // Load settings from the options API
    $settings = get_option('heritagepress_config', array());
    // Make $settings available to the view
    global $settings;
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/config-main.php';
  }

  public function save_settings()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    check_admin_referer('heritagepress_config_save', '_wpnonce');

    $fields = [
      // Folders
      'photopath',
      'documentpath',
      'historypath',
      'headstonepath',
      'mediapath',
      'modspath',
      'extspath',
      'gendexfile',
      'backuppath',
      'saveconfig',
      // Site      'homepage',
      'domain',
      'sitename',
      'site_desc',
      'doctype',
      'dbowner',
      'customheader',
      'customfooter',
      'footer_message',
      'custommeta',
      // Media
      'photosext',
      'showextended',
      'imgmaxh',
      'imgmaxw',
      'thumbprefix',
      'thumbsuffix',
      'thumbmaxh',
      'thumbmaxw',
      // Languages
      'language',
      'charset',
      'chooselang',
      'norels',
      // Privacy
      'requirelogin',
      'treerestrict',
      'ldsdefault',
      'livedefault',
      // Names
      'nameorder',
      'ucsurnames',
      'lnprefixes',
      'lnpfxnum',
      'specpfx',
      // Cemeteries
      'cemrows',
      'cemblanks',
      // Mail/Registration
      'emailaddr',
      'fromadmin',
      'disallowreg',
      'revmail',
      'autotree',
      // Preferences
      'pedigreegen',
      'maxgedcom',
      'maxdesc',
      'chartwidth',
      'chartheight',
      // Mobile
      'mobiletheme',
      'mobilethemename',
      'mobilelogo',
      'mobileheader',
      'mobilefooter',
      // DNA
      'enable_dna',
      'dna_kit_link',
      'dna_results_link',
      'dna_privacy',
      // Misc
      'custom_css',
      'custom_js',
      'maintenance_mode',
      'maintenance_message',
    ];
    $settings = [];
    foreach ($fields as $field) {
      $settings[$field] = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
    }
    update_option('heritagepress_config', $settings);
    wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
    exit;
  }
}
// Register the controller for use in admin menu
if (is_admin()) {
  $GLOBALS['heritagepress_config_controller'] = new HeritagePress_Config_Controller();
}
