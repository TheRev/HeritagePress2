<?php

/**
 * Add Citation Form View
 * Based on TNG admin_addcitation.php
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get available trees for dropdown
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Pre-fill form if coming from specific context
$pre_gedcom = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$pre_persfam = isset($_GET['personID']) ? sanitize_text_field($_GET['personID']) : (isset($_GET['familyID']) ? sanitize_text_field($_GET['familyID']) : '');
$pre_event = isset($_GET['eventID']) ? sanitize_text_field($_GET['eventID']) : '';

?>

<div class="add-citation-section">

  <div class="section-header">
    <h3>Add New Citation</h3>
    <p>Enter citation information. Specify either a Source ID or Description.</p>
  </div>

  <form id="add-citation-form" method="post" class="citation-form">
    <?php wp_nonce_field('hp_citation_nonce', '_wpnonce'); ?>
    <input type="hidden" name="action" value="add_citation">

    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="gedcom">Tree *:</label>
          </th>
          <td>
            <select name="gedcom" id="gedcom" required>
              <option value="">Select Tree...</option>
              <?php foreach ($trees_result as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>"
                  <?php selected($pre_gedcom, $tree['gedcom']); ?>>
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="persfamID">Person/Family ID *:</label>
          </th>
          <td>
            <input type="text" name="persfamID" id="persfamID" value="<?php echo esc_attr($pre_persfam); ?>"
              size="20" required placeholder="e.g., I1, F1">
            <button type="button" class="button" onclick="findPerson()">Find Person</button>
            <button type="button" class="button" onclick="findFamily()">Find Family</button>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="eventID">Event ID:</label>
          </th>
          <td>
            <input type="text" name="eventID" id="eventID" value="<?php echo esc_attr($pre_event); ?>"
              size="20" placeholder="Leave blank for general citation">
            <button type="button" class="button" onclick="findEvent()">Find Event</button>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="sourceID">Source:</label>
          </th>
          <td>
            <input type="text" name="sourceID" id="sourceID" value="" size="20" />
            <span> or </span>
            <button type="button" class="button" onclick="findSource()">Find</button>
            <button type="button" class="button" onclick="createNewSource()">Create</button>
            <div id="sourceTitle" class="source-title-display"></div>
          </td>
        </tr>

        <tr id="description-row">
          <th scope="row">
            <label for="description">Description:</label>
          </th>
          <td>
            <input type="text" name="description" id="description" value="" size="60"
              placeholder="Required if no Source ID specified">
            <div class="description">Enter description if not using a formal source</div>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="citepage">Page:</label>
          </th>
          <td>
            <input type="text" name="citepage" id="citepage" value="" size="60"
              placeholder="Page, section, or location within source">
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="quay">Reliability *:</label>
          </th>
          <td>
            <select name="quay" id="quay">
              <option value=""></option>
              <option value="0">0 - Unreliable evidence or estimated data</option>
              <option value="1">1 - Questionable reliability (interviews, census, oral)</option>
              <option value="2" selected>2 - Secondary evidence (published works, etc.)</option>
              <option value="3">3 - Direct and primary evidence</option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="citedate">Citation Date:</label>
          </th>
          <td>
            <input type="text" name="citedate" id="citedate" value="" size="30"
              onblur="checkDate(this);" placeholder="DD MMM YYYY (e.g., 15 Jan 1950)">
            <div class="description">Date when this citation was added or verified</div>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="citetext">Actual Text:</label>
          </th>
          <td>
            <textarea name="citetext" id="citetext" cols="60" rows="4"
              placeholder="Exact text from the source, if applicable"></textarea>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="citenote">Notes:</label>
          </th>
          <td>
            <textarea name="citenote" id="citenote" cols="60" rows="4"
              placeholder="Additional notes about this citation"></textarea>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="form-actions">
      <button type="submit" class="button button-primary">Add Citation</button>
      <button type="reset" class="button button-secondary">Reset Form</button>
      <a href="?page=heritagepress-citations" class="button button-secondary">Cancel</a>
    </div>
  </form>

</div>

<!-- Source Selection Modal -->
<div id="source-selection-modal" class="modal-overlay" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h4>Select Source</h4>
      <span class="close-modal" onclick="closeSourceModal()">&times;</span>
    </div>
    <div class="modal-body">
      <div class="source-search">
        <input type="text" id="source-search-term" placeholder="Search sources..." style="width: 100%;">
        <button type="button" class="button" onclick="searchSources()">Search</button>
      </div>
      <div id="source-search-results" class="search-results"></div>
    </div>
  </div>
</div>

<script type="text/javascript">
  function findPerson() {
    const gedcom = document.getElementById('gedcom').value;
    if (!gedcom) {
      alert('Please select a tree first');
      return;
    }

    // Open person finder - would need to implement
    alert('Person finder dialog would open here');
  }

  function findFamily() {
    const gedcom = document.getElementById('gedcom').value;
    if (!gedcom) {
      alert('Please select a tree first');
      return;
    }

    // Open family finder - would need to implement
    alert('Family finder dialog would open here');
  }

  function findEvent() {
    const gedcom = document.getElementById('gedcom').value;
    const persfamID = document.getElementById('persfamID').value;

    if (!gedcom || !persfamID) {
      alert('Please select a tree and person/family first');
      return;
    }

    // Open event finder - would need to implement
    alert('Event finder dialog would open here');
  }

  function findSource() {
    const gedcom = document.getElementById('gedcom').value;
    if (!gedcom) {
      alert('Please select a tree first');
      return;
    }

    document.getElementById('source-selection-modal').style.display = 'block';
    document.getElementById('source-search-term').focus();
  }

  function createNewSource() {
    // Open source creation dialog
    alert('Source creation dialog would open here');
  }

  function closeSourceModal() {
    document.getElementById('source-selection-modal').style.display = 'none';
    document.getElementById('source-search-results').innerHTML = '';
  }

  function searchSources() {
    const searchTerm = document.getElementById('source-search-term').value;
    const gedcom = document.getElementById('gedcom').value;

    if (!searchTerm) {
      alert('Please enter a search term');
      return;
    }

    jQuery.post(ajaxurl, {
      action: 'hp_search_sources',
      nonce: jQuery('#hp_citation_nonce').val(),
      search: searchTerm,
      gedcom: gedcom
    }, function(response) {
      if (response.success) {
        displaySourceResults(response.data.sources);
      } else {
        alert('Error searching sources: ' + response.data);
      }
    });
  }

  function displaySourceResults(sources) {
    const resultsDiv = document.getElementById('source-search-results');

    if (sources.length === 0) {
      resultsDiv.innerHTML = '<p>No sources found.</p>';
      return;
    }

    let html = '<div class="source-list">';
    sources.forEach(function(source) {
      const title = source.title || source.shorttitle || source.sourceID;
      const author = source.author ? ' by ' + source.author : '';

      html += '<div class="source-item" onclick="selectSource(\'' + source.sourceID + '\', \'' +
        title.replace(/'/g, "\\'") + '\')">';
      html += '<strong>[' + source.sourceID + ']</strong> ' + title + author;
      html += '</div>';
    });
    html += '</div>';

    resultsDiv.innerHTML = html;
  }

  function selectSource(sourceID, title) {
    document.getElementById('sourceID').value = sourceID;
    document.getElementById('sourceTitle').innerHTML = '<strong>Selected:</strong> ' + title;

    // Hide description field if source selected
    document.getElementById('description-row').style.display = 'none';
    document.getElementById('description').required = false;

    closeSourceModal();
  }

  function checkDate(input) {
    const value = input.value.trim();
    if (value && !value.match(/^\d{1,2}\s+\w{3}\s+\d{4}$/)) {
      input.style.borderColor = '#ffcc00';
      input.title = 'Recommended format: DD MMM YYYY (e.g., 15 Jan 1950)';
    } else {
      input.style.borderColor = '';
      input.title = '';
    }
  }

  // Form handling
  jQuery(document).ready(function($) {
    // Show/hide description field based on source ID
    $('#sourceID').on('input', function() {
      const hasSourceID = $(this).val().trim() !== '';
      $('#description-row').toggle(!hasSourceID);
      $('#description').prop('required', !hasSourceID);
    });

    // Form submission
    $('#add-citation-form').on('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const data = {};
      for (let [key, value] of formData.entries()) {
        data[key] = value;
      }

      // Validate required fields
      if (!data.gedcom || !data.persfamID) {
        alert('Tree and Person/Family ID are required');
        return;
      }

      if (!data.sourceID && !data.description) {
        alert('Please specify either a Source ID or Description');
        return;
      }

      $.post(ajaxurl, {
        action: 'hp_add_citation',
        nonce: $('#hp_citation_nonce').val(),
        ...data
      }, function(response) {
        if (response.success) {
          alert('Citation added successfully!');

          // Redirect based on context
          const urlParams = new URLSearchParams(window.location.search);
          if (urlParams.get('personID') || urlParams.get('familyID')) {
            // Return to manage view
            window.location.href = '?page=heritagepress-citations&tab=manage' +
              '&personID=' + (urlParams.get('personID') || '') +
              '&familyID=' + (urlParams.get('familyID') || '') +
              '&eventID=' + (urlParams.get('eventID') || '') +
              '&tree=' + (urlParams.get('tree') || '');
          } else {
            // Reset form for another entry
            this.reset();
            document.getElementById('sourceTitle').innerHTML = '';
            $('#description-row').show();
            $('#description').prop('required', true);
          }
        } else {
          alert('Error: ' + response.data);
        }
      }.bind(this));
    });
  });
</script>

<style>
  .add-citation-section {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    margin: 20px 0;
  }

  .section-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
  }

  .section-header h3 {
    margin: 0 0 10px 0;
    color: #333;
  }

  .citation-form .form-table th {
    width: 180px;
    text-align: left;
    vertical-align: top;
    padding-top: 15px;
  }

  .citation-form .form-table td {
    padding-top: 12px;
  }

  .form-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
  }

  .form-actions .button {
    margin-right: 10px;
  }

  .source-title-display {
    margin-top: 8px;
    padding: 8px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    font-style: italic;
    color: #333;
  }

  .description {
    font-size: 0.9em;
    color: #666;
    font-style: italic;
    margin-top: 5px;
  }

  /* Modal Styles */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 90%;
    max-width: 600px;
    max-height: 80%;
    overflow: hidden;
  }

  .modal-header {
    padding: 15px 20px;
    background: #f5f5f5;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-header h4 {
    margin: 0;
  }

  .close-modal {
    font-size: 24px;
    cursor: pointer;
    color: #666;
  }

  .close-modal:hover {
    color: #000;
  }

  .modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
  }

  .source-search {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
  }

  .source-search input {
    flex: 1;
    padding: 8px;
  }

  .search-results {
    border: 1px solid #ddd;
    max-height: 300px;
    overflow-y: auto;
  }

  .source-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
  }

  .source-item:hover {
    background: #f0f0f0;
  }

  .source-item:last-child {
    border-bottom: none;
  }

  /* Form validation styles */
  .form-table input:invalid,
  .form-table select:invalid {
    border-color: #dc3232;
  }

  .form-table input:valid,
  .form-table select:valid {
    border-color: #46b450;
  }
</style>
