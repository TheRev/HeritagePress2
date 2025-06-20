<?php

/**
 * Merge Repositories View for HeritagePress
 *
 * This file provides the merge repositories interface.
 * Ported from admin_mergerepos.php functionality
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get repository controller
$controller = new HP_Repository_Controller();

// Get available trees
$trees = $controller->get_available_trees();
?>

<div class="merge-repositories-section">
  <div class="hp-admin-block">
    <h3><?php _e('Merge Repositories', 'heritagepress'); ?></h3>

    <div class="hp-notice hp-notice-info">
      <p>
        <?php _e('Use this tool to merge duplicate repositories. The "merge into" repository will be kept, and all sources that reference the "merge from" repository will be updated to point to the kept repository.', 'heritagepress'); ?>
      </p>
    </div>

    <form method="post" id="merge-repositories-form" class="hp-merge-form">
      <?php wp_nonce_field('hp_merge_repositories', 'hp_repository_nonce'); ?>
      <input type="hidden" name="action" value="merge_repositories">

      <div class="hp-form-section">
        <h4><?php _e('Select Repositories to Merge', 'heritagepress'); ?></h4>

        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="tree_filter"><?php _e('Tree:', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="tree_filter" id="tree_filter" onchange="loadRepositories();">
                <option value=""><?php _e('Select a tree...', 'heritagepress'); ?></option>
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>">
                    <?php echo esc_html($tree['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="description"><?php _e('Select the tree containing the repositories to merge.', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>

      <div id="repository-selection" style="display: none;">
        <div class="hp-form-section">
          <h4><?php _e('Repository Selection', 'heritagepress'); ?></h4>

          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="merge_from"><?php _e('Merge FROM (will be deleted):', 'heritagepress'); ?></label>
              </th>
              <td>
                <select name="merge_from" id="merge_from" required>
                  <option value=""><?php _e('Select repository to merge from...', 'heritagepress'); ?></option>
                </select>
                <div id="merge_from_details" class="repository-details"></div>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="merge_into"><?php _e('Merge INTO (will be kept):', 'heritagepress'); ?></label>
              </th>
              <td>
                <select name="merge_into" id="merge_into" required>
                  <option value=""><?php _e('Select repository to merge into...', 'heritagepress'); ?></option>
                </select>
                <div id="merge_into_details" class="repository-details"></div>
              </td>
            </tr>
          </table>
        </div>

        <div class="hp-form-section">
          <h4><?php _e('Merge Options', 'heritagepress'); ?></h4>

          <table class="form-table">
            <tr>
              <th scope="row">
                <?php _e('Actions to perform:', 'heritagepress'); ?>
              </th>
              <td>
                <label>
                  <input type="checkbox" name="update_sources" value="1" checked disabled>
                  <?php _e('Update all sources that reference the "merge from" repository', 'heritagepress'); ?>
                </label>
                <br>
                <label>
                  <input type="checkbox" name="delete_original" value="1" checked>
                  <?php _e('Delete the "merge from" repository after merging', 'heritagepress'); ?>
                </label>
                <br>
                <label>
                  <input type="checkbox" name="merge_addresses" value="1">
                  <?php _e('Merge address information if "merge into" repository has no address', 'heritagepress'); ?>
                </label>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <?php _e('Confirmation:', 'heritagepress'); ?>
              </th>
              <td>
                <label>
                  <input type="checkbox" name="confirm_merge" value="1" required>
                  <strong><?php _e('I understand this action cannot be undone', 'heritagepress'); ?></strong>
                </label>
              </td>
            </tr>
          </table>
        </div>

        <div class="hp-form-section">
          <div class="submit-actions">
            <input type="submit" name="merge_repositories" id="merge_repositories" class="button-primary"
              value="<?php esc_attr_e('Merge Repositories', 'heritagepress'); ?>" disabled>
            <button type="button" id="preview_merge" class="button">
              <?php _e('Preview Merge', 'heritagepress'); ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=search'); ?>" class="button">
              <?php _e('Cancel', 'heritagepress'); ?>
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Merge Preview Modal -->
<div id="merge-preview-modal" class="hp-modal" style="display: none;">
  <div class="hp-modal-content">
    <div class="hp-modal-header">
      <h3><?php _e('Merge Preview', 'heritagepress'); ?></h3>
      <span class="hp-modal-close">&times;</span>
    </div>
    <div class="hp-modal-body">
      <div id="merge-preview-content">
        <!-- Content loaded via AJAX -->
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Load repositories when tree is selected
    window.loadRepositories = function() {
      var gedcom = $('#tree_filter').val();

      if (!gedcom) {
        $('#repository-selection').hide();
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_search_repositories',
          gedcom: gedcom,
          search_term: '',
          limit: 1000,
          nonce: '<?php echo wp_create_nonce('hp_repository_nonce'); ?>'
        },
        success: function(response) {
          if (response.success) {
            populateRepositorySelects(response.data.repositories);
            $('#repository-selection').show();
          } else {
            alert('<?php echo esc_js(__('Error loading repositories:', 'heritagepress')); ?> ' + response.data);
          }
        },
        error: function() {
          alert('<?php echo esc_js(__('An error occurred while loading repositories.', 'heritagepress')); ?>');
        }
      });
    };

    function populateRepositorySelects(repositories) {
      var fromSelect = $('#merge_from');
      var intoSelect = $('#merge_into');

      // Clear existing options
      fromSelect.html('<option value=""><?php echo esc_js(__('Select repository to merge from...', 'heritagepress')); ?></option>');
      intoSelect.html('<option value=""><?php echo esc_js(__('Select repository to merge into...', 'heritagepress')); ?></option>');

      // Populate with repositories
      $.each(repositories, function(index, repo) {
        var option = '<option value="' + repo.repoID + '">[' + repo.repoID + '] ' + repo.reponame + '</option>';
        fromSelect.append(option);
        intoSelect.append(option);
      });
    }

    // Show repository details when selected
    $('#merge_from').change(function() {
      var repoID = $(this).val();
      if (repoID) {
        loadRepositoryDetails(repoID, '#merge_from_details');
      } else {
        $('#merge_from_details').html('');
      }
      validateForm();
    });

    $('#merge_into').change(function() {
      var repoID = $(this).val();
      if (repoID) {
        loadRepositoryDetails(repoID, '#merge_into_details');
      } else {
        $('#merge_into_details').html('');
      }
      validateForm();
    });

    function loadRepositoryDetails(repoID, targetElement) {
      var gedcom = $('#tree_filter').val();

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_get_repository',
          repoID: repoID,
          gedcom: gedcom,
          nonce: '<?php echo wp_create_nonce('hp_repository_nonce'); ?>'
        },
        success: function(response) {
          if (response.success) {
            var repo = response.data.repository;
            var html = '<div class="repository-preview">';
            html += '<strong>' + repo.reponame + '</strong><br>';

            if (repo.city || repo.state || repo.country) {
              html += '<small>';
              if (repo.city) html += repo.city;
              if (repo.state) html += (repo.city ? ', ' : '') + repo.state;
              if (repo.country) html += '<br>' + repo.country;
              html += '</small>';
            }

            html += '</div>';
            $(targetElement).html(html);
          }
        }
      });
    }

    function validateForm() {
      var fromRepo = $('#merge_from').val();
      var intoRepo = $('#merge_into').val();
      var confirmChecked = $('#confirm_merge').is(':checked');

      var isValid = fromRepo && intoRepo && fromRepo !== intoRepo && confirmChecked;
      $('#merge_repositories').prop('disabled', !isValid);

      if (fromRepo && intoRepo && fromRepo === intoRepo) {
        alert('<?php echo esc_js(__('You cannot merge a repository into itself. Please select different repositories.', 'heritagepress')); ?>');
        $('#merge_into').val('');
        $('#merge_into_details').html('');
      }
    }

    // Validate form when confirmation checkbox changes
    $('#confirm_merge').change(validateForm);

    // Preview merge
    $('#preview_merge').click(function() {
      var fromRepo = $('#merge_from').val();
      var intoRepo = $('#merge_into').val();
      var gedcom = $('#tree_filter').val();

      if (!fromRepo || !intoRepo || !gedcom) {
        alert('<?php echo esc_js(__('Please select both repositories first.', 'heritagepress')); ?>');
        return;
      }

      // This would load a preview of what the merge would do
      var previewHtml = '<h4><?php echo esc_js(__('Merge Preview', 'heritagepress')); ?></h4>';
      previewHtml += '<p><strong><?php echo esc_js(__('From:', 'heritagepress')); ?></strong> ' + $('#merge_from option:selected').text() + '</p>';
      previewHtml += '<p><strong><?php echo esc_js(__('Into:', 'heritagepress')); ?></strong> ' + $('#merge_into option:selected').text() + '</p>';
      previewHtml += '<p><?php echo esc_js(__('This functionality will show a detailed preview of affected sources and records.', 'heritagepress')); ?></p>';

      $('#merge-preview-content').html(previewHtml);
      $('#merge-preview-modal').show();
    });

    // Form submission
    $('#merge-repositories-form').submit(function(e) {
      e.preventDefault();

      if (!confirm('<?php echo esc_js(__('Are you absolutely sure you want to merge these repositories? This action cannot be undone.', 'heritagepress')); ?>')) {
        return;
      }

      // This would handle the actual merge process
      alert('<?php echo esc_js(__('Repository merge functionality will be implemented in a future update.', 'heritagepress')); ?>');
    });

    // Close modal
    $('.hp-modal-close').click(function() {
      $('.hp-modal').hide();
    });

    $(window).click(function(event) {
      if (event.target.classList.contains('hp-modal')) {
        $('.hp-modal').hide();
      }
    });

  });
</script>

<style>
  .merge-repositories-section {
    max-width: 900px;
  }

  .hp-merge-form {
    /* Form styling inherits from main styles */
  }

  .repository-details {
    margin-top: 10px;
    padding: 10px;
    background: #f0f0f1;
    border-radius: 4px;
    min-height: 20px;
  }

  .repository-preview {
    font-size: 13px;
    line-height: 1.4;
  }

  .hp-notice {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
  }

  .hp-notice-info {
    background: #e7f3ff;
    border-left: 4px solid #0073aa;
    color: #0073aa;
  }

  .submit-actions {
    text-align: left;
    padding-top: 10px;
    border-top: 1px solid #ddd;
    margin-top: 20px;
  }

  .submit-actions .button {
    margin-right: 10px;
  }

  /* Modal styles are inherited from main repository styles */
</style>
