<?php

/**
 * Notes Management Main View
 * Display and manage notes in a tabbed interface
 * Based on note management pages
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
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

// Determine person/family ID
$persfam_id = !empty($person_id) ? $person_id : $family_id;
$is_managing_specific = !empty($persfam_id) && !empty($tree);

// If managing specific notes, default to manage tab
if ($is_managing_specific && $current_tab === 'browse') {
  $current_tab = 'manage';
}

?>

<div class="wrap heritagepress-notes">
  <h1>
    <?php if ($is_managing_specific): ?>
      <?php printf(__('Notes for %s', 'heritagepress'), esc_html($persfam_id)); ?>
    <?php else: ?>
      <?php _e('Note Management', 'heritagepress'); ?>
    <?php endif; ?>
  </h1>

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper wp-clearfix">
    <?php if (!$is_managing_specific): ?>
      <a href="?page=heritagepress-notes&tab=browse"
        class="nav-tab <?php echo $current_tab === 'browse' ? 'nav-tab-active' : ''; ?>">
        <?php _e('All Notes', 'heritagepress'); ?>
      </a>
    <?php endif; ?>

    <a href="?page=heritagepress-notes&tab=add<?php echo $is_managing_specific ? '&personID=' . urlencode($person_id) . '&familyID=' . urlencode($family_id) . '&eventID=' . urlencode($event_id) . '&tree=' . urlencode($tree) : ''; ?>"
      class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Add Note', 'heritagepress'); ?>
    </a>

    <?php if ($is_managing_specific): ?>
      <a href="?page=heritagepress-notes&tab=manage&personID=<?php echo urlencode($person_id); ?>&familyID=<?php echo urlencode($family_id); ?>&eventID=<?php echo urlencode($event_id); ?>&tree=<?php echo urlencode($tree); ?>"
        class="nav-tab <?php echo $current_tab === 'manage' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Manage Notes', 'heritagepress'); ?>
      </a>
    <?php endif; ?>
  </nav>

  <!-- Tab Content -->
  <div class="tab-content">
    <?php if ($current_tab === 'browse' && !$is_managing_specific): ?>
      <!-- Browse All Notes -->
      <div class="notes-browse">
        <p><?php _e('Browse and manage all notes in the system.', 'heritagepress'); ?></p>

        <!-- Notes List -->
        <div id="notes-list">
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <td id="cb" class="manage-column column-cb check-column">
                  <input id="cb-select-all-1" type="checkbox">
                </td>
                <th scope="col" class="manage-column column-note">
                  <?php _e('Note', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-person">
                  <?php _e('Person/Family', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-event">
                  <?php _e('Event', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-tree">
                  <?php _e('Tree', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-private">
                  <?php _e('Private', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-actions">
                  <?php _e('Actions', 'heritagepress'); ?>
                </th>
              </tr>
            </thead>
            <tbody id="the-list">
              <!-- Notes will be loaded here via AJAX -->
            </tbody>
          </table>
        </div>

        <!-- Bulk Actions -->
        <div class="tablenav bottom">
          <div class="alignleft actions bulkactions">
            <select name="action" id="bulk-action-selector-bottom">
              <option value="-1"><?php _e('Bulk actions', 'heritagepress'); ?></option>
              <option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>
            </select>
            <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'heritagepress'); ?>">
          </div>
        </div>
      </div>

    <?php elseif ($current_tab === 'add'): ?>
      <!-- Add Note Form -->
      <?php include dirname(__FILE__) . '/notes-add.php'; ?>

    <?php elseif ($current_tab === 'manage' && $is_managing_specific): ?>
      <!-- Manage Specific Notes -->
      <?php include dirname(__FILE__) . '/notes-manage.php'; ?>

    <?php endif; ?>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Initialize HeritagePress Notes namespace
    window.HeritagePress = window.HeritagePress || {};
    HeritagePress.Notes = {

      /**
       * Add note via AJAX
       */
      addNote: function(formData) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_add_note',
            nonce: heritagepress_admin.nonce,
            ...formData
          },
          success: function(response) {
            if (response.success) {
              alert('<?php _e('Note added successfully!', 'heritagepress'); ?>');
              // Reload notes list or update display
              location.reload();
            } else {
              alert('<?php _e('Error adding note:', 'heritagepress'); ?> ' + response.data);
            }
          },
          error: function() {
            alert('<?php _e('Error adding note. Please try again.', 'heritagepress'); ?>');
          }
        });
      },

      /**
       * Update note via AJAX
       */
      updateNote: function(formData) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_update_note',
            nonce: heritagepress_admin.nonce,
            ...formData
          },
          success: function(response) {
            if (response.success) {
              alert('<?php _e('Note updated successfully!', 'heritagepress'); ?>');
              // Update display text
              if (response.data.display) {
                $('#note-display-' + formData.ID).text(response.data.display);
              }
            } else {
              alert('<?php _e('Error updating note:', 'heritagepress'); ?> ' + response.data);
            }
          },
          error: function() {
            alert('<?php _e('Error updating note. Please try again.', 'heritagepress'); ?>');
          }
        });
      },

      /**
       * Delete note via AJAX
       */
      deleteNote: function(noteID, personID, tree, eventID) {
        if (!confirm('<?php _e('Are you sure you want to delete this note?', 'heritagepress'); ?>')) {
          return false;
        }

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_delete_note',
            nonce: heritagepress_admin.nonce,
            noteID: noteID,
            personID: personID,
            tree: tree,
            eventID: eventID
          },
          success: function(response) {
            if (response.success) {
              alert('<?php _e('Note deleted successfully!', 'heritagepress'); ?>');
              // Remove from display or reload
              location.reload();
            } else {
              alert('<?php _e('Error deleting note:', 'heritagepress'); ?> ' + response.data);
            }
          },
          error: function() {
            alert('<?php _e('Error deleting note. Please try again.', 'heritagepress'); ?>');
          }
        });
        return false;
      },

      /**
       * Get notes for person/family/event
       */
      getNotes: function(tree, persfamID, eventID) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_get_notes',
            nonce: heritagepress_admin.nonce,
            tree: tree,
            persfamID: persfamID,
            eventID: eventID
          },
          success: function(response) {
            if (response.success) {
              // Update notes display
              this.displayNotes(response.data.notes);
            }
          }.bind(this)
        });
      },

      /**
       * Display notes in table
       */
      displayNotes: function(notes) {
        var $tbody = $('#the-list');
        $tbody.empty();

        if (notes.length === 0) {
          $tbody.append('<tr><td colspan="7"><?php _e('No notes found.', 'heritagepress'); ?></td></tr>');
          return;
        }

        notes.forEach(function(note) {
          var row = '<tr>' +
            '<th scope="row" class="check-column">' +
            '<input type="checkbox" name="selected_notes[]" value="' + note.ID + '">' +
            '</th>' +
            '<td class="column-note">' + this.truncateText(note.note, 100) + '</td>' +
            '<td class="column-person">' + note.persfamID + '</td>' +
            '<td class="column-event">' + (note.eventID || '—') + '</td>' +
            '<td class="column-tree">' + note.gedcom + '</td>' +
            '<td class="column-private">' + (note.secret ? '<?php _e('Yes', 'heritagepress'); ?>' : '<?php _e('No', 'heritagepress'); ?>') + '</td>' +
            '<td class="column-actions">' +
            '<a href="?page=heritagepress-notes&tab=edit&noteID=' + note.ID + '"><?php _e('Edit', 'heritagepress'); ?></a> | ' +
            '<a href="#" onclick="HeritagePress.Notes.deleteNote(' + note.ID + ', \'' + note.persfamID + '\', \'' + note.gedcom + '\', \'' + (note.eventID || '') + '\'); return false;"><?php _e('Delete', 'heritagepress'); ?></a>' +
            '</td>' +
            '</tr>';
          $tbody.append(row);
        }.bind(this));
      },

      /**
       * Truncate text for display
       */
      truncateText: function(text, length) {
        if (text.length <= length) return text;
        return text.substring(0, length) + '...';
      }
    };

    // Global compatibility functions
    window.addNote = function(formData) {
      return HeritagePress.Notes.addNote(formData);
    };

    window.updateNote = function(form) {
      const formData = new FormData(form);
      const data = {};
      for (let [key, value] of formData.entries()) {
        data[key] = value;
      }
      HeritagePress.Notes.updateNote(data);
      return false;
    };

    window.deleteNote = function(noteID, personID, tree, eventID) {
      return HeritagePress.Notes.deleteNote(noteID, personID, tree, eventID);
    };

    // Load initial notes if on browse tab
    <?php if ($current_tab === 'browse' && !$is_managing_specific): ?>
      // Load all notes for browse view
      HeritagePress.Notes.getNotes('', '', '');
    <?php elseif ($current_tab === 'manage' && $is_managing_specific): ?>
      // Load specific notes for manage view
      HeritagePress.Notes.getNotes('<?php echo esc_js($tree); ?>', '<?php echo esc_js($persfam_id); ?>', '<?php echo esc_js($event_id); ?>');
    <?php endif; ?>

    // Handle bulk actions
    $('#doaction').on('click', function(e) {
      e.preventDefault();
      var action = $('#bulk-action-selector-bottom').val();
      var selected = $('input[name="selected_notes[]"]:checked').map(function() {
        return this.value;
      }).get();

      if (action === '-1') {
        alert('<?php _e('Please select an action.', 'heritagepress'); ?>');
        return;
      }

      if (selected.length === 0) {
        alert('<?php _e('Please select at least one note.', 'heritagepress'); ?>');
        return;
      }

      if (action === 'delete') {
        if (!confirm('<?php _e('Are you sure you want to delete the selected notes?', 'heritagepress'); ?>')) {
          return;
        }
      }

      // Submit bulk action
      var form = $('<form method="post">' +
        '<input type="hidden" name="action" value="bulk_action">' +
        '<input type="hidden" name="bulk_action" value="' + action + '">' +
        '<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('heritagepress_note_action'); ?>">' +
        '</form>');

      selected.forEach(function(id) {
        form.append('<input type="hidden" name="selected_notes[]" value="' + id + '">');
      });

      form.appendTo('body').submit();
    });
  });
</script>

<!-- Note Management CSS -->
<style>
  .heritagepress-notes .nav-tab-wrapper {
    margin-bottom: 20px;
  }

  .heritagepress-notes .tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-top: none;
  }

  .notes-browse {
    min-height: 400px;
  }

  .column-note {
    width: 40%;
  }

  .column-person {
    width: 15%;
  }

  .column-event {
    width: 10%;
  }

  .column-tree {
    width: 10%;
  }

  .column-private {
    width: 8%;
  }

  .column-actions {
    width: 12%;
  }

  .notes-browse .wp-list-table th,
  .notes-browse .wp-list-table td {
    padding: 8px 10px;
  }

  .tablenav {
    margin-top: 10px;
  }

  /* Loading state */
  .notes-loading {
    text-align: center;
    padding: 20px;
    color: #666;
  }

  /* Empty state */
  .notes-empty {
    text-align: center;
    padding: 40px;
    color: #666;
  }

  .notes-empty h3 {
    margin-bottom: 10px;
  }
</style>
