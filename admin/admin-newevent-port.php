<?php
// HeritagePress: Ported from TNG admin_newevent.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_newevent_page');
function heritagepress_add_newevent_page()
{
  add_submenu_page(
    'heritagepress',
    __('Add New Event', 'heritagepress'),
    __('Add New Event', 'heritagepress'),
    'manage_options',
    'heritagepress-newevent',
    'heritagepress_render_newevent_page'
  );
}

function heritagepress_render_newevent_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $nonce = wp_create_nonce('heritagepress_newevent');
  // Fetch event types (replace $prefix as needed)
  $eventtypes_table = $wpdb->prefix . 'tng_eventtypes';
  $prefix = 'I'; // TODO: Set prefix dynamically if needed
  $eventtypes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $eventtypes_table WHERE keep = %s AND type = %s ORDER BY tag", '1', $prefix), ARRAY_A);
?>
  <div class="wrap">
    <h1><?php _e('Add New Event', 'heritagepress'); ?></h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" name="form1" id="form1">
      <input type="hidden" name="action" value="heritagepress_add_newevent">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <table class="form-table">
        <tr>
          <th><label for="eventtypeID"><?php _e('Event Type', 'heritagepress'); ?></label></th>
          <td>
            <select name="eventtypeID" id="eventtypeID">
              <option value=""></option>
              <?php
              $events = array();
              foreach ($eventtypes as $eventtype) {
                $display = esc_html($eventtype['display']);
                $option = $display . ($eventtype['tag'] != 'EVEN' ? ' (' . esc_html($eventtype['tag']) . ')' : '');
                $option = mb_strimwidth($option, 0, 40, '&hellip;');
                $events[$display] = '<option value="' . esc_attr($eventtype['eventtypeID']) . '">' . $option . '</option>';
              }
              ksort($events);
              foreach ($events as $event) echo $event;
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <th><label for="eventdate"><?php _e('Event Date', 'heritagepress'); ?></label></th>
          <td><input type="text" name="eventdate" id="eventdate"> <span class="description"><?php _e('Date format', 'heritagepress'); ?></span></td>
        </tr>
        <tr>
          <th><label for="eventplace"><?php _e('Event Place', 'heritagepress'); ?></label></th>
          <td><input type="text" name="eventplace" id="eventplace" size="70"></td>
        </tr>
        <tr>
          <th><label for="info"><?php _e('Detail', 'heritagepress'); ?></label></th>
          <td><textarea name="info" id="info" rows="6" cols="70"></textarea></td>
        </tr>
        <tr>
          <th colspan="2"><?php _e('Duplicate For', 'heritagepress'); ?></th>
        </tr>
        <tr>
          <th><label for="dupIDs"><?php _e('ID(s)', 'heritagepress'); ?></label></th>
          <td><input type="text" name="dupIDs" id="dupIDs" class="regular-text"> <span class="description">(<?php _e('comma separated', 'heritagepress'); ?>)</span></td>
        </tr>
      </table>
      <a href="#" onclick="jQuery('#more-event-fields').toggle();return false;" class="button-secondary"><?php _e('More', 'heritagepress'); ?></a>
      <div id="more-event-fields" style="display:none; margin-top:1em;">
        <table class="form-table">
          <tr>
            <th><label for="age"><?php _e('Age', 'heritagepress'); ?></label></th>
            <td><input type="text" name="age" id="age" size="12" maxlength="12"></td>
          </tr>
          <tr>
            <th><label for="agency"><?php _e('Agency', 'heritagepress'); ?></label></th>
            <td><input type="text" name="agency" id="agency" size="40"></td>
          </tr>
          <tr>
            <th><label for="cause"><?php _e('Cause', 'heritagepress'); ?></label></th>
            <td><input type="text" name="cause" id="cause" size="40"></td>
          </tr>
          <tr>
            <th><label for="address1"><?php _e('Address 1', 'heritagepress'); ?></label></th>
            <td><input type="text" name="address1" id="address1" size="40"></td>
          </tr>
          <tr>
            <th><label for="address2"><?php _e('Address 2', 'heritagepress'); ?></label></th>
            <td><input type="text" name="address2" id="address2" size="40"></td>
          </tr>
          <tr>
            <th><label for="city"><?php _e('City', 'heritagepress'); ?></label></th>
            <td><input type="text" name="city" id="city" size="40"></td>
          </tr>
          <tr>
            <th><label for="state"><?php _e('State/Province', 'heritagepress'); ?></label></th>
            <td><input type="text" name="state" id="state" size="40"></td>
          </tr>
          <tr>
            <th><label for="zip"><?php _e('ZIP', 'heritagepress'); ?></label></th>
            <td><input type="text" name="zip" id="zip" size="20"></td>
          </tr>
          <tr>
            <th><label for="country"><?php _e('Country', 'heritagepress'); ?></label></th>
            <td><input type="text" name="country" id="country" size="40"></td>
          </tr>
          <tr>
            <th><label for="phone"><?php _e('Phone', 'heritagepress'); ?></label></th>
            <td><input type="text" name="phone" id="phone" size="30"></td>
          </tr>
          <tr>
            <th><label for="email"><?php _e('Email', 'heritagepress'); ?></label></th>
            <td><input type="text" name="email" id="email" size="50"></td>
          </tr>
          <tr>
            <th><label for="www"><?php _e('Website', 'heritagepress'); ?></label></th>
            <td><input type="text" name="www" id="www" size="50"></td>
          </tr>
        </table>
      </div>
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save', 'heritagepress'); ?>">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-events')); ?>" class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
      </p>
    </form>
  </div>
<?php
}

add_action('admin_post_heritagepress_add_newevent', 'heritagepress_handle_add_newevent');
function heritagepress_handle_add_newevent()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  check_admin_referer('heritagepress_newevent');
  // TODO: Sanitize and save the event data to the database
  wp_redirect(admin_url('admin.php?page=heritagepress-events&message=added'));
  exit;
}
