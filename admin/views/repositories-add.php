<?php

/**
 * Add Repository View for HeritagePress
 *
 * This file provides the add repository interface.
 * Based on admin_newrepo.php functionality
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

// Get selected tree (from cookie or first available)
$selected_tree = '';
if (isset($_COOKIE['heritagepress_tree'])) {
  $selected_tree = sanitize_text_field($_COOKIE['heritagepress_tree']);
} elseif (!empty($trees)) {
  $selected_tree = $trees[0]['gedcom'];
}
?>

<div class="add-repository-section">
  <div class="hp-admin-block">
    <h3><?php _e('Add New Repository', 'heritagepress'); ?></h3>

    <form method="post" id="add-repository-form" class="hp-repository-form" onsubmit="return validateRepositoryForm();">
      <?php wp_nonce_field('hp_add_repository', 'hp_repository_nonce'); ?>
      <input type="hidden" name="action" value="add_repository">

      <!-- Repository Identification -->
      <div class="hp-form-section">
        <h4><?php _e('Repository Identification', 'heritagepress'); ?></h4>

        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <select name="gedcom" id="gedcom" required onchange="generateRepositoryID();">
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>"
                    <?php selected($selected_tree, $tree['gedcom']); ?>>
                    <?php echo esc_html($tree['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="repoID"><?php _e('Repository ID:', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <input type="text" name="repoID" id="repoID" class="regular-text" required
                onblur="this.value=this.value.toUpperCase(); checkRepositoryID();">
              <button type="button" id="generate-id-btn" class="button">
                <?php _e('Generate', 'heritagepress'); ?>
              </button>
              <button type="button" id="check-id-btn" class="button">
                <?php _e('Check Availability', 'heritagepress'); ?>
              </button>
              <span id="repo-id-status" class="repo-id-status"></span>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="reponame"><?php _e('Repository Name:', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <input type="text" name="reponame" id="reponame" class="regular-text" required>
              <p class="description"><?php _e('Full name of the repository, library, or archive.', 'heritagepress'); ?></p>
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
              <input type="text" name="address1" id="address1" class="regular-text">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="address2"><?php _e('Address Line 2:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="address2" id="address2" class="regular-text">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="city"><?php _e('City:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="city" id="city" class="regular-text">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="state"><?php _e('State/Province:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="state" id="state" class="regular-text">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="zip"><?php _e('ZIP/Postal Code:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="zip" id="zip" class="small-text">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="country"><?php _e('Country:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="country" id="country" class="regular-text">
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
              <input type="tel" name="phone" id="phone" class="regular-text">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="email"><?php _e('Email:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="email" name="email" id="email" class="regular-text">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="www"><?php _e('Website:', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="url" name="www" id="www" class="regular-text">
              <p class="description"><?php _e('Include http:// or https://', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>

      <!-- Submit Section -->
      <div class="hp-form-section">
        <p class="description">
          <strong><?php _e('Note:', 'heritagepress'); ?></strong>
          <?php _e('After saving, you will be able to add additional details and manage sources associated with this repository.', 'heritagepress'); ?>
        </p>

        <div class="submit-actions">
          <input type="submit" name="save_repository" id="save_repository" class="button-primary"
            value="<?php esc_attr_e('Save Repository', 'heritagepress'); ?>">
          <a href="<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=search'); ?>" class="button">
            <?php _e('Cancel', 'heritagepress'); ?>
          </a>
        </div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Generate repository ID
    $('#generate-id-btn').click(function() {
      generateRepositoryID();
    });

    // Check repository ID availability
    $('#check-id-btn').click(function() {
      checkRepositoryID();
    });

    // Auto-generate ID when page loads
    generateRepositoryID();

    function generateRepositoryID() {
      var gedcom = $('#gedcom').val();
      if (!gedcom) return;

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_generate_repo_id',
          gedcom: gedcom,
          nonce: '<?php echo wp_create_nonce('hp_repository_nonce'); ?>'
        },
        success: function(response) {
          if (response.success) {
            $('#repoID').val(response.data.repoID);
            checkRepositoryID();
          }
        }
      });
    }

    function checkRepositoryID() {
      var repoID = $('#repoID').val();
      var gedcom = $('#gedcom').val();
      var statusEl = $('#repo-id-status');

      if (!repoID || !gedcom) {
        statusEl.html('').removeClass('available unavailable');
        return;
      }

      statusEl.html('<span class="spinner is-active"></span>');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_check_repo_id',
          repoID: repoID,
          gedcom: gedcom,
          nonce: '<?php echo wp_create_nonce('hp_repository_nonce'); ?>'
        },
        success: function(response) {
          if (response.success) {
            if (response.data.exists) {
              statusEl.html('<span class="unavailable">âœ— ' + response.data.message + '</span>')
                .removeClass('available').addClass('unavailable');
            } else {
              statusEl.html('<span class="available">âœ“ ' + response.data.message + '</span>')
                .removeClass('unavailable').addClass('available');
            }
          }
        },
        error: function() {
          statusEl.html('').removeClass('available unavailable');
        }
      });
    }

    // Make checkRepositoryID available globally
    window.checkRepositoryID = checkRepositoryID;
    window.generateRepositoryID = generateRepositoryID;

  });

  // Form validation
  function validateRepositoryForm() {
    var repoID = document.getElementById('repoID').value.trim();
    var repoName = document.getElementById('reponame').value.trim();

    if (!repoID) {
      alert('<?php echo esc_js(__('Repository ID is required.', 'heritagepress')); ?>');
      document.getElementById('repoID').focus();
      return false;
    }

    if (!repoName) {
      alert('<?php echo esc_js(__('Repository name is required.', 'heritagepress')); ?>');
      document.getElementById('reponame').focus();
      return false;
    }

    // Check if ID is available
    var statusEl = document.getElementById('repo-id-status');
    if (statusEl && statusEl.classList.contains('unavailable')) {
      alert('<?php echo esc_js(__('Please choose a different Repository ID. The current one is not available.', 'heritagepress')); ?>');
      document.getElementById('repoID').focus();
      return false;
    }

    return true;
  }

  // Set cookie when tree changes
  function setTreeCookie() {
    var gedcom = document.getElementById('gedcom').value;
    document.cookie = 'heritagepress_tree=' + gedcom + '; path=/';
  }

  document.getElementById('gedcom').addEventListener('change', setTreeCookie);
</script>

<style>
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

  .repo-id-status {
    margin-left: 10px;
    font-weight: bold;
  }

  .repo-id-status .available {
    color: #00a32a;
  }

  .repo-id-status .unavailable {
    color: #d63638;
  }

  .repo-id-status .spinner {
    float: none;
    margin: 0;
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

  .form-table th {
    width: 150px;
  }

  .small-text {
    width: 120px;
  }

  /* Responsive design */
  @media (max-width: 768px) {
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
