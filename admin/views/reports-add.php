<?php

/**
 * Add/Edit Report Form
 * Drag-and-drop report builder interface
 */

if (!defined('ABSPATH')) {
  exit;
}

$is_edit = isset($report_data) && $report_data['reportID'] > 0;
$page_title = $is_edit ? __('Edit Report', 'heritagepress') : __('Add New Report', 'heritagepress');
$form_action = $is_edit ? 'update_report' : 'create_report';
$nonce_action = $is_edit ? 'heritagepress_report_update' : 'heritagepress_report_create';

// Parse display fields, criteria, and order for editing
$display_fields = !empty($report_data['display']) ? explode(',', $report_data['display']) : array();
$criteria_items = !empty($report_data['criteria']) ? explode(',', $report_data['criteria']) : array();
$order_fields = !empty($report_data['orderby']) ? explode(',', $report_data['orderby']) : array();

settings_errors('heritagepress_reports');
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=heritagepress-reports'); ?>" class="page-title-action">
    <?php _e('Back to Reports', 'heritagepress'); ?>
  </a>

  <hr class="wp-header-end">

  <div class="heritagepress-report-builder">
    <form method="post" id="report-form">
      <?php wp_nonce_field($nonce_action); ?>
      <?php if ($is_edit): ?>
        <input type="hidden" name="reportID" value="<?php echo esc_attr($report_data['reportID']); ?>">
      <?php endif; ?>

      <!-- Basic Report Information -->
      <div class="report-basic-info">
        <h2><?php _e('Report Information', 'heritagepress'); ?></h2>

        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="reportname"><?php _e('Report Name', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <input type="text" id="reportname" name="reportname"
                value="<?php echo esc_attr($report_data['reportname']); ?>"
                class="regular-text" required>
              <p class="description"><?php _e('Enter a unique name for this report.', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="reportdesc"><?php _e('Description', 'heritagepress'); ?></label>
            </th>
            <td>
              <textarea id="reportdesc" name="reportdesc" rows="3"
                class="large-text"><?php echo esc_textarea($report_data['reportdesc']); ?></textarea>
              <p class="description"><?php _e('Optional description of what this report shows.', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="ranking"><?php _e('Display Priority', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="number" id="ranking" name="ranking"
                value="<?php echo esc_attr($report_data['ranking']); ?>"
                min="1" max="999" class="small-text">
              <p class="description"><?php _e('Lower numbers appear first in lists.', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="active"><?php _e('Status', 'heritagepress'); ?></label>
            </th>
            <td>
              <fieldset>
                <label>
                  <input type="radio" name="active" value="1" <?php checked($report_data['active'], 1); ?>>
                  <?php _e('Active', 'heritagepress'); ?>
                </label><br>
                <label>
                  <input type="radio" name="active" value="0" <?php checked($report_data['active'], 0); ?>>
                  <?php _e('Inactive', 'heritagepress'); ?>
                </label>
              </fieldset>
              <p class="description"><?php _e('Only active reports can be run.', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>

      <!-- Report Builder Interface -->
      <div class="report-builder-interface">
        <h2><?php _e('Report Builder', 'heritagepress'); ?></h2>
        <p class="description"><?php _e('Build your report by selecting display fields, criteria, and sort options. Alternatively, write custom SQL below.', 'heritagepress'); ?></p>

        <!-- Display Fields Section -->
        <div class="builder-section">
          <h3><?php _e('Display Fields', 'heritagepress'); ?></h3>
          <p class="description"><?php _e('Select which fields to show in your report:', 'heritagepress'); ?></p>

          <div class="field-builder">
            <div class="available-fields">
              <h4><?php _e('Available Fields', 'heritagepress'); ?></h4>
              <div class="field-groups">
                <?php foreach ($field_definitions as $group => $fields): ?>
                  <div class="field-group">
                    <h5><?php echo esc_html(ucfirst($group)); ?></h5>
                    <ul class="field-list" data-group="<?php echo esc_attr($group); ?>">
                      <?php foreach ($fields as $key => $label): ?>
                        <li class="field-item" data-field="<?php echo esc_attr($key); ?>"
                          ondblclick="addToDisplayFields(this)">
                          <?php echo esc_html($label); ?>
                          <span class="field-key hidden"><?php echo esc_html($key); ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="selected-fields">
              <h4><?php _e('Selected Display Fields', 'heritagepress'); ?></h4>
              <ul id="display-fields" class="selected-field-list">
                <?php foreach ($display_fields as $field): ?>
                  <?php if (!empty(trim($field))): ?>
                    <li class="selected-field" onclick="removeFromList(this)">
                      <?php echo esc_html($field); ?>
                    </li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>

        <!-- Criteria Section -->
        <div class="builder-section">
          <h3><?php _e('Filter Criteria', 'heritagepress'); ?></h3>
          <p class="description"><?php _e('Build conditions to filter your data:', 'heritagepress'); ?></p>

          <div class="criteria-builder">
            <div class="criteria-tools">
              <div class="criteria-fields">
                <h4><?php _e('Fields', 'heritagepress'); ?></h4>
                <ul class="tool-list">
                  <?php foreach ($field_definitions['people'] as $key => $label): ?>
                    <li class="tool-item" ondblclick="addToCriteria(this, 'field')">
                      <?php echo esc_html($label); ?>
                      <span class="tool-value hidden"><?php echo esc_html($key); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <div class="criteria-operators">
                <h4><?php _e('Operators', 'heritagepress'); ?></h4>
                <ul class="tool-list">
                  <?php foreach ($operator_definitions as $key => $label): ?>
                    <li class="tool-item" ondblclick="addToCriteria(this, 'operator')">
                      <?php echo esc_html($label); ?>
                      <span class="tool-value hidden"><?php echo esc_html($key); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <div class="criteria-values">
                <h4><?php _e('Add Value', 'heritagepress'); ?></h4>
                <input type="text" id="criteria-value" class="regular-text"
                  placeholder="<?php _e('Enter value...', 'heritagepress'); ?>">
                <button type="button" onclick="addValueToCriteria()" class="button">
                  <?php _e('Add', 'heritagepress'); ?>
                </button>
              </div>
            </div>

            <div class="selected-criteria">
              <h4><?php _e('Current Criteria', 'heritagepress'); ?></h4>
              <ul id="criteria-list" class="criteria-list">
                <?php foreach ($criteria_items as $item): ?>
                  <?php if (!empty(trim($item))): ?>
                    <li class="criteria-item" onclick="removeFromList(this)">
                      <?php echo esc_html($item); ?>
                    </li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>

        <!-- Sort Order Section -->
        <div class="builder-section">
          <h3><?php _e('Sort Order', 'heritagepress'); ?></h3>
          <p class="description"><?php _e('Define how results should be sorted:', 'heritagepress'); ?></p>

          <div class="sort-builder">
            <div class="sort-fields">
              <h4><?php _e('Available Sort Fields', 'heritagepress'); ?></h4>
              <ul class="field-list">
                <?php foreach ($field_definitions['people'] as $key => $label): ?>
                  <li class="field-item" ondblclick="addToSortOrder(this)">
                    <?php echo esc_html($label); ?>
                    <span class="field-key hidden"><?php echo esc_html($key); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="selected-sort">
              <h4><?php _e('Sort Order', 'heritagepress'); ?></h4>
              <ul id="sort-order" class="sort-list">
                <?php foreach ($order_fields as $field): ?>
                  <?php if (!empty(trim($field))): ?>
                    <li class="sort-item" onclick="removeFromList(this)">
                      <?php echo esc_html($field); ?>
                    </li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>

        <!-- Advanced SQL Section -->
        <div class="builder-section">
          <h3><?php _e('Advanced SQL Query', 'heritagepress'); ?></h3>
          <p class="description">
            <?php _e('For advanced users: Write a custom SQL SELECT statement. This will override the visual builder above.', 'heritagepress'); ?>
            <strong><?php _e('Warning: Only SELECT statements are allowed for security.', 'heritagepress'); ?></strong>
          </p>

          <textarea id="sqlselect" name="sqlselect" rows="10" class="large-text code"
            placeholder="SELECT personID, firstname, lastname FROM wp_hp_people WHERE..."><?php echo esc_textarea($report_data['sqlselect']); ?></textarea>
        </div>
      </div>

      <!-- Hidden Fields for Visual Builder Data -->
      <input type="hidden" name="display" id="display-hidden" value="<?php echo esc_attr($report_data['display']); ?>">
      <input type="hidden" name="criteria" id="criteria-hidden" value="<?php echo esc_attr($report_data['criteria']); ?>">
      <input type="hidden" name="orderby" id="orderby-hidden" value="<?php echo esc_attr($report_data['orderby']); ?>">

      <!-- Submit Buttons -->
      <p class="submit">
        <input type="submit" name="<?php echo esc_attr($form_action); ?>" class="button button-primary"
          value="<?php echo $is_edit ? __('Update Report', 'heritagepress') : __('Save Report', 'heritagepress'); ?>">
        <?php if (!$is_edit): ?>
          <input type="submit" name="save_and_edit" class="button button-secondary"
            value="<?php _e('Save & Continue Editing', 'heritagepress'); ?>">
        <?php endif; ?>
        <a href="<?php echo admin_url('admin.php?page=heritagepress-reports'); ?>" class="button button-secondary">
          <?php _e('Cancel', 'heritagepress'); ?>
        </a>
      </p>
    </form>
  </div>
</div>

<script type="text/javascript">
  // Report Builder JavaScript
  function addToDisplayFields(element) {
    var fieldName = element.textContent.trim();
    var fieldKey = element.querySelector('.field-key').textContent;
    var displayList = document.getElementById('display-fields');

    // Check if already added
    var existing = displayList.querySelector(`li[data-field="${fieldKey}"]`);
    if (existing) return;

    var li = document.createElement('li');
    li.className = 'selected-field';
    li.setAttribute('data-field', fieldKey);
    li.textContent = fieldName;
    li.onclick = function() {
      removeFromList(this);
    };

    displayList.appendChild(li);
    updateHiddenFields();
  }

  function addToCriteria(element, type) {
    var value = element.querySelector('.tool-value').textContent;
    var criteriaList = document.getElementById('criteria-list');

    var li = document.createElement('li');
    li.className = 'criteria-item';
    li.textContent = value;
    li.onclick = function() {
      removeFromList(this);
    };

    criteriaList.appendChild(li);
    updateHiddenFields();
  }

  function addValueToCriteria() {
    var valueInput = document.getElementById('criteria-value');
    var value = valueInput.value.trim();

    if (!value) return;

    var criteriaList = document.getElementById('criteria-list');
    var li = document.createElement('li');
    li.className = 'criteria-item';
    li.textContent = '"' + value + '"';
    li.onclick = function() {
      removeFromList(this);
    };

    criteriaList.appendChild(li);
    valueInput.value = '';
    updateHiddenFields();
  }

  function addToSortOrder(element) {
    var fieldName = element.textContent.trim();
    var fieldKey = element.querySelector('.field-key').textContent;
    var sortList = document.getElementById('sort-order');

    var li = document.createElement('li');
    li.className = 'sort-item';
    li.setAttribute('data-field', fieldKey);
    li.textContent = fieldName;
    li.onclick = function() {
      removeFromList(this);
    };

    sortList.appendChild(li);
    updateHiddenFields();
  }

  function removeFromList(element) {
    element.parentNode.removeChild(element);
    updateHiddenFields();
  }

  function updateHiddenFields() {
    // Update display fields
    var displayItems = document.querySelectorAll('#display-fields .selected-field');
    var displayValues = Array.from(displayItems).map(item => item.getAttribute('data-field') || item.textContent);
    document.getElementById('display-hidden').value = displayValues.join(',');

    // Update criteria
    var criteriaItems = document.querySelectorAll('#criteria-list .criteria-item');
    var criteriaValues = Array.from(criteriaItems).map(item => item.textContent);
    document.getElementById('criteria-hidden').value = criteriaValues.join(',');

    // Update sort order
    var sortItems = document.querySelectorAll('#sort-order .sort-item');
    var sortValues = Array.from(sortItems).map(item => item.getAttribute('data-field') || item.textContent);
    document.getElementById('orderby-hidden').value = sortValues.join(',');
  }

  // Form validation
  document.getElementById('report-form').addEventListener('submit', function(e) {
    var reportName = document.getElementById('reportname').value.trim();
    var displayFields = document.getElementById('display-hidden').value.trim();
    var sqlSelect = document.getElementById('sqlselect').value.trim();

    if (!reportName) {
      alert('<?php _e('Please enter a report name.', 'heritagepress'); ?>');
      e.preventDefault();
      return false;
    }

    if (!displayFields && !sqlSelect) {
      alert('<?php _e('Please select display fields or enter a custom SQL query.', 'heritagepress'); ?>');
      e.preventDefault();
      return false;
    }

    updateHiddenFields();
    return true;
  });

  // Initialize
  document.addEventListener('DOMContentLoaded', function() {
    updateHiddenFields();
  });
</script>

<style>
  .heritagepress-report-builder {
    max-width: 1200px;
  }

  .report-basic-info {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    margin-bottom: 20px;
  }

  .report-builder-interface {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
  }

  .builder-section {
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #dcdcde;
  }

  .builder-section:last-child {
    border-bottom: none;
  }

  .field-builder {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 15px;
  }

  .available-fields,
  .selected-fields {
    border: 1px solid #c3c4c7;
    padding: 15px;
    min-height: 300px;
  }

  .field-groups {
    max-height: 400px;
    overflow-y: auto;
  }

  .field-group {
    margin-bottom: 20px;
  }

  .field-group h5 {
    margin: 0 0 10px 0;
    padding: 5px 0;
    border-bottom: 1px solid #dcdcde;
    color: #1d2327;
  }

  .field-list,
  .tool-list,
  .selected-field-list,
  .criteria-list,
  .sort-list {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .field-item,
  .tool-item,
  .selected-field,
  .criteria-item,
  .sort-item {
    padding: 8px 12px;
    margin-bottom: 5px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    cursor: pointer;
    border-radius: 3px;
    transition: background-color 0.2s;
  }

  .field-item:hover,
  .tool-item:hover {
    background: #e0e6ed;
  }

  .selected-field,
  .criteria-item,
  .sort-item {
    background: #d7e7f7;
    border-color: #72aee6;
  }

  .selected-field:hover,
  .criteria-item:hover,
  .sort-item:hover {
    background: #b3d7f7;
  }

  .criteria-builder {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-top: 15px;
  }

  .criteria-tools {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
  }

  .criteria-fields,
  .criteria-operators,
  .criteria-values,
  .selected-criteria,
  .sort-fields,
  .selected-sort {
    border: 1px solid #c3c4c7;
    padding: 15px;
    min-height: 200px;
  }

  .sort-builder {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 15px;
  }

  .criteria-values input {
    width: 100%;
    margin-bottom: 10px;
  }

  .hidden {
    display: none;
  }

  .required {
    color: #d63638;
  }

  h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: 600;
  }

  .code {
    font-family: Consolas, Monaco, 'Courier New', monospace;
    font-size: 13px;
  }

  @media (max-width: 768px) {

    .field-builder,
    .criteria-builder,
    .sort-builder {
      grid-template-columns: 1fr;
    }

    .criteria-tools {
      grid-template-columns: 1fr;
    }
  }
</style>
