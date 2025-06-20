<?php
if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
$updated = isset($_GET['updated']);
$provider = get_option('hp_map_provider', '');
$apikey = get_option('hp_map_apikey', '');
$default_lat = get_option('hp_map_default_lat', '');
$default_lng = get_option('hp_map_default_lng', '');
$map_width = get_option('hp_map_width', '');
$map_height = get_option('hp_map_height', '');
?>
<div class="wrap">
  <h1><?php esc_html_e('Map Config', 'heritagepress'); ?></h1>
  <?php if ($updated): ?>
    <div class="notice notice-success">
      <p><?php esc_html_e('Settings saved.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>
  <form method="post" action="<?php echo esc_attr(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('hp_map_config'); ?>
    <input type="hidden" name="action" value="hp_save_map_config">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="provider"><?php esc_html_e('Map Provider', 'heritagepress'); ?></label></th>
        <td><input name="provider" type="text" id="provider" value="<?php echo esc_attr($provider); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="apikey"><?php esc_html_e('API Key', 'heritagepress'); ?></label></th>
        <td><input name="apikey" type="text" id="apikey" value="<?php echo esc_attr($apikey); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="default_lat"><?php esc_html_e('Default Latitude', 'heritagepress'); ?></label></th>
        <td><input name="default_lat" type="text" id="default_lat" value="<?php echo esc_attr($default_lat); ?>" class="small-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="default_lng"><?php esc_html_e('Default Longitude', 'heritagepress'); ?></label></th>
        <td><input name="default_lng" type="text" id="default_lng" value="<?php echo esc_attr($default_lng); ?>" class="small-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="map_width"><?php esc_html_e('Map Width', 'heritagepress'); ?></label></th>
        <td><input name="map_width" type="text" id="map_width" value="<?php echo esc_attr($map_width); ?>" class="small-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="map_height"><?php esc_html_e('Map Height', 'heritagepress'); ?></label></th>
        <td><input name="map_height" type="text" id="map_height" value="<?php echo esc_attr($map_height); ?>" class="small-text"></td>
      </tr>
    </table>
    <?php submit_button(__('Save Settings', 'heritagepress')); ?>
  </form>
</div>
