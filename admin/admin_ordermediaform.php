<?php
// HeritagePress: Ported from TNG admin_ordermediaform.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register admin menu page
add_action('admin_menu', 'heritagepress_add_ordermediaform_page');
function heritagepress_add_ordermediaform_page()
{
  add_submenu_page(
    'heritagepress',
    __('Order Media', 'heritagepress'),
    __('Order Media', 'heritagepress'),
    'manage_options',
    'heritagepress-ordermediaform',
    'heritagepress_render_ordermediaform_page'
  );
}

// Render the Order Media Form admin page
function heritagepress_render_ordermediaform_page()
{
  global $wpdb;
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $nonce = wp_create_nonce('heritagepress_ordermediaform');
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

  // Fetch trees
  $trees_table = $wpdb->prefix . 'tng_trees';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);

  // Define media types (customize as needed)
  $mediatypes = array(
    array('ID' => 'photos', 'display' => __('Photos', 'heritagepress')),
    array('ID' => 'documents', 'display' => __('Documents', 'heritagepress')),
    array('ID' => 'headstones', 'display' => __('Headstones', 'heritagepress')),
    array('ID' => 'histories', 'display' => __('Histories', 'heritagepress')),
    array('ID' => 'recordings', 'display' => __('Recordings', 'heritagepress')),
    array('ID' => 'videos', 'display' => __('Videos', 'heritagepress')),
  );
?>
  <div class="wrap">
    <h1><?php _e('Order Media', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form action="<?php echo esc_url(admin_url('admin.php?page=heritagepress-ordermedia')); ?>" method="get" name="find" id="find" onsubmit="return heritagepress_validate_ordermediaform();">
      <table class="form-table">
        <tr>
          <th><?php _e('Tree', 'heritagepress'); ?></th>
          <th><?php _e('Link Type', 'heritagepress'); ?></th>
          <th><?php _e('Media Type', 'heritagepress'); ?></th>
          <th colspan="3"><?php _e('ID', 'heritagepress'); ?></th>
        </tr>
        <tr>
          <td>
            <select name="tree1" id="tree1">
              <?php foreach ($trees as $row): ?>
                <option value="<?php echo esc_attr($row['gedcom']); ?>"><?php echo esc_html($row['treename']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <select name="linktype1" id="linktype1">
              <option value="I"><?php _e('Person', 'heritagepress'); ?></option>
              <option value="F"><?php _e('Family', 'heritagepress'); ?></option>
              <option value="S"><?php _e('Source', 'heritagepress'); ?></option>
              <option value="R"><?php _e('Repository', 'heritagepress'); ?></option>
              <option value="L"><?php _e('Place', 'heritagepress'); ?></option>
            </select>
          </td>
          <td>
            <select name="mediatypeID" id="mediatypeID">
              <?php foreach ($mediatypes as $mediatype): ?>
                <option value="<?php echo esc_attr($mediatype['ID']); ?>"><?php echo esc_html($mediatype['display']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <input type="text" name="newlink1" id="newlink1" value="">
          </td>
          <td>
            <!-- TODO: Implement Find button/modal for ID lookup -->
            <a href="#" onclick="alert('TODO: Implement Find modal'); return false;" title="<?php esc_attr_e('Find', 'heritagepress'); ?>" class="button">🔍</a>
          </td>
          <td>
            <input type="submit" class="button-primary" value="<?php esc_attr_e('Continue', 'heritagepress'); ?>">
          </td>
        </tr>
        <tr>
          <td colspan="3">&nbsp;</td>
          <td colspan="2">
            <span id="eventlink1" class="normal">
              <input type="checkbox" name="eventlink1" value="1" onclick="return heritagepress_toggle_eventrow(this.checked);" />
              <?php _e('Event Link', 'heritagepress'); ?>
            </span><br />
            <select name="event1" id="eventrow1" style="display:none">
              <option value=""></option>
              <!-- TODO: Populate with event options if needed -->
            </select>
          </td>
          <td class="normal" valign="top">&nbsp;</td>
        </tr>
      </table>
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
    </form>
  </div>
  <script type="text/javascript">
    function heritagepress_validate_ordermediaform() {
      var newlink1 = document.getElementById('newlink1').value.trim();
      if (!newlink1) {
        alert('<?php _e('Please enter an ID.', 'heritagepress'); ?>');
        return false;
      }
      return true;
    }

    function heritagepress_toggle_eventrow(checked) {
      var eventRow = document.getElementById('eventrow1');
      if (eventRow) eventRow.style.display = checked ? '' : 'none';
      return true;
    }
  </script>
<?php
}
