<?php

/**
 * Language Management Admin Page
 *
 * Replicates admin_languages.php, admin_newlanguage.php, admin_editlanguage.php functionality
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

// Include the language controller
require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-language-controller.php';

$controller = new HP_Language_Controller();

// Handle messages
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
$message_type = isset($_GET['message_type']) ? sanitize_text_field($_GET['message_type']) : 'success';

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'search';

// Initialize search parameters
$search_string = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
?>

<div class="wrap">
  <h1><?php _e('Language Management', 'heritagepress'); ?></h1>

  <?php if ($message): ?>
    <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
      <p><?php echo esc_html($message); ?></p>
    </div>
  <?php endif; ?>

  <!-- Tab Navigation (matching genealogy style) -->
  <h2 class="nav-tab-wrapper">
    <a href="<?php echo admin_url('admin.php?page=heritagepress-languages&tab=search'); ?>"
      class="nav-tab <?php echo ($current_tab === 'search') ? 'nav-tab-active' : ''; ?>">
      <?php _e('Search', 'heritagepress'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=heritagepress-languages&tab=add'); ?>"
      class="nav-tab <?php echo ($current_tab === 'add') ? 'nav-tab-active' : ''; ?>">
      <?php _e('Add New', 'heritagepress'); ?>
    </a>
    <?php if (isset($_GET['edit']) && intval($_GET['edit'])): ?>
      <a href="#" class="nav-tab nav-tab-active">
        <?php _e('Edit', 'heritagepress'); ?>
      </a>
    <?php endif; ?>

    <span class="nav-tab-help" style="float: right; margin-top: 8px;">
      <a href="#" onclick="return false;" class="button-secondary">
        <?php _e('Help', 'heritagepress'); ?>
      </a>
    </span>
  </h2>

  <div class="tab-content">
    <?php if ($current_tab === 'search' && !isset($_GET['edit'])): ?>
      <!-- Search Tab -->
      <div class="heritagepress-admin-section">
        <h3><?php _e('Language Search', 'heritagepress'); ?></h3>

        <!-- Search Form -->
        <form method="get" action="">
          <input type="hidden" name="page" value="heritagepress-languages">
          <input type="hidden" name="tab" value="search">
          <table class="form-table">
            <tr>
              <td style="padding-left: 0;">
                <?php _e('Search for:', 'heritagepress'); ?>
                <input type="text" name="search" value="<?php echo esc_attr($search_string); ?>" class="regular-text">
                <input type="submit" name="submit" value="<?php _e('Search', 'heritagepress'); ?>" class="button">
                <input type="submit" name="reset" value="<?php _e('Reset', 'heritagepress'); ?>"
                  class="button" onclick="document.querySelector('input[name=search]').value='';">
              </td>
            </tr>
          </table>
        </form>

        <!-- Languages List -->
        <div id="languages-list">
          <!-- This will be populated by AJAX -->
        </div>
      </div>

    <?php elseif ($current_tab === 'add'): ?>
      <!-- Add New Language Tab -->
      <div class="heritagepress-admin-section">
        <h3><?php _e('Add New Language', 'heritagepress'); ?></h3>

        <form id="add-language-form" method="post">
          <?php wp_nonce_field('heritagepress_admin_nonce', 'nonce'); ?>

          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="folder"><?php _e('Language Folder:', 'heritagepress'); ?></label>
              </th>
              <td>
                <select name="folder" id="folder" class="regular-text" required>
                  <option value=""><?php _e('Select folder...', 'heritagepress'); ?></option>
                  <?php
                  $folders = $controller->get_available_language_folders();
                  foreach ($folders as $folder):
                  ?>
                    <option value="<?php echo esc_attr($folder); ?>"><?php echo esc_html($folder); ?></option>
                  <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select the folder containing the language files', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="display"><?php _e('Display Name:', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="display" id="display" class="regular-text" maxlength="50" required>
                <p class="description"><?php _e('How this language will be displayed to users', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="langcharset"><?php _e('Character Set:', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="langcharset" id="langcharset" class="regular-text"
                  value="UTF-8" maxlength="20" required>
                <p class="description"><?php _e('Character encoding for this language (e.g., UTF-8, ISO-8859-1)', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="langnorels"><?php _e('Disable Relationships:', 'heritagepress'); ?></label>
              </th>
              <td>
                <select name="langnorels" id="langnorels">
                  <option value="0"><?php _e('No', 'heritagepress'); ?></option>
                  <option value="1"><?php _e('Yes', 'heritagepress'); ?></option>
                </select>
                <p class="description"><?php _e('Whether to disable relationship calculations for this language', 'heritagepress'); ?></p>
              </td>
            </tr>
          </table>

          <p class="submit">
            <input type="submit" name="submit_save_return" class="button-primary"
              value="<?php _e('Save & Return to List', 'heritagepress'); ?>">
            <input type="submit" name="submit_save_edit" class="button"
              value="<?php _e('Save & Continue Editing', 'heritagepress'); ?>">
            <a href="<?php echo admin_url('admin.php?page=heritagepress-languages'); ?>"
              class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
          </p>
        </form>
      </div>

    <?php elseif (isset($_GET['edit'])): ?>
      <!-- Edit Language Tab -->
      <?php $edit_id = intval($_GET['edit']); ?>
      <div class="heritagepress-admin-section">
        <h3><?php _e('Edit Language', 'heritagepress'); ?></h3>

        <form id="edit-language-form" method="post">
          <?php wp_nonce_field('heritagepress_admin_nonce', 'nonce'); ?>
          <input type="hidden" name="language_id" value="<?php echo esc_attr($edit_id); ?>">

          <div id="edit-language-fields">
            <!-- This will be populated by AJAX -->
          </div>

          <p class="submit">
            <input type="submit" name="submit_save_return" class="button-primary"
              value="<?php _e('Save & Return to List', 'heritagepress'); ?>">
            <input type="submit" name="submit_save_edit" class="button"
              value="<?php _e('Save & Continue Editing', 'heritagepress'); ?>">
            <a href="<?php echo admin_url('admin.php?page=heritagepress-languages'); ?>"
              class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
          </p>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-language-modal" style="display: none;">
  <div class="modal-content">
    <h3><?php _e('Confirm Deletion', 'heritagepress'); ?></h3>
    <p><?php _e('Are you sure you want to delete this language? This action cannot be undone.', 'heritagepress'); ?></p>
    <p class="submit">
      <button type="button" id="confirm-delete" class="button-primary"><?php _e('Delete', 'heritagepress'); ?></button>
      <button type="button" id="cancel-delete" class="button"><?php _e('Cancel', 'heritagepress'); ?></button>
    </p>
  </div>
</div>

<style>
  /* Heritage Press styling */
  .heritagepress-admin-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
  }

  .languages-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
  }

  .languages-table th,
  .languages-table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
  }

  .languages-table th {
    background: #f1f1f1;
    font-weight: bold;
  }

  .languages-table tr:hover {
    background: #f9f9f9;
  }

  .action-buttons {
    white-space: nowrap;
  }

  .action-buttons a {
    margin-right: 5px;
    text-decoration: none;
  }

  .pagination {
    margin: 15px 0;
    text-align: center;
  }

  .pagination a,
  .pagination span {
    display: inline-block;
    padding: 6px 12px;
    margin: 0 2px;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 3px;
  }

  .pagination .current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
  }

  #delete-language-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
  }

  #delete-language-modal .modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 4px;
    min-width: 400px;
  }

  .nav-tab-help {
    line-height: 24px;
  }

  .loading {
    opacity: 0.6;
    pointer-events: none;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    let currentLanguageId = null;
    let currentAction = 'search';

    // Initialize based on current tab
    <?php if ($current_tab === 'search' && !isset($_GET['edit'])): ?>
      loadLanguagesList();
    <?php elseif (isset($_GET['edit'])): ?>
      loadLanguageForEdit(<?php echo intval($_GET['edit']); ?>);
    <?php endif; ?>

    // Search form handling
    $('form[method="get"]').on('submit', function(e) {
      e.preventDefault();
      loadLanguagesList();
    });

    // Add language form handling
    $('#add-language-form').on('submit', function(e) {
      e.preventDefault();
      addLanguage($(this));
    });

    // Edit language form handling
    $('#edit-language-form').on('submit', function(e) {
      e.preventDefault();
      updateLanguage($(this));
    });

    // Delete confirmation
    $(document).on('click', '.delete-language', function(e) {
      e.preventDefault();
      currentLanguageId = $(this).data('language-id');
      $('#delete-language-modal').show();
    });

    $('#confirm-delete').on('click', function() {
      if (currentLanguageId) {
        deleteLanguage(currentLanguageId);
      }
    });

    $('#cancel-delete').on('click', function() {
      $('#delete-language-modal').hide();
      currentLanguageId = null;
    });

    // Form validation
    function validateForm(formData) {
      const errors = [];

      if (!formData.get('display')) {
        errors.push('<?php _e('Display name is required', 'heritagepress'); ?>');
      }

      if (!formData.get('folder')) {
        errors.push('<?php _e('Folder name is required', 'heritagepress'); ?>');
      }

      if (!formData.get('langcharset')) {
        errors.push('<?php _e('Character set is required', 'heritagepress'); ?>');
      }

      return errors;
    }

    // Load languages list with search and pagination
    function loadLanguagesList(page = 1) {
      const search = $('input[name="search"]').val() || '';

      $.post(ajaxurl, {
        action: 'heritagepress_search_languages',
        nonce: '<?php echo wp_create_nonce('heritagepress_admin_nonce'); ?>',
        search: search,
        page: page
      }, function(response) {
        if (response.success) {
          renderLanguagesList(response.data);
        } else {
          showMessage(response.data.message, 'error');
        }
      });
    }

    // Render languages list HTML
    function renderLanguagesList(data) {
      let html = '<div class="list-info">';
      html += '<p>' + '<?php _e('Showing', 'heritagepress'); ?> ' +
        ((data.page - 1) * data.per_page + 1) + '-' +
        Math.min(data.page * data.per_page, data.total) +
        ' <?php _e('of', 'heritagepress'); ?> ' + data.total + ' <?php _e('languages', 'heritagepress'); ?></p>';
      html += '</div>';

      if (data.languages.length > 0) {
        html += '<table class="languages-table">';
        html += '<thead><tr>';
        html += '<th><?php _e('Actions', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Display Name', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Folder', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Character Set', 'heritagepress'); ?></th>';
        html += '<th><?php _e('No Relationships', 'heritagepress'); ?></th>';
        html += '</tr></thead><tbody>';

        data.languages.forEach(function(language) {
          html += '<tr>';
          html += '<td class="action-buttons">';
          html += '<a href="<?php echo admin_url('admin.php?page=heritagepress-languages&tab=search&edit='); ?>' + language.languageID + '" ';
          html += 'title="<?php _e('Edit', 'heritagepress'); ?>" class="button-secondary"><?php _e('Edit', 'heritagepress'); ?></a> ';
          html += '<a href="#" class="delete-language button-secondary" ';
          html += 'data-language-id="' + language.languageID + '" ';
          html += 'title="<?php _e('Delete', 'heritagepress'); ?>"><?php _e('Delete', 'heritagepress'); ?></a>';
          html += '</td>';
          html += '<td>' + escapeHtml(language.display) + '</td>';
          html += '<td>' + escapeHtml(language.folder) + '</td>';
          html += '<td>' + escapeHtml(language.charset) + '</td>';
          html += '<td>' + (language.norels == 1 ? '<?php _e('Yes', 'heritagepress'); ?>' : '<?php _e('No', 'heritagepress'); ?>') + '</td>';
          html += '</tr>';
        });

        html += '</tbody></table>';

        // Pagination
        if (data.total_pages > 1) {
          html += '<div class="pagination">';
          for (let i = 1; i <= data.total_pages; i++) {
            if (i === data.page) {
              html += '<span class="current">' + i + '</span>';
            } else {
              html += '<a href="#" onclick="loadLanguagesList(' + i + '); return false;">' + i + '</a>';
            }
          }
          html += '</div>';
        }
      } else {
        html += '<p><?php _e('No languages found.', 'heritagepress'); ?></p>';
      }

      $('#languages-list').html(html);
    }

    // Load language data for editing
    function loadLanguageForEdit(languageId) {
      $.post(ajaxurl, {
        action: 'heritagepress_get_language',
        nonce: '<?php echo wp_create_nonce('heritagepress_admin_nonce'); ?>',
        language_id: languageId
      }, function(response) {
        if (response.success) {
          renderEditForm(response.data);
        } else {
          showMessage(response.data.message, 'error');
        }
      });
    }

    // Render edit form with language data
    function renderEditForm(language) {
      const folders = <?php echo json_encode($controller->get_available_language_folders()); ?>;

      let html = '<table class="form-table">';
      html += '<tr>';
      html += '<th scope="row"><label for="edit_folder"><?php _e('Language Folder:', 'heritagepress'); ?></label></th>';
      html += '<td>';
      html += '<select name="folder" id="edit_folder" class="regular-text" required>';

      folders.forEach(function(folder) {
        const selected = folder === language.folder ? ' selected' : '';
        html += '<option value="' + escapeHtml(folder) + '"' + selected + '>' + escapeHtml(folder) + '</option>';
      });

      // Add current folder if not found in available folders
      if (!folders.includes(language.folder)) {
        html += '<option value="' + escapeHtml(language.folder) + '" selected>' + escapeHtml(language.folder) + '</option>';
      }

      html += '</select>';
      html += '<p class="description"><?php _e('Select the folder containing the language files', 'heritagepress'); ?></p>';
      html += '</td></tr>';

      html += '<tr>';
      html += '<th scope="row"><label for="edit_display"><?php _e('Display Name:', 'heritagepress'); ?></label></th>';
      html += '<td>';
      html += '<input type="text" name="display" id="edit_display" class="regular-text" ';
      html += 'value="' + escapeHtml(language.display) + '" maxlength="50" required>';
      html += '<p class="description"><?php _e('How this language will be displayed to users', 'heritagepress'); ?></p>';
      html += '</td></tr>';

      html += '<tr>';
      html += '<th scope="row"><label for="edit_langcharset"><?php _e('Character Set:', 'heritagepress'); ?></label></th>';
      html += '<td>';
      html += '<input type="text" name="langcharset" id="edit_langcharset" class="regular-text" ';
      html += 'value="' + escapeHtml(language.charset) + '" maxlength="20" required>';
      html += '<p class="description"><?php _e('Character encoding for this language', 'heritagepress'); ?></p>';
      html += '</td></tr>';

      html += '<tr>';
      html += '<th scope="row"><label for="edit_langnorels"><?php _e('Disable Relationships:', 'heritagepress'); ?></label></th>';
      html += '<td>';
      html += '<select name="langnorels" id="edit_langnorels">';
      html += '<option value="0"' + (language.norels == 0 ? ' selected' : '') + '><?php _e('No', 'heritagepress'); ?></option>';
      html += '<option value="1"' + (language.norels == 1 ? ' selected' : '') + '><?php _e('Yes', 'heritagepress'); ?></option>';
      html += '</select>';
      html += '<p class="description"><?php _e('Whether to disable relationship calculations for this language', 'heritagepress'); ?></p>';
      html += '</td></tr>';
      html += '</table>';

      $('#edit-language-fields').html(html);
    }

    // Add new language
    function addLanguage(form) {
      const formData = new FormData(form[0]);
      const errors = validateForm(formData);

      if (errors.length > 0) {
        showMessage(errors.join('<br>'), 'error');
        return;
      }

      form.addClass('loading');

      $.post(ajaxurl, {
        action: 'heritagepress_add_language',
        nonce: formData.get('nonce'),
        display: formData.get('display'),
        folder: formData.get('folder'),
        langcharset: formData.get('langcharset'),
        langnorels: formData.get('langnorels')
      }, function(response) {
        form.removeClass('loading');

        if (response.success) {
          showMessage(response.data.message, 'success');

          // Redirect based on which submit button was clicked
          const submitButton = form.find('input[type="submit"]:focus');
          if (submitButton.attr('name') === 'submit_save_return') {
            window.location.href = '<?php echo admin_url('admin.php?page=heritagepress-languages'); ?>';
          } else {
            // Continue editing - redirect to edit form
            window.location.href = '<?php echo admin_url('admin.php?page=heritagepress-languages&tab=search&edit='); ?>' + response.data.language_id;
          }
        } else {
          showMessage(response.data.message, 'error');
        }
      });
    }

    // Update existing language
    function updateLanguage(form) {
      const formData = new FormData(form[0]);
      const errors = validateForm(formData);

      if (errors.length > 0) {
        showMessage(errors.join('<br>'), 'error');
        return;
      }

      form.addClass('loading');

      $.post(ajaxurl, {
        action: 'heritagepress_update_language',
        nonce: formData.get('nonce'),
        language_id: formData.get('language_id'),
        display: formData.get('display'),
        folder: formData.get('folder'),
        langcharset: formData.get('langcharset'),
        langnorels: formData.get('langnorels')
      }, function(response) {
        form.removeClass('loading');

        if (response.success) {
          showMessage(response.data.message, 'success');

          // Redirect based on which submit button was clicked
          const submitButton = form.find('input[type="submit"]:focus');
          if (submitButton.attr('name') === 'submit_save_return') {
            window.location.href = '<?php echo admin_url('admin.php?page=heritagepress-languages'); ?>';
          }
          // If continuing to edit, just show success message
        } else {
          showMessage(response.data.message, 'error');
        }
      });
    }

    // Delete language
    function deleteLanguage(languageId) {
      $('#delete-language-modal').hide();

      $.post(ajaxurl, {
        action: 'heritagepress_delete_language',
        nonce: '<?php echo wp_create_nonce('heritagepress_admin_nonce'); ?>',
        language_id: languageId
      }, function(response) {
        if (response.success) {
          showMessage(response.data.message, 'success');
          loadLanguagesList(); // Reload the list
        } else {
          showMessage(response.data.message, 'error');
        }
        currentLanguageId = null;
      });
    }

    // Show message
    function showMessage(message, type) {
      const messageHtml = '<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>';
      $('.wrap h1').after(messageHtml);

      // Auto-hide after 5 seconds
      setTimeout(function() {
        $('.notice').fadeOut();
      }, 5000);
    }

    // Escape HTML
    function escapeHtml(text) {
      const map = {
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

    // Make loadLanguagesList available globally for pagination
    window.loadLanguagesList = loadLanguagesList;
  });
</script>
