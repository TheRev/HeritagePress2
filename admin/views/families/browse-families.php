<?php

/**
 * Browse Families Admin View
 *
 * Provides a searchable, filterable, paginated list of families with add/edit/delete actions.
 * UI/UX matches HeritagePress admin_families.php but uses WordPress admin styles and security.
 */
if (!defined('ABSPATH')) {
  exit;
}
$controller = isset($controller) ? $controller : (class_exists('HP_Families_Controller') ? new HP_Families_Controller() : null);
if (!$controller) {
  echo '<div class="notice notice-error"><p>Families controller not found.</p></div>';
  return;
}
// Get filter/search params from request
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$living = isset($_GET['living']) ? sanitize_text_field($_GET['living']) : '';
$exactmatch = isset($_GET['exactmatch']) ? (bool)$_GET['exactmatch'] : false;
$spousename = isset($_GET['spousename']) ? sanitize_text_field($_GET['spousename']) : '';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'familyID';
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'ASC';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$args = compact('search', 'tree', 'living', 'exactmatch', 'spousename', 'order', 'orderby', 'paged', 'per_page');
$data = $controller->get_families($args);
$families = $data['families'];
$total = $data['total'];
$total_pages = ceil($total / $per_page);
?>
<div class="wrap heritagepress-admin">
  <h1>
    <?php echo __('Families', 'heritagepress'); ?>
    <a href="<?php echo admin_url('admin.php?page=heritagepress-families&action=add'); ?>" class="page-title-action">
      <?php echo __('Add New', 'heritagepress'); ?>
    </a>
  </h1>
  <form method="get" action="">
    <input type="hidden" name="page" value="heritagepress-families" />
    <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search families...', 'heritagepress'); ?>" />
    <select name="tree">
      <option value=""><?php esc_html_e('All Trees', 'heritagepress'); ?></option>
      <!-- TODO: Populate with available trees -->
    </select>
    <select name="living">
      <option value=""><?php esc_html_e('Living/Deceased', 'heritagepress'); ?></option>
      <option value="1" <?php selected($living, '1'); ?>><?php esc_html_e('Living', 'heritagepress'); ?></option>
      <option value="0" <?php selected($living, '0'); ?>><?php esc_html_e('Deceased', 'heritagepress'); ?></option>
    </select>
    <label><input type="checkbox" name="exactmatch" value="1" <?php checked($exactmatch, true); ?> /> <?php esc_html_e('Exact match', 'heritagepress'); ?></label>
    <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'heritagepress'); ?>" />
  </form>
  <table class="wp-list-table widefat fixed striped">
    <thead>
      <tr>
        <th><?php esc_html_e('Family ID', 'heritagepress'); ?></th>
        <th><?php esc_html_e('Husband', 'heritagepress'); ?></th>
        <th><?php esc_html_e('Wife', 'heritagepress'); ?></th>
        <th><?php esc_html_e('Marriage Date', 'heritagepress'); ?></th>
        <th><?php esc_html_e('Marriage Place', 'heritagepress'); ?></th>
        <th><?php esc_html_e('Living', 'heritagepress'); ?></th>
        <th><?php esc_html_e('Actions', 'heritagepress'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($families)): ?>
        <tr>
          <td colspan="7"><?php esc_html_e('No families found.', 'heritagepress'); ?></td>
        </tr>
        <?php else: foreach ($families as $family): ?>
          <tr>
            <td><?php echo esc_html($family['familyID']); ?></td>
            <td><?php echo esc_html(trim($family['husband_firstname'] . ' ' . $family['husband_lastname'])); ?></td>
            <td><?php echo esc_html(trim($family['wife_firstname'] . ' ' . $family['wife_lastname'])); ?></td>
            <td><?php echo esc_html($family['marrdate']); ?></td>
            <td><?php echo esc_html($family['marrplace']); ?></td>
            <td><?php echo $family['living'] ? esc_html__('Yes', 'heritagepress') : esc_html__('No', 'heritagepress'); ?></td>
            <td>
              <a href="<?php echo admin_url('admin.php?page=heritagepress-families&action=edit&family_id=' . urlencode($family['familyID']) . '&gedcom=' . urlencode($family['gedcom'])); ?>" class="button button-small"><?php esc_html_e('Edit'); ?></a>
              <a href="<?php echo admin_url('admin.php?page=heritagepress-families&action=delete&family_id=' . urlencode($family['familyID']) . '&gedcom=' . urlencode($family['gedcom'])); ?>" class="button button-small delete-family" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this family?'); ?>');"><?php esc_html_e('Delete'); ?></a>
            </td>
          </tr>
      <?php endforeach;
      endif; ?>
    </tbody>
  </table>
  <div class="tablenav bottom">
    <div class="tablenav-pages">
      <?php if ($total_pages > 1): ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $paged): ?>
            <span class="paging-input"><span class="current"><?php echo $i; ?></span></span>
          <?php else: ?>
            <a class="page-numbers" href="<?php echo esc_url(add_query_arg('paged', $i)); ?>"><?php echo $i; ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
