<?php

/**
 * Add Citation Modal Form
 * Replicates HeritagePress admin_citations.php add citation form exactly
 */

if (!defined('ABSPATH')) {
  exit;
}

?>

<div class="databack ajaxwindow" id="addcitation">
  <form action="" name="citeform2" onSubmit="return addCitation(this);">
    <div style="float:right;text-align:center">
      <input type="submit" name="submit" class="btn" value="Save">
      <p><a href="#" onclick="return CitationModal.gotoSection('addcitation','citations');" class="cancel-link">Cancel</a></p>
    </div>

    <p class="subhead">
      <strong>Add New Citation</strong> |
      <a href="#" onclick="return openHelp('citations_help.php#add');">Help</a>
    </p>

    <table border="0" cellpadding="2" class="normal citation-form">
      <tr>
        <td>Source ID:</td>
        <td>
          <input type="text" name="sourceID" id="sourceID" size="20" /> &nbsp;or&nbsp;
          <input type="button" value="Find" onclick="return initFilter('addcitation','findsource','sourceID','sourceTitle');" />
          <input type="button" value="Create" onclick="return initNewItem('source', document.newsourceform.sourceID, 'sourceID', 'sourceTitle', 'addcitation','newsource');" />
          <?php if (!empty($last_citation)): ?>
            <input type="button" value="Copy Last" onclick="return copylast(document.citeform2,'<?php echo esc_js($last_citation); ?>');">
            &nbsp; <img src="<?php echo plugins_url('assets/images/spinner.gif', HERITAGEPRESS_PLUGIN_FILE); ?>" id="lastspinner" style="vertical-align:-3px; display:none" />
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td></td>
        <td id="sourceTitle"></td>
      </tr>
      <tr>
        <td>Page:</td>
        <td><input type="text" name="citepage" id="citepage" size="60" /></td>
      </tr>
      <tr>
        <td>Reliability:</td>
        <td>
          <select name="quay" id="quay">
            <option value=""></option>
            <option value="0">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
          </select>
          <span class="reliability-explanation">(0=Unreliable, 1=Questionable, 2=Secondary, 3=Primary)</span>
        </td>
      </tr>
      <tr>
        <td>Citation Date:</td>
        <td><input type="text" name="citedate" id="citedate" size="60" onBlur="checkDate(this);" /></td>
      </tr>
      <tr>
        <td valign="top">Actual Text:</td>
        <td><textarea cols="50" rows="5" name="citetext" id="citetext"></textarea></td>
      </tr>
      <tr>
        <td valign="top">Notes:</td>
        <td><textarea cols="50" rows="5" name="citenote" id="citenote"></textarea></td>
      </tr>

      <?php if ($eventID != 'SLGC'): ?>
        <tr>
          <td valign="top">Events</td>
          <td>
            <select name="events[]" multiple size="8">
              <?php foreach ($events as $event_key => $event_label): ?>
                <option value="<?php echo esc_attr($event_key); ?>" <?php echo ($event_key == $eventID) ? ' selected' : ''; ?>>
                  <?php echo esc_html($event_label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      <?php endif; ?>
    </table>
    <br />

    <input type="hidden" name="persfamID" value="<?php echo esc_attr($persfamID); ?>" />
    <input type="hidden" name="tree" value="<?php echo esc_attr($tree); ?>" />
    <input type="hidden" name="eventID" value="<?php echo esc_attr($eventID); ?>" />
  </form>
</div>

<script>
  // Check date format
  function checkDate(input) {
    // Basic date validation
    var value = input.value.trim();
    if (value && !value.match(/^\d{1,2}\s+\w{3}\s+\d{4}$/)) {
      input.style.borderColor = '#ffcc00';
      input.title = 'Recommended format: DD MMM YYYY (e.g., 15 Jan 1950)';
    } else {
      input.style.borderColor = '';
      input.title = '';
    }
  }

  // Initialize new item creation
  function initNewItem(type, targetField, sourceIdField, sourceTitleField, currentSection, targetSection) {
    CitationModal.gotoSection(currentSection, targetSection);
    return false;
  }
</script>
