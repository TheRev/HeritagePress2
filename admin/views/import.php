<?php

/**
 * GEDCOM Import Admin View
 */

if (!defined('ABSPATH')) {
  exit;
}

$max_file_size = wp_max_upload_size();
$max_file_size_mb = round($max_file_size / 1024 / 1024, 1);
?>

<div class="wrap">
  <h1><?php _e('Import GEDCOM File', 'heritagepress'); ?></h1>

  <?php settings_errors('heritagepress_import'); ?>

  <div class="heritagepress-import-container">
    <div class="card">
      <h2 class="title"><?php _e('Upload GEDCOM File', 'heritagepress'); ?></h2>

      <form method="post" enctype="multipart/form-data" id="gedcom-import-form">
        <?php wp_nonce_field('heritagepress_import', '_wpnonce'); ?>
        <input type="hidden" name="action" value="upload_gedcom" />

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="gedcom_file"><?php _e('GEDCOM File', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="file"
                  name="gedcom_file"
                  id="gedcom_file"
                  accept=".ged,.gedcom"
                  required />
                <p class="description">
                  <?php printf(
                    __('Select a GEDCOM file (.ged or .gedcom). Maximum file size: %s MB', 'heritagepress'),
                    $max_file_size_mb
                  ); ?>
                </p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="tree_id"><?php _e('Tree ID', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text"
                  name="tree_id"
                  id="tree_id"
                  value="main"
                  class="regular-text"
                  required />
                <p class="description">
                  <?php _e('Unique identifier for this family tree (e.g., "smith_family", "main")', 'heritagepress'); ?>
                </p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="encoding"><?php _e('File Encoding', 'heritagepress'); ?></label>
              </th>
              <td>
                <select name="encoding" id="encoding">
                  <option value="auto"><?php _e('Auto-detect', 'heritagepress'); ?></option>
                  <option value="UTF-8">UTF-8</option>
                  <option value="ANSI">ANSI</option>
                  <option value="ASCII">ASCII</option>
                  <option value="UTF-16">UTF-16</option>
                </select>
                <p class="description">
                  <?php _e('Character encoding of the GEDCOM file. Auto-detect is recommended.', 'heritagepress'); ?>
                </p>
              </td>
            </tr>

            <tr>
              <th scope="row"><?php _e('Import Options', 'heritagepress'); ?></th>
              <td>
                <fieldset>
                  <label>
                    <input type="checkbox" name="import_living" value="1" checked />
                    <?php _e('Import living individuals', 'heritagepress'); ?>
                  </label>
                  <br />
                  <label>
                    <input type="checkbox" name="import_private" value="1" />
                    <?php _e('Import private records', 'heritagepress'); ?>
                  </label>
                  <br />
                  <label>
                    <input type="checkbox" name="import_sources" value="1" checked />
                    <?php _e('Import sources and citations', 'heritagepress'); ?>
                  </label>
                  <br />
                  <label>
                    <input type="checkbox" name="import_media" value="1" checked />
                    <?php _e('Import media references', 'heritagepress'); ?>
                  </label>
                </fieldset>
              </td>
            </tr>
          </tbody>
        </table>

        <?php submit_button(__('Start Import', 'heritagepress'), 'primary', 'submit', true, array('id' => 'start-import')); ?>
      </form>
    </div>

    <div class="card" style="margin-top: 20px;">
      <h2 class="title"><?php _e('Import Progress', 'heritagepress'); ?></h2>
      <div id="import-progress" style="display: none;">
        <div class="progress-bar">
          <div class="progress-fill" style="width: 0%;"></div>
        </div>
        <div id="progress-text"><?php _e('Preparing import...', 'heritagepress'); ?></div>
        <div id="import-log"></div>
      </div>
      <div id="import-results" style="display: none;">
        <h3><?php _e('Import Results', 'heritagepress'); ?></h3>
        <div id="results-content"></div>
      </div>
    </div>

    <div class="card" style="margin-top: 20px;">
      <h2 class="title"><?php _e('Import Guidelines', 'heritagepress'); ?></h2>
      <ul>
        <li><?php _e('GEDCOM files should follow GEDCOM 5.5.1 specification', 'heritagepress'); ?></li>
        <li><?php _e('UTF-8 encoding is recommended for international characters', 'heritagepress'); ?></li>
        <li><?php _e('Large files may take several minutes to process', 'heritagepress'); ?></li>
        <li><?php _e('Ensure you have a backup before importing', 'heritagepress'); ?></li>
        <li><?php _e('Import will create people, families, events, and sources', 'heritagepress'); ?></li>
      </ul>
    </div>
  </div>
</div>

<style>
  .heritagepress-import-container .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    padding: 20px;
  }

  .progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background-color: #0073aa;
    transition: width 0.3s ease;
  }

  #progress-text {
    margin-top: 10px;
    font-weight: bold;
  }

  #import-log {
    margin-top: 15px;
    max-height: 200px;
    overflow-y: auto;
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 10px;
    font-family: monospace;
    font-size: 12px;
  }
</style>
