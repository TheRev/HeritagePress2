<?php

/**
 * Add Tree Tab - Admin Interface
 * Complete facsimile of HeritagePress admin_newtree.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get trees table for validation
$trees_table = $wpdb->prefix . 'hp_trees';

// Initialize form values
$form_data = array(
  'gedcom' => '',
  'treename' => '',
  'description' => '',
  'owner' => '',
  'email' => '',
  'address' => '',
  'city' => '',
  'state' => '',
  'zip' => '',
  'country' => '',
  'phone' => '',
  'private' => 0,
  'disallowgedcreate' => 0,
  'disallowpdf' => 0
);

// Pre-populate with current user data if available
$current_user = wp_get_current_user();
if ($current_user && $current_user->ID) {
  $form_data['owner'] = $current_user->display_name ?: $current_user->user_login;
  $form_data['email'] = $current_user->user_email;
}

// Handle error message from redirect
$error_message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

// Pre-fill form data if coming from a failed submission
$preserve_fields = array('treename', 'description', 'owner', 'email', 'address', 'city', 'state', 'country', 'zip', 'phone', 'private', 'disallowgedcreate', 'disallowpdf');
foreach ($preserve_fields as $field) {
  if (isset($_GET[$field])) {
    $form_data[$field] = sanitize_text_field($_GET[$field]);
  }
}

// Check if this is being called in import context
$before_import = isset($_GET['beforeimport']) ? sanitize_text_field($_GET['beforeimport']) : '';
$is_ajax_context = $before_import === 'yes';

?>

<?php if ($is_ajax_context): ?>
  <div class="databack ajaxwindow" id="newtree">
    <p class="subhead">
      <strong><?php _e('Add New Tree', 'heritagepress'); ?></strong> |
      <a href="#" onclick="return openHelp('trees_help.php#add', 'newwindow', 'height=500,width=700,resizable=yes,scrollbars=yes'); newwindow.focus();">
        <?php _e('Help', 'heritagepress'); ?>
      </a>
    </p>
  <?php else: ?>
    <div class="add-tree-admin-section">
      <?php if (!empty($error_message)): ?>
        <div class="notice notice-error is-dismissible">
          <p><?php echo esc_html($error_message); ?></p>
        </div>
      <?php endif; ?>

      <div class="form-card">
        <div class="form-card-header">
          <h2 class="form-card-title"><?php _e('Add New Tree', 'heritagepress'); ?></h2>
        </div>
        <div class="form-card-body">
        <?php endif; ?> <form action="" method="post" name="treeform" id="add-tree-form" onsubmit="return validateTreeForm(this);">
          <?php wp_nonce_field('heritagepress_add_tree'); ?>
          <input type="hidden" name="action" value="add_tree">
          <input type="hidden" name="beforeimport" value="<?php echo esc_attr($before_import); ?>">

          <table class="hp-form-table">
            <tr>
              <td>
                <label for="gedcom"><?php _e('Tree ID', 'heritagepress'); ?> <span class="required">*</span></label>
              </td>
              <td>
                <input type="text"
                  name="gedcom"
                  id="gedcom"
                  value="<?php echo esc_attr($form_data['gedcom']); ?>"
                  size="20"
                  maxlength="20"
                  required
                  class="regular-text"
                  pattern="[a-zA-Z0-9_-]+"
                  title="<?php _e('Tree ID must contain only letters, numbers, underscores, and hyphens', 'heritagepress'); ?>">
                <p class="description"><?php _e('Unique identifier for this tree (letters, numbers, underscore, hyphen only)', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <td>
                <label for="treename"><?php _e('Tree Name', 'heritagepress'); ?> <span class="required">*</span></label>
              </td>
              <td>
                <input type="text"
                  name="treename"
                  id="treename"
                  value="<?php echo esc_attr($form_data['treename']); ?>"
                  size="50"
                  maxlength="100"
                  required
                  class="regular-text">
                <p class="description"><?php _e('Display name for this family tree', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <td>
                <label for="description"><?php _e('Description', 'heritagepress'); ?></label>
              </td>
              <td>
                <textarea name="description"
                  id="description"
                  cols="50"
                  rows="3"
                  maxlength="255"
                  class="large-text"><?php echo esc_textarea($form_data['description']); ?></textarea>
                <p class="description"><?php _e('Optional description of this family tree', 'heritagepress'); ?></p>
              </td>
            </tr>
          </table>

          <!-- Owner Information Section -->
          <h3><?php _e('Owner Information', 'heritagepress'); ?></h3>
          <table class="hp-form-table">
            <tr>
              <td>
                <label for="owner"><?php _e('Owner Name', 'heritagepress'); ?></label>
              </td>
              <td>
                <input type="text"
                  name="owner"
                  id="owner"
                  value="<?php echo esc_attr($form_data['owner']); ?>"
                  size="50"
                  maxlength="100"
                  class="regular-text">
              </td>
            </tr>
            <tr>
              <td>
                <label for="email"><?php _e('Email Address', 'heritagepress'); ?></label>
              </td>
              <td>
                <input type="email"
                  name="email"
                  id="email"
                  value="<?php echo esc_attr($form_data['email']); ?>"
                  size="50"
                  maxlength="100"
                  class="regular-text">
              </td>
            </tr>
            <tr>
              <td>
                <label for="address"><?php _e('Address', 'heritagepress'); ?></label>
              </td>
              <td>
                <textarea name="address"
                  id="address"
                  cols="50"
                  rows="3"
                  maxlength="255"
                  class="large-text"><?php echo esc_textarea($form_data['address']); ?></textarea>
              </td>
            </tr>
            <tr>
              <td>
                <label for="city"><?php _e('City', 'heritagepress'); ?></label>
              </td>
              <td>
                <input type="text"
                  name="city"
                  id="city"
                  value="<?php echo esc_attr($form_data['city']); ?>"
                  size="50"
                  maxlength="50"
                  class="regular-text">
              </td>
            </tr>
            <tr>
              <td>
                <label for="state"><?php _e('State/Province', 'heritagepress'); ?></label>
              </td>
              <td>
                <input type="text"
                  name="state"
                  id="state"
                  value="<?php echo esc_attr($form_data['state']); ?>"
                  size="50"
                  maxlength="50"
                  class="regular-text">
              </td>
            </tr>
            <tr>
              <td>
                <label for="zip"><?php _e('ZIP/Postal Code', 'heritagepress'); ?></label>
              </td>
              <td>
                <input type="text"
                  name="zip"
                  id="zip"
                  value="<?php echo esc_attr($form_data['zip']); ?>"
                  size="50"
                  maxlength="10"
                  class="regular-text">
              </td>
            </tr>
            <tr>
              <td>
                <label for="country"><?php _e('Country', 'heritagepress'); ?></label>
              </td>
              <td>
                <input type="text"
                  name="country"
                  id="country"
                  value="<?php echo esc_attr($form_data['country']); ?>"
                  size="50"
                  maxlength="50"
                  class="regular-text">
              </td>
            </tr>
            <tr>
              <td>
                <label for="phone"><?php _e('Phone', 'heritagepress'); ?></label>
              </td>
              <td>
                <input type="text"
                  name="phone"
                  id="phone"
                  value="<?php echo esc_attr($form_data['phone']); ?>"
                  size="50"
                  maxlength="20"
                  class="regular-text">
              </td>
            </tr>
          </table>

          <!-- Tree Settings Section -->
          <h3><?php _e('Tree Settings', 'heritagepress'); ?></h3>
          <table class="hp-form-table">
            <tr>
              <td colspan="2">
                <label>
                  <input type="checkbox"
                    name="private"
                    id="private"
                    value="1"
                    <?php checked($form_data['private'], 1); ?>>
                  <?php _e('Keep Private', 'heritagepress'); ?>
                </label>
                <p class="description"><?php _e('Private trees are only visible to authorized users', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <label>
                  <input type="checkbox"
                    name="disallowgedcreate"
                    id="disallowgedcreate"
                    value="1"
                    <?php checked($form_data['disallowgedcreate'], 1); ?>>
                  <?php _e('Disable GEDCOM Extraction', 'heritagepress'); ?>
                </label>
                <p class="description"><?php _e('Prevent users from downloading GEDCOM files from this tree', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <label>
                  <input type="checkbox"
                    name="disallowpdf"
                    id="disallowpdf"
                    value="1"
                    <?php checked($form_data['disallowpdf'], 1); ?>>
                  <?php _e('Disable PDF Generation', 'heritagepress'); ?>
                </label>
                <p class="description"><?php _e('Prevent PDF reports from being generated for this tree', 'heritagepress'); ?></p>
              </td>
            </tr>
          </table>

          <div class="submit-section">
            <?php if ($is_ajax_context): ?>
              <input type="submit" name="submit" accesskey="s" class="button button-primary" value="<?php _e('Save', 'heritagepress'); ?>">
              <input type="button" name="cancel" class="button" value="<?php _e('Cancel', 'heritagepress'); ?>" onclick="jQuery('#LB_close').click();">
            <?php else: ?>
              <input type="submit" name="submitx" class="button button-primary" value="<?php _e('Save and Return to Trees', 'heritagepress'); ?>">
              <input type="submit" name="submit" accesskey="s" class="button" value="<?php _e('Save and Edit Tree', 'heritagepress'); ?>">
              <input type="button" name="cancel" class="button" value="<?php _e('Cancel', 'heritagepress'); ?>" onclick="window.location.href='?page=heritagepress-trees';">
            <?php endif; ?>
          </div>
        </form>

        <?php if (!$is_ajax_context): ?>
        </div>
      </div>
    </div>
  <?php else: ?>
  </div>
<?php endif; ?>

<script type="text/javascript">
  function validateTreeForm(form) {
    var rval = true;

    // Validate Tree ID
    if (form.gedcom.value.length == 0) {
      alert("<?php _e('Please enter a Tree ID.', 'heritagepress'); ?>");
      form.gedcom.focus();
      rval = false;
    } else if (!alphaNumericCheck(form.gedcom.value)) {
      alert("<?php _e('Tree ID must contain only letters, numbers, underscores, and hyphens.', 'heritagepress'); ?>");
      form.gedcom.focus();
      rval = false;
    } else if (form.treename.value.length == 0) {
      alert("<?php _e('Please enter a Tree Name.', 'heritagepress'); ?>");
      form.treename.focus();
      rval = false;
    }

    return rval;
  }

  function alphaNumericCheck(string) {
    var regex = /^[0-9A-Za-z_-]+$/;
    return regex.test(string);
  }

  // AJAX tree ID availability check
  jQuery(document).ready(function($) {
    var gedcomInput = $('#gedcom');
    var checkTimeout;

    gedcomInput.on('input', function() {
      var treeId = $(this).val().trim();

      // Clear previous timeout
      clearTimeout(checkTimeout);

      // Remove any existing availability indicators
      $('.tree-id-availability').remove();

      if (treeId.length > 0 && alphaNumericCheck(treeId)) {
        checkTimeout = setTimeout(function() {
          checkTreeIdAvailability(treeId);
        }, 500);
      }
    });

    function checkTreeIdAvailability(treeId) {
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_check_tree_id',
          tree_id: treeId,
          nonce: '<?php echo wp_create_nonce('hp_check_tree_id'); ?>'
        },
        success: function(response) {
          var indicator = '';
          if (response.success) {
            if (response.data.exists) {
              indicator = '<span class="tree-id-availability error" style="color: #d63638; margin-left: 10px;"><span class="dashicons dashicons-no"></span> ' + "<?php _e('Tree ID already exists', 'heritagepress'); ?>" + '</span>';
            } else {
              indicator = '<span class="tree-id-availability success" style="color: #00a32a; margin-left: 10px;"><span class="dashicons dashicons-yes"></span> ' + "<?php _e('Tree ID available', 'heritagepress'); ?>" + '</span>';
            }
            gedcomInput.after(indicator);
          }
        }
      });
    }
  });
</script>

<style>
  .add-tree-admin-section .hp-form-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
  }

  .add-tree-admin-section .hp-form-table td {
    padding: 10px 15px;
    vertical-align: top;
    border: none;
  }

  .add-tree-admin-section .hp-form-table td:first-child {
    width: 200px;
    font-weight: 500;
    color: #1d2327;
  }

  .add-tree-admin-section .required {
    color: #d63638;
  }

  .add-tree-admin-section .submit-section {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #dcdcde;
  }

  .add-tree-admin-section h3 {
    margin: 25px 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid #dcdcde;
    font-size: 16px;
    font-weight: 600;
  }

  .add-tree-admin-section p.description {
    margin-top: 5px;
    margin-bottom: 0;
    color: #646970;
    font-style: italic;
    font-size: 13px;
  }

  /* AJAX context styling */
  .ajaxwindow .hp-form-table {
    border: 0;
    cellpadding: 10;
    cellspacing: 2;
  }

  .ajaxwindow .hp-form-table td {
    padding: 8px 10px;
  }
</style>
