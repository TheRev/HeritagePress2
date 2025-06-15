<?php

/**
 * Advanced Search People Tab
 * Enhanced search interface with multiple criteria
 */

if (!defined('ABSPATH')) {
  exit;
}

// Include date utilities for enhanced date searching
require_once __DIR__ . '/../../class-hp-date-utils.php';

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Get search parameters
$search_params = array(
  'firstname' => isset($_GET['firstname']) ? sanitize_text_field($_GET['firstname']) : '',
  'lastname' => isset($_GET['lastname']) ? sanitize_text_field($_GET['lastname']) : '',
  'personID' => isset($_GET['personID']) ? sanitize_text_field($_GET['personID']) : '',
  'tree' => isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '',
  'birthdate_from' => isset($_GET['birthdate_from']) ? sanitize_text_field($_GET['birthdate_from']) : '',
  'birthdate_to' => isset($_GET['birthdate_to']) ? sanitize_text_field($_GET['birthdate_to']) : '',
  'birthplace' => isset($_GET['birthplace']) ? sanitize_text_field($_GET['birthplace']) : '',
  'deathdate_from' => isset($_GET['deathdate_from']) ? sanitize_text_field($_GET['deathdate_from']) : '',
  'deathdate_to' => isset($_GET['deathdate_to']) ? sanitize_text_field($_GET['deathdate_to']) : '',
  'deathplace' => isset($_GET['deathplace']) ? sanitize_text_field($_GET['deathplace']) : '',
  'sex' => isset($_GET['sex']) ? sanitize_text_field($_GET['sex']) : '',
  'living' => isset($_GET['living']) ? sanitize_text_field($_GET['living']) : '',
  'private' => isset($_GET['private']) ? sanitize_text_field($_GET['private']) : '',
  'nickname' => isset($_GET['nickname']) ? sanitize_text_field($_GET['nickname']) : '',
  'notes' => isset($_GET['notes']) ? sanitize_text_field($_GET['notes']) : ''
);

$has_search = !empty(array_filter($search_params));
$search_results = array();
$total_found = 0;

// Perform search if parameters provided
if ($has_search) {
  $people_table = $wpdb->prefix . 'hp_people';
  $trees_table = $wpdb->prefix . 'hp_trees';

  $where_conditions = array("$people_table.gedcom = $trees_table.gedcom");

  if (!empty($search_params['firstname'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.firstname LIKE %s", '%' . $wpdb->esc_like($search_params['firstname']) . '%');
  }

  if (!empty($search_params['lastname'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.lastname LIKE %s", '%' . $wpdb->esc_like($search_params['lastname']) . '%');
  }

  if (!empty($search_params['personID'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.personID LIKE %s", '%' . $wpdb->esc_like($search_params['personID']) . '%');
  }

  if (!empty($search_params['tree'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.gedcom = %s", $search_params['tree']);
  }

  if (!empty($search_params['birthdate_from'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.birthdatetr >= %s", $search_params['birthdate_from']);
  }

  if (!empty($search_params['birthdate_to'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.birthdatetr <= %s", $search_params['birthdate_to']);
  }

  if (!empty($search_params['birthplace'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.birthplace LIKE %s", '%' . $wpdb->esc_like($search_params['birthplace']) . '%');
  }

  if (!empty($search_params['deathdate_from'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.deathdatetr >= %s", $search_params['deathdate_from']);
  }

  if (!empty($search_params['deathdate_to'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.deathdatetr <= %s", $search_params['deathdate_to']);
  }

  if (!empty($search_params['deathplace'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.deathplace LIKE %s", '%' . $wpdb->esc_like($search_params['deathplace']) . '%');
  }

  if (!empty($search_params['sex'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.sex = %s", $search_params['sex']);
  }

  if (!empty($search_params['living'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.living = %s", $search_params['living']);
  }

  if (!empty($search_params['private'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.private = %s", $search_params['private']);
  }

  if (!empty($search_params['nickname'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.nickname LIKE %s", '%' . $wpdb->esc_like($search_params['nickname']) . '%');
  }

  if (!empty($search_params['notes'])) {
    $where_conditions[] = $wpdb->prepare("$people_table.notes LIKE %s", '%' . $wpdb->esc_like($search_params['notes']) . '%');
  }

  $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
  // Get search results - use sortable date fields for better chronological ordering
  $search_query = "SELECT $people_table.*, $trees_table.treename
                   FROM $people_table
                   INNER JOIN $trees_table ON $people_table.gedcom = $trees_table.gedcom
                   $where_clause
                   ORDER BY $people_table.birthdatetr, $people_table.lastname, $people_table.firstname
                   LIMIT 100";

  $search_results = $wpdb->get_results($search_query, ARRAY_A);
  $total_found = count($search_results);

  // Get total count (for pagination if needed)
  $count_query = "SELECT COUNT(*) FROM $people_table INNER JOIN $trees_table ON $people_table.gedcom = $trees_table.gedcom $where_clause";
  $total_found = $wpdb->get_var($count_query);
}
?>

<div class="search-people-section">
  <div class="search-form-card">
    <form method="get" id="advanced-search-form" class="advanced-search-form">
      <input type="hidden" name="page" value="heritagepress-people">
      <input type="hidden" name="tab" value="search">

      <div class="form-header">
        <h3><?php _e('Advanced People Search', 'heritagepress'); ?></h3>
        <p class="description"><?php _e('Enter search criteria in any combination. All fields are optional.', 'heritagepress'); ?></p>
      </div>

      <!-- Basic Information -->
      <div class="search-section">
        <h4><?php _e('Basic Information', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field">
            <label for="firstname"><?php _e('First Name:', 'heritagepress'); ?></label>
            <input type="text" id="firstname" name="firstname" value="<?php echo esc_attr($search_params['firstname']); ?>" placeholder="<?php _e('Partial names OK', 'heritagepress'); ?>">
          </div>
          <div class="form-field">
            <label for="lastname"><?php _e('Last Name:', 'heritagepress'); ?></label>
            <input type="text" id="lastname" name="lastname" value="<?php echo esc_attr($search_params['lastname']); ?>" placeholder="<?php _e('Partial names OK', 'heritagepress'); ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="personID"><?php _e('Person ID:', 'heritagepress'); ?></label>
            <input type="text" id="personID" name="personID" value="<?php echo esc_attr($search_params['personID']); ?>" placeholder="<?php _e('Exact or partial ID', 'heritagepress'); ?>">
          </div>
          <div class="form-field">
            <label for="tree"><?php _e('Tree:', 'heritagepress'); ?></label>
            <select id="tree" name="tree">
              <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
              <?php foreach ($trees_result as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>" <?php selected($search_params['tree'], $tree['gedcom']); ?>>
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="nickname"><?php _e('Nickname:', 'heritagepress'); ?></label>
            <input type="text" id="nickname" name="nickname" value="<?php echo esc_attr($search_params['nickname']); ?>">
          </div>
          <div class="form-field">
            <label for="sex"><?php _e('Gender:', 'heritagepress'); ?></label>
            <select id="sex" name="sex">
              <option value=""><?php _e('Any', 'heritagepress'); ?></option>
              <option value="M" <?php selected($search_params['sex'], 'M'); ?>><?php _e('Male', 'heritagepress'); ?></option>
              <option value="F" <?php selected($search_params['sex'], 'F'); ?>><?php _e('Female', 'heritagepress'); ?></option>
            </select>
          </div>
        </div>
      </div>

      <!-- Birth Information -->
      <div class="search-section">
        <h4><?php _e('Birth Information', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field">
            <label for="birthdate_from"><?php _e('Birth Date From:', 'heritagepress'); ?></label>
            <input type="date" id="birthdate_from" name="birthdate_from" value="<?php echo esc_attr($search_params['birthdate_from']); ?>">
          </div>
          <div class="form-field">
            <label for="birthdate_to"><?php _e('Birth Date To:', 'heritagepress'); ?></label>
            <input type="date" id="birthdate_to" name="birthdate_to" value="<?php echo esc_attr($search_params['birthdate_to']); ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field full-width">
            <label for="birthplace"><?php _e('Birth Place:', 'heritagepress'); ?></label>
            <input type="text" id="birthplace" name="birthplace" value="<?php echo esc_attr($search_params['birthplace']); ?>" placeholder="<?php _e('City, State, Country (partial OK)', 'heritagepress'); ?>">
          </div>
        </div>
      </div>

      <!-- Death Information -->
      <div class="search-section">
        <h4><?php _e('Death Information', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field">
            <label for="deathdate_from"><?php _e('Death Date From:', 'heritagepress'); ?></label>
            <input type="date" id="deathdate_from" name="deathdate_from" value="<?php echo esc_attr($search_params['deathdate_from']); ?>">
          </div>
          <div class="form-field">
            <label for="deathdate_to"><?php _e('Death Date To:', 'heritagepress'); ?></label>
            <input type="date" id="deathdate_to" name="deathdate_to" value="<?php echo esc_attr($search_params['deathdate_to']); ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field full-width">
            <label for="deathplace"><?php _e('Death Place:', 'heritagepress'); ?></label>
            <input type="text" id="deathplace" name="deathplace" value="<?php echo esc_attr($search_params['deathplace']); ?>" placeholder="<?php _e('City, State, Country (partial OK)', 'heritagepress'); ?>">
          </div>
        </div>
      </div>

      <!-- Status and Other -->
      <div class="search-section">
        <h4><?php _e('Status & Additional', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field">
            <label for="living"><?php _e('Living Status:', 'heritagepress'); ?></label>
            <select id="living" name="living">
              <option value=""><?php _e('Any', 'heritagepress'); ?></option>
              <option value="1" <?php selected($search_params['living'], '1'); ?>><?php _e('Living', 'heritagepress'); ?></option>
              <option value="0" <?php selected($search_params['living'], '0'); ?>><?php _e('Deceased', 'heritagepress'); ?></option>
            </select>
          </div>
          <div class="form-field">
            <label for="private"><?php _e('Privacy Status:', 'heritagepress'); ?></label>
            <select id="private" name="private">
              <option value=""><?php _e('Any', 'heritagepress'); ?></option>
              <option value="1" <?php selected($search_params['private'], '1'); ?>><?php _e('Private', 'heritagepress'); ?></option>
              <option value="0" <?php selected($search_params['private'], '0'); ?>><?php _e('Public', 'heritagepress'); ?></option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field full-width">
            <label for="notes"><?php _e('Notes Contain:', 'heritagepress'); ?></label>
            <input type="text" id="notes" name="notes" value="<?php echo esc_attr($search_params['notes']); ?>" placeholder="<?php _e('Search within notes', 'heritagepress'); ?>">
          </div>
        </div>
      </div>

      <!-- Search Actions -->
      <div class="form-actions">
        <button type="submit" class="button button-primary button-large"><?php _e('Search People', 'heritagepress'); ?></button>
        <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=search'); ?>" class="button button-secondary"><?php _e('Clear All', 'heritagepress'); ?></a>

        <div class="search-actions-secondary">
          <button type="button" id="save-search" class="button"><?php _e('Save Search', 'heritagepress'); ?></button>
          <button type="button" id="load-search" class="button"><?php _e('Load Saved Search', 'heritagepress'); ?></button>
        </div>
      </div>
    </form>
  </div>

  <!-- Search Results -->
  <?php if ($has_search): ?>
    <div class="search-results-section">
      <div class="results-header">
        <h3><?php printf(__('Search Results (%d found)', 'heritagepress'), $total_found); ?></h3>

        <?php if ($total_found > 0): ?>
          <div class="results-actions">
            <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=browse&' . http_build_query($search_params)); ?>" class="button"><?php _e('View in Browse Tab', 'heritagepress'); ?></a>
            <button type="button" id="export-results" class="button"><?php _e('Export Results', 'heritagepress'); ?></button>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($total_found > 0): ?>
        <div class="results-table-container">
          <table class="wp-list-table widefat fixed striped search-results-table">
            <thead>
              <tr>
                <th><?php _e('Person ID', 'heritagepress'); ?></th>
                <th><?php _e('Name', 'heritagepress'); ?></th>
                <th><?php _e('Birth', 'heritagepress'); ?></th>
                <th><?php _e('Death', 'heritagepress'); ?></th>
                <th><?php _e('Tree', 'heritagepress'); ?></th>
                <th><?php _e('Actions', 'heritagepress'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($search_results as $person): ?>
                <tr>
                  <td>
                    <strong><?php echo esc_html($person['personID']); ?></strong>
                  </td>
                  <td>
                    <?php
                    $name_parts = array();
                    if (!empty($person['prefix'])) $name_parts[] = $person['prefix'];
                    if (!empty($person['firstname'])) $name_parts[] = $person['firstname'];
                    if (!empty($person['lnprefix'])) $name_parts[] = $person['lnprefix'];
                    if (!empty($person['lastname'])) $name_parts[] = '<strong>' . $person['lastname'] . '</strong>';
                    if (!empty($person['suffix'])) $name_parts[] = $person['suffix'];

                    echo implode(' ', $name_parts);

                    if (!empty($person['nickname'])) {
                      echo ' <em>"' . esc_html($person['nickname']) . '"</em>';
                    }
                    ?>

                    <div class="person-status">
                      <?php if ($person['living'] == 1): ?>
                        <span class="status-living"><?php _e('Living', 'heritagepress'); ?></span>
                      <?php endif; ?>
                      <?php if ($person['private'] == 1): ?>
                        <span class="status-private"><?php _e('Private', 'heritagepress'); ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <?php
                    $birth_display = HP_Date_Utils::format_display_date($person, 'birth');
                    if (!empty($birth_display)):
                    ?>
                      <strong><?php echo $birth_display; ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($person['birthplace'])): ?>
                      <br><small><?php echo esc_html($person['birthplace']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                    $death_display = HP_Date_Utils::format_display_date($person, 'death');
                    if (!empty($death_display)):
                    ?>
                      <strong><?php echo $death_display; ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($person['deathplace'])): ?>
                      <br><small><?php echo esc_html($person['deathplace']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php echo esc_html($person['treename']); ?>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=edit&personID=' . urlencode($person['personID']) . '&tree=' . urlencode($person['gedcom'])); ?>" class="button button-small" title="<?php _e('Edit Person', 'heritagepress'); ?>">
                        <span class="dashicons dashicons-edit"></span>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($total_found >= 100): ?>
          <div class="results-notice">
            <p><strong><?php _e('Note:', 'heritagepress'); ?></strong> <?php _e('Only the first 100 results are shown. Please refine your search for more specific results.', 'heritagepress'); ?></p>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="no-results">
          <p><?php _e('No people found matching your search criteria.', 'heritagepress'); ?></p>
          <p><?php _e('Try different search terms or broaden your criteria.', 'heritagepress'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Saved Searches Panel -->
  <div class="saved-searches-panel">
    <h4><?php _e('Saved Searches', 'heritagepress'); ?></h4>
    <div class="saved-searches-list">
      <p><em><?php _e('Saved search functionality will be available in a future update.', 'heritagepress'); ?></em></p>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Form validation
    $('#advanced-search-form').on('submit', function(e) {
      // Check if at least one field is filled
      var hasValue = false;
      $(this).find('input[type="text"], input[type="date"], select').each(function() {
        if ($(this).val().trim() !== '') {
          hasValue = true;
          return false; // break loop
        }
      });

      if (!hasValue) {
        e.preventDefault();
        alert('<?php _e('Please enter at least one search criterion.', 'heritagepress'); ?>');
        return false;
      }
    });

    // Date range validation
    $('#birthdate_from, #birthdate_to').on('change', function() {
      var from = $('#birthdate_from').val();
      var to = $('#birthdate_to').val();

      if (from && to && from > to) {
        alert('<?php _e('Birth date "from" cannot be later than "to" date.', 'heritagepress'); ?>');
        $(this).val('');
      }
    });

    $('#deathdate_from, #deathdate_to').on('change', function() {
      var from = $('#deathdate_from').val();
      var to = $('#deathdate_to').val();

      if (from && to && from > to) {
        alert('<?php _e('Death date "from" cannot be later than "to" date.', 'heritagepress'); ?>');
        $(this).val('');
      }
    });

    // Save search functionality (placeholder)
    $('#save-search').on('click', function() {
      var searchName = prompt('<?php _e('Enter a name for this search:', 'heritagepress'); ?>');
      if (searchName) {
        // TODO: Implement save search functionality
        alert('<?php _e('Search saving functionality will be available in a future update.', 'heritagepress'); ?>');
      }
    });

    // Load search functionality (placeholder)
    $('#load-search').on('click', function() {
      // TODO: Implement load search functionality
      alert('<?php _e('Saved search loading functionality will be available in a future update.', 'heritagepress'); ?>');
    });

    // Export results functionality (placeholder)
    $('#export-results').on('click', function() {
      // TODO: Implement export functionality
      alert('<?php _e('Export functionality will be available in a future update.', 'heritagepress'); ?>');
    });

    // Auto-suggest for places (placeholder for future enhancement)
    $('#birthplace, #deathplace').on('focus', function() {
      // TODO: Implement place auto-complete
    });

    // Quick search presets
    $('.quick-preset').on('click', function(e) {
      e.preventDefault();
      var preset = $(this).data('preset');

      // Clear existing values
      $('#advanced-search-form')[0].reset();

      switch (preset) {
        case 'living':
          $('#living').val('1');
          break;
        case 'recent':
          var thirtyYearsAgo = new Date();
          thirtyYearsAgo.setFullYear(thirtyYearsAgo.getFullYear() - 30);
          $('#birthdate_from').val(thirtyYearsAgo.toISOString().substr(0, 10));
          break;
        case 'no-dates':
          // Search for people with no birth or death dates would need more complex logic
          break;
      }
    });
  });
</script>
