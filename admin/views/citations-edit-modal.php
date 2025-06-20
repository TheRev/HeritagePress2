<?php

/**
 * Edit Citation Modal Form
 * Replicates TNG admin_citations.php edit citation form exactly
 */

if (!defined('ABSPATH')) {
  exit;
}

?>

<div class="databack ajaxwindow" id="editcitation">
  <form action="" name="editciteform" onSubmit="return updateCitation(this);">
    <div style="float:right;text-align:center">
      <input type="submit" name="submit" class="btn" value="Update">
      <p><a href="#" onclick="return CitationModal.gotoSection('editcitation','citations');" class="cancel-link">Cancel</a></p>
    </div>

    <p class="subhead">
      <strong>Edit Citation</strong> |
      <a href="#" onclick="return openHelp('citations_help.php#edit');">Help</a>
    </p>

    <table border="0" cellpadding="2" class="normal citation-form">
      <tr>
        <td>Source ID:</td>
        <td>
          <input type="text" name="sourceID" id="sourceID" size="20" value="<?php echo esc_attr($citation['sourceID'] ?? ''); ?>" /> &nbsp;or&nbsp;
          <input type="button" value="Find" onclick="return initFilter('editcitation','findsource','sourceID','sourceTitle');" />
          <input type="button" value="Create" onclick="return initNewItem('source', document.newsourceform.sourceID, 'sourceID', 'sourceTitle', 'editcitation','newsource');" />
        </td>
      </tr>
      <tr>
        <td></td>
        <td id="sourceTitle">
          <?php if (!empty($citation['title']) || !empty($citation['shorttitle'])): ?>
            <?php echo esc_html($citation['title'] ?: $citation['shorttitle']); ?>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td>Page:</td>
        <td><input type="text" name="citepage" id="citepage" size="60" value="<?php echo esc_attr($citation['page'] ?? ''); ?>" /></td>
      </tr>
      <tr>
        <td>Reliability:</td>
        <td>
          <select name="quay" id="quay">
            <option value=""></option>
            <option value="0" <?php selected($citation['quay'] ?? '', '0'); ?>>0</option>
            <option value="1" <?php selected($citation['quay'] ?? '', '1'); ?>>1</option>
            <option value="2" <?php selected($citation['quay'] ?? '', '2'); ?>>2</option>
            <option value="3" <?php selected($citation['quay'] ?? '', '3'); ?>>3</option>
          </select>
          <span class="reliability-explanation">(0=Unreliable, 1=Questionable, 2=Secondary, 3=Primary)</span>
        </td>
      </tr>
      <tr>
        <td>Citation Date:</td>
        <td><input type="text" name="citedate" id="citedate" size="60" value="<?php echo esc_attr($citation['citedate'] ?? ''); ?>" onBlur="checkDate(this);" /></td>
      </tr>
      <tr>
        <td valign="top">Actual Text:</td>
        <td><textarea cols="50" rows="5" name="citetext" id="citetext"><?php echo esc_textarea($citation['citetext'] ?? ''); ?></textarea></td>
      </tr>
      <tr>
        <td valign="top">Notes:</td>
        <td><textarea cols="50" rows="5" name="citenote" id="citenote"><?php echo esc_textarea($citation['note'] ?? ''); ?></textarea></td>
      </tr>

      <?php if (($citation['eventID'] ?? '') != 'SLGC'): ?>
        <tr>
          <td valign="top">Events</td>
          <td>
            <select name="events[]" multiple size="8">
              <?php foreach ($events as $event_key => $event_label): ?>
                <option value="<?php echo esc_attr($event_key); ?>" <?php echo ($event_key == ($citation['eventID'] ?? '')) ? ' selected' : ''; ?>>
                  <?php echo esc_html($event_label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      <?php endif; ?>
    </table>
    <br />

    <input type="hidden" name="citationID" value="<?php echo esc_attr($citation['citationID']); ?>" />
    <input type="hidden" name="persfamID" value="<?php echo esc_attr($citation['persfamID']); ?>" />
    <input type="hidden" name="tree" value="<?php echo esc_attr($citation['gedcom']); ?>" />
    <input type="hidden" name="eventID" value="<?php echo esc_attr($citation['eventID'] ?? ''); ?>" />
  </form>
</div>

<script>
  // Check date format
  function checkDate(input) {
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
