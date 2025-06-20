<?php
// HeritagePress: Renumber Menu admin page, 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_renumbermenu_page');
function heritagepress_add_renumbermenu_page()
{
  add_submenu_page(
    'heritagepress',
    __('Renumber Menu', 'heritagepress'),
    __('Renumber Menu', 'heritagepress'),
    'manage_options',
    'heritagepress-renumbermenu',
    'heritagepress_render_renumbermenu_page'
  );
}

function heritagepress_render_renumbermenu_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  global $wpdb;
  $trees_table = $wpdb->prefix . 'HeritagePress_trees';
  $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename");
  // Maintenance mode: for now, always enabled. Replace with your own check if needed.
  $maintenance_mode = true;
  $warning = !$maintenance_mode ? __('Site must be in maintenance mode to renumber IDs.', 'heritagepress') : '';
?>
  <div class="wrap">
    <h1><?php _e('Renumber Menu', 'heritagepress'); ?></h1>
    <?php if ($warning): ?>
      <div class="notice notice-warning">
        <p><?php echo esc_html($warning); ?></p>
      </div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=heritagepress-renumber')); ?>">
      <?php wp_nonce_field('heritagepress_renumbermenu', 'heritagepress_renumbermenu_nonce'); ?>
      <table class="form-table">
        <tr>
          <th><?php _e('Tree', 'heritagepress'); ?></th>
          <td>
            <select name="tree">
              <?php foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree->gedcom); ?>"><?php echo esc_html($tree->treename); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th><?php _e('Entity Type', 'heritagepress'); ?></th>
          <td>
            <select name="type">
              <option value="person"><?php _e('Person', 'heritagepress'); ?></option>
              <option value="family"><?php _e('Family', 'heritagepress'); ?></option>
              <option value="source"><?php _e('Source', 'heritagepress'); ?></option>
              <option value="repo"><?php _e('Repository', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th><?php _e('Digits', 'heritagepress'); ?></th>
          <td>
            <select name="digits">
              <?php for ($i = 1; $i <= 20; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
              <?php endfor; ?>
            </select>
          </td>
        </tr>
      </table>
      <input type="hidden" name="start" value="1" />
      <p><input type="submit" class="button button-primary" value="<?php esc_attr_e('Renumber', 'heritagepress'); ?>" <?php if (!$maintenance_mode) echo ' disabled'; ?>></p>
      <?php if (!$maintenance_mode): ?>
        <span class="description"><?php _e('You must enable maintenance mode to use this tool.', 'heritagepress'); ?></span>
      <?php endif; ?>
      <p class="description"><?php _e('This utility will renumber IDs for the selected entity type and tree, starting from 1. All related references will be updated.', 'heritagepress'); ?></p>
    </form>
  </div>
<?php
}
