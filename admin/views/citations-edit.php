<?php

/**
 * Edit Citation View for HeritagePress
 *
 * This file provides the edit citation interface for the WordPress admin.
 * Ported from TNG admin_editcitation.php
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get citation ID from URL parameter
$citation_id = isset($_GET['citation_id']) ? intval($_GET['citation_id']) : 0;

if (!$citation_id) {
  wp_die(__('Invalid citation ID.', 'heritagepress'));
}

// Get citation data
global $wpdb;
$citation = $wpdb->get_row($wpdb->prepare("
    SELECT c.*, s.title as source_title, s.author, s.publisher
    FROM {$wpdb->prefix}hp_citations c
    LEFT JOIN {$wpdb->prefix}hp_sources s ON c.sourceID = s.sourceID
    WHERE c.citationID = %d
", $citation_id));

if (!$citation) {
  wp_die(__('Citation not found.', 'heritagepress'));
}

// Get event types
$event_types = $wpdb->get_results("SELECT DISTINCT eventtype FROM {$wpdb->prefix}hp_events ORDER BY eventtype");

// Get sources for dropdown
$sources = $wpdb->get_results("SELECT sourceID, title, author FROM {$wpdb->prefix}hp_sources ORDER BY title");
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Edit Citation', 'heritagepress'); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=hp-citations'); ?>" class="page-title-action">
    <?php esc_html_e('Back to Citations', 'heritagepress'); ?>
  </a>
  <hr class="wp-header-end">

  <div id="citation-edit-container">
    <form id="edit-citation-form" method="post">
      <?php wp_nonce_field('hp_edit_citation', 'hp_citation_nonce'); ?>
      <input type="hidden" name="action" value="hp_edit_citation">
      <input type="hidden" name="citation_id" value="<?php echo esc_attr($citation->citationID); ?>">

      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="source_id"><?php esc_html_e('Source', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <select name="source_id" id="source_id" class="regular-text" required>
                <option value=""><?php esc_html_e('Select a source...', 'heritagepress'); ?></option>
                <?php foreach ($sources as $source): ?>
                  <option value="<?php echo esc_attr($source->sourceID); ?>"
                    <?php selected($citation->sourceID, $source->sourceID); ?>>
                    <?php echo esc_html($source->title); ?>
                    <?php if ($source->author): ?>
                      - <?php echo esc_html($source->author); ?>
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" id="find-source-btn" class="button"><?php esc_html_e('Find Source', 'heritagepress'); ?></button>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="description"><?php esc_html_e('Description', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="description" id="description"
                value="<?php echo esc_attr($citation->description); ?>"
                class="regular-text">
              <p class="description"><?php esc_html_e('Brief description of this citation.', 'heritagepress'); ?></p>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="page"><?php esc_html_e('Page/Location', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="page" id="page"
                value="<?php echo esc_attr($citation->page); ?>"
                class="regular-text">
              <p class="description"><?php esc_html_e('Page number, location, or other reference within the source.', 'heritagepress'); ?></p>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="quality"><?php esc_html_e('Quality', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="quality" id="quality">
                <option value=""><?php esc_html_e('Not specified', 'heritagepress'); ?></option>
                <option value="3" <?php selected($citation->quality, '3'); ?>><?php esc_html_e('Primary evidence', 'heritagepress'); ?></option>
                <option value="2" <?php selected($citation->quality, '2'); ?>><?php esc_html_e('Secondary evidence', 'heritagepress'); ?></option>
                <option value="1" <?php selected($citation->quality, '1'); ?>><?php esc_html_e('Questionable evidence', 'heritagepress'); ?></option>
                <option value="0" <?php selected($citation->quality, '0'); ?>><?php esc_html_e('Unreliable evidence', 'heritagepress'); ?></option>
              </select>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="citetext"><?php esc_html_e('Citation Text', 'heritagepress'); ?></label>
            </th>
            <td>
              <textarea name="citetext" id="citetext" rows="4" class="large-text"><?php echo esc_textarea($citation->citetext); ?></textarea>
              <p class="description"><?php esc_html_e('Full citation text or additional notes.', 'heritagepress'); ?></p>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="citedate"><?php esc_html_e('Citation Date', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="date" name="citedate" id="citedate"
                value="<?php echo esc_attr($citation->citedate); ?>"
                class="regular-text">
              <p class="description"><?php esc_html_e('Date when this citation was created or verified.', 'heritagepress'); ?></p>
            </td>
          </tr>

          <?php if ($citation->personID || $citation->familyID || $citation->eventID): ?>
            <tr>
              <th scope="row">
                <label><?php esc_html_e('Linked To', 'heritagepress'); ?></label>
              </th>
              <td>
                <?php if ($citation->personID): ?>
                  <p><strong><?php esc_html_e('Person:', 'heritagepress'); ?></strong>
                    <?php
                    $person = $wpdb->get_row($wpdb->prepare("
                                    SELECT firstname, lastname FROM {$wpdb->prefix}hp_people
                                    WHERE personID = %s
                                ", $citation->personID));
                    if ($person) {
                      echo esc_html($person->firstname . ' ' . $person->lastname);
                    }
                    ?>
                  </p>
                <?php endif; ?>

                <?php if ($citation->familyID): ?>
                  <p><strong><?php esc_html_e('Family:', 'heritagepress'); ?></strong>
                    <?php echo esc_html($citation->familyID); ?>
                  </p>
                <?php endif; ?>

                <?php if ($citation->eventID): ?>
                  <p><strong><?php esc_html_e('Event:', 'heritagepress'); ?></strong>
                    <?php
                    $event = $wpdb->get_row($wpdb->prepare("
                                    SELECT eventtype, eventdate FROM {$wpdb->prefix}hp_events
                                    WHERE eventID = %s
                                ", $citation->eventID));
                    if ($event) {
                      echo esc_html($event->eventtype . ' - ' . $event->eventdate);
                    }
                    ?>
                  </p>
                <?php endif; ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary"
          value="<?php esc_attr_e('Update Citation', 'heritagepress'); ?>">
        <a href="<?php echo admin_url('admin.php?page=hp-citations'); ?>" class="button">
          <?php esc_html_e('Cancel', 'heritagepress'); ?>
        </a>
      </p>
    </form>
  </div>

  <!-- Source Finder Modal -->
  <div id="source-finder-modal" class="hp-modal" style="display: none;">
    <div class="hp-modal-content">
      <div class="hp-modal-header">
        <h3><?php esc_html_e('Find Source', 'heritagepress'); ?></h3>
        <span class="hp-modal-close">&times;</span>
      </div>
      <div class="hp-modal-body">
        <div class="source-search">
          <input type="text" id="source-search-input" placeholder="<?php esc_attr_e('Search sources...', 'heritagepress'); ?>" class="regular-text">
          <button type="button" id="source-search-btn" class="button"><?php esc_html_e('Search', 'heritagepress'); ?></button>
        </div>
        <div id="source-search-results"></div>
      </div>
    </div>
  </div>

  <div id="citation-messages"></div>
</div>

<style>
  .hp-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .hp-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 4px;
  }

  .hp-modal-header {
    padding: 15px 20px;
    background-color: #f1f1f1;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .hp-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .hp-modal-close:hover {
    color: #000;
  }

  .hp-modal-body {
    padding: 20px;
  }

  .source-search {
    margin-bottom: 20px;
  }

  .source-search input {
    margin-right: 10px;
  }

  .source-result {
    padding: 10px;
    border: 1px solid #ddd;
    margin-bottom: 10px;
    cursor: pointer;
    border-radius: 4px;
  }

  .source-result:hover {
    background-color: #f9f9f9;
  }

  .source-result h4 {
    margin: 0 0 5px 0;
  }

  .source-result p {
    margin: 0;
    color: #666;
    font-size: 12px;
  }

  .required {
    color: #d63638;
  }

  #citation-messages {
    margin-top: 20px;
  }

  .notice {
    margin: 5px 0 15px;
    padding: 1px 12px;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Source finder modal
    $('#find-source-btn').click(function() {
      $('#source-finder-modal').show();
    });

    $('.hp-modal-close').click(function() {
      $('.hp-modal').hide();
    });

    $(window).click(function(event) {
      if (event.target.classList.contains('hp-modal')) {
        $('.hp-modal').hide();
      }
    });

    // Source search
    $('#source-search-btn').click(function() {
      var searchTerm = $('#source-search-input').val();
      if (searchTerm.length < 2) {
        alert('<?php esc_js_e('Please enter at least 2 characters to search.', 'heritagepress'); ?>');
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_search_sources',
          search_term: searchTerm,
          nonce: '<?php echo wp_create_nonce('hp_search_sources'); ?>'
        },
        success: function(response) {
          if (response.success) {
            displaySourceResults(response.data);
          } else {
            $('#source-search-results').html('<p>' + response.data + '</p>');
          }
        },
        error: function() {
          $('#source-search-results').html('<p><?php esc_js_e('Error searching sources.', 'heritagepress'); ?></p>');
        }
      });
    });

    function displaySourceResults(sources) {
      var html = '';
      if (sources.length === 0) {
        html = '<p><?php esc_js_e('No sources found.', 'heritagepress'); ?></p>';
      } else {
        $.each(sources, function(index, source) {
          html += '<div class="source-result" data-source-id="' + source.sourceID + '">';
          html += '<h4>' + escapeHtml(source.title) + '</h4>';
          if (source.author) {
            html += '<p><strong><?php esc_js_e('Author:', 'heritagepress'); ?></strong> ' + escapeHtml(source.author) + '</p>';
          }
          if (source.publisher) {
            html += '<p><strong><?php esc_js_e('Publisher:', 'heritagepress'); ?></strong> ' + escapeHtml(source.publisher) + '</p>';
          }
          html += '</div>';
        });
      }
      $('#source-search-results').html(html);

      // Handle source selection
      $('.source-result').click(function() {
        var sourceId = $(this).data('source-id');
        var sourceTitle = $(this).find('h4').text();

        $('#source_id').val(sourceId);
        $('#source-finder-modal').hide();
      });
    }

    // Form submission
    $('#edit-citation-form').submit(function(e) {
      e.preventDefault();

      if (!$('#source_id').val()) {
        alert('<?php esc_js_e('Please select a source.', 'heritagepress'); ?>');
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
          if (response.success) {
            $('#citation-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
            setTimeout(function() {
              window.location.href = '<?php echo admin_url('admin.php?page=hp-citations'); ?>';
            }, 1500);
          } else {
            $('#citation-messages').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
          }
        },
        error: function() {
          $('#citation-messages').html('<div class="notice notice-error"><p><?php esc_js_e('Error updating citation.', 'heritagepress'); ?></p></div>');
        }
      });
    });

    function escapeHtml(text) {
      var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, function(m) {
        return map[m];
      });
    }
  });
</script>
