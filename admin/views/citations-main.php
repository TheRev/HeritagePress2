<?php

/**
 * Citations Main Admin Interface
 * Complete citation management with tabbed navigation
 * Based on genealogy admin citations and add citation
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Check if managing citations for a specific person/event
$person_id = isset($_GET['personID']) ? sanitize_text_field($_GET['personID']) : '';
$family_id = isset($_GET['familyID']) ? sanitize_text_field($_GET['familyID']) : '';
$event_id = isset($_GET['eventID']) ? sanitize_text_field($_GET['eventID']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$note_id = isset($_GET['noteID']) ? sanitize_text_field($_GET['noteID']) : '';

// Determine person/family ID
$persfam_id = !empty($person_id) ? $person_id : $family_id;
$is_managing_specific = !empty($persfam_id) && !empty($tree);

// If managing specific citations, default to add tab
if ($is_managing_specific && $current_tab === 'browse') {
  $current_tab = 'manage';
}

?>

<div class="wrap heritagepress-citations">
  <h1>
    <?php if ($is_managing_specific): ?>
      <?php printf(__('Citations for %s', 'heritagepress'), esc_html($persfam_id)); ?>
    <?php else: ?>
      <?php _e('Citation Management', 'heritagepress'); ?>
    <?php endif; ?>
  </h1>

  <!-- Tab Navigation -->
  <h2 class="nav-tab-wrapper">
    <?php if ($is_managing_specific): ?> <a href="?page=hp-citations&tab=manage&personID=<?php echo esc_attr($person_id); ?>&familyID=<?php echo esc_attr($family_id); ?>&eventID=<?php echo esc_attr($event_id); ?>&tree=<?php echo esc_attr($tree); ?>&noteID=<?php echo esc_attr($note_id); ?>" class="nav-tab <?php echo $current_tab === 'manage' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Manage Citations', 'heritagepress'); ?>
      </a>
      <a href="?page=hp-citations&tab=add&personID=<?php echo esc_attr($person_id); ?>&familyID=<?php echo esc_attr($family_id); ?>&eventID=<?php echo esc_attr($event_id); ?>&tree=<?php echo esc_attr($tree); ?>&noteID=<?php echo esc_attr($note_id); ?>" class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Add Citation', 'heritagepress'); ?>
      </a>
    <?php else: ?>
      <a href="?page=hp-citations&tab=browse" class="nav-tab <?php echo $current_tab === 'browse' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Browse Citations', 'heritagepress'); ?>
      </a>
      <a href="?page=hp-citations&tab=search" class="nav-tab <?php echo $current_tab === 'search' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Search Citations', 'heritagepress'); ?>
      </a>
      <a href="?page=hp-citations&tab=add" class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Add Citation', 'heritagepress'); ?>
      </a>
    <?php endif; ?>
  </h2>

  <!-- Tab Content -->
  <div class="citations-content">
    <?php if ($current_tab === 'manage' && $is_managing_specific): ?>
      <?php include 'citations-manage.php'; ?>
    <?php elseif ($current_tab === 'add'): ?>
      <?php include 'citations-add.php'; ?>
    <?php elseif ($current_tab === 'search'): ?>
      <?php include 'citations-search.php'; ?>
    <?php else: ?>
      <?php include 'citations-browse.php'; ?>
    <?php endif; ?>
  </div>

</div>

<!-- Citation Management JavaScript -->
<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Initialize citation management
    window.HeritagePress = window.HeritagePress || {};
    window.HeritagePress.Citations = {

      // Add citation
      addCitation: function(formData) {
        $.post(ajaxurl, {
          action: 'hp_add_citation',
          nonce: $('#hp_citation_nonce').val(),
          ...formData
        }, function(response) {
          if (response.success) {
            alert('<?php _e('Citation added successfully!', 'heritagepress'); ?>');
            location.reload();
          } else {
            alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
          }
        });
      },

      // Update citation
      updateCitation: function(formData) {
        $.post(ajaxurl, {
          action: 'hp_update_citation',
          nonce: $('#hp_citation_nonce').val(),
          ...formData
        }, function(response) {
          if (response.success) {
            alert('<?php _e('Citation updated successfully!', 'heritagepress'); ?>');
            // Update display text if provided
            if (response.data.display) {
              $('#citation_' + formData.citationID + ' .citation-display').text(response.data.display);
            }
          } else {
            alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
          }
        });
      },

      // Delete citation
      deleteCitation: function(citationID, persfamID, gedcom, eventID) {
        if (!confirm('<?php _e('Are you sure you want to delete this citation?', 'heritagepress'); ?>')) {
          return false;
        }

        $.post(ajaxurl, {
          action: 'hp_delete_citation',
          nonce: $('#hp_citation_nonce').val(),
          citationID: citationID,
          persfamID: persfamID,
          gedcom: gedcom,
          eventID: eventID
        }, function(response) {
          if (response.success) {
            $('#citation_' + citationID).fadeOut(function() {
              $(this).remove();
            });

            // Update citation count if provided
            if (response.data.remaining_count !== undefined) {
              $('#citation-count').text(response.data.remaining_count);
              if (response.data.remaining_count === 0) {
                $('#citations-container').hide();
                $('#no-citations-message').show();
              }
            }
          } else {
            alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
          }
        });

        return false;
      },

      // Edit citation
      editCitation: function(citationID) {
        window.location.href = '?page=hp-citations&tab=edit&citationID=' + citationID;
        return false;
      },

      // Search sources for citation form
      searchSources: function(searchTerm, gedcom, callback) {
        $.post(ajaxurl, {
          action: 'hp_search_sources',
          nonce: $('#hp_citation_nonce').val(),
          search: searchTerm,
          gedcom: gedcom
        }, function(response) {
          if (response.success && typeof callback === 'function') {
            callback(response.data.sources);
          }
        });
      },

      // Create new source
      createSource: function(sourceData, callback) {
        $.post(ajaxurl, {
          action: 'hp_create_source',
          nonce: $('#hp_citation_nonce').val(),
          ...sourceData
        }, function(response) {
          if (response.success && typeof callback === 'function') {
            callback(response.data);
          } else {
            alert('<?php _e('Error creating source:', 'heritagepress'); ?> ' + response.data);
          }
        });
      }
    };

    // Global citation functions for compatibility
    window.addCitation = function(formData) {
      return HeritagePress.Citations.addCitation(formData);
    };

    window.updateCitation = function(form) {
      const formData = new FormData(form);
      const data = {};
      for (let [key, value] of formData.entries()) {
        data[key] = value;
      }
      HeritagePress.Citations.updateCitation(data);
      return false;
    };

    window.deleteCitation = function(citationID, persfamID, gedcom, eventID) {
      return HeritagePress.Citations.deleteCitation(citationID, persfamID, gedcom, eventID);
    };

    window.editCitation = function(citationID) {
      return HeritagePress.Citations.editCitation(citationID);
    };

  });
</script>

<!-- Citation Management CSS -->
<style>
  .citations-content {
    margin-top: 20px;
  }

  .citation-item {
    border: 1px solid #ddd;
    margin-bottom: 10px;
    padding: 15px;
    background: #fff;
  }

  .citation-item .citation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
  }

  .citation-item .citation-actions {
    display: flex;
    gap: 5px;
  }

  .citation-item .citation-display {
    font-weight: bold;
    color: #333;
  }

  .citation-item .citation-details {
    margin-top: 10px;
    color: #666;
    font-size: 0.9em;
  }

  .citation-form {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 20px;
  }

  .citation-form .form-table th {
    width: 150px;
    text-align: left;
  }

  .citation-search {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 20px;
  }

  .citation-search .search-form {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .citation-results {
    margin-top: 20px;
  }

  .smallicon {
    display: inline-block;
    width: 16px;
    height: 16px;
    background-size: contain;
    background-repeat: no-repeat;
    text-decoration: none;
    margin: 0 2px;
    vertical-align: middle;
  }

  .admin-edit-icon {
    background-image: url('<?php echo plugins_url('assets/images/edit.gif', HERITAGEPRESS_PLUGIN_FILE); ?>');
  }

  .admin-delete-icon {
    background-image: url('<?php echo plugins_url('assets/images/delete.gif', HERITAGEPRESS_PLUGIN_FILE); ?>');
  }

  .citation-count {
    background: #0073aa;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.8em;
  }

  .no-citations {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
  }

  /* Sortable citations */
  .sortrow {
    margin-bottom: 5px;
  }

  .dragarea {
    cursor: move;
    text-align: center;
    width: 20px;
    padding: 5px;
  }

  .lightback {
    background-color: #f9f9f9;
  }
</style>

<?php wp_nonce_field('hp_citation_nonce', 'hp_citation_nonce'); ?>
