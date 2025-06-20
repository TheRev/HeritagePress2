<?php

/**
 * Edit Source View
 *
 * @package HeritagePress
 * @subpackage Admin/Views
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get source ID
$source_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$source_id) {
  wp_die(__('Invalid source ID.', 'heritagepress'));
}

// Get source data
global $wpdb;
$source = $wpdb->get_row($wpdb->prepare(
  "SELECT s.*, r.repositoryname
     FROM {$wpdb->prefix}hp_sources s
     LEFT JOIN {$wpdb->prefix}hp_repositories r ON s.repositoryID = r.repositoryID
     WHERE s.sourceID = %d",
  $source_id
));

if (!$source) {
  wp_die(__('Source not found.', 'heritagepress'));
}

// Get repositories for dropdown
$repositories = $wpdb->get_results("SELECT repositoryID, repositoryname FROM {$wpdb->prefix}hp_repositories ORDER BY repositoryname");

// Get source types for reference
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

// Get citation count
$citation_count = $wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM {$wpdb->prefix}hp_citations WHERE sourceID = %d",
  $source_id
));
?>

<div class="wrap">
  <h1>
    <?php _e('Edit Source', 'heritagepress'); ?>
    <a href="<?php echo home_url('/source/' . $source->sourceID); ?>" class="page-title-action" target="_blank">
      <?php _e('View Source', 'heritagepress'); ?>
    </a>
  </h1>

  <?php if ($citation_count > 0): ?>
    <div class="notice notice-info">
      <p>
        <?php printf(
          _n(
            'This source has %d citation. Changes will affect all citations.',
            'This source has %d citations. Changes will affect all citations.',
            $citation_count,
            'heritagepress'
          ),
          $citation_count
        ); ?>
        <a href="<?php echo admin_url('admin.php?page=hp-citations&source_id=' . $source_id); ?>">
          <?php _e('View Citations', 'heritagepress'); ?>
        </a>
      </p>
    </div>
  <?php endif; ?>

  <form method="post" action="<?php echo admin_url('admin.php?page=hp-sources&action=edit&id=' . $source_id); ?>" id="edit-source-form">
    <?php wp_nonce_field('hp_edit_source', 'hp_source_nonce'); ?>

    <table class="form-table">
      <tbody>
        <!-- Source ID (readonly) -->
        <tr>
          <th scope="row">
            <label><?php _e('Source ID', 'heritagepress'); ?></label>
          </th>
          <td>
            <strong><?php echo esc_html($source->sourceID); ?></strong>
            <p class="description"><?php _e('Internal system ID', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Title -->
        <tr>
          <th scope="row">
            <label for="title"><?php _e('Title', 'heritagepress'); ?> <span class="required">*</span></label>
          </th>
          <td>
            <input type="text" name="title" id="title" class="regular-text"
              value="<?php echo esc_attr($source->title); ?>" required />
            <p class="description"><?php _e('Full title of the source', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Abbreviated Title -->
        <tr>
          <th scope="row">
            <label for="shorttitle"><?php _e('Abbreviated Title', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="shorttitle" id="shorttitle" class="regular-text"
              value="<?php echo esc_attr($source->shorttitle); ?>" />
            <p class="description"><?php _e('Shortened version of the title for citations', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Author -->
        <tr>
          <th scope="row">
            <label for="author"><?php _e('Author', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="author" id="author" class="regular-text"
              value="<?php echo esc_attr($source->author); ?>" />
            <p class="description"><?php _e('Author(s) or creator(s) of the source', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Source Type -->
        <tr>
          <th scope="row">
            <label for="sourcetype"><?php _e('Source Type', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="sourcetype" id="sourcetype" class="regular-text"
              value="<?php echo esc_attr($source->sourcetype); ?>" list="source-types" />
            <datalist id="source-types">
              <?php foreach ($all_types as $type): ?>
                <option value="<?php echo esc_attr($type); ?>">
                <?php endforeach; ?>
            </datalist>
            <p class="description"><?php _e('Type or category of source (e.g., Book, Article, Website)', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Publisher Information -->
        <tr>
          <th scope="row">
            <label for="publisherinfo"><?php _e('Publisher Information', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="publisherinfo" id="publisherinfo" class="large-text" rows="3"><?php echo esc_textarea($source->publisherinfo); ?></textarea>
            <p class="description"><?php _e('Publication details (publisher, place, date, etc.)', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Repository -->
        <tr>
          <th scope="row">
            <label for="repositoryID"><?php _e('Repository', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="repositoryID" id="repositoryID" class="regular-text">
              <option value=""><?php _e('-- Select Repository --', 'heritagepress'); ?></option>
              <?php foreach ($repositories as $repo): ?>
                <option value="<?php echo esc_attr($repo->repositoryID); ?>"
                  <?php selected($source->repositoryID, $repo->repositoryID); ?>>
                  <?php echo esc_html($repo->repositoryname); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="description">
              <?php _e('Repository where this source is held', 'heritagepress'); ?>
              <?php if ($source->repositoryname): ?>
                | <a href="<?php echo admin_url('admin.php?page=hp-repositories&action=edit&id=' . $source->repositoryID); ?>">
                  <?php _e('Edit Repository', 'heritagepress'); ?>
                </a>
              <?php endif; ?>
              | <a href="<?php echo admin_url('admin.php?page=hp-repositories&action=add'); ?>" target="_blank">
                <?php _e('Add New Repository', 'heritagepress'); ?>
              </a>
            </p>
          </td>
        </tr>

        <!-- Call Number -->
        <tr>
          <th scope="row">
            <label for="callnumber"><?php _e('Call Number', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="callnumber" id="callnumber" class="regular-text"
              value="<?php echo esc_attr($source->callnumber); ?>" />
            <p class="description"><?php _e('Repository call number or reference identifier', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Source Text -->
        <tr>
          <th scope="row">
            <label for="sourcetext"><?php _e('Source Text', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="sourcetext" id="sourcetext" class="large-text" rows="5"><?php echo esc_textarea($source->sourcetext); ?></textarea>
            <p class="description"><?php _e('Detailed description or transcription of the source content', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Comments -->
        <tr>
          <th scope="row">
            <label for="comments"><?php _e('Comments', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="comments" id="comments" class="large-text" rows="3"><?php echo esc_textarea($source->comments); ?></textarea>
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
              <option value="" <?php selected($source->quality, ''); ?>><?php _e('-- Not Rated --', 'heritagepress'); ?></option>
              <option value="3" <?php selected($source->quality, '3'); ?>><?php _e('3 - Primary source, original document', 'heritagepress'); ?></option>
              <option value="2" <?php selected($source->quality, '2'); ?>><?php _e('2 - Secondary source, good reliability', 'heritagepress'); ?></option>
              <option value="1" <?php selected($source->quality, '1'); ?>><?php _e('1 - Questionable reliability', 'heritagepress'); ?></option>
              <option value="0" <?php selected($source->quality, '0'); ?>><?php _e('0 - Unreliable or unverified', 'heritagepress'); ?></option>
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
                <input type="text" name="volume" id="volume" class="small-text"
                  value="<?php echo esc_attr($source->volume); ?>" />
              </div>
              <div>
                <label for="page"><?php _e('Page:', 'heritagepress'); ?></label>
                <input type="text" name="page" id="page" class="small-text"
                  value="<?php echo esc_attr($source->page); ?>" />
              </div>
              <div>
                <label for="filename"><?php _e('Filename:', 'heritagepress'); ?></label>
                <input type="text" name="filename" id="filename" class="regular-text"
                  value="<?php echo esc_attr($source->filename); ?>" />
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
                <input type="checkbox" name="secret" value="1" <?php checked($source->secret, '1'); ?> />
                <?php _e('Mark as private (restrict public access)', 'heritagepress'); ?>
              </label>
            </fieldset>
            <p class="description"><?php _e('Private sources are only visible to logged-in users with appropriate permissions', 'heritagepress'); ?></p>
          </td>
        </tr>

        <!-- Last Updated -->
        <tr>
          <th scope="row">
            <label><?php _e('Last Updated', 'heritagepress'); ?></label>
          </th>
          <td>
            <?php if ($source->changedate): ?>
              <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($source->changedate))); ?>
            <?php else: ?>
              <?php _e('Never', 'heritagepress'); ?>
            <?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit">
      <input type="submit" name="submit" class="button-primary" value="<?php _e('Update Source', 'heritagepress'); ?>" />
      <a href="<?php echo admin_url('admin.php?page=hp-sources'); ?>" class="button">
        <?php _e('Back to Sources', 'heritagepress'); ?>
      </a>
      <?php if ($citation_count == 0): ?>
        <input type="submit" name="delete" class="button button-link-delete"
          value="<?php _e('Delete Source', 'heritagepress'); ?>"
          onclick="return confirm('<?php _e('Are you sure you want to delete this source? This action cannot be undone.', 'heritagepress'); ?>');" />
      <?php endif; ?>
    </p>
  </form>

  <?php if ($citation_count > 0): ?>
    <div class="postbox">
      <h3 class="hndle"><?php _e('Citations Using This Source', 'heritagepress'); ?></h3>
      <div class="inside">
        <p>
          <?php printf(
            _n(
              'This source is referenced by %d citation.',
              'This source is referenced by %d citations.',
              $citation_count,
              'heritagepress'
            ),
            $citation_count
          ); ?>
        </p>
        <p>
          <a href="<?php echo admin_url('admin.php?page=hp-citations&source_id=' . $source_id); ?>" class="button">
            <?php _e('Manage Citations', 'heritagepress'); ?>
          </a>
        </p>
      </div>
    </div>
  <?php endif; ?>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Form validation
    $('#edit-source-form').on('submit', function(e) {
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

    // Trigger character counter on load
    $('textarea[name="sourcetext"], textarea[name="comments"]').trigger('input');
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

  .postbox {
    margin-top: 20px;
  }

  .postbox .hndle {
    padding: 10px 15px;
    margin: 0;
    background: #f7f7f7;
    border-bottom: 1px solid #ddd;
  }

  .postbox .inside {
    padding: 15px;
  }
</style>
