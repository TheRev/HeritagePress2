<?php

/**
 * Merge Sources View
 *
 * @package HeritagePress
 * @subpackage Admin/Views
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get source IDs to merge
$source_ids = isset($_GET['ids']) ? explode(',', sanitize_text_field($_GET['ids'])) : array();
$source_ids = array_map('intval', array_filter($source_ids));

if (count($source_ids) < 2) {
  wp_die(__('Please select at least 2 sources to merge.', 'heritagepress'));
}

// Get source data
global $wpdb;
$placeholders = implode(',', array_fill(0, count($source_ids), '%d'));
$sources = $wpdb->get_results($wpdb->prepare(
  "SELECT s.*, r.repositoryname,
     (SELECT COUNT(*) FROM {$wpdb->prefix}hp_citations WHERE sourceID = s.sourceID) as citation_count
     FROM {$wpdb->prefix}hp_sources s
     LEFT JOIN {$wpdb->prefix}hp_repositories r ON s.repositoryID = r.repositoryID
     WHERE s.sourceID IN ($placeholders)
     ORDER BY s.sourceID",
  ...$source_ids
));

if (count($sources) < 2) {
  wp_die(__('Unable to load sources for merging.', 'heritagepress'));
}

// Calculate total citations
$total_citations = array_sum(wp_list_pluck($sources, 'citation_count'));
?>

<div class="wrap">
  <h1><?php _e('Merge Sources', 'heritagepress'); ?></h1>

  <div class="notice notice-warning">
    <p>
      <strong><?php _e('Warning:', 'heritagepress'); ?></strong>
      <?php printf(
        _n(
          'You are about to merge %d source. This action cannot be undone.',
          'You are about to merge %d sources. This action cannot be undone.',
          count($sources),
          'heritagepress'
        ),
        count($sources)
      ); ?>
    </p>
    <?php if ($total_citations > 0): ?>
      <p>
        <?php printf(
          _n(
            'This will affect %d citation.',
            'This will affect %d citations.',
            $total_citations,
            'heritagepress'
          ),
          $total_citations
        ); ?>
      </p>
    <?php endif; ?>
  </div>

  <form method="post" action="<?php echo admin_url('admin.php?page=hp-sources&action=merge'); ?>" id="merge-sources-form">
    <?php wp_nonce_field('hp_merge_sources', 'hp_sources_nonce'); ?>

    <!-- Hidden source IDs -->
    <?php foreach ($source_ids as $id): ?>
      <input type="hidden" name="source_ids[]" value="<?php echo esc_attr($id); ?>" />
    <?php endforeach; ?>

    <h2><?php _e('Select Master Source', 'heritagepress'); ?></h2>
    <p class="description">
      <?php _e('Choose which source should be kept as the master record. All other sources will be merged into this one and then deleted.', 'heritagepress'); ?>
    </p>

    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th class="check-column"><?php _e('Master', 'heritagepress'); ?></th>
          <th><?php _e('Source ID', 'heritagepress'); ?></th>
          <th><?php _e('Title', 'heritagepress'); ?></th>
          <th><?php _e('Author', 'heritagepress'); ?></th>
          <th><?php _e('Type', 'heritagepress'); ?></th>
          <th><?php _e('Repository', 'heritagepress'); ?></th>
          <th><?php _e('Citations', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sources as $index => $source): ?>
          <tr>
            <td class="check-column">
              <input type="radio" name="master_source_id" value="<?php echo esc_attr($source->sourceID); ?>"
                <?php checked($index, 0); ?> required />
            </td>
            <td><?php echo esc_html($source->sourceID); ?></td>
            <td>
              <strong><?php echo esc_html($source->title ?: __('(No Title)', 'heritagepress')); ?></strong>
              <?php if ($source->shorttitle): ?>
                <br><em><?php echo esc_html($source->shorttitle); ?></em>
              <?php endif; ?>
            </td>
            <td><?php echo esc_html($source->author ?: '-'); ?></td>
            <td><?php echo esc_html($source->sourcetype ?: '-'); ?></td>
            <td>
              <?php if ($source->repositoryname): ?>
                <?php echo esc_html($source->repositoryname); ?>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?php echo intval($source->citation_count); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h2><?php _e('Merge Options', 'heritagepress'); ?></h2>

    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label><?php _e('Citation Handling', 'heritagepress'); ?></label>
          </th>
          <td>
            <fieldset>
              <label>
                <input type="radio" name="citation_handling" value="move_all" checked />
                <?php _e('Move all citations to the master source', 'heritagepress'); ?>
              </label>
              <br>
              <label>
                <input type="radio" name="citation_handling" value="merge_duplicates" />
                <?php _e('Move citations and merge duplicate citations', 'heritagepress'); ?>
              </label>
            </fieldset>
            <p class="description">
              <?php _e('Choose how to handle citations from the sources being merged.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label><?php _e('Field Merging', 'heritagepress'); ?></label>
          </th>
          <td>
            <fieldset>
              <label>
                <input type="radio" name="field_handling" value="keep_master" checked />
                <?php _e('Keep master source fields unchanged', 'heritagepress'); ?>
              </label>
              <br>
              <label>
                <input type="radio" name="field_handling" value="merge_empty" />
                <?php _e('Fill empty master fields with data from other sources', 'heritagepress'); ?>
              </label>
              <br>
              <label>
                <input type="radio" name="field_handling" value="merge_all" />
                <?php _e('Combine all field data (may result in duplicate information)', 'heritagepress'); ?>
              </label>
            </fieldset>
            <p class="description">
              <?php _e('Choose how to handle field data when merging sources.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="merge_notes"><?php _e('Merge Notes', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="merge_notes" id="merge_notes" class="large-text" rows="3"
              placeholder="<?php _e('Optional notes about this merge operation...', 'heritagepress'); ?>"></textarea>
            <p class="description"><?php _e('These notes will be added to the master source comments.', 'heritagepress'); ?></p>
          </td>
        </tr>
      </tbody>
    </table>

    <h2><?php _e('Preview Changes', 'heritagepress'); ?></h2>

    <div id="merge-preview">
      <p class="description">
        <?php _e('Select a master source and merge options to preview the changes.', 'heritagepress'); ?>
      </p>
    </div>

    <p class="submit">
      <input type="submit" name="submit" class="button-primary"
        value="<?php _e('Merge Sources', 'heritagepress'); ?>"
        onclick="return confirm('<?php _e('Are you sure you want to merge these sources? This action cannot be undone.', 'heritagepress'); ?>');" />
      <a href="<?php echo admin_url('admin.php?page=hp-sources'); ?>" class="button">
        <?php _e('Cancel', 'heritagepress'); ?>
      </a>
    </p>
  </form>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    var sources = <?php echo json_encode($sources); ?>;

    function updatePreview() {
      var masterId = $('input[name="master_source_id"]:checked').val();
      var fieldHandling = $('input[name="field_handling"]:checked').val();
      var citationHandling = $('input[name="citation_handling"]:checked').val();

      if (!masterId) {
        $('#merge-preview').html('<p class="description"><?php _e('Please select a master source.', 'heritagepress'); ?></p>');
        return;
      }

      var masterSource = sources.find(function(s) {
        return s.sourceID == masterId;
      });
      var otherSources = sources.filter(function(s) {
        return s.sourceID != masterId;
      });

      var html = '<div class="merge-preview-content">';
      html += '<h4><?php _e('Master Source (will be kept):', 'heritagepress'); ?></h4>';
      html += '<div class="source-preview master-source">';
      html += '<strong>' + (masterSource.title || '<?php _e('(No Title)', 'heritagepress'); ?>') + '</strong><br>';
      html += '<?php _e('ID:', 'heritagepress'); ?> ' + masterSource.sourceID + '<br>';
      if (masterSource.author) html += '<?php _e('Author:', 'heritagepress'); ?> ' + masterSource.author + '<br>';
      if (masterSource.sourcetype) html += '<?php _e('Type:', 'heritagepress'); ?> ' + masterSource.sourcetype + '<br>';
      html += '<?php _e('Citations:', 'heritagepress'); ?> ' + masterSource.citation_count;
      html += '</div>';

      html += '<h4><?php _e('Sources to be merged and deleted:', 'heritagepress'); ?></h4>';
      otherSources.forEach(function(source) {
        html += '<div class="source-preview other-source">';
        html += '<strong>' + (source.title || '<?php _e('(No Title)', 'heritagepress'); ?>') + '</strong><br>';
        html += '<?php _e('ID:', 'heritagepress'); ?> ' + source.sourceID + '<br>';
        if (source.author) html += '<?php _e('Author:', 'heritagepress'); ?> ' + source.author + '<br>';
        if (source.sourcetype) html += '<?php _e('Type:', 'heritagepress'); ?> ' + source.sourcetype + '<br>';
        html += '<?php _e('Citations:', 'heritagepress'); ?> ' + source.citation_count;
        html += '</div>';
      });

      html += '<h4><?php _e('After Merge:', 'heritagepress'); ?></h4>';
      html += '<ul>';

      var totalCitations = sources.reduce(function(sum, s) {
        return sum + parseInt(s.citation_count);
      }, 0);
      html += '<li>' + totalCitations + ' <?php _e('citations will reference the master source', 'heritagepress'); ?></li>';

      if (fieldHandling === 'merge_empty') {
        html += '<li><?php _e('Empty fields in master source will be filled with data from other sources', 'heritagepress'); ?></li>';
      } else if (fieldHandling === 'merge_all') {
        html += '<li><?php _e('Field data from all sources will be combined', 'heritagepress'); ?></li>';
      } else {
        html += '<li><?php _e('Master source fields will remain unchanged', 'heritagepress'); ?></li>';
      }

      if (citationHandling === 'merge_duplicates') {
        html += '<li><?php _e('Duplicate citations will be merged if possible', 'heritagepress'); ?></li>';
      }

      html += '<li>' + (otherSources.length) + ' <?php _e('source(s) will be permanently deleted', 'heritagepress'); ?></li>';
      html += '</ul>';
      html += '</div>';

      $('#merge-preview').html(html);
    }

    // Update preview when options change
    $('input[name="master_source_id"], input[name="field_handling"], input[name="citation_handling"]').on('change', updatePreview);

    // Initial preview
    updatePreview();
  });
</script>

<style>
  .merge-preview-content {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
  }

  .source-preview {
    background: white;
    border: 1px solid #ddd;
    padding: 10px;
    margin: 5px 0;
    border-radius: 3px;
  }

  .master-source {
    border-left: 4px solid #2ea2cc;
  }

  .other-source {
    border-left: 4px solid #dc3232;
  }

  .form-table th {
    width: 200px;
  }

  .wp-list-table .check-column {
    width: 60px;
  }

  .notice.notice-warning {
    border-left-color: #ffb900;
  }
</style>
