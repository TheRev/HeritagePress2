<?php

/**
 * HeritagePress What's New Controller
 * Handles admin UI and saving for the "What's New" message/announcement
 */
if (!defined('ABSPATH')) exit;

class HP_WhatsNew_Controller
{
  const OPTION_KEY = 'hp_whatsnew_message';

  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_menu'));
    add_action('admin_post_hp_save_whatsnew', array($this, 'save_whatsnew'));
  }

  public function register_menu()
  {
    add_submenu_page(
      'heritagepress',
      __('What\'s New Message', 'heritagepress'),
      __('What\'s New', 'heritagepress'),
      'manage_options',
      'heritagepress-whatsnew',
      array($this, 'display_page')
    );
  }

  public function display_page()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    $message = get_option(self::OPTION_KEY, '');
?>
    <div class="wrap">
      <h1><?php esc_html_e("What's New Message", 'heritagepress'); ?></h1>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('hp_save_whatsnew'); ?>
        <input type="hidden" name="action" value="hp_save_whatsnew">
        <textarea name="hp_whatsnew_message" rows="10" style="width:100%" class="large-text"><?php echo esc_textarea($message); ?></textarea>
        <p><input type="submit" class="button-primary" value="<?php esc_attr_e('Save Message', 'heritagepress'); ?>"></p>
      </form>
    </div>
<?php
  }

  public function save_whatsnew()
  {
    if (!current_user_can('manage_options') || !check_admin_referer('hp_save_whatsnew')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }
    $message = isset($_POST['hp_whatsnew_message']) ? wp_kses_post($_POST['hp_whatsnew_message']) : '';
    update_option(self::OPTION_KEY, $message);
    wp_redirect(admin_url('admin.php?page=heritagepress-whatsnew&updated=1'));
    exit;
  }
}
// Register controller
new HP_WhatsNew_Controller();
