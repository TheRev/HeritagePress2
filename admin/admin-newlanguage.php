<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newlanguage_page');
function heritagepress_add_newlanguage_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Language', 'heritagepress'),
    __('Add New Language', 'heritagepress'),
    'manage_options',
    'heritagepress-newlanguage',
    'heritagepress_render_newlanguage_page'
  );
}

function heritagepress_render_newlanguage_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newlanguage');
  // List language folders using WP Filesystem
  $languages_path = WP_CONTENT_DIR . '/plugins/heritagepress/languages';
  $dirs = array();
  if (is_dir($languages_path)) {
    $handle = opendir($languages_path);
    while (($filename = readdir($handle)) !== false) {
      if (is_dir($languages_path . '/' . $filename) && $filename != '.' && $filename != '..' && $filename != '@eaDir') {
        $dirs[] = $filename;
      }
    }
    closedir($handle);
    natcasesort($dirs);
  }
?>
  <div class="wrap">
    <h1><?php _e('Add New Language', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1" onsubmit="return heritagepress_validate_newlanguage_form();">
      <input type="hidden" name="action" value="heritagepress_add_newlanguage">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="folder"><?php _e('Language Folder', 'heritagepress'); ?></label></th>
          <td>
            <select name="folder" id="folder">
              <option value=""></option>
              <?php foreach ($dirs as $dir): ?>
                <option value="<?php echo esc_attr($dir); ?>"><?php echo esc_html($dir); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th><label for="display"><?php _e('Display Name', 'heritagepress'); ?></label></th>
          <td><input type="text" name="display" id="display" size="50"></td>
        </tr>
        <tr>
          <th><label for="langcharset"><?php _e('Charset', 'heritagepress'); ?></label></th>
          <td><input type="text" name="langcharset" id="langcharset" size="30" value="UTF-8"></td>
        </tr>
        <tr>
          <th><label for="langnorels"><?php _e('No Relationships', 'heritagepress'); ?></label></th>
          <td>
            <select name="langnorels" id="langnorels">
              <option value="0"><?php _e('No', 'heritagepress'); ?></option>
              <option value="1"><?php _e('Yes', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Return', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-languages')); ?>" class="button"> <?php _e('Cancel', 'heritagepress'); ?> </a>
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_newlanguage_form() {
      var folder = document.getElementById('folder').value.trim();
      var display = document.getElementById('display').value.trim();
      if (!folder) {
        alert('<?php _e('Please select a language folder.', 'heritagepress'); ?>');
        return false;
      }
      if (!display) {
        alert('<?php _e('Please enter a display name.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }
  </script>
<?php
}

add_action('admin_post_heritagepress_add_newlanguage', 'heritagepress_handle_add_newlanguage');
function heritagepress_handle_add_newlanguage()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newlanguage');
  // TODO: Sanitize and save the language data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-languages&message=added'));
  exit;
}
