<?php

/**
 * DNA Test Form - Add/Edit
 *
 * Complete replication of TNG admin_new_dna_test.php form functionality
 * Used for both adding new tests and editing existing ones
 */

if (!defined('ABSPATH')) {
  exit;
}

// Determine if we're editing or adding
$is_editing = isset($test_data) && !empty($test_data);
$form_title = $is_editing ? __('Edit DNA Test', 'heritagepress') : __('Add New DNA Test', 'heritagepress');
$form_action = $is_editing ? 'update_dna_test' : 'add_dna_test';

// Default values for new test
if (!$is_editing) {
  $test_data = array(
    'testID' => '',
    'test_type' => '',
    'test_number' => '',
    'notes' => '',
    'vendor' => '',
    'test_date' => '',
    'match_date' => '',
    'personID' => '',
    'gedcom' => '',
    'person_name' => '',
    'urls' => '',
    'mtdna_haplogroup' => '',
    'ydna_haplogroup' => '',
    'significant_snp' => '',
    'terminal_snp' => '',
    'markers' => '',
    'y_results' => '',
    'hvr1_results' => '',
    'hvr2_results' => '',
    'mtdna_confirmed' => '',
    'ydna_confirmed' => '',
    'markeropt' => '',
    'notesopt' => '',
    'linksopt' => '',
    'surnamesopt' => 0,
    'private_dna' => '0',
    'private_test' => '0',
    'dna_group' => '',
    'surnames' => '',
    'GEDmatchID' => ''
  );
}

?>

<div class="dna-test-form-container">
  <div class="form-header">
    <h2><?php echo esc_html($form_title); ?></h2>
    <?php if ($is_editing && isset($_GET['added'])): ?>
      <div class="notice notice-success">
        <p><?php _e('DNA test has been created successfully. You can now edit the details below.', 'heritagepress'); ?></p>
      </div>
    <?php endif; ?>
  </div>

  <form id="dna-test-form" method="post" action="" class="dna-test-form">
    <?php wp_nonce_field('heritagepress_dna_test', '_wpnonce'); ?>
    <input type="hidden" name="action" value="<?php echo esc_attr($form_action); ?>">
    <?php if ($is_editing): ?>
      <input type="hidden" name="testID" value="<?php echo esc_attr($test_data['testID']); ?>">
    <?php endif; ?>

    <!-- Test Information Section -->
    <div class="form-card test-info-card" data-section="test-info">
      <div class="form-card-header">
        <h3 class="form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-analytics"></span>
          <?php _e('Test Information', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="form-card-content">
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="test_type"><?php _e('Test Type', 'heritagepress'); ?> <span class="required">*</span></label>
              </th>
              <td>
                <select name="test_type" id="test_type" required>
                  <option value=""><?php _e('Select Test Type', 'heritagepress'); ?></option>
                  <option value="atDNA" <?php selected($test_data['test_type'], 'atDNA'); ?>><?php _e('Autosomal DNA (atDNA)', 'heritagepress'); ?></option>
                  <option value="Y-DNA" <?php selected($test_data['test_type'], 'Y-DNA'); ?>><?php _e('Y-DNA', 'heritagepress'); ?></option>
                  <option value="mtDNA" <?php selected($test_data['test_type'], 'mtDNA'); ?>><?php _e('Mitochondrial DNA (mtDNA)', 'heritagepress'); ?></option>
                  <option value="X-DNA" <?php selected($test_data['test_type'], 'X-DNA'); ?>><?php _e('X-DNA', 'heritagepress'); ?></option>
                </select>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="test_number"><?php _e('Test Number', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="test_number" id="test_number" value="<?php echo esc_attr($test_data['test_number']); ?>" class="regular-text">
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="vendor"><?php _e('Testing Company/Vendor', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="vendor" id="vendor" value="<?php echo esc_attr($test_data['vendor']); ?>" class="regular-text">
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="test_date"><?php _e('Test Date', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="date" name="test_date" id="test_date" value="<?php echo esc_attr($test_data['test_date'] === '0000-00-00' ? '' : $test_data['test_date']); ?>" class="regular-text">
                <p class="description"><?php _e('Date when the test was taken', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="match_date"><?php _e('Match Date', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="date" name="match_date" id="match_date" value="<?php echo esc_attr($test_data['match_date'] === '0000-00-00' ? '' : $test_data['match_date']); ?>" class="regular-text">
                <p class="description"><?php _e('Date when matches were found/processed', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr id="gedmatch-row" <?php echo ($test_data['test_type'] !== 'atDNA') ? 'style="display:none;"' : ''; ?>>
              <th scope="row">
                <label for="GEDmatchID"><?php _e('GEDmatch ID', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="GEDmatchID" id="GEDmatchID" value="<?php echo esc_attr($test_data['GEDmatchID']); ?>" class="regular-text" maxlength="40">
                <p class="description"><?php _e('GEDmatch kit number (for autosomal DNA tests)', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="private_test"><?php _e('Private Test', 'heritagepress'); ?></label>
              </th>
              <td>
                <select name="private_test" id="private_test">
                  <option value="0" <?php selected($test_data['private_test'], '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
                  <option value="1" <?php selected($test_data['private_test'], '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
                </select>
                <p class="description"><?php _e('Whether this test should be kept private', 'heritagepress'); ?></p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Test Taker Section -->
    <div class="form-card test-taker-card" data-section="test-taker">
      <div class="form-card-header">
        <h3 class="form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-admin-users"></span>
          <?php _e('Test Taker', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="form-card-content">
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="gedcom"><?php _e('Tree', 'heritagepress'); ?></label>
              </th>
              <td>
                <select name="gedcom" id="gedcom">
                  <option value=""><?php _e('Select Tree', 'heritagepress'); ?></option>
                  <?php foreach ($trees_result as $tree): ?>
                    <option value="<?php echo esc_attr($tree['gedcom']); ?>" <?php selected($test_data['gedcom'], $tree['gedcom']); ?>>
                      <?php echo esc_html($tree['treename']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="personID"><?php _e('Person ID', 'heritagepress'); ?></label>
              </th>
              <td>
                <div class="person-id-field">
                  <input type="text" name="personID" id="personID" value="<?php echo esc_attr($test_data['personID']); ?>" class="regular-text" maxlength="22">
                  <button type="button" id="find-person" class="button" title="<?php _e('Find Person', 'heritagepress'); ?>">
                    <span class="dashicons dashicons-search"></span>
                  </button>
                </div>
                <div id="person-info"></div>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="person_name"><?php _e('OR Person Name', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="person_name" id="person_name" value="<?php echo esc_attr($test_data['person_name']); ?>" class="regular-text" maxlength="100">
                <p class="description"><?php _e('Enter name if person is not in database', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="private_dna"><?php _e('Keep Name Private', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="checkbox" name="private_dna" id="private_dna" value="1" <?php checked($test_data['private_dna'], '1'); ?>>
                <label for="private_dna"><?php _e('Keep this person\'s name private', 'heritagepress'); ?></label>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DNA Group Section -->
    <div class="form-card dna-group-card" data-section="dna-group">
      <div class="form-card-header">
        <h3 class="form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-groups"></span>
          <?php _e('DNA Group', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="form-card-content">
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="dna_group"><?php _e('DNA Group', 'heritagepress'); ?></label>
              </th>
              <td>
                <select name="dna_group" id="dna_group">
                  <option value=""><?php _e('No Group', 'heritagepress'); ?></option>
                  <?php foreach ($groups_result as $group): ?>
                    <option value="<?php echo esc_attr($group['dna_group']); ?>"
                      data-test-type="<?php echo esc_attr($group['test_type']); ?>"
                      <?php selected($test_data['dna_group'], $group['dna_group']); ?>>
                      <?php echo esc_html($group['description'] . ' (' . $group['test_type'] . ')'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Optional: Assign this test to a DNA group', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="surnames"><?php _e('Associated Surnames', 'heritagepress'); ?></label>
              </th>
              <td>
                <textarea name="surnames" id="surnames" rows="3" class="large-text"><?php echo esc_textarea($test_data['surnames']); ?></textarea>
                <p class="description"><?php _e('Surnames associated with this DNA test (one per line)', 'heritagepress'); ?></p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DNA Results Section -->
    <div class="form-card dna-results-card" data-section="dna-results">
      <div class="form-card-header">
        <h3 class="form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-chart-line"></span>
          <?php _e('DNA Results', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="form-card-content">
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="ydna_haplogroup"><?php _e('Y-DNA Haplogroup', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="ydna_haplogroup" id="ydna_haplogroup" value="<?php echo esc_attr($test_data['ydna_haplogroup']); ?>" class="regular-text">
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="mtdna_haplogroup"><?php _e('mtDNA Haplogroup', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="mtdna_haplogroup" id="mtdna_haplogroup" value="<?php echo esc_attr($test_data['mtdna_haplogroup']); ?>" class="regular-text">
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="significant_snp"><?php _e('Significant SNP', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="significant_snp" id="significant_snp" value="<?php echo esc_attr($test_data['significant_snp']); ?>" class="regular-text">
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="terminal_snp"><?php _e('Terminal SNP', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="terminal_snp" id="terminal_snp" value="<?php echo esc_attr($test_data['terminal_snp']); ?>" class="regular-text">
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="markers"><?php _e('Markers', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="markers" id="markers" value="<?php echo esc_attr($test_data['markers']); ?>" class="regular-text">
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="y_results"><?php _e('Y-DNA Results', 'heritagepress'); ?></label>
              </th>
              <td>
                <textarea name="y_results" id="y_results" rows="3" class="large-text"><?php echo esc_textarea($test_data['y_results']); ?></textarea>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="hvr1_results"><?php _e('HVR1 Results', 'heritagepress'); ?></label>
              </th>
              <td>
                <textarea name="hvr1_results" id="hvr1_results" rows="2" class="large-text"><?php echo esc_textarea($test_data['hvr1_results']); ?></textarea>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="hvr2_results"><?php _e('HVR2 Results', 'heritagepress'); ?></label>
              </th>
              <td>
                <textarea name="hvr2_results" id="hvr2_results" rows="2" class="large-text"><?php echo esc_textarea($test_data['hvr2_results']); ?></textarea>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Notes and URLs Section -->
    <div class="form-card notes-urls-card" data-section="notes-urls">
      <div class="form-card-header">
        <h3 class="form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-admin-post"></span>
          <?php _e('Notes & URLs', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="form-card-content">
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="notes"><?php _e('Notes', 'heritagepress'); ?></label>
              </th>
              <td>
                <textarea name="notes" id="notes" rows="5" class="large-text"><?php echo esc_textarea($test_data['notes']); ?></textarea>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="urls"><?php _e('Related URLs', 'heritagepress'); ?></label>
              </th>
              <td>
                <textarea name="urls" id="urls" rows="3" class="large-text"><?php echo esc_textarea($test_data['urls']); ?></textarea>
                <p class="description"><?php _e('Enter URLs related to this test (one per line)', 'heritagepress'); ?></p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
      <div class="form-actions-primary">
        <button type="submit" class="button button-primary button-large">
          <span class="dashicons dashicons-yes"></span>
          <?php echo $is_editing ? __('Update Test', 'heritagepress') : __('Save Test', 'heritagepress'); ?>
        </button>

        <button type="button" id="cancel-form" class="button button-large">
          <?php _e('Cancel', 'heritagepress'); ?>
        </button>
      </div>

      <?php if ($is_editing && $allow_delete): ?>
        <div class="form-actions-secondary">
          <button type="button" id="delete-test" class="button button-link-delete">
            <?php _e('Delete Test', 'heritagepress'); ?>
          </button>
        </div>
      <?php endif; ?>
    </div>

  </form>
</div>

<!-- Person Finder Modal -->
<div id="person-finder-modal" class="dna-modal" style="display: none;">
  <div class="dna-modal-content person-finder-content">
    <div class="modal-header">
      <h3><?php _e('Find Person', 'heritagepress'); ?></h3>
      <button type="button" class="modal-close" id="close-person-finder">&times;</button>
    </div>
    <div class="modal-body">
      <div class="person-search">
        <input type="text" id="person-search-input" placeholder="<?php _e('Enter name or Person ID...', 'heritagepress'); ?>" class="regular-text">
        <button type="button" id="search-person" class="button"><?php _e('Search', 'heritagepress'); ?></button>
      </div>
      <div id="person-search-results"></div>
    </div>
  </div>
</div>

<style>
  .dna-test-form-container {
    max-width: 1200px;
  }

  .form-header h2 {
    margin-bottom: 20px;
    font-size: 24px;
  }

  .form-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .form-card-header {
    background: #f6f7f7;
    border-bottom: 1px solid #ccd0d4;
    padding: 12px 20px;
    cursor: pointer;
    user-select: none;
  }

  .form-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .form-card-content {
    padding: 20px;
  }

  .form-card.collapsed .form-card-content {
    display: none;
  }

  .form-card.collapsed .toggle-icon {
    transform: rotate(-90deg);
  }

  .toggle-icon {
    transition: transform 0.2s ease;
  }

  .person-id-field {
    display: flex;
    gap: 5px;
    align-items: center;
  }

  .person-id-field input {
    flex: 1;
  }

  .person-id-field .button {
    padding: 3px 8px;
    height: auto;
    min-height: 30px;
  }

  .form-actions {
    background: #f6f7f7;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .form-actions-primary {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .required {
    color: #d63384;
  }

  .person-finder-content {
    width: 600px;
    max-width: 90vw;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
  }

  .modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .person-search {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
  }

  .person-search input {
    flex: 1;
  }

  #person-search-results {
    max-height: 300px;
    overflow-y: auto;
  }

  .person-result {
    padding: 10px;
    border: 1px solid #ddd;
    margin-bottom: 5px;
    cursor: pointer;
    border-radius: 3px;
  }

  .person-result:hover {
    background: #f0f0f1;
  }

  .person-result.selected {
    background: #0073aa;
    color: #fff;
  }

  @media (max-width: 768px) {
    .form-actions {
      flex-direction: column;
      gap: 15px;
      align-items: stretch;
    }

    .form-actions-primary {
      justify-content: center;
    }
  }
</style>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Card collapse/expand functionality
    $('.form-card-header').on('click', function() {
      var $card = $(this).closest('.form-card');
      var $content = $card.find('.form-card-content');
      var $icon = $(this).find('.toggle-icon');

      $card.toggleClass('collapsed');

      if ($card.hasClass('collapsed')) {
        $content.slideUp(200);
        $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
      } else {
        $content.slideDown(200);
        $icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
      }
    });

    // Test type change handler
    $('#test_type').on('change', function() {
      var testType = $(this).val();
      if (testType === 'atDNA') {
        $('#gedmatch-row').show();
      } else {
        $('#gedmatch-row').hide();
      }

      // Filter DNA groups by test type
      filterDNAGroups(testType);
    });

    // Filter DNA groups based on test type
    function filterDNAGroups(testType) {
      var $dnaGroup = $('#dna_group');
      var currentValue = $dnaGroup.val();

      $dnaGroup.find('option').each(function() {
        var $option = $(this);
        var optionTestType = $option.data('test-type');

        if (!optionTestType || optionTestType === testType || $option.val() === '') {
          $option.show();
        } else {
          $option.hide();
          if ($option.is(':selected')) {
            $dnaGroup.val('');
          }
        }
      });
    }

    // Initialize form
    filterDNAGroups($('#test_type').val());

    // Person finder
    $('#find-person').on('click', function() {
      var tree = $('#gedcom').val();
      if (!tree) {
        alert('<?php _e('Please select a tree first.', 'heritagepress'); ?>');
        return;
      }
      $('#person-finder-modal').show();
    });

    // Close person finder
    $('#close-person-finder').on('click', function() {
      $('#person-finder-modal').hide();
    });

    // Person search
    $('#search-person').on('click', function() {
      searchPersons();
    });

    $('#person-search-input').on('keypress', function(e) {
      if (e.which === 13) {
        searchPersons();
      }
    });

    function searchPersons() {
      var searchTerm = $('#person-search-input').val();
      var tree = $('#gedcom').val();

      if (!searchTerm.trim()) {
        alert('<?php _e('Please enter a search term.', 'heritagepress'); ?>');
        return;
      }

      $.post(ajaxurl, {
        action: 'hp_search_people',
        nonce: '<?php echo wp_create_nonce('hp_dna_test_nonce'); ?>',
        search_term: searchTerm,
        gedcom: tree,
        limit: 20
      }, function(response) {
        if (response.success) {
          displayPersonResults(response.data.people);
        } else {
          $('#person-search-results').html('<p class="error">' + response.data + '</p>');
        }
      });
    }

    function displayPersonResults(people) {
      if (people.length === 0) {
        $('#person-search-results').html('<p><?php _e('No people found.', 'heritagepress'); ?></p>');
        return;
      }

      var html = '';
      $.each(people, function(index, person) {
        var dates = '';
        if (person.birthdate || person.deathdate) {
          dates = ' (' + (person.birthdate || '?') + ' - ' + (person.deathdate || '?') + ')';
        }

        html += '<div class="person-result" data-person-id="' + person.personID + '" data-name="' + person.firstname + ' ' + person.lastname + '">';
        html += '<strong>' + person.personID + '</strong>: ' + person.firstname + ' ' + person.lastname + dates;
        html += '</div>';
      });

      $('#person-search-results').html(html);
    }

    // Select person
    $(document).on('click', '.person-result', function() {
      var personId = $(this).data('person-id');
      var personName = $(this).data('name');

      $('#personID').val(personId);
      $('#person_name').val(''); // Clear person name when selecting from database
      $('#person-finder-modal').hide();

      // Clear search
      $('#person-search-input').val('');
      $('#person-search-results').html('');

      // Show person info
      $('#person-info').html('<small style="color: #666;"><?php _e('Selected:', 'heritagepress'); ?> ' + personName + '</small>');
    });

    // Form validation
    $('#dna-test-form').on('submit', function(e) {
      var testType = $('#test_type').val();
      if (!testType) {
        alert('<?php _e('Please select a test type.', 'heritagepress'); ?>');
        e.preventDefault();
        return false;
      }

      // If no person ID and no person name, require one
      var personId = $('#personID').val().trim();
      var personName = $('#person_name').val().trim();

      if (!personId && !personName) {
        alert('<?php _e('Please enter either a Person ID or Person Name.', 'heritagepress'); ?>');
        e.preventDefault();
        return false;
      }

      return true;
    });

    // Cancel button
    $('#cancel-form').on('click', function() {
      if (confirm('<?php _e('Are you sure you want to cancel? Any unsaved changes will be lost.', 'heritagepress'); ?>')) {
        window.location.href = '<?php echo admin_url('admin.php?page=heritagepress-dna-tests'); ?>';
      }
    });

    <?php if ($is_editing && $allow_delete): ?>
      // Delete test
      $('#delete-test').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to delete this DNA test? This action cannot be undone.', 'heritagepress'); ?>')) {
          // Create and submit delete form
          var form = $('<form method="post">')
            .append($('<input type="hidden" name="action" value="delete_dna_test">'))
            .append($('<input type="hidden" name="testID" value="<?php echo esc_js($test_data['testID']); ?>">'))
            .append('<?php echo wp_nonce_field('heritagepress_dna_test', '_wpnonce', true, false); ?>');

          $('body').append(form);
          form.submit();
        }
      });
    <?php endif; ?>

  });
</script>
