<?php

/**
 * HeritagePress Admin Tools View
 * Miscellaneous utility tools
 *
 * @package    HeritagePress
 * @subpackage Admin\Views
 */

if (!defined('ABSPATH')) {
  exit;
}

// Add nonce for AJAX operations
$tools_nonce = wp_create_nonce('hp_tools_operation');
?>

<div class="heritagepress-tools-section">
  <div class="tools-instructions">
    <p>
      <?php _e('These utilities help with specific genealogy data management tasks and operations.', 'heritagepress'); ?>
    </p>
  </div>

  <div class="tools-grid">
    <!-- Data Export Tool -->
    <div class="tool-card card">
      <div class="tool-header">
        <span class="dashicons dashicons-download"></span>
        <h3><?php _e('Data Export', 'heritagepress'); ?></h3>
      </div>
      <div class="tool-body">
        <p><?php _e('Export your genealogy data to various formats for use in other applications or for backup purposes.', 'heritagepress'); ?></p>
        <div class="tool-options">
          <label><?php _e('Export Format:', 'heritagepress'); ?></label>
          <select id="export-format">
            <option value="gedcom"><?php _e('GEDCOM (.ged)', 'heritagepress'); ?></option>
            <option value="csv"><?php _e('CSV (.csv)', 'heritagepress'); ?></option>
            <option value="sql"><?php _e('SQL (.sql)', 'heritagepress'); ?></option>
            <option value="xml"><?php _e('XML (.xml)', 'heritagepress'); ?></option>
          </select>
        </div>
        <div class="tool-options">
          <label><?php _e('Choose Tree:', 'heritagepress'); ?></label>
          <select id="export-tree">
            <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
            <?php
            // Tree options would be populated here
            ?>
          </select>
        </div>
      </div>
      <div class="tool-footer">
        <button type="button" id="start-export" class="button button-primary"><?php _e('Start Export', 'heritagepress'); ?></button>
      </div>
    </div>

    <!-- Date Fix Tool -->
    <div class="tool-card card">
      <div class="tool-header">
        <span class="dashicons dashicons-calendar-alt"></span>
        <h3><?php _e('Date Standardization', 'heritagepress'); ?></h3>
      </div>
      <div class="tool-body">
        <p><?php _e('Standardize date formats across your genealogy database and fix common date issues.', 'heritagepress'); ?></p>
        <div class="tool-options">
          <label><?php _e('Choose Tree:', 'heritagepress'); ?></label>
          <select id="date-fix-tree">
            <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
            <?php
            // Tree options would be populated here
            ?>
          </select>
        </div>
        <div class="tool-options">
          <label>
            <input type="checkbox" id="date-fix-preview" checked>
            <?php _e('Preview changes before applying', 'heritagepress'); ?>
          </label>
        </div>
      </div>
      <div class="tool-footer">
        <button type="button" id="start-date-fix" class="button button-primary"><?php _e('Analyze Dates', 'heritagepress'); ?></button>
      </div>
    </div>

    <!-- Duplicate Finder Tool -->
    <div class="tool-card card">
      <div class="tool-header">
        <span class="dashicons dashicons-search"></span>
        <h3><?php _e('Duplicate Finder', 'heritagepress'); ?></h3>
      </div>
      <div class="tool-body">
        <p><?php _e('Find and merge potential duplicate person records in your genealogy database.', 'heritagepress'); ?></p>
        <div class="tool-options">
          <label><?php _e('Matching Strictness:', 'heritagepress'); ?></label>
          <select id="duplicate-strictness">
            <option value="high"><?php _e('High (fewer matches)', 'heritagepress'); ?></option>
            <option value="medium" selected><?php _e('Medium', 'heritagepress'); ?></option>
            <option value="low"><?php _e('Low (more matches)', 'heritagepress'); ?></option>
          </select>
        </div>
        <div class="tool-options">
          <label><?php _e('Choose Tree:', 'heritagepress'); ?></label>
          <select id="duplicate-tree">
            <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
            <?php
            // Tree options would be populated here
            ?>
          </select>
        </div>
      </div>
      <div class="tool-footer">
        <button type="button" id="find-duplicates" class="button button-primary"><?php _e('Find Duplicates', 'heritagepress'); ?></button>
      </div>
    </div>

    <!-- Place Geocoder Tool -->
    <div class="tool-card card">
      <div class="tool-header">
        <span class="dashicons dashicons-location"></span>
        <h3><?php _e('Place Geocoder', 'heritagepress'); ?></h3>
      </div>
      <div class="tool-body">
        <p><?php _e('Add latitude and longitude coordinates to place records for mapping and visualization.', 'heritagepress'); ?></p>
        <div class="tool-options">
          <label><?php _e('Geocoding Service:', 'heritagepress'); ?></label>
          <select id="geocoding-service">
            <option value="google"><?php _e('Google Maps', 'heritagepress'); ?></option>
            <option value="nominatim"><?php _e('Nominatim (OpenStreetMap)', 'heritagepress'); ?></option>
          </select>
        </div>
        <div class="tool-options">
          <label>
            <input type="checkbox" id="geocode-missing-only" checked>
            <?php _e('Only geocode places without coordinates', 'heritagepress'); ?>
          </label>
        </div>
      </div>
      <div class="tool-footer">
        <button type="button" id="start-geocoding" class="button button-primary"><?php _e('Start Geocoding', 'heritagepress'); ?></button>
      </div>
    </div>

    <!-- Privacy Tool -->
    <div class="tool-card card">
      <div class="tool-header">
        <span class="dashicons dashicons-shield"></span>
        <h3><?php _e('Privacy Manager', 'heritagepress'); ?></h3>
      </div>
      <div class="tool-body">
        <p><?php _e('Update privacy settings for your genealogy data and manage living person information.', 'heritagepress'); ?></p>
        <div class="tool-options">
          <label><?php _e('Privacy Action:', 'heritagepress'); ?></label>
          <select id="privacy-action">
            <option value="mark-living"><?php _e('Mark Living People', 'heritagepress'); ?></option>
            <option value="privacy-sensitive"><?php _e('Find Privacy-Sensitive Data', 'heritagepress'); ?></option>
            <option value="anonymize"><?php _e('Anonymize Living People', 'heritagepress'); ?></option>
          </select>
        </div>
        <div class="tool-options">
          <label><?php _e('Consider living if born after year:', 'heritagepress'); ?></label>
          <input type="number" id="living-year-threshold" value="1930" min="1800" max="2025">
        </div>
      </div>
      <div class="tool-footer">
        <button type="button" id="run-privacy-tool" class="button button-primary"><?php _e('Run Privacy Check', 'heritagepress'); ?></button>
      </div>
    </div>

    <!-- Custom Text Update Tool -->
    <div class="tool-card card">
      <div class="tool-header">
        <span class="dashicons dashicons-translation"></span>
        <h3><?php _e('Custom Text Update', 'heritagepress'); ?></h3>
      </div>
      <div class="tool-body">
        <p><?php _e('Update custom text files across all language directories with standardized comments and format improvements.', 'heritagepress'); ?></p>
        <div class="tool-options">
          <p><strong><?php _e('What this tool does:', 'heritagepress'); ?></strong></p>
          <ul>
            <li><?php _e('Adds standardized comments to custom text files', 'heritagepress'); ?></li>
            <li><?php _e('Updates message format to current standard', 'heritagepress'); ?></li>
            <li><?php _e('Creates missing custom text files', 'heritagepress'); ?></li>
            <li><?php _e('Creates automatic backups (cust_text.bak)', 'heritagepress'); ?></li>
          </ul>
        </div>
        <div class="tool-options">
          <label>
            <input type="checkbox" id="backup-files" checked disabled>
            <?php _e('Create backup files (always enabled)', 'heritagepress'); ?>
          </label>
        </div>
      </div>
      <div class="tool-footer">
        <a href="<?php echo admin_url('admin.php?page=heritagepress-utilities-custom-text'); ?>"
          class="button button-primary"><?php _e('Open Custom Text Utility', 'heritagepress'); ?></a>
      </div>
    </div>

    <!-- Orphaned Data Cleaner -->
    <div class="tool-card card">
      <div class="tool-header">
        <span class="dashicons dashicons-trash"></span>
        <h3><?php _e('Orphaned Data Cleaner', 'heritagepress'); ?></h3>
      </div>
      <div class="tool-body">
        <p><?php _e('Find and clean up orphaned data that is no longer connected to any people or families.', 'heritagepress'); ?></p>
        <div class="tool-options">
          <label><?php _e('Select Data Types:', 'heritagepress'); ?></label>
          <div class="checkbox-group">
            <label><input type="checkbox" name="orphan-types[]" value="media" checked> <?php _e('Media', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="orphan-types[]" value="notes" checked> <?php _e('Notes', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="orphan-types[]" value="events" checked> <?php _e('Events', 'heritagepress'); ?></label>
            <label><input type="checkbox" name="orphan-types[]" value="sources" checked> <?php _e('Sources', 'heritagepress'); ?></label>
          </div>
        </div>
        <div class="tool-options">
          <label>
            <input type="checkbox" id="preview-orphans" checked>
            <?php _e('Preview before deletion', 'heritagepress'); ?>
          </label>
        </div>
      </div>
      <div class="tool-footer">
        <button type="button" id="find-orphans" class="button button-primary"><?php _e('Find Orphaned Data', 'heritagepress'); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Results Modal -->
<div id="tools-results-modal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="modal-title"><?php _e('Tool Results', 'heritagepress'); ?></h3>
    <div id="modal-results"></div>
    <div class="modal-actions">
      <button type="button" id="modal-apply" class="button button-primary"><?php _e('Apply Changes', 'heritagepress'); ?></button>
      <button type="button" id="modal-download" class="button"><?php _e('Download Results', 'heritagepress'); ?></button>
      <button type="button" id="modal-close" class="button"><?php _e('Close', 'heritagepress'); ?></button>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    let toolsNonce = '<?php echo esc_js($tools_nonce); ?>';

    // Export Tool
    $('#start-export').on('click', function() {
      let format = $('#export-format').val();
      let tree = $('#export-tree').val();

      // Show loading
      $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Preparing Export...', 'heritagepress'); ?>');

      // In a real implementation, this would be an AJAX call
      // For the placeholder, we just simulate success
      setTimeout(function() {
        $('#start-export').prop('disabled', false).text('<?php esc_html_e('Start Export', 'heritagepress'); ?>');

        // Simulate download initiation
        const downloadUrl = '<?php echo admin_url('admin-ajax.php?action=hp_download_export&format='); ?>' + format;
        window.location = downloadUrl + '&tree=' + tree + '&_wpnonce=' + toolsNonce;
      }, 2000);
    });

    // Date Fix Tool
    $('#start-date-fix').on('click', function() {
      let tree = $('#date-fix-tree').val();
      let preview = $('#date-fix-preview').prop('checked');

      // Show loading
      $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Analyzing Dates...', 'heritagepress'); ?>');

      // In a real implementation, this would be an AJAX call
      // For the placeholder, we simulate results
      setTimeout(function() {
        $('#start-date-fix').prop('disabled', false).text('<?php esc_html_e('Analyze Dates', 'heritagepress'); ?>');

        // Simulate results
        let results = `
        <div class="date-fix-results">
          <p><?php esc_html_e('Analysis complete. Found 27 date issues that can be standardized.', 'heritagepress'); ?></p>

          <table class="widefat stripe">
            <thead>
              <tr>
                <th><?php esc_html_e('Person', 'heritagepress'); ?></th>
                <th><?php esc_html_e('Event', 'heritagepress'); ?></th>
                <th><?php esc_html_e('Current Date', 'heritagepress'); ?></th>
                <th><?php esc_html_e('Standardized Date', 'heritagepress'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Smith, John</td>
                <td><?php esc_html_e('Birth', 'heritagepress'); ?></td>
                <td>12-25-1880</td>
                <td>25 Dec 1880</td>
              </tr>
              <tr>
                <td>Johnson, Mary</td>
                <td><?php esc_html_e('Death', 'heritagepress'); ?></td>
                <td>January 3 1952</td>
                <td>3 Jan 1952</td>
              </tr>
              <tr>
                <td>Williams, Robert</td>
                <td><?php esc_html_e('Marriage', 'heritagepress'); ?></td>
                <td>abt 1905</td>
                <td>ABT 1905</td>
              </tr>
            </tbody>
          </table>
        </div>
      `;

        showResultsModal('<?php esc_html_e('Date Standardization Results', 'heritagepress'); ?>', results, preview);
      }, 2000);
    });

    // Duplicate Finder
    $('#find-duplicates').on('click', function() {
      let strictness = $('#duplicate-strictness').val();
      let tree = $('#duplicate-tree').val();

      // Show loading
      $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Searching for Duplicates...', 'heritagepress'); ?>');

      // In a real implementation, this would be an AJAX call
      // For the placeholder, we simulate results
      setTimeout(function() {
        $('#find-duplicates').prop('disabled', false).text('<?php esc_html_e('Find Duplicates', 'heritagepress'); ?>');

        // Simulate results
        let results = `
        <div class="duplicate-results">
          <p><?php esc_html_e('Found 5 potential duplicate sets.', 'heritagepress'); ?></p>

          <div class="duplicate-set">
            <h4><?php esc_html_e('Duplicate Set 1', 'heritagepress'); ?> - <span class="match-score high">92% <?php esc_html_e('Match', 'heritagepress'); ?></span></h4>
            <div class="duplicate-people">
              <div class="duplicate-person">
                <strong>Smith, John William (1880-1952)</strong><br>
                <?php esc_html_e('Tree', 'heritagepress'); ?>: Family Tree 1<br>
                <?php esc_html_e('Birth', 'heritagepress'); ?>: 15 Mar 1880, London, England<br>
                <?php esc_html_e('Parents', 'heritagepress'); ?>: William Smith, Mary Jones
              </div>
              <div class="duplicate-person">
                <strong>Smith, John W (1880-1952)</strong><br>
                <?php esc_html_e('Tree', 'heritagepress'); ?>: Family Tree 2<br>
                <?php esc_html_e('Birth', 'heritagepress'); ?>: 15 March 1880, London<br>
                <?php esc_html_e('Parents', 'heritagepress'); ?>: William Smith, Mary Jones
              </div>
            </div>
            <div class="duplicate-actions">
              <label>
                <input type="checkbox" class="merge-check" name="merge[]" value="1" checked>
                <?php esc_html_e('Merge these records', 'heritagepress'); ?>
              </label>
            </div>
          </div>

          <div class="duplicate-set">
            <h4><?php esc_html_e('Duplicate Set 2', 'heritagepress'); ?> - <span class="match-score medium">78% <?php esc_html_e('Match', 'heritagepress'); ?></span></h4>
            <div class="duplicate-people">
              <div class="duplicate-person">
                <strong>Johnson, Elizabeth (1902-1985)</strong><br>
                <?php esc_html_e('Tree', 'heritagepress'); ?>: Family Tree 1<br>
                <?php esc_html_e('Birth', 'heritagepress'); ?>: 22 Nov 1902, Chicago, IL<br>
                <?php esc_html_e('Parents', 'heritagepress'); ?>: Robert Johnson, Sarah Miller
              </div>
              <div class="duplicate-person">
                <strong>Johnson, Beth (1902-1985)</strong><br>
                <?php esc_html_e('Tree', 'heritagepress'); ?>: Family Tree 3<br>
                <?php esc_html_e('Birth', 'heritagepress'); ?>: 22 November 1902, Chicago<br>
                <?php esc_html_e('Parents', 'heritagepress'); ?>: R. Johnson, S. Miller
              </div>
            </div>
            <div class="duplicate-actions">
              <label>
                <input type="checkbox" class="merge-check" name="merge[]" value="2" checked>
                <?php esc_html_e('Merge these records', 'heritagepress'); ?>
              </label>
            </div>
          </div>
        </div>
      `;

        showResultsModal('<?php esc_html_e('Duplicate Person Results', 'heritagepress'); ?>', results, true);
      }, 2500);
    });

    // Geocoder Tool
    $('#start-geocoding').on('click', function() {
      let service = $('#geocoding-service').val();
      let missingOnly = $('#geocode-missing-only').prop('checked');

      // Show loading
      $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Geocoding Places...', 'heritagepress'); ?>');

      // In a real implementation, this would be an AJAX call
      // For the placeholder, we simulate results
      setTimeout(function() {
        $('#start-geocoding').prop('disabled', false).text('<?php esc_html_e('Start Geocoding', 'heritagepress'); ?>');

        // Simulate results
        let results = `
        <div class="geocode-results">
          <p><?php esc_html_e('Geocoding completed for 124 places.', 'heritagepress'); ?></p>

          <div class="geocode-stats">
            <div class="stat">
              <span class="stat-value">118</span>
              <span class="stat-label"><?php esc_html_e('Successfully Geocoded', 'heritagepress'); ?></span>
            </div>
            <div class="stat">
              <span class="stat-value">6</span>
              <span class="stat-label"><?php esc_html_e('Failed to Geocode', 'heritagepress'); ?></span>
            </div>
            <div class="stat">
              <span class="stat-value">95%</span>
              <span class="stat-label"><?php esc_html_e('Success Rate', 'heritagepress'); ?></span>
            </div>
          </div>

          <h4><?php esc_html_e('Places with Issues', 'heritagepress'); ?></h4>
          <table class="widefat stripe">
            <thead>
              <tr>
                <th><?php esc_html_e('Place Name', 'heritagepress'); ?></th>
                <th><?php esc_html_e('Issue', 'heritagepress'); ?></th>
                <th><?php esc_html_e('Suggestion', 'heritagepress'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>New Amsterdam, NY, USA</td>
                <td><?php esc_html_e('Historic name', 'heritagepress'); ?></td>
                <td><?php esc_html_e('Try "New York, NY, USA"', 'heritagepress'); ?></td>
              </tr>
              <tr>
                <td>Petersville, Lancashire, England</td>
                <td><?php esc_html_e('Place not found', 'heritagepress'); ?></td>
                <td><?php esc_html_e('Check spelling or use nearby city', 'heritagepress'); ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      `;

        showResultsModal('<?php esc_html_e('Geocoding Results', 'heritagepress'); ?>', results, false);
      }, 3000);
    });

    // Privacy Tool
    $('#run-privacy-tool').on('click', function() {
      let action = $('#privacy-action').val();
      let yearThreshold = $('#living-year-threshold').val();

      // Show loading
      $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Processing...', 'heritagepress'); ?>');

      // In a real implementation, this would be an AJAX call
      // For the placeholder, we simulate results
      setTimeout(function() {
        $('#run-privacy-tool').prop('disabled', false).text('<?php esc_html_e('Run Privacy Check', 'heritagepress'); ?>');

        // Simulate results based on action
        let results = '';

        if (action === 'mark-living') {
          results = `
          <div class="privacy-results">
            <p><?php esc_html_e('Privacy analysis found 42 people likely to be living.', 'heritagepress'); ?></p>

            <div class="privacy-summary">
              <ul>
                <li><?php esc_html_e('Born after', 'heritagepress'); ?> ${yearThreshold}: 37 <?php esc_html_e('people', 'heritagepress'); ?></li>
                <li><?php esc_html_e('No death date and under 90 years old', 'heritagepress'); ?>: 5 <?php esc_html_e('additional people', 'heritagepress'); ?></li>
              </ul>
            </div>

            <table class="widefat stripe">
              <thead>
                <tr>
                  <th><?php esc_html_e('Person', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Birth Date', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Reason', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Mark as Living', 'heritagepress'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Smith, John</td>
                  <td>1950</td>
                  <td><?php esc_html_e('Born after threshold year', 'heritagepress'); ?></td>
                  <td><input type="checkbox" checked></td>
                </tr>
                <tr>
                  <td>Johnson, Mary</td>
                  <td>1945</td>
                  <td><?php esc_html_e('No death date, under 90', 'heritagepress'); ?></td>
                  <td><input type="checkbox" checked></td>
                </tr>
              </tbody>
            </table>
          </div>
        `;
        } else if (action === 'privacy-sensitive') {
          results = `
          <div class="privacy-results">
            <p><?php esc_html_e('Found 18 records with potentially sensitive data.', 'heritagepress'); ?></p>

            <table class="widefat stripe">
              <thead>
                <tr>
                  <th><?php esc_html_e('Record Type', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Description', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Sensitivity', 'heritagepress'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><?php esc_html_e('Medical Event', 'heritagepress'); ?></td>
                  <td><?php esc_html_e('Contains medical diagnoses for living people', 'heritagepress'); ?></td>
                  <td><span class="high-sensitivity"><?php esc_html_e('High', 'heritagepress'); ?></span></td>
                </tr>
                <tr>
                  <td><?php esc_html_e('Adoption', 'heritagepress'); ?></td>
                  <td><?php esc_html_e('Contains adoption records for people born after 1950', 'heritagepress'); ?></td>
                  <td><span class="high-sensitivity"><?php esc_html_e('High', 'heritagepress'); ?></span></td>
                </tr>
                <tr>
                  <td><?php esc_html_e('Address', 'heritagepress'); ?></td>
                  <td><?php esc_html_e('Contains current addresses for living people', 'heritagepress'); ?></td>
                  <td><span class="medium-sensitivity"><?php esc_html_e('Medium', 'heritagepress'); ?></span></td>
                </tr>
              </tbody>
            </table>
          </div>
        `;
        } else {
          results = `
          <div class="privacy-results">
            <p><?php esc_html_e('Ready to anonymize data for 42 living individuals.', 'heritagepress'); ?></p>

            <div class="anonymize-options">
              <h4><?php esc_html_e('Anonymization Options', 'heritagepress'); ?></h4>
              <label><input type="checkbox" checked> <?php esc_html_e('Replace full dates with year only', 'heritagepress'); ?></label><br>
              <label><input type="checkbox" checked> <?php esc_html_e('Hide addresses and locations', 'heritagepress'); ?></label><br>
              <label><input type="checkbox" checked> <?php esc_html_e('Replace names with "Living"', 'heritagepress'); ?></label><br>
              <label><input type="checkbox"> <?php esc_html_e('Hide relationships to other individuals', 'heritagepress'); ?></label>
            </div>

            <div class="anonymize-preview">
              <h4><?php esc_html_e('Preview (Before/After)', 'heritagepress'); ?></h4>
              <div class="comparison">
                <div class="before">
                  <h5><?php esc_html_e('Before', 'heritagepress'); ?></h5>
                  <p><strong>Smith, John William</strong></p>
                  <p><?php esc_html_e('Born', 'heritagepress'); ?>: 15 Mar 1950, Chicago, IL</p>
                  <p><?php esc_html_e('Address', 'heritagepress'); ?>: 123 Main St, Springfield, IL</p>
                </div>
                <div class="after">
                  <h5><?php esc_html_e('After', 'heritagepress'); ?></h5>
                  <p><strong>Living</strong></p>
                  <p><?php esc_html_e('Born', 'heritagepress'); ?>: 1950</p>
                  <p><?php esc_html_e('Address', 'heritagepress'); ?>: [Private]</p>
                </div>
              </div>
            </div>
          </div>
        `;
        }

        showResultsModal('<?php esc_html_e('Privacy Analysis Results', 'heritagepress'); ?>', results, true);
      }, 2000);
    });

    // Find Orphaned Data
    $('#find-orphans').on('click', function() {
      let selectedTypes = [];
      $('input[name="orphan-types[]"]:checked').each(function() {
        selectedTypes.push($(this).val());
      });

      let preview = $('#preview-orphans').prop('checked');

      // Show loading
      $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Searching...', 'heritagepress'); ?>');

      // In a real implementation, this would be an AJAX call
      // For the placeholder, we simulate results
      setTimeout(function() {
        $('#find-orphans').prop('disabled', false).text('<?php esc_html_e('Find Orphaned Data', 'heritagepress'); ?>');

        // Simulate results
        let results = `
        <div class="orphan-results">
          <p><?php esc_html_e('Found 34 orphaned records that can be safely removed.', 'heritagepress'); ?></p>

          <div class="orphan-summary">
            <div class="stat">
              <span class="stat-value">12</span>
              <span class="stat-label"><?php esc_html_e('Media Files', 'heritagepress'); ?></span>
            </div>
            <div class="stat">
              <span class="stat-value">8</span>
              <span class="stat-label"><?php esc_html_e('Notes', 'heritagepress'); ?></span>
            </div>
            <div class="stat">
              <span class="stat-value">9</span>
              <span class="stat-label"><?php esc_html_e('Events', 'heritagepress'); ?></span>
            </div>
            <div class="stat">
              <span class="stat-value">5</span>
              <span class="stat-label"><?php esc_html_e('Sources', 'heritagepress'); ?></span>
            </div>
          </div>

          <div class="orphan-details">
            <h4><?php esc_html_e('Media Files', 'heritagepress'); ?> (12)</h4>
            <table class="widefat">
              <thead>
                <tr>
                  <th><?php esc_html_e('File', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Original Path', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Delete', 'heritagepress'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>unknown_photo_1.jpg</td>
                  <td>/uploads/2023/05/unknown_photo_1.jpg</td>
                  <td><input type="checkbox" checked></td>
                </tr>
                <tr>
                  <td>document_scan_3.pdf</td>
                  <td>/uploads/2023/02/document_scan_3.pdf</td>
                  <td><input type="checkbox" checked></td>
                </tr>
              </tbody>
            </table>

            <h4><?php esc_html_e('Notes', 'heritagepress'); ?> (8)</h4>
            <table class="widefat">
              <thead>
                <tr>
                  <th><?php esc_html_e('Note ID', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Note Text', 'heritagepress'); ?></th>
                  <th><?php esc_html_e('Delete', 'heritagepress'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>N123</td>
                  <td><?php esc_html_e('Research note about family origins...', 'heritagepress'); ?></td>
                  <td><input type="checkbox" checked></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      `;

        showResultsModal('<?php esc_html_e('Orphaned Data Results', 'heritagepress'); ?>', results, preview);
      }, 2500);
    });

    // Close Modal
    $('.close, #modal-close').on('click', function() {
      $('#tools-results-modal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function(event) {
      if ($(event.target).hasClass('modal')) {
        $('.modal').hide();
      }
    });

    // Helper function to show results modal
    function showResultsModal(title, content, showApply) {
      $('#modal-title').text(title);
      $('#modal-results').html(content);

      // Show or hide apply button based on preview mode
      if (showApply) {
        $('#modal-apply').show();
      } else {
        $('#modal-apply').hide();
      }

      // Show modal
      $('#tools-results-modal').show();

      // Set up apply button action
      $('#modal-apply').off('click').on('click', function() {
        $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Applying Changes...', 'heritagepress'); ?>');

        // Simulate applying changes
        setTimeout(function() {
          $('#modal-apply').prop('disabled', false).text('<?php esc_html_e('Apply Changes', 'heritagepress'); ?>');
          $('#modal-results').html('<div class="notice notice-success"><p><?php esc_html_e('Changes have been successfully applied.', 'heritagepress'); ?></p></div>');

          // Hide apply button after changes applied
          $('#modal-apply').hide();
        }, 2000);
      });

      // Set up download button action
      $('#modal-download').off('click').on('click', function() {
        // Simulate download initiation
        const downloadUrl = '<?php echo admin_url('admin-ajax.php?action=hp_download_report&type='); ?>' + title.toLowerCase().replace(/\s+/g, '-');
        window.location = downloadUrl + '&_wpnonce=' + toolsNonce;
      });
    }
  });
</script>

<style>
  .heritagepress-tools-section {
    margin: 20px 0;
  }

  .tools-instructions {
    margin-bottom: 20px;
  }

  .tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
  }

  .tool-card {
    background: #fff;
    border: 1px solid #e5e5e5;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
  }

  .tool-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
  }

  .tool-header .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    margin-right: 10px;
    color: #0073aa;
  }

  .tool-header h3 {
    margin: 0;
  }

  .tool-body {
    margin-bottom: 20px;
  }

  .tool-options {
    margin: 15px 0;
  }

  .tool-footer {
    text-align: right;
  }

  /* Modal Styles */
  .modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
  }

  .modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 70%;
    max-width: 1000px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
    border-radius: 5px;
  }

  #modal-results {
    margin: 20px 0;
    max-height: 60vh;
    overflow-y: auto;
  }

  .close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .close:hover,
  .close:focus {
    color: black;
    text-decoration: none;
  }

  .modal-actions {
    margin-top: 20px;
    text-align: right;
    border-top: 1px solid #eee;
    padding-top: 15px;
  }

  /* Results Styling */
  .geocode-stats,
  .orphan-summary {
    display: flex;
    justify-content: space-around;
    margin: 20px 0;
    text-align: center;
  }

  .stat {
    padding: 10px 15px;
    background: #f9f9f9;
    border-radius: 4px;
    min-width: 100px;
  }

  .stat-value {
    font-size: 24px;
    font-weight: bold;
    display: block;
    color: #0073aa;
  }

  .stat-label {
    font-size: 13px;
    color: #666;
  }

  .duplicate-set {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #eee;
    border-left-width: 5px;
    border-left-color: #0073aa;
    background: #f9f9f9;
  }

  .duplicate-people {
    display: flex;
    gap: 20px;
    margin: 15px 0;
  }

  .duplicate-person {
    flex: 1;
    background: #fff;
    padding: 15px;
    border: 1px solid #eee;
  }

  .match-score {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: normal;
  }

  .match-score.high {
    background-color: #dff0d8;
    color: #3c763d;
  }

  .match-score.medium {
    background-color: #fcf8e3;
    color: #8a6d3b;
  }

  .match-score.low {
    background-color: #f2dede;
    color: #a94442;
  }

  .high-sensitivity {
    color: #a94442;
    font-weight: bold;
  }

  .medium-sensitivity {
    color: #8a6d3b;
    font-weight: bold;
  }

  .comparison {
    display: flex;
    gap: 30px;
  }

  .before,
  .after {
    flex: 1;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #eee;
  }

  .before h5,
  .after h5 {
    margin-top: 0;
  }

  .checkbox-group {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 5px;
    margin-top: 8px;
  }
</style>
