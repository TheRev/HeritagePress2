<?php
// HeritagePress: 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newreport_page');
function heritagepress_add_newreport_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Report', 'heritagepress'),
    __('Add New Report', 'heritagepress'),
    'manage_options',
    'heritagepress-newreport',
    'heritagepress_render_newreport_page'
  );
}

function heritagepress_render_newreport_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  // Security nonce for form
  $nonce = wp_create_nonce('heritagepress_newreport');
  // Fetch custom event types
  $eventtypes_table = $wpdb->prefix . 'HeritagePress_eventtypes';
  $cetypes = array();
  $query = $wpdb->prepare("SELECT eventtypeID, tag, display FROM $eventtypes_table WHERE keep = %s AND type = %s ORDER BY display", '1', 'I');
  $ceresult = $wpdb->get_results($query, ARRAY_A);
  $dontdo = array(); // TODO: Port logic for $dontdo if needed
  foreach ($ceresult as $cerow) {
    if (!in_array($cerow['tag'], $dontdo)) {
      $eventtypeID = $cerow['eventtypeID'];
      $cetypes[$eventtypeID] = $cerow;
    }
  }
  // TODO: Port $dfields, $cfields, $ofields from HeritagePress or define as needed
  $dfields = $cfields = $ofields = array();
  // TODO: Port $admtext and $text arrays for translations
  $admtext = $text = array();
?>
  <div class="wrap">
    <h1><?php _e('Add New Report', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1" onsubmit="return heritagepress_validate_newreport_form();">
      <input type="hidden" name="action" value="heritagepress_add_newreport">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="reportname"><?php _e('Report Name', 'heritagepress'); ?></label></th>
          <td><input type="text" name="reportname" id="reportname" size="50" maxlength="80"></td>
        </tr>
        <tr>
          <th><label for="reportdesc"><?php _e('Description', 'heritagepress'); ?></label></th>
          <td><textarea cols="50" rows="3" name="reportdesc" id="reportdesc"></textarea></td>
        </tr>
        <tr>
          <th><label for="ranking"><?php _e('Rank/Priority', 'heritagepress'); ?></label></th>
          <td><input type="text" name="ranking" id="ranking" size="3" maxlength="3" value="1"></td>
        </tr>
        <tr>
          <th><?php _e('Active', 'heritagepress'); ?></th>
          <td><label><input type="radio" name="active" value="1"> <?php _e('Yes', 'heritagepress'); ?></label> <label><input type="radio" name="active" value="0" checked> <?php _e('No', 'heritagepress'); ?></label></td>
        </tr>
      </table>
      <!-- TODO: Port display fields, criteria, sort, and SQL textarea as in original file -->
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save and Return', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-reports')); ?>" class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
      </p>
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_newreport_form() {
      var reportname = document.getElementById('reportname').value;
      if (!reportname) {
        alert('<?php _e('Please enter a report name.', 'heritagepress'); ?>');
        return false;
      }
      // TODO: Add more validation as needed
      return true;
    }
  </script>
<?php
}

// Handle form submission
add_action('admin_post_heritagepress_add_newreport', 'heritagepress_handle_add_newreport');
function heritagepress_handle_add_newreport()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newreport');
  // TODO: Sanitize and save the report data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-reports&message=added'));
  exit;
}
