<?php

/**
 * Add New Source View
 *
 * @package HeritagePress
 * @subpackage Admin/Views
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get repositories for dropdown
global $wpdb;
$repositories = $wpdb->get_results("SELECT repositoryID, repositoryname FROM {$wpdb->prefix}hp_repositories ORDER BY repositoryname");

// Get source types for reference (from existing sources)
$source_types = $wpdb->get_col("SELECT DISTINCT sourcetype FROM {$wpdb->prefix}hp_sources WHERE sourcetype IS NOT NULL AND sourcetype != '' ORDER BY sourcetype");

// Common source types to suggest
$suggested_types = array(
  'Book',
  'Article',
  'Website',
  'Newspaper',
  'Journal',
  'Magazine',
  'Government Record',
  'Church Record',
  'Cemetery Record',
  'Census',
  'Vital Record',
  'Military Record',
  'Immigration Record',
  'Court Record',
  'Land Record',
  'Tax Record',
  'Directory',
  'Yearbook',
  'Obituary',
  'Interview',
  'Letter',
  'Diary',
  'Photograph',
  'Map',
  'Document',
  'Manuscript',
  'Other'
);

$all_types = array_unique(array_merge($suggested_types, $source_types));
sort($all_types);
?>

<div class="wrap">
  <h1><?php _e('Add New Source', 'heritagepress'); ?></h1>
  <form method="post" action="<?php echo admin_url('admin.php?page=heritagepress-sources'); ?>" id="add-source-form">
    <?php wp_nonce_field('hp_add_source', 'hp_source_nonce'); ?>
    <input type="hidden" name="action" value="add_source" />

    <table class="form-table">
      <tbody>
        <!-- Tree Selection -->
        <tr>
          <th scope="row">
            <label for="gedcom"><?php _e('Tree', 'heritagepress'); ?> <span class="required">*</span></label>
          </th>
          <td>
            <select name="gedcom" id="gedcom" class="regular-text" required>
              <option value=""><?php _e('-- Select Tree --', 'heritagepress'); ?></option>
              <?php
              global $wpdb;
              $trees = $wpdb->get_results("SELECT gedcom, treename FROM {$wpdb->prefix}hp_trees ORDER BY treename");
              foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree->gedcom); ?>">
                  <?php echo esc_html($tree->treename); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr> <!-- Source ID -->
        <tr>
          <th scope="row">
            <label for="sourceID"><?php _e('Source ID', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="sourceID" id="sourceID" class="regular-text" data-message-id="sourceid-check-message" />
            <input type="button" class="button" value="<?php _e('Check', 'heritagepress'); ?>" onclick="HP_IDChecker.checkSourceID(document.getElementById('sourceID').value, document.getElementById('tree').value, 'sourceid-check-message');" />
            <div id="sourceid-check-message" style="margin-top: 5px;"></div>
            <p class="description"><?php _e('Leave blank to auto-generate', 'heritagepress'); ?></p>
          </td>
        </tr>
        <!-- Title -->
        <tr>
          <th scope="row">
            <label for="title"><?php _e('Title', 'heritagepress'); ?> <span class="required">*</span></label>
          </th>
          <td>
            <input type="text" name="title" id="title" class="regular-text" required />
            <p class="description"><?php _e('Full title of the source', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Abbreviated Title -->
        <tr>
          <th scope="row">
            <label for="shorttitle"><?php _e('Abbreviated Title', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="shorttitle" id="shorttitle" class="regular-text" />
            <p class="description"><?php _e('Shortened version of the title for citations', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Author -->
        <tr>
          <th scope="row">
            <label for="author"><?php _e('Author', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="author" id="author" class="regular-text" />
            <p class="description"><?php _e('Author(s) or creator(s) of the source', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Source Type -->
        <tr>
          <th scope="row">
            <label for="sourcetype"><?php _e('Source Type', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="sourcetype" id="sourcetype" class="regular-text" list="source-types" />
            <datalist id="source-types">
              <?php foreach ($all_types as $type): ?>
                <option value="<?php echo esc_attr($type); ?>">
                <?php endforeach; ?>
            </datalist>
            <p class="description"><?php _e('Type or category of source (e.g., Book, Article, Website)', 'heritagepress'); ?></p>
          </td>
        </tr> <!-- Publisher -->
        <tr>
          <th scope="row">
            <label for="publisher"><?php _e('Publisher', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="publisher" id="publisher" class="regular-text" />
            <p class="description"><?php _e('Publisher information', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Repository -->
        <tr>
          <th scope="row">
            <label for="repoID"><?php _e('Repository', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="repoID" id="repoID" class="regular-text">
              <option value=""><?php _e('-- Select Repository --', 'heritagepress'); ?></option>
              <?php foreach ($repositories as $repo): ?>
                <option value="<?php echo esc_attr($repo->repositoryID); ?>">
                  <?php echo esc_html($repo->repositoryname); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="description">
              <?php _e('Repository where this source is held', 'heritagepress'); ?>
              <a href="<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=add'); ?>" target="_blank">
                <?php _e('Add New Repository', 'heritagepress'); ?>
              </a>
            </p>
          </td>
        </tr>

        <!-- Call Number -->
        <tr>
          <th scope="row">
            <label for="callnum"><?php _e('Call Number', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="callnum" id="callnum" class="regular-text" />
            <p class="description"><?php _e('Repository call number or reference identifier', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Source Text (actualtext field) -->
        <tr>
          <th scope="row">
            <label for="actualtext"><?php _e('Source Text', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="actualtext" id="actualtext" class="large-text" rows="5"></textarea>
            <p class="description"><?php _e('Detailed description or transcription of the source content', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Comments -->
        <tr>
          <th scope="row">
            <label for="comments"><?php _e('Comments', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="comments" id="comments" class="large-text" rows="3"></textarea>
            <p class="description"><?php _e('Additional notes or comments about this source', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Quality -->
        <tr>
          <th scope="row">
            <label for="quality"><?php _e('Quality Rating', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="quality" id="quality">
              <option value=""><?php _e('-- Not Rated --', 'heritagepress'); ?></option>
              <option value="3"><?php _e('3 - Primary source, original document', 'heritagepress'); ?></option>
              <option value="2"><?php _e('2 - Secondary source, good reliability', 'heritagepress'); ?></option>
              <option value="1"><?php _e('1 - Questionable reliability', 'heritagepress'); ?></option>
              <option value="0"><?php _e('0 - Unreliable or unverified', 'heritagepress'); ?></option>
            </select>
            <p class="description"><?php _e('Quality assessment of the source reliability', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Volume, Page, Filename -->
        <tr>
          <th scope="row">
            <label><?php _e('Reference Details', 'heritagepress'); ?></label>
          </th>
          <td>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
              <div>
                <label for="volume"><?php _e('Volume:', 'heritagepress'); ?></label>
                <input type="text" name="volume" id="volume" class="small-text" />
              </div>
              <div>
                <label for="page"><?php _e('Page:', 'heritagepress'); ?></label>
                <input type="text" name="page" id="page" class="small-text" />
              </div>
              <div>
                <label for="filename"><?php _e('Filename:', 'heritagepress'); ?></label>
                <input type="text" name="filename" id="filename" class="regular-text" />
              </div>
            </div>
            <p class="description"><?php _e('Volume number, page reference, and associated filename if applicable', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Privacy Settings -->
        <tr>
          <th scope="row">
            <label><?php _e('Privacy Settings', 'heritagepress'); ?></label>
          </th>
          <td>
            <fieldset>
              <label>
                <input type="checkbox" name="secret" value="1" />
                <?php _e('Mark as private (restrict public access)', 'heritagepress'); ?>
              </label>
            </fieldset>
            <p class="description"><?php _e('Private sources are only visible to logged-in users with appropriate permissions', 'heritagepress'); ?></p>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit">
      <input type="submit" name="submit" class="button-primary" value="<?php _e('Add Source', 'heritagepress'); ?>" />
      <a href="<?php echo admin_url('admin.php?page=hp-sources'); ?>" class="button">
        <?php _e('Cancel', 'heritagepress'); ?>
      </a>
    </p>
  </form>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Auto-generate abbreviated title from full title
    $('#title').on('blur', function() {
      var title = $(this).val();
      var shortTitle = $('#shorttitle').val();

      if (title && !shortTitle) {
        // Generate abbreviated title (first 50 characters)
        var abbreviated = title.length > 50 ? title.substring(0, 47) + '...' : title;
        $('#shorttitle').val(abbreviated);
      }
    });

    // Form validation
    $('#add-source-form').on('submit', function(e) {
      var title = $('#title').val().trim();

      if (!title) {
        e.preventDefault();
        alert('<?php _e('Please enter a title for the source.', 'heritagepress'); ?>');
        $('#title').focus();
        return false;
      }
    });

    // Character counter for text areas
    $('textarea[name="sourcetext"], textarea[name="comments"]').on('input', function() {
      var maxLength = 5000;
      var currentLength = $(this).val().length;
      var remaining = maxLength - currentLength;

      var counterId = $(this).attr('name') + '-counter';

      if ($('#' + counterId).length === 0) {
        $(this).after('<div id="' + counterId + '" class="character-counter"></div>');
      }

      $('#' + counterId).text(remaining + ' <?php _e('characters remaining', 'heritagepress'); ?>');

      if (remaining < 0) {
        $('#' + counterId).css('color', 'red');
      } else if (remaining < 100) {
        $('#' + counterId).css('color', 'orange');
      } else {
        $('#' + counterId).css('color', 'green');
      }
    });
  });
</script>

<style>
  .required {
    color: red;
  }

  .character-counter {
    font-size: 11px;
    color: #666;
    margin-top: 5px;
  }

  .form-table th {
    width: 200px;
  }

  .form-table input[type="text"],
  .form-table textarea,
  .form-table select {
    width: 100%;
    max-width: 500px;
  }

  .form-table input.small-text {
    width: 80px;
  }

  .form-table input.regular-text {
    width: 300px;
  }

  .form-table .large-text {
    width: 500px;
  }
</style>
