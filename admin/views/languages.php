<?php
if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
global $wpdb;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$where = $search ? $wpdb->prepare("WHERE display LIKE %s OR folder LIKE %s", "%$search%", "%$search%") : '';
$languages = $wpdb->get_results("SELECT languageID, display, folder, charset FROM {$wpdb->prefix}hp_languages $where ORDER BY display");
?>
<div class="wrap">
  <h1><?php esc_html_e('Languages', 'heritagepress'); ?></h1>
  <form method="get" action="">
    <input type="hidden" name="page" value="hp_languages">
    <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search languages...', 'heritagepress'); ?>">
    <button class="button"><?php esc_html_e('Search', 'heritagepress'); ?></button>
  </form>
  <table class="widefat fixed striped">
    <thead>
      <tr>
        <th><?php esc_html_e('Name', 'heritagepress'); ?></th>
        <th><?php esc_html_e('Folder', 'heritagepress'); ?></th>
        <th><?php esc_html_e('Charset', 'heritagepress'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if ($languages): foreach ($languages as $lang): ?>
          <tr>
            <td><?php echo esc_html($lang->display); ?></td>
            <td><?php echo esc_html($lang->folder); ?></td>
            <td><?php echo esc_html($lang->charset); ?></td>
          </tr>
        <?php endforeach;
      else: ?>
        <tr>
          <td colspan="3"><?php esc_html_e('No languages found.', 'heritagepress'); ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
