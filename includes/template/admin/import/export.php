<?php

/**
 * Export Admin View
 * GEDCOM data export interface (Based on TNG admin_export.php)
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Media types for export
$media_types = array(
  array('ID' => 'photos', 'display' => 'Photos'),
  array('ID' => 'histories', 'display' => 'Histories'),
  array('ID' => 'documents', 'display' => 'Documents'),
  array('ID' => 'headstones', 'display' => 'Headstones'),
  array('ID' => 'other', 'display' => 'Other')
);
?>

<!-- Export Tab Content (Based on admin_export.php) -->
<div class="export-section">
  <div class="section-header">
    <h2 class="section-title"><?php _e('Export GEDCOM Data', 'heritagepress'); ?></h2>
    <p class="section-description"><?php _e('Export genealogy data from your HeritagePress database to GEDCOM format', 'heritagepress'); ?></p>
  </div>

  <form action="<?php echo admin_url('admin.php?page=heritagepress-import&tab=export'); ?>" method="post" name="form1">
    <?php wp_nonce_field('heritagepress_export', '_wpnonce'); ?>
    <input type="hidden" name="action" value="export_gedcom" />

    <!-- Tree and Branch Selection Card -->
    <div class="form-card">
      <div class="form-card-header">
        <h3 class="form-card-title"><?php _e('Select Data to Export', 'heritagepress'); ?></h3>
      </div>
      <div class="form-card-body">
        <table class="hp-form-table">
          <tr>
            <td><?php _e('Tree', 'heritagepress'); ?>:</td>
            <td>
              <select name="tree" id="treeselect" onchange="swapBranches(document.form1);">
                <option value=""><?php _e('Select a tree...', 'heritagepress'); ?></option>
                <?php foreach ($trees_result as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>"><?php echo esc_html($tree['treename']); ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr>
            <td><?php _e('Branch', 'heritagepress'); ?>:</td>
            <td>
              <select name="branch" id="branch">
                <option value=""><?php _e('All branches', 'heritagepress'); ?></option>
                <!-- Branches will be loaded via JavaScript -->
              </select>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Export Options Card -->
    <div class="form-card">
      <div class="form-card-header">
        <h3 class="form-card-title"><?php _e('Export Options', 'heritagepress'); ?></h3>
      </div>
      <div class="form-card-body">
        <h4 style="margin-bottom: 15px; font-weight: 600;"><?php _e('Data Filtering', 'heritagepress'); ?></h4>
        <div class="option-group">
          <div class="option-item">
            <input type="checkbox" name="exliving" id="exliving" value="1" />
            <label for="exliving"><?php _e('Exclude living people', 'heritagepress'); ?></label>
          </div>
          <div class="option-item">
            <input type="checkbox" name="exprivate" id="exprivate" value="1" />
            <label for="exprivate"><?php _e('Exclude private records', 'heritagepress'); ?></label>
          </div>
          <div class="option-item">
            <input type="checkbox" name="exnotes" id="exnotes" value="1" />
            <label for="exnotes"><?php _e('Exclude notes', 'heritagepress'); ?></label>
          </div>
        </div>

        <h4 style="margin: 20px 0 15px 0; font-weight: 600;"><?php _e('Media Export', 'heritagepress'); ?></h4>
        <div class="option-group">
          <div class="option-item">
            <input type="checkbox" name="exportmedia" id="exportmedia" value="1" onClick="toggleStuff();" />
            <label for="exportmedia"><?php _e('Export media links', 'heritagepress'); ?></label>
          </div>
          <div class="option-item">
            <input type="checkbox" name="exportmediafiles" id="exportmediafiles" value="1" disabled />
            <label for="exportmediafiles"><?php _e('Export media files', 'heritagepress'); ?></label>
          </div>
        </div>

        <div id="exprows" style="display:none;">
          <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 20px; margin-top: 20px;">
            <h4 style="margin-bottom: 15px; font-weight: 600;"><?php _e('Media Export Paths', 'heritagepress'); ?></h4>
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 2px solid #ddd;">
                  <th style="text-align: left; padding: 8px; width: 80px;"><?php _e('Include', 'heritagepress'); ?></th>
                  <th style="text-align: left; padding: 8px; width: 150px;"><?php _e('Media Type', 'heritagepress'); ?></th>
                  <th style="text-align: left; padding: 8px;"><?php _e('Export Path', 'heritagepress'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($media_types as $mediatype): ?>
                  <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px;">
                      <input type="checkbox" name="incl_<?php echo $mediatype['ID']; ?>" value="1" checked="checked" />
                    </td>
                    <td style="padding: 8px; font-weight: 600;">
                      <?php echo $mediatype['display']; ?>:
                    </td>
                    <td style="padding: 8px;">
                      <input type="text" value="" name="exp_path_<?php echo $mediatype['ID']; ?>" placeholder="<?php _e('Enter export path for', 'heritagepress'); ?> <?php echo strtolower($mediatype['display']); ?>" style="width: 100%; max-width: 400px;" />
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e1e1e1; text-align: right;">
          <input type="submit" name="submit" class="button button-primary" value="<?php _e('Export Data', 'heritagepress'); ?>" />
        </div>
      </div>
    </div>
  </form>
</div>
