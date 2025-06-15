<?php

/**
 * Import/Export Admin View
 * Complete facsimile of TNG data import/export interface
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'import';

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Get branches
$branches_table = $wpdb->prefix . 'hp_branches';

// Import configuration
$import_config = array(
  'gedpath' => 'gedcom',
  'saveconfig' => '',
  'saveimport' => 1,
  'rrnum' => 100,
  'readmsecs' => 750,
  'defimpopt' => 0,
  'chdate' => 0,
  'livingreqbirth' => 0,
  'maxlivingage' => 110,
  'maxprivyrs' => '',
  'maxdecdyrs' => '',
  'maxmarriedage' => 0,
  'privnote' => '',
  'coerce' => 0
);

// Media types for export
$media_types = array(
  array('ID' => 'photos', 'display' => 'Photos'),
  array('ID' => 'histories', 'display' => 'Histories'),
  array('ID' => 'documents', 'display' => 'Documents'),
  array('ID' => 'headstones', 'display' => 'Headstones'),
  array('ID' => 'other', 'display' => 'Other')
);

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

$max_file_size = wp_max_upload_size();
$max_file_size_mb = round($max_file_size / 1024 / 1024, 1);
?>

<div class="wrap">
  <h1><?php _e('Import / Export', 'heritagepress'); ?></h1>

  <?php
  settings_errors('heritagepress_import');
  settings_errors('heritagepress_export');
  settings_errors('heritagepress_post_import');
  ?>

  <!-- Tab Navigation -->
  <h2 class="nav-tab-wrapper">
    <a href="?page=heritagepress-import&tab=import" class="nav-tab <?php echo $current_tab === 'import' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Import', 'heritagepress'); ?>
    </a>
    <a href="?page=heritagepress-import&tab=export" class="nav-tab <?php echo $current_tab === 'export' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Export', 'heritagepress'); ?>
    </a>
    <a href="?page=heritagepress-import&tab=post-import" class="nav-tab <?php echo $current_tab === 'post-import' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Post-Import', 'heritagepress'); ?>
    </a>
  </h2>
  <div class="tab-content">
    <?php if ($current_tab === 'import'): ?>
      <!-- Import Tab Content (Based on admin_dataimport.php) -->
      <div class="import-section">
        <div class="section-header">
          <h2 class="section-title"><?php _e('Import GEDCOM Data', 'heritagepress'); ?></h2>
          <p class="section-description"><?php _e('Add or replace genealogy data in your HeritagePress database from GEDCOM files', 'heritagepress'); ?></p>
        </div>

        <form action="<?php echo admin_url('admin.php?page=heritagepress-import&tab=import'); ?>" method="post" name="form1" enctype="multipart/form-data" id="gedcom-import-form">
          <?php wp_nonce_field('heritagepress_import', '_wpnonce'); ?>
          <input type="hidden" name="action" value="import_gedcom" />

          <!-- File Selection Card -->
          <div class="form-card">
            <div class="form-card-header">
              <h3 class="form-card-title"><?php _e('Select GEDCOM File', 'heritagepress'); ?></h3>
            </div>
            <div class="form-card-body">
              <table class="hp-form-table">
                <tr>
                  <td><?php _e('From your computer', 'heritagepress'); ?>:</td>
                  <td><input type="file" name="remotefile" accept=".ged,.gedcom" /></td>
                </tr>
                <tr>
                  <td><?php _e('From web server', 'heritagepress'); ?>:</td>
                  <td>
                    <input type="text" name="database" id="database" placeholder="<?php _e('Enter file path or select...', 'heritagepress'); ?>" />
                    <input type="hidden" id="database_org" value="" />
                    <input type="hidden" id="database_last" value="" />
                    <input type="button" value="<?php _e('Browse...', 'heritagepress'); ?>" name="gedselect" class="button button-secondary" onclick="alert('File picker not yet implemented');" />
                  </td>
                </tr>
              </table>

              <div class="option-group">
                <div class="option-item">
                  <input type="checkbox" name="allevents" id="allevents" value="yes" onclick="if(document.form1.allevents.checked && document.form1.eventsonly.checked) {document.form1.eventsonly.checked ='';toggleSections(false)}" />
                  <label for="allevents"><?php _e('Import all events', 'heritagepress'); ?></label>
                </div>
                <div class="option-item">
                  <input type="checkbox" name="eventsonly" id="eventsonly" value="yes" onclick="toggleSections(this.checked);" />
                  <label for="eventsonly"><?php _e('Import events only', 'heritagepress'); ?></label>
                </div>
              </div>
            </div>
          </div>

          <!-- Tree Selection Card -->
          <div class="form-card" id="desttree">
            <div class="form-card-header">
              <h3 class="form-card-title"><?php _e('Destination Tree', 'heritagepress'); ?></h3>
            </div>
            <div class="form-card-body">
              <table class="hp-form-table">
                <tr id="desttree2">
                  <td><?php _e('Select tree', 'heritagepress'); ?>:</td>
                  <td>
                    <select name="tree1" id="tree1" onchange="getBranches(this,this.selectedIndex);">
                      <?php if (count($trees_result) != 1): ?>
                        <option value=""><?php _e('Select a tree...', 'heritagepress'); ?></option>
                      <?php endif; ?>
                      <?php foreach ($trees_result as $tree): ?>
                        <option value="<?php echo esc_attr($tree['gedcom']); ?>"><?php echo esc_html($tree['treename']); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <input type="button" name="newtree" value="<?php _e('Add New Tree', 'heritagepress'); ?>" class="button button-secondary" onclick="alert('Add new tree functionality not yet implemented');" />
                  </td>
                </tr>
                <tr id="destbranch" style="display:none">
                  <td><?php _e('Select branch', 'heritagepress'); ?>:</td>
                  <td>
                    <div id="branch1div">
                      <select name="branch1" id="branch1">
                        <option value=""><?php _e('All branches', 'heritagepress'); ?></option>
                      </select>
                    </div>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Import Options Card -->
          <div class="form-card" id="replace">
            <div class="form-card-header">
              <h3 class="form-card-title"><?php _e('Import Options', 'heritagepress'); ?></h3>
            </div>
            <div class="form-card-body">
              <div class="radio-group">
                <div class="radio-option <?php echo ($import_config['defimpopt'] == 1) ? 'selected' : ''; ?>">
                  <input type="radio" name="del" id="del_yes" value="yes" <?php if ($import_config['defimpopt'] == 1) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(0);" />
                  <div class="radio-option-content">
                    <div class="radio-option-title"><?php _e('Replace all current data in tree', 'heritagepress'); ?></div>
                    <div class="radio-option-description"><?php _e('This will completely replace all existing data in the selected tree', 'heritagepress'); ?></div>
                  </div>
                </div>

                <div class="radio-option <?php echo (!$import_config['defimpopt']) ? 'selected' : ''; ?>">
                  <input type="radio" name="del" id="del_match" value="match" <?php if (!$import_config['defimpopt']) echo " checked=\"checked\""; ?> onclick="toggleNorecalcdiv(1); toggleAppenddiv(0);" />
                  <div class="radio-option-content">
                    <div class="radio-option-title"><?php _e('Replace matching data only', 'heritagepress'); ?></div>
                    <div class="radio-option-description"><?php _e('Only replace records that have matching IDs in both the file and database', 'heritagepress'); ?></div>
                  </div>
                </div>

                <div class="radio-option <?php echo ($import_config['defimpopt'] == 2) ? 'selected' : ''; ?>">
                  <input type="radio" name="del" id="del_no" value="no" <?php if ($import_config['defimpopt'] == 2) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(0);" />
                  <div class="radio-option-content">
                    <div class="radio-option-title"><?php _e('Do not replace, ignore matching data', 'heritagepress'); ?></div>
                    <div class="radio-option-description"><?php _e('Skip any records that already exist in the database', 'heritagepress'); ?></div>
                  </div>
                </div>

                <div class="radio-option <?php echo ($import_config['defimpopt'] == 3) ? 'selected' : ''; ?>">
                  <input type="radio" name="del" id="del_append" value="append" <?php if ($import_config['defimpopt'] == 3) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(1);" />
                  <div class="radio-option-content">
                    <div class="radio-option-title"><?php _e('Append all data (add ID offset)', 'heritagepress'); ?></div>
                    <div class="radio-option-description"><?php _e('Import all records with new IDs, adding an offset to avoid conflicts', 'heritagepress'); ?></div>
                  </div>
                </div>
              </div>

              <div style="display: flex; gap: 40px; margin-top: 20px;">
                <div style="flex: 1;">
                  <h4 style="margin-bottom: 10px; font-weight: 600;"><?php _e('Additional Options', 'heritagepress'); ?></h4>
                  <div class="option-group" style="flex-direction: column; align-items: flex-start;">
                    <div class="option-item">
                      <input type="checkbox" name="ucaselast" id="ucaselast" value="1" />
                      <label for="ucaselast"><?php _e('Uppercase surnames', 'heritagepress'); ?></label>
                    </div>
                    <div class="option-item">
                      <input type="checkbox" name="importmedia" id="importmedia" value="1" />
                      <label for="importmedia"><?php _e('Import media links', 'heritagepress'); ?></label>
                    </div>
                    <div class="option-item">
                      <input type="checkbox" name="importlatlong" id="importlatlong" value="1" />
                      <label for="importlatlong"><?php _e('Import latitude/longitude', 'heritagepress'); ?></label>
                    </div>
                    <div id="norecalcdiv" <?php if ($import_config['defimpopt']) echo " style=\"display:none\""; ?>>
                      <div class="option-item">
                        <input type="checkbox" name="norecalc" id="norecalc" value="1" />
                        <label for="norecalc"><?php _e('Skip relationships recalculation', 'heritagepress'); ?></label>
                      </div>
                      <div class="option-item">
                        <input type="checkbox" name="neweronly" id="neweronly" value="1" />
                        <label for="neweronly"><?php _e('Import newer records only', 'heritagepress'); ?></label>
                      </div>
                    </div>
                  </div>
                </div>

                <div style="flex: 1;">
                  <div id="appenddiv" <?php if ($import_config['defimpopt'] != 3) echo " style=\"display:none;\""; ?>>
                    <h4 style="margin-bottom: 10px; font-weight: 600;"><?php _e('ID Offset Settings', 'heritagepress'); ?></h4>
                    <div class="option-group" style="flex-direction: column; align-items: flex-start;">
                      <div class="option-item">
                        <input type="radio" name="offsetchoice" id="offset_auto" value="auto" checked />
                        <label for="offset_auto"><?php _e('Calculate ID offset automatically', 'heritagepress'); ?></label>
                      </div>
                      <div class="option-item">
                        <input type="radio" name="offsetchoice" id="offset_user" value="user" />
                        <label for="offset_user"><?php _e('Use this ID offset:', 'heritagepress'); ?></label>
                        <input type="text" name="useroffset" style="width: 100px; margin-left: 8px;" maxlength="9" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="notice notice-info" style="margin-top: 20px;">
                <ul style="margin: 0; padding-left: 20px;">
                  <li><em><?php _e('Stop and backup before importing large files', 'heritagepress'); ?></em></li>
                  <li><em><?php _e('Check import settings before importing', 'heritagepress'); ?></em></li>
                  <li><em><?php _e('Large imports may take several minutes', 'heritagepress'); ?></em></li>
                </ul>
              </div>

              <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e1e1e1;">
                <div class="option-item">
                  <input type="checkbox" name="old" id="old" value="1" onclick="toggleTarget(document.form1);" />
                  <label for="old"><?php _e('Use legacy import method', 'heritagepress'); ?></label>
                </div>
                <input type="submit" name="submit" class="button button-primary" value="<?php _e('Import Data', 'heritagepress'); ?>" />
              </div>
            </div>
          </div>
        </form>

        <iframe id="results" height="0" width="0" frameborder="0" name="results" onload="iframeLoaded();" style="display:none;"></iframe>
      </div> <?php elseif ($current_tab === 'export'): ?>
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
                    <select name="branch" id="branch" size="6" style="min-height: 120px;">
                      <option value=""><?php _e('All branches', 'heritagepress'); ?></option>
                      <!-- Branches will be loaded via JavaScript -->
                    </select>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                      <?php _e('Hold Ctrl/Cmd to select multiple branches', 'heritagepress'); ?>
                    </div>
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
      </div> <?php elseif ($current_tab === 'post-import'): ?>
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
    <?php endif; ?>
  </div>
</div>

<script type="text/javascript">
  // JavaScript functions for import/export functionality

  function toggleSections(eventsOnly) {
    // Toggle sections based on events only checkbox
    var sections = ['desttree', 'replace'];
    for (var i = 0; i < sections.length; i++) {
      var element = document.getElementById(sections[i]);
      if (element) {
        element.style.display = eventsOnly ? 'none' : '';
      }
    }
  }

  function toggleNorecalcdiv(show) {
    var div = document.getElementById('norecalcdiv');
    if (div) {
      div.style.display = show ? '' : 'none';
    }
  }

  function toggleAppenddiv(show) {
    var div = document.getElementById('appenddiv');
    if (div) {
      div.style.display = show ? '' : 'none';
    }
  }

  function toggleTarget(form) {
    // Toggle form target for legacy import
    if (form.old.checked) {
      form.target = 'results';
      document.getElementById('results').style.display = 'block';
      document.getElementById('results').height = '300';
      document.getElementById('results').width = '100%';
    } else {
      form.target = '';
      document.getElementById('results').style.display = 'none';
    }
  }

  function getBranches(selectElement, selectedIndex) {
    // Load branches for selected tree
    var tree = selectElement.value;
    var branchSelect = document.getElementById('branch1');

    if (branchSelect) {
      // Clear existing options
      branchSelect.innerHTML = '<option value="">All branches</option>';

      // Show/hide branch selection
      var branchRow = document.getElementById('destbranch');
      if (branchRow) {
        branchRow.style.display = tree ? '' : 'none';
      }

      // AJAX call to load branches would go here
      if (tree) {
        // Placeholder for actual AJAX implementation
        console.log('Loading branches for tree: ' + tree);
      }
    }
  }

  function swapBranches(form) {
    // Swap branches for export tree selection
    var tree = form.tree.value;
    var branchSelect = document.getElementById('branch');

    if (branchSelect && tree) {
      // Clear existing options
      branchSelect.innerHTML = '<option value="">All branches</option>';

      // AJAX call to load branches would go here
      console.log('Loading branches for export tree: ' + tree);
    }
  }

  function toggleStuff() {
    // Toggle media export options
    var exportMedia = document.getElementById('exportmedia');
    var exportMediaFiles = document.getElementById('exportmediafiles');
    var expRows = document.getElementById('exprows');

    if (exportMedia && exportMediaFiles && expRows) {
      if (exportMedia.checked) {
        exportMediaFiles.disabled = false;
        expRows.style.display = 'block';
      } else {
        exportMediaFiles.disabled = true;
        exportMediaFiles.checked = false;
        expRows.style.display = 'none';
      }
    }
  }

  function iframeLoaded() {
    // Handle iframe load for import progress
    console.log('Import iframe loaded');
  }

  function runPostImportUtility(utility) {
    if (confirm('Run post-import utility: ' + utility + '?\n\nThis may take several minutes for large databases.')) {
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = window.location.href;

      var nonceField = document.createElement('input');
      nonceField.type = 'hidden';
      nonceField.name = '_wpnonce';
      nonceField.value = '<?php echo wp_create_nonce('heritagepress_post_import'); ?>';
      form.appendChild(nonceField);

      var actionField = document.createElement('input');
      actionField.type = 'hidden';
      actionField.name = 'secaction';
      actionField.value = utility;
      form.appendChild(actionField);

      var treeField = document.createElement('input');
      treeField.type = 'hidden';
      treeField.name = 'tree';
      treeField.value = document.getElementById('treequeryselect').value;
      form.appendChild(treeField);

      document.body.appendChild(form);
      form.submit();
    }
  }

  // Enhanced radio option selection
  function updateRadioSelection() {
    var radioOptions = document.querySelectorAll('.radio-option');
    radioOptions.forEach(function(option) {
      var radio = option.querySelector('input[type="radio"]');
      if (radio && radio.checked) {
        option.classList.add('selected');
      } else {
        option.classList.remove('selected');
      }
    });
  }

  // Initialize page
  jQuery(document).ready(function($) {
    // Initialize form elements
    toggleNorecalcdiv(<?php echo $import_config['defimpopt'] ? 1 : 0; ?>);
    toggleAppenddiv(<?php echo $import_config['defimpopt'] == 3 ? 1 : 0; ?>);

    // Add radio option click handlers
    $('.radio-option').on('click', function() {
      var radio = $(this).find('input[type="radio"]');
      if (radio.length) {
        radio.prop('checked', true).trigger('change');
        updateRadioSelection();
      }
    });

    // Add change handlers for radio buttons
    $('input[type="radio"]').on('change', function() {
      updateRadioSelection();
    });

    // Initialize radio selection state
    updateRadioSelection();

    // Add hover effects for utility cards
    $('.utility-card').on('mouseenter', function() {
      $(this).css('transform', 'translateY(-2px)');
    }).on('mouseleave', function() {
      $(this).css('transform', 'translateY(0)');
    });

    // Form validation
    $('#gedcom-import-form').on('submit', function(e) {
      var hasFile = $('input[name="remotefile"]').val();
      var hasPath = $('input[name="database"]').val();
      var hasTree = $('#tree1').val();

      if (!hasFile && !hasPath) {
        alert('Please select a GEDCOM file to import.');
        e.preventDefault();
        return false;
      }

      if (!hasTree) {
        alert('Please select a destination tree.');
        e.preventDefault();
        return false;
      }

      return true;
    });
  });
</script>
