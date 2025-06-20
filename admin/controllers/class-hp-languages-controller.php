<?php

/**
 * Languages Controller
 * Provides an admin tool to manage languages in HeritagePress.
 */
if (!defined('ABSPATH')) exit;
class HP_Languages_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_page'));
  }
  public function register_page()
  {
    add_submenu_page(
      'options-general.php',
      __('Languages', 'heritagepress'),
      __('Languages', 'heritagepress'),
      'manage_options',
      'hp_languages',
      array($this, 'render_page')
    );
  }
  public function render_page()
  {
    include dirname(__FILE__) . '/../views/languages.php';
  }
}
new HP_Languages_Controller();
