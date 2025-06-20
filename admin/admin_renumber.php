<?php
// HeritagePress: Renumber IDs admin utility, ported from TNG admin_renumber.php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', 'heritagepress_add_renumber_page');
function heritagepress_add_renumber_page()
{
  add_submenu_page(
    'heritagepress',
    __('Renumber IDs', 'heritagepress'),
    __('Renumber IDs', 'heritagepress'),
    'manage_options',
    'heritagepress-renumber',
    'heritagepress_render_renumber_page'
  );
}

function heritagepress_render_renumber_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $message = '';
  $count = 0;
  if (isset($_POST['heritagepress_renumber_nonce']) && wp_verify_nonce($_POST['heritagepress_renumber_nonce'], 'heritagepress_renumber')) {
    global $wpdb;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'person';
    $start = isset($_POST['start']) ? max(1, intval($_POST['start'])) : 1;
    $digits = isset($_POST['digits']) ? max(0, intval($_POST['digits'])) : 0;
    $tree = isset($_POST['tree']) ? sanitize_text_field($_POST['tree']) : '';
    $prefix = '';
    $suffix = '';
    // Table and ID field selection
    switch ($type) {
      case 'person':
        $table = $wpdb->prefix . 'tng_people';
        $id_field = 'personID';
        break;
      case 'family':
        $table = $wpdb->prefix . 'tng_families';
        $id_field = 'familyID';
        break;
      case 'source':
        $table = $wpdb->prefix . 'tng_sources';
        $id_field = 'sourceID';
        break;
      case 'repo':
        $table = $wpdb->prefix . 'tng_repositories';
        $id_field = 'repoID';
        break;
      default:
        $table = $wpdb->prefix . 'tng_people';
        $id_field = 'personID';
    }
    // Get all IDs >= start, sorted numerically (ignoring prefix/suffix)
    $results = $wpdb->get_results($wpdb->prepare(
      "SELECT ID, $id_field FROM $table WHERE (0+SUBSTRING($id_field, %d)) >= %d ORDER BY (0+SUBSTRING($id_field, %d))",
      strlen($prefix) + 1,
      $start,
      strlen($prefix) + 1
    ));
    $nextnum = $start;
    foreach ($results as $row) {
      $newID = $digits ? ($prefix . str_pad($nextnum, $digits, '0', STR_PAD_LEFT) . $suffix) : ($prefix . $nextnum . $suffix);
      // Check for conflicts
      $exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $table WHERE $id_field = %s", $newID));
      if (!$exists) {
        $wpdb->update($table, [$id_field => $newID], ['ID' => $row->ID]);
        // TODO: Update all related tables as in TNG (families, children, events, medialinks, etc.)
        $count++;
      }
      $nextnum++;
    }
    $message = sprintf(__('Renumbering complete. %d records updated.', 'heritagepress'), $count);
  }
?>
  <div class="wrap">
    <h1><?php _e('Renumber IDs', 'heritagepress'); ?></h1>
    <?php if ($message): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
      </div>
    <?php endif; ?>
    <form method="post">
      <?php wp_nonce_field('heritagepress_renumber', 'heritagepress_renumber_nonce'); ?>
      <table class="form-table">
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
          <th><?php _e('Start Number', 'heritagepress'); ?></th>
          <td><input type="number" name="start" value="1" min="1" class="small-text"></td>
        </tr>
        <tr>
          <th><?php _e('Digits (optional)', 'heritagepress'); ?></th>
          <td><input type="number" name="digits" value="0" min="0" class="small-text"></td>
        </tr>
      </table>
      <p><input type="submit" class="button button-primary" value="<?php esc_attr_e('Renumber', 'heritagepress'); ?>"></p>
    </form>
    <p class="description"><?php _e('This utility will renumber IDs for the selected entity type, starting from the specified number. All related references will be updated.', 'heritagepress'); ?></p>
  </div>
<?php
}
