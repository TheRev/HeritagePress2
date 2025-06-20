<?php

/**
 * Edit Repository View for HeritagePress
 *
 * This file provides the edit repository interface.
 * Ported from admin_editrepo.php functionality
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get repository controller
$controller = new HP_Repository_Controller();

// Get repository details
$repo_id = isset($_GET['repoID']) ? sanitize_text_field($_GET['repoID']) : '';
$gedcom = isset($_GET['gedcom']) ? sanitize_text_field($_GET['gedcom']) : '';
$added = isset($_GET['added']) ? intval($_GET['added']) : 0;

if (empty($repo_id) || empty($gedcom)) {
  wp_die(__('Invalid repository parameters.', 'heritagepress'));
}

$repository = $controller->get_repository($repo_id, $gedcom);

if (!$repository) {
  wp_die(__('Repository not found.', 'heritagepress'));
}

// Get available trees for tree selection
$trees = $controller->get_available_trees();

// Show success message if just added
if ($added) {
  echo '<div class="notice notice-success is-dismissible">';
  echo '<p>' . __('Repository added successfully! You can now edit additional details.', 'heritagepress') . '</p>';
  echo '</div>';
}
?>

<div class="edit-repository-section">
  <div class="hp-admin-block">
    <div class="repository-header">
      <h3>
        <?php printf(__('Edit Repository: %s (%s)', 'heritagepress'), esc_html($repository['reponame']), esc_html($repository['repoID'])); ?>
      </h3>
      <div class="repository-actions">
        <a href="<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=search'); ?>" class="button">
          <?php _e('Back to Search', 'heritagepress'); ?>
        </a>
        <button type="button" id="show-notes-btn" class="button">
          <?php _e('Notes', 'heritagepress'); ?>
        </button>
      </div>
    </div>

    <div class="repository-meta">
      <p class="last-modified">
        <?php
        printf(
          __('Last modified: %s by %s', 'heritagepress'),
          esc_html($repository['formatted_date']),
          esc_html($repository['changedby'])
        );
        ?>
      </p>
    </div>

    <form method="post" id="edit-repository-form" class="hp-repository-form">
      <?php wp_nonce_field('hp_update_repository', 'hp_repository_nonce'); ?>
      <input type="hidden" name="action" value="update_repository">
      <input type="hidden" name="repoID" value="<?php echo esc_attr($repository['repoID']); ?>">
      <input type="hidden" name="gedcom" value="<?php echo esc_attr($repository['gedcom']); ?>">
      <input type="hidden" name="addressID" value="<?php echo esc_attr($repository['addressID']); ?>">

      <!-- Repository Information -->
      <div class="hp-form-section">
        <h4><?php _e('Repository Information', 'heritagepress'); ?></h4>

        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="tree_display"><?php _e('Tree:', 'heritagepress'); ?></label>
            </th>
            <td>
              <?php
              // Find and display tree name
              $tree_name = '';
              foreach ($trees as $tree) {
                if ($tree['gedcom'] === $repository['gedcom']) {
                  $tree_name = $tree['treename'];
                  break;
                }
              }
              ?>
              <strong><?php echo esc_html($tree_name); ?></strong>
              <small>(<?php echo esc_html($repository['gedcom']); ?>)</small>
              <a href="#" onclick="return changeTree('<?php echo esc_js($repository['gedcom']); ?>', '<?php echo esc_js($repository['repoID']); ?>');" class="button-link">
                <?php _e('Change Tree', 'heritagepress'); ?>
              </a>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="repoID_display"><?php _e('Repository ID:', 'heritagepress'); ?></label>
            </th>
            <td>
              <strong><?php echo esc_html($repository['repoID']); ?></strong>
              <small><?php _e('(Cannot be changed)', 'heritagepress'); ?></small>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="reponame"><?php _e('Repository Name:', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <input type="text" name="reponame" id="reponame" class="regular-text" required
                value="<?php echo esc_attr($repository['reponame']); ?>">
            </td>
          </tr>
        </table>
      </div>

      <!-- Address Information -->
      <div class="hp-form-section">
        <h4><?php _e('Address Information', 'heritagepress'); ?></h4>

        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="address1"><?php _e('Address Line 1:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="address1" id="address1" class="regular-text"
                value="<?php echo esc_attr($repository['address1']); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="address2"><?php _e('Address Line 2:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="address2" id="address2" class="regular-text"
                value="<?php echo esc_attr($repository['address2']); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="city"><?php _e('City:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="city" id="city" class="regular-text"
                value="<?php echo esc_attr($repository['city']); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="state"><?php _e('State/Province:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="state" id="state" class="regular-text"
                value="<?php echo esc_attr($repository['state']); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="zip"><?php _e('ZIP/Postal Code:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="zip" id="zip" class="small-text"
                value="<?php echo esc_attr($repository['zip']); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="country"><?php _e('Country:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="country" id="country" class="regular-text"
                value="<?php echo esc_attr($repository['country']); ?>">
            </td>
          </tr>
        </table>
      </div>

      <!-- Contact Information -->
      <div class="hp-form-section">
        <h4><?php _e('Contact Information', 'heritagepress'); ?></h4>

        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="phone"><?php _e('Phone:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="tel" name="phone" id="phone" class="regular-text"
                value="<?php echo esc_attr($repository['phone']); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="email"><?php _e('Email:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="email" name="email" id="email" class="regular-text"
                value="<?php echo esc_attr($repository['email']); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="www"><?php _e('Website:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="url" name="www" id="www" class="regular-text"
                value="<?php echo esc_attr($repository['www']); ?>">
              <?php if (!empty($repository['www'])): ?>
                <a href="<?php echo esc_url($repository['www']); ?>" target="_blank" class="button-link">
                  <?php _e('Visit', 'heritagepress'); ?>
                </a>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </div>

      <!-- Events Section (Future enhancement) -->
      <div class="hp-form-section">
        <h4><?php _e('Associated Events', 'heritagepress'); ?></h4>
        <p class="description">
          <?php _e('Event management for repositories will be available in a future update.', 'heritagepress'); ?>
        </p>
        <button type="button" class="button" onclick="addRepositoryEvent();">
          <?php _e('Add New Event', 'heritagepress'); ?>
        </button>
      </div>

      <!-- Submit Actions -->
      <div class="hp-form-section">
        <div class="submit-actions">
          <input type="submit" name="save_return" id="save_return" class="button-primary"
            value="<?php esc_attr_e('Save & Return', 'heritagepress'); ?>">
          <input type="submit" name="save_stay" id="save_stay" class="button"
            value="<?php esc_attr_e('Save & Continue Editing', 'heritagepress'); ?>">
          <button type="button" id="delete_repository" class="button button-secondary delete-button">
            <?php _e('Delete Repository', 'heritagepress'); ?>
          </button>
          <a href="<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=search'); ?>" class="button">
            <?php _e('Cancel', 'heritagepress'); ?>
          </a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Notes Modal -->
<div id="repository-notes-modal" class="hp-modal" style="display: none;">
  <div class="hp-modal-content">
    <div class="hp-modal-header">
      <h3><?php _e('Repository Notes', 'heritagepress'); ?></h3>
      <span class="hp-modal-close">&times;</span>
    </div>
    <div class="hp-modal-body">
      <div id="repository-notes-content">
        <p><?php _e('Notes functionality will be implemented in a future update.', 'heritagepress'); ?></p>
      </div>
    </div>
  </div>
</div>

<!-- Tree Change Modal -->
<div id="tree-change-modal" class="hp-modal" style="display: none;">
  <div class="hp-modal-content">
    <div class="hp-modal-header">
      <h3><?php _e('Change Tree', 'heritagepress'); ?></h3>
      <span class="hp-modal-close">&times;</span>
    </div>
    <div class="hp-modal-body">
      <form id="tree-change-form">
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="new_tree"><?php _e('Select New Tree:', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="new_tree" id="new_tree">
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>"
                    <?php selected($repository['gedcom'], $tree['gedcom']); ?>>
                    <?php echo esc_html($tree['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        </table>
        <div class="modal-actions">
          <button type="button" id="confirm-tree-change" class="button-primary">
            <?php _e('Change Tree', 'heritagepress'); ?>
          </button>
          <button type="button" class="button hp-modal-close">
            <?php _e('Cancel', 'heritagepress'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Handle form submission
    $('#edit-repository-form').submit(function(e) {
      e.preventDefault();

      var formData = $(this).serialize();
      var button = $(':submit:focus', this);
      var action = button.attr('name');

      // Add the specific action to form data
      formData += '&submit_action=' + action;

      $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        success: function(response) {
          // Handle success - reload page or redirect based on action
          if (action === 'save_return') {
            window.location.href = '<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=search'); ?>';
          } else {
            location.reload();
          }
        },
        error: function() {
          alert('<?php echo esc_js(__('An error occurred while saving.', 'heritagepress')); ?>');
        }
      });
    });

    // Delete repository
    $('#delete_repository').click(function() {
      if (confirm('<?php echo esc_js(__('Are you sure you want to delete this repository? This action cannot be undone.', 'heritagepress')); ?>')) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_delete_repository',
            repoID: '<?php echo esc_js($repository['repoID']); ?>',
            gedcom: '<?php echo esc_js($repository['gedcom']); ?>',
            nonce: '<?php echo wp_create_nonce('hp_repository_nonce'); ?>'
          },
          success: function(response) {
            if (response.success) {
              alert('<?php echo esc_js(__('Repository deleted successfully.', 'heritagepress')); ?>');
              window.location.href = '<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=search'); ?>';
            } else {
              alert('<?php echo esc_js(__('Error:', 'heritagepress')); ?> ' + response.data);
            }
          },
          error: function() {
            alert('<?php echo esc_js(__('An error occurred while deleting the repository.', 'heritagepress')); ?>');
          }
        });
      }
    });

    // Show notes modal
    $('#show-notes-btn').click(function() {
      $('#repository-notes-modal').show();
    });

    // Close modals
    $('.hp-modal-close').click(function() {
      $('.hp-modal').hide();
    });

    $(window).click(function(event) {
      if (event.target.classList.contains('hp-modal')) {
        $('.hp-modal').hide();
      }
    });

    // Tree change functionality
    $('#confirm-tree-change').click(function() {
      var newTree = $('#new_tree').val();
      // This would typically involve more complex logic to move the repository
      alert('<?php echo esc_js(__('Tree change functionality will be implemented in a future update.', 'heritagepress')); ?>');
      $('#tree-change-modal').hide();
    });

  });

  // Change tree function
  function changeTree(currentTree, repoID) {
    jQuery('#tree-change-modal').show();
    return false;
  }

  // Add repository event function (placeholder)
  function addRepositoryEvent() {
    alert('<?php echo esc_js(__('Event management functionality will be implemented in a future update.', 'heritagepress')); ?>');
  }
</script>

<style>
  .repository-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
  }

  .repository-header h3 {
    margin: 0;
  }

  .repository-actions .button {
    margin-left: 10px;
  }

  .repository-meta {
    margin-bottom: 20px;
    padding: 10px;
    background: #f0f0f1;
    border-radius: 4px;
  }

  .last-modified {
    margin: 0;
    font-size: 12px;
    color: #646970;
  }

  .hp-repository-form {
    max-width: 800px;
  }

  .hp-form-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
  }

  .hp-form-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #23282d;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
  }

  .required {
    color: #d63638;
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

  .delete-button {
    color: #d63638 !important;
    border-color: #d63638 !important;
  }

  .delete-button:hover {
    background: #d63638 !important;
    color: #fff !important;
  }

  .form-table th {
    width: 150px;
  }

  .small-text {
    width: 120px;
  }

  .button-link {
    text-decoration: none;
    color: #0073aa;
    margin-left: 10px;
    font-size: 12px;
  }

  .button-link:hover {
    color: #005177;
  }

  /* Modal styles */
  .hp-modal {
    position: fixed;
    z-index: 100000;
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
    border-radius: 4px;
    width: 80%;
    max-width: 600px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .hp-modal-header {
    background: #f1f1f1;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    border-radius: 4px 4px 0 0;
    position: relative;
  }

  .hp-modal-header h3 {
    margin: 0;
    font-size: 18px;
  }

  .hp-modal-close {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .hp-modal-close:hover,
  .hp-modal-close:focus {
    color: black;
  }

  .hp-modal-body {
    padding: 20px;
  }

  .modal-actions {
    text-align: right;
    padding-top: 15px;
    border-top: 1px solid #ddd;
    margin-top: 15px;
  }

  .modal-actions .button {
    margin-left: 10px;
  }

  /* Responsive design */
  @media (max-width: 768px) {
    .repository-header {
      flex-direction: column;
      align-items: flex-start;
    }

    .repository-actions {
      margin-top: 10px;
    }

    .hp-repository-form {
      max-width: 100%;
    }

    .form-table th,
    .form-table td {
      display: block;
      width: 100%;
      padding: 10px 0;
    }

    .form-table th {
      border-bottom: none;
      padding-bottom: 5px;
    }
  }
</style>
