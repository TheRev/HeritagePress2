<?php
// HeritagePress: Pedigree/Descendant Chart Config, 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_pedconfig_page');
function heritagepress_add_pedconfig_page()
{
  add_submenu_page(
    'heritagepress',
    __('Pedigree & Descendant Chart Settings', 'heritagepress'),
    __('Pedigree & Descendant Chart Settings', 'heritagepress'),
    'manage_options',
    'heritagepress-pedconfig',
    'heritagepress_render_pedconfig_page'
  );
}

function heritagepress_render_pedconfig_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
  $option_name = 'heritagepress_pedconfig';
  $defaults = [
    'usepopups' => 1,
    'maxgen' => 4,
    'initpedgens' => 4,
    'popupspouses' => 1,
    'popupkids' => 1,
    'popupchartlinks' => 1,
    'hideempty' => 0,
    'boxwidth' => 100,
    'boxheight' => 42,
    'boxalign' => 'center',
    'boxheightshift' => 0,
    'vwidth' => 100,
    'vheight' => 42,
    'vspacing' => 20,
    'vfontsize' => 7,
    'defdesc' => 2,
    'maxdesc' => 4,
    'initdescgens' => 4,
    'stdesc' => 0,
    'regnotes' => 0,
    'regnosp' => 0,
    'dvwidth' => 100,
    'dvheight' => 42,
    'dvfontsize' => 7
  ];
  $settings = get_option($option_name, $defaults);
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('heritagepress_pedconfig')) {
    foreach ($defaults as $key => $def) {
      $settings[$key] = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : $def;
    }
    update_option($option_name, $settings);
    wp_redirect(admin_url('admin.php?page=heritagepress-pedconfig&message=' . urlencode(__('Settings saved.', 'heritagepress'))));
    exit;
  }
?>
  <div class="wrap">
    <h1><?php _e('Pedigree & Descendant Chart Settings', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form method="post">
      <?php wp_nonce_field('heritagepress_pedconfig'); ?>
      <h2><?php _e('Pedigree Chart', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th><?php _e('Chart Style', 'heritagepress'); ?></th>
          <td>
            <select name="usepopups">
              <option value="1" <?php selected($settings['usepopups'], 1); ?>><?php _e('Standard', 'heritagepress'); ?></option>
              <option value="0" <?php selected($settings['usepopups'], 0); ?>><?php _e('Box', 'heritagepress'); ?></option>
              <option value="-1" <?php selected($settings['usepopups'], -1); ?>><?php _e('Text Only', 'heritagepress'); ?></option>
              <option value="2" <?php selected($settings['usepopups'], 2); ?>><?php _e('Compact', 'heritagepress'); ?></option>
              <option value="3" <?php selected($settings['usepopups'], 3); ?>><?php _e('Ahnentafel', 'heritagepress'); ?></option>
              <option value="4" <?php selected($settings['usepopups'], 4); ?>><?php _e('Vertical', 'heritagepress'); ?></option>
              <option value="5" <?php selected($settings['usepopups'], 5); ?>><?php _e('Fan Chart', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th><?php _e('Max Generations', 'heritagepress'); ?></th>
          <td><input type="number" name="maxgen" value="<?php echo esc_attr($settings['maxgen']); ?>" min="1" max="99"></td>
        </tr>
        <tr>
          <th><?php _e('Initial Generations', 'heritagepress'); ?></th>
          <td><input type="number" name="initpedgens" value="<?php echo esc_attr($settings['initpedgens']); ?>" min="1" max="99"></td>
        </tr>
        <tr>
          <th><?php _e('Show Spouses in Popups', 'heritagepress'); ?></th>
          <td><input type="checkbox" name="popupspouses" value="1" <?php checked($settings['popupspouses'], 1); ?>></td>
        </tr>
        <tr>
          <th><?php _e('Show Children in Popups', 'heritagepress'); ?></th>
          <td><input type="checkbox" name="popupkids" value="1" <?php checked($settings['popupkids'], 1); ?>></td>
        </tr>
        <tr>
          <th><?php _e('Show Chart Links in Popups', 'heritagepress'); ?></th>
          <td><input type="checkbox" name="popupchartlinks" value="1" <?php checked($settings['popupchartlinks'], 1); ?>></td>
        </tr>
        <tr>
          <th><?php _e('Hide Empty Boxes', 'heritagepress'); ?></th>
          <td><input type="checkbox" name="hideempty" value="1" <?php checked($settings['hideempty'], 1); ?>></td>
        </tr>
        <tr>
          <th><?php _e('Box Width', 'heritagepress'); ?></th>
          <td><input type="number" name="boxwidth" value="<?php echo esc_attr($settings['boxwidth']); ?>" min="10" max="1000"></td>
        </tr>
        <tr>
          <th><?php _e('Box Height', 'heritagepress'); ?></th>
          <td><input type="number" name="boxheight" value="<?php echo esc_attr($settings['boxheight']); ?>" min="10" max="1000"></td>
        </tr>
        <tr>
          <th><?php _e('Box Alignment', 'heritagepress'); ?></th>
          <td><select name="boxalign">
              <option value="center" <?php selected($settings['boxalign'], 'center'); ?>><?php _e('Center', 'heritagepress'); ?></option>
              <option value="left" <?php selected($settings['boxalign'], 'left'); ?>><?php _e('Left', 'heritagepress'); ?></option>
              <option value="right" <?php selected($settings['boxalign'], 'right'); ?>><?php _e('Right', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr>
          <th><?php _e('Box Height Shift', 'heritagepress'); ?></th>
          <td><input type="number" name="boxheightshift" value="<?php echo esc_attr($settings['boxheightshift']); ?>" min="-100" max="1000"></td>
        </tr>
        <tr>
          <th><?php _e('Vertical Chart Box Width', 'heritagepress'); ?></th>
          <td><input type="number" name="vwidth" value="<?php echo esc_attr($settings['vwidth']); ?>" min="10" max="1000"></td>
        </tr>
        <tr>
          <th><?php _e('Vertical Chart Box Height', 'heritagepress'); ?></th>
          <td><input type="number" name="vheight" value="<?php echo esc_attr($settings['vheight']); ?>" min="10" max="1000"></td>
        </tr>
        <tr>
          <th><?php _e('Vertical Chart Box Spacing', 'heritagepress'); ?></th>
          <td><input type="number" name="vspacing" value="<?php echo esc_attr($settings['vspacing']); ?>" min="0" max="1000"></td>
        </tr>
        <tr>
          <th><?php _e('Vertical Chart Font Size', 'heritagepress'); ?></th>
          <td><input type="number" name="vfontsize" value="<?php echo esc_attr($settings['vfontsize']); ?>" min="1" max="99"></td>
        </tr>
      </table>
      <h2><?php _e('Descendant Chart', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th><?php _e('Chart Style', 'heritagepress'); ?></th>
          <td><select name="defdesc">
              <option value="2" <?php selected($settings['defdesc'], 2); ?>><?php _e('Standard', 'heritagepress'); ?></option>
              <option value="0" <?php selected($settings['defdesc'], 0); ?>><?php _e('Text Only', 'heritagepress'); ?></option>
              <option value="3" <?php selected($settings['defdesc'], 3); ?>><?php _e('Compact', 'heritagepress'); ?></option>
              <option value="4" <?php selected($settings['defdesc'], 4); ?>><?php _e('Vertical', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['defdesc'], 1); ?>><?php _e('Register Format', 'heritagepress'); ?></option>
              <option value="5" <?php selected($settings['defdesc'], 5); ?>><?php _e('DT Format', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr>
          <th><?php _e('Max Generations', 'heritagepress'); ?></th>
          <td><input type="number" name="maxdesc" value="<?php echo esc_attr($settings['maxdesc']); ?>" min="1" max="99"></td>
        </tr>
        <tr>
          <th><?php _e('Initial Generations', 'heritagepress'); ?></th>
          <td><input type="number" name="initdescgens" value="<?php echo esc_attr($settings['initdescgens']); ?>" min="1" max="99"></td>
        </tr>
        <tr>
          <th><?php _e('Start Descendant Chart', 'heritagepress'); ?></th>
          <td><select name="stdesc">
              <option value="0" <?php selected($settings['stdesc'], 0); ?>><?php _e('Expand', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['stdesc'], 1); ?>><?php _e('Collapse', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr>
          <th><?php _e('Show Notes in Register Format', 'heritagepress'); ?></th>
          <td><select name="regnotes">
              <option value="0" <?php selected($settings['regnotes'], 0); ?>><?php _e('No', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['regnotes'], 1); ?>><?php _e('Yes', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr>
          <th><?php _e('Show Children if Spouse', 'heritagepress'); ?></th>
          <td><select name="regnosp">
              <option value="0" <?php selected($settings['regnosp'], 0); ?>><?php _e('Show', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['regnosp'], 1); ?>><?php _e('If Spouse', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr>
          <th><?php _e('Vertical Chart Box Width', 'heritagepress'); ?></th>
          <td><input type="number" name="dvwidth" value="<?php echo esc_attr($settings['dvwidth']); ?>" min="10" max="1000"></td>
        </tr>
        <tr>
          <th><?php _e('Vertical Chart Box Height', 'heritagepress'); ?></th>
          <td><input type="number" name="dvheight" value="<?php echo esc_attr($settings['dvheight']); ?>" min="10" max="1000"></td>
        </tr>
        <tr>
          <th><?php _e('Vertical Chart Font Size', 'heritagepress'); ?></th>
          <td><input type="number" name="dvfontsize" value="<?php echo esc_attr($settings['dvfontsize']); ?>" min="1" max="99"></td>
        </tr>
      </table>
      <p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'heritagepress'); ?>"></p>
    </form>
  </div>
<?php
}
