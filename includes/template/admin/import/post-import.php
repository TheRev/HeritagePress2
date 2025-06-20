<?php

/**
 * Post-Import Admin View
 * Post-import utilities interface (Based on admin second menu)
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Post-import utilities
$post_import_utils = array(
  'Track Lines',
  'Sort Children',
  'Sort Spouses',
  'Relabel Branches',
  'Create GenDex',
  'Evaluate Media',
  'Refresh Living',
  'Make Private'
);
?>

<!-- Post-Import Tab Content (Based on admin_secondmenu.php) -->
<div class="post-import-section">
  <div class="section-header">
    <h2 class="section-title"><?php _e('Post-Import Utilities', 'heritagepress'); ?></h2>
    <p class="section-description"><?php _e('Maintain data integrity and relationships after importing genealogy data', 'heritagepress'); ?></p>
  </div>

  <!-- Tree Selection Card -->
  <div class="form-card">
    <div class="form-card-header">
      <h3 class="form-card-title"><?php _e('Select Tree for Processing', 'heritagepress'); ?></h3>
    </div>
    <div class="form-card-body">
      <table class="hp-form-table">
        <tr>
          <td><?php _e('Tree', 'heritagepress'); ?>:</td>
          <td>
            <select name="tree" id="treequeryselect" style="min-width: 250px;">
              <option value="--all--"><?php _e('All trees', 'heritagepress'); ?></option>
              <?php foreach ($trees_result as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>"><?php echo esc_html($tree['treename']); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Utilities Card -->
  <div class="form-card">
    <div class="form-card-header">
      <h3 class="form-card-title"><?php _e('Available Utilities', 'heritagepress'); ?></h3>
    </div>
    <div class="form-card-body">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
        <?php $i = 1;
        foreach ($post_import_utils as $util): ?>
          <div class="utility-card" style="border: 1px solid #e1e1e1; border-radius: 6px; padding: 15px; background: #fafafa; transition: all 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <div style="background: #0073aa; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">
                <?php echo $i; ?>
              </div>
              <div style="flex: 1;">
                <h4 style="margin: 0 0 5px 0; font-weight: 600; color: #23282d;">
                  <a href="#" onclick="runPostImportUtility('<?php echo esc_js($util); ?>'); return false;" class="utility-link">
                    <?php echo esc_html($util); ?>
                  </a>
                </h4>
                <div style="font-size: 13px; color: #666;">
                  <?php
                  // Add descriptions for each utility
                  $descriptions = array(
                    'Track Lines' => __('Rebuild ancestry and descendancy lines for all individuals', 'heritagepress'),
                    'Sort Children' => __('Sort children by birth date within each family', 'heritagepress'),
                    'Sort Spouses' => __('Sort spouses by marriage date for each individual', 'heritagepress'),
                    'Relabel Branches' => __('Update branch labels and hierarchies', 'heritagepress'),
                    'Create GenDex' => __('Generate genealogy index for faster searches', 'heritagepress'),
                    'Evaluate Media' => __('Check and validate media file links', 'heritagepress'),
                    'Refresh Living' => __('Update living status based on current criteria', 'heritagepress'),
                    'Make Private' => __('Apply privacy settings to appropriate records', 'heritagepress')
                  );
                  echo isset($descriptions[$util]) ? $descriptions[$util] : __('Post-import data processing utility', 'heritagepress');
                  ?>
                </div>
              </div>
            </div>
          </div>
        <?php $i++;
        endforeach; ?>
      </div>

      <div class="notice notice-info" style="margin-top: 25px;">
        <p style="margin: 0;">
          <strong><?php _e('Note:', 'heritagepress'); ?></strong>
          <?php _e('These utilities help maintain data integrity after importing GEDCOM files. Run them in order for best results. Processing may take several minutes for large databases.', 'heritagepress'); ?>
        </p>
      </div>

      <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e1e1e1; text-align: center;">
        <p style="color: #666; margin: 0;">
          <?php _e('For additional genealogy tools and resources, visit', 'heritagepress'); ?>:
          <a href="https://www.familytreeseeker.com" target="_blank" style="color: #0073aa;">FamilyTreeSeeker.com</a>
        </p>
      </div>
    </div>
  </div>
</div>
