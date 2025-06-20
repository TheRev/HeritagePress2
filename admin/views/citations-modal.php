<?php

/**
 * Citations Modal View
 * Replicates TNG admin_citations.php modal interface exactly
 * Based on TNG citation listing and management
 */

if (!defined('ABSPATH')) {
  exit;
}

?>

<div class="databack ajaxwindow" id="citations" <?php if (!$citation_count) echo ' style="display:none"'; ?>>
  <form name="citeform">
    <p class="subhead">
      <strong><?php echo esc_html($event_type_desc); ?></strong> |
      <a href="#" onclick="return openHelp('citations_help.php');">Help</a>
    </p>

    <div class="citation-actions">
      <input type="button" value="Add New" onclick="CitationModal.showAddCitationForm();" />
      <input type="button" value="Finish" class="citation-finish-btn" />
    </div>

    <table id="citationstbl" class="fieldname normal" cellpadding="3" cellspacing="1" border="0" <?php if (!$citation_count) echo ' style="display:none"'; ?>>
      <tbody id="citationstblbody">
        <tr>
          <td class="fieldnameback" width="50"><b>Sort</b></td>
          <td class="fieldnameback" width="70"><b>Action</b></td>
          <td class="fieldnameback" width="445"><b>Title</b></td>
        </tr>

        <?php if ($citations && $citation_count > 0): ?>
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

            <div class="sortrow" id="citations_<?php echo $citation['citationID']; ?>">
              <table class="normal" cellpadding="3" cellspacing="1" border="0">
                <tr id="row_<?php echo $citation['citationID']; ?>">
                  <td class="dragarea">
                    <img src="<?php echo plugins_url('assets/images/admArrowUp.gif', HERITAGEPRESS_PLUGIN_FILE); ?>" alt="">
                    <br>
                    <img src="<?php echo plugins_url('assets/images/admArrowDown.gif', HERITAGEPRESS_PLUGIN_FILE); ?>" alt="">
                  </td>
                  <td class="lightback" width="70">
                    <a href="#" onclick="return editCitation(<?php echo $citation['citationID']; ?>);"
                      title="Edit" class="smallicon admin-edit-icon"></a>
                    <a href="#" onclick="return deleteCitation(<?php echo $citation['citationID']; ?>,'<?php echo esc_js($persfamID); ?>','<?php echo esc_js($tree); ?>','<?php echo esc_js($eventID); ?>');"
                      title="Delete" class="smallicon admin-delete-icon"></a>
                  </td>
                  <td class="lightback" width="445"><?php echo $truncated_display; ?></td>
                </tr>
              </table>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div id="cites" width="460">
      <!-- Citation rows loaded here -->
    </div>
  </form>
</div>

<!-- Add Citation Section (initially hidden) -->
<div class="databack ajaxwindow" style="display:none;" id="addcitation">
  <!-- Add citation form will be loaded here via AJAX -->
</div>

<!-- Edit Citation Section (initially hidden) -->
<div class="databack ajaxwindow" style="display:none;" id="editcitation">
  <!-- Edit citation form will be loaded here via AJAX -->
</div>

<!-- Find Source Section (initially hidden) -->
<div class="databack ajaxwindow" style="display:none;" id="findsource">
  <form action="" method="post" name="findsourceform1" id="findsourceform1" onsubmit="return applyFilter({form:'findsourceform1', fieldId:'mytitle', type:'S', tree:'<?php echo esc_js($tree); ?>', destdiv:'sourceresults'});">
    <p class="subhead">
      <strong>Find Source ID</strong><br />
      <span class="normal">(Enter source title or part of title)</span>
    </p>

    <table border="0" cellspacing="0" cellpadding="2" class="normal">
      <tr>
        <td>Title: </td>
        <td>
          <input type="text" name="mytitle" id="mytitle"
            onkeyup="filterChanged(event, {form:'findsourceform1', fieldId:'mytitle', type:'S', tree:'<?php echo esc_js($tree); ?>', destdiv:'sourceresults'});" />
        </td>
        <td>
          <input type="submit" value="Search">
          <input type="button" value="Cancel" onclick="CitationModal.gotoSection('findsource', CitationModal.prevsection);">
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <input type="radio" name="filter" value="s" onclick="applyFilter({form:'findsourceform1', fieldId:'mytitle', type:'S', tree:'<?php echo esc_js($tree); ?>', destdiv:'sourceresults'});" /> Starts with &nbsp;&nbsp;
          <input type="radio" name="filter" value="c" checked="checked" onclick="applyFilter({form:'findsourceform1', fieldId:'mytitle', type:'S', tree:'<?php echo esc_js($tree); ?>', destdiv:'sourceresults'});" /> Contains
        </td>
      </tr>
    </table>
  </form>

  <p><strong>Search Results</strong> (Click to select)</p>
  <div id="sourceresults" style="width:605px;height:380px;overflow:auto"></div>
</div>

<!-- New Source Section (initially hidden) -->
<div class="databack ajaxwindow" style="display:none;" id="newsource">
  <form action="" method="post" name="newsourceform" id="newsourceform" onsubmit="return saveSource(this);">
    <div style="float:right;text-align:center">
      <input type="submit" name="submit" class="bigsave" accesskey="s" value="Save">
      <p><a href="#" onclick="CitationModal.gotoSection('newsource', CitationModal.prevsection);">Cancel</a></p>
    </div>

    <p class="subhead">
      <strong>Add New Source</strong> |
      <a href="#" onclick="return openHelp('sources_help.php#add');">Help</a>
    </p>

    <span class="normal"><strong>Source ID will be generated automatically</strong></span><br />

    <table border="0" cellspacing="0" cellpadding="2" class="normal">
      <tr>
        <td>Source ID:</td>
        <td>
          <input type="hidden" name="tree1" value="<?php echo esc_attr($tree); ?>" />
          <input type="text" name="sourceID" id="sourceIDnew" size="10" onBlur="this.value=this.value.toUpperCase()">
          <input type="button" value="Generate" onclick="generateID('source',document.newsourceform.sourceIDnew);">
          <input type="button" value="Check" onclick="checkID(document.newsourceform.sourceIDnew.value,'source','checkmsg');">
          <span id="checkmsg" class="normal"></span>
        </td>
      </tr>
      <!-- Additional source fields would go here -->
    </table>

    <p class="normal"><strong>Additional fields can be completed later</strong></p>
  </form>
</div>

<style>
  /* Modal-specific styles */
  .databack.ajaxwindow {
    background: #fff;
    margin-bottom: 10px;
    padding: 15px;
  }

  .subhead {
    font-size: 14px;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
  }

  .subhead strong {
    color: #333;
  }

  .citation-actions {
    margin-bottom: 15px;
  }

  .citation-actions input[type="button"] {
    padding: 6px 12px;
    margin-right: 8px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
  }

  .citation-actions input[type="button"]:hover {
    background: #005a87;
  }

  #citationstbl {
    width: 100%;
    border-collapse: collapse;
  }

  #citationstbl td {
    padding: 8px;
    border: 1px solid #ddd;
  }

  .fieldnameback {
    background: #f9f9f9;
    font-weight: bold;
  }

  .lightback {
    background: #fafafa;
  }

  .sortrow {
    margin-bottom: 2px;
  }

  .dragarea {
    text-align: center;
    cursor: move;
  }

  .smallicon {
    text-decoration: none;
    margin-right: 4px;
  }
</style>
