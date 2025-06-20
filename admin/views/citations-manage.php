<?php

/**
 * Citations Management View
 * Display and manage citations for a specific person/family/event
 * Based on admin_citations.php
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get parameters
$person_id = isset($_GET['personID']) ? sanitize_text_field($_GET['personID']) : '';
$family_id = isset($_GET['familyID']) ? sanitize_text_field($_GET['familyID']) : '';
$event_id = isset($_GET['eventID']) ? sanitize_text_field($_GET['eventID']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$note_id = isset($_GET['noteID']) ? sanitize_text_field($_GET['noteID']) : '';

// Determine person/family ID
$persfam_id = !empty($person_id) ? $person_id : (!empty($family_id) ? $family_id : $note_id);

if (empty($persfam_id) || empty($tree)) {
  echo '<div class="notice notice-error"><p>Missing required parameters for citation management.</p></div>';
  return;
}

// Get event type information
$event_type_desc = 'General';
if (!empty($event_id)) {
  $events_table = $wpdb->prefix . 'hp_events';
  $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

  $event_query = $wpdb->prepare("
    SELECT et.tag, et.display
    FROM $events_table e
    LEFT JOIN $eventtypes_table et ON e.eventtypeID = et.eventtypeID
    WHERE e.eventID = %s
  ", $event_id);

  $event_result = $wpdb->get_row($event_query, ARRAY_A);
  if ($event_result) {
    $event_type_desc = !empty($event_result['display']) ? $event_result['display'] : $event_result['tag'];
  }
}

// Get citations for this person/family/event
$citations_table = $wpdb->prefix . 'hp_citations';
$sources_table = $wpdb->prefix . 'hp_sources';

$where_clause = "WHERE c.gedcom = %s AND c.persfamID = %s";
$params = array($tree, $persfam_id);

if (!empty($event_id)) {
  $where_clause .= " AND c.eventID = %s";
  $params[] = $event_id;
}

// Include note citations if noteID is provided
$note_clause = '';
if (!empty($note_id)) {
  $note_clause = " OR c.persfamID = %s";
  $params[] = $note_id;
}

$citations_query = $wpdb->prepare("
  SELECT c.citationID, c.sourceID, c.description, c.page, c.quay, c.citedate, c.citetext, c.note, c.ordernum,
         s.title, s.shorttitle
  FROM $citations_table c
  LEFT JOIN $sources_table s ON c.sourceID = s.sourceID AND s.gedcom = c.gedcom
  $where_clause$note_clause
  ORDER BY c.ordernum, c.citationID
", $params);

$citations = $wpdb->get_results($citations_query, ARRAY_A);
$citation_count = count($citations);

?>

<div class="citations-manage-section">

  <!-- Section Header -->
  <div class="citations-header">
    <h3>
      <?php printf('Citations: %s', esc_html($event_type_desc)); ?>
      <?php if ($citation_count > 0): ?>
        <span class="citation-count"><?php echo $citation_count; ?></span>
      <?php endif; ?>
    </h3>

    <div class="citations-actions">
      <button type="button" class="button button-primary" onclick="showAddCitationForm()">
        Add New Citation
      </button>
      <button type="button" class="button button-secondary" onclick="window.close()">
        Finish
      </button>
    </div>
  </div>

  <!-- Citations List -->
  <?php if ($citation_count > 0): ?>
    <div id="citations-container" class="citations-container">
      <table id="citations-table" class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th style="width: 50px;"><strong>Sort</strong></th>
            <th style="width: 70px;"><strong>Action</strong></th>
            <th><strong>Source / Description</strong></th>
          </tr>
        </thead>
        <tbody id="citations-table-body">
          <?php foreach ($citations as $citation): ?>
            <?php
            // Build citation display text
            if (!empty($citation['sourceID'])) {
              $source_title = !empty($citation['title']) ? $citation['title'] : $citation['shorttitle'];
              $citation_display = '[' . esc_html($citation['sourceID']) . '] ' . esc_html($source_title);
            } else {
              $citation_display = esc_html($citation['description']);
            }

            // Truncate display text
            $truncated_display = strlen($citation_display) > 75 ? substr($citation_display, 0, 72) . '...' : $citation_display;
            ?>

            <tr class="sortrow" id="citation_<?php echo $citation['citationID']; ?>">
              <td class="dragarea">
                <img src="<?php echo plugins_url('assets/images/ArrowUp.gif', HERITAGEPRESS_PLUGIN_FILE); ?>" alt="Up">
                <br>
                <img src="<?php echo plugins_url('assets/images/ArrowDown.gif', HERITAGEPRESS_PLUGIN_FILE); ?>" alt="Down">
              </td>
              <td class="lightback">
                <a href="#" onclick="return editCitation(<?php echo $citation['citationID']; ?>);"
                  title="Edit" class="smallicon admin-edit-icon"></a>
                <a href="#" onclick="return deleteCitation(<?php echo $citation['citationID']; ?>,'<?php echo esc_js($persfam_id); ?>','<?php echo esc_js($tree); ?>','<?php echo esc_js($event_id); ?>');"
                  title="Delete" class="smallicon admin-delete-icon"></a>
              </td>
              <td class="lightback citation-display">
                <?php echo $truncated_display; ?>

                <!-- Citation Details (initially hidden) -->
                <div class="citation-details" style="display: none;">
                  <?php if (!empty($citation['page'])): ?>
                    <div><strong>Page:</strong> <?php echo esc_html($citation['page']); ?></div>
                  <?php endif; ?>
                  <?php if (!empty($citation['citedate'])): ?>
                    <div><strong>Date:</strong> <?php echo esc_html($citation['citedate']); ?></div>
                  <?php endif; ?>
                  <?php if (!empty($citation['quay'])): ?>
                    <div><strong>Quality:</strong> <?php echo esc_html($citation['quay']); ?></div>
                  <?php endif; ?>
                  <?php if (!empty($citation['citetext'])): ?>
                    <div><strong>Text:</strong> <?php echo esc_html($citation['citetext']); ?></div>
                  <?php endif; ?>
                  <?php if (!empty($citation['note'])): ?>
                    <div><strong>Notes:</strong> <?php echo esc_html($citation['note']); ?></div>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div id="no-citations-message" class="no-citations">
      <p>No citations found for this <?php echo !empty($event_id) ? 'event' : 'person/family'; ?>.</p>
      <button type="button" class="button button-primary" onclick="showAddCitationForm()">
        Add First Citation
      </button>
    </div>
  <?php endif; ?>

  <!-- Add Citation Form (initially hidden) -->
  <div id="add-citation-form" class="citation-form" style="display: none;">
    <h4>Add New Citation</h4>

    <form id="citation-form" method="post">
      <?php wp_nonce_field('hp_citation_nonce', '_wpnonce'); ?>
      <input type="hidden" name="action" value="add_citation">
      <input type="hidden" name="gedcom" value="<?php echo esc_attr($tree); ?>">
      <input type="hidden" name="persfamID" value="<?php echo esc_attr($persfam_id); ?>">
      <input type="hidden" name="eventID" value="<?php echo esc_attr($event_id); ?>">

      <table class="form-table">
        <tbody>
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

          <tr id="description-row" style="display: none;">
            <th scope="row">
              <label for="description">Description:</label>
            </th>
            <td>
              <input type="text" name="description" id="description" value="" size="60">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="citepage">Page:</label>
            </th>
            <td>
              <input type="text" name="citepage" id="citepage" value="" size="60">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="quay">Reliability:</label>
            </th>
            <td>
              <select name="quay" id="quay">
                <option value=""></option>
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
              </select>
              <span class="description">(0=Unreliable, 1=Questionable, 2=Secondary, 3=Primary)</span>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="citedate">Citation Date:</label>
            </th>
            <td>
              <input type="text" name="citedate" id="citedate" value="" size="60"
                onblur="checkDate(this);" placeholder="DD MMM YYYY">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="citetext">Actual Text:</label>
            </th>
            <td>
              <textarea name="citetext" id="citetext" cols="50" rows="5"></textarea>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="citenote">Notes:</label>
            </th>
            <td>
              <textarea name="citenote" id="citenote" cols="50" rows="5"></textarea>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="form-actions">
        <button type="submit" class="button button-primary">Add Citation</button>
        <button type="button" class="button button-secondary" onclick="hideAddCitationForm()">Cancel</button>
      </div>
    </form>
  </div>

</div>

<!-- Citation Management JavaScript -->
<script type="text/javascript">
  function showAddCitationForm() {
    document.getElementById('add-citation-form').style.display = 'block';
    document.getElementById('sourceID').focus();
  }

  function hideAddCitationForm() {
    document.getElementById('add-citation-form').style.display = 'none';
    document.getElementById('citation-form').reset();
    document.getElementById('sourceTitle').innerHTML = '';
  }

  function findSource() {
    // Open source finder dialog
    const searchTerm = document.getElementById('sourceID').value;
    if (searchTerm) {
      HeritagePress.Citations.searchSources(searchTerm, '<?php echo esc_js($tree); ?>', function(sources) {
        showSourceSelection(sources);
      });
    } else {
      alert('Please enter a source ID or search term');
    }
  }

  function createNewSource() {
    // Placeholder for source creation
    alert('Source creation dialog would open here');
  }

  function showSourceSelection(sources) {
    if (sources.length === 0) {
      alert('No sources found');
      return;
    }

    let html = '<div class="source-selection"><h4>Select Source:</h4><ul>';
    sources.forEach(function(source) {
      const title = source.title || source.shorttitle || source.sourceID;
      html += '<li><a href="#" onclick="selectSource(\'' + source.sourceID + '\', \'' + title.replace(/'/g, "\\'") + '\')">' +
        '[' + source.sourceID + '] ' + title + '</a></li>';
    });
    html += '</ul></div>';

    document.getElementById('sourceTitle').innerHTML = html;
  }

  function selectSource(sourceID, title) {
    document.getElementById('sourceID').value = sourceID;
    document.getElementById('sourceTitle').innerHTML = title;
    document.getElementById('description-row').style.display = 'none';
  }

  function checkDate(input) {
    // Basic date validation - could be enhanced
    const value = input.value.trim();
    if (value && !value.match(/^\d{1,2}\s+\w{3}\s+\d{4}$/)) {
      // Allow flexibility but warn about format
      input.style.borderColor = '#ffcc00';
      input.title = 'Recommended format: DD MMM YYYY (e.g., 15 Jan 1950)';
    } else {
      input.style.borderColor = '';
      input.title = '';
    }
  }

  // Handle form submission
  jQuery(document).ready(function($) {
    $('#citation-form').on('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const data = {};
      for (let [key, value] of formData.entries()) {
        data[key] = value;
      }

      // Validate required fields
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
          location.reload();
        } else {
          alert('Error: ' + response.data);
        }
      });
    });

    // Show/hide description field based on source ID
    $('#sourceID').on('input', function() {
      const hasSourceID = $(this).val().trim() !== '';
      $('#description-row').toggle(!hasSourceID);
    });
  });
</script>

<style>
  .citations-manage-section {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    margin: 20px 0;
  }

  .citations-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
  }

  .citations-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .citations-actions {
    display: flex;
    gap: 10px;
  }

  .citations-container {
    margin-bottom: 20px;
  }

  .citation-form {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 20px;
    margin-top: 20px;
  }

  .citation-form h4 {
    margin-top: 0;
    color: #333;
  }

  .form-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
  }

  .source-title-display {
    margin-top: 5px;
    font-style: italic;
    color: #666;
  }

  .source-selection ul {
    list-style: none;
    padding: 0;
    margin: 10px 0;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    background: #fff;
  }

  .source-selection li {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
  }

  .source-selection li:hover {
    background: #f0f0f0;
  }

  .source-selection a {
    text-decoration: none;
    color: #0073aa;
  }

  .source-selection a:hover {
    color: #005a87;
  }

  .description {
    font-size: 0.9em;
    color: #666;
    font-style: italic;
  }

  .dragarea img {
    display: block;
    margin: 2px auto;
  }

  .sortrow:hover {
    background-color: #f0f0f0;
  }

  .citation-count {
    background: #0073aa;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: normal;
  }
</style>
