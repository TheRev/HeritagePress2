<?php

/**
 * Edit DNA Group Template
 *
 * Complete replication of genealogy admin edit DNA group interface
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get DNA group data
$dna_group_id = sanitize_text_field($_GET['dna_group']);
$tree = sanitize_text_field($_GET['tree']);

$dna_groups_table = $wpdb->prefix . 'hp_dna_groups';
$trees_table = $wpdb->prefix . 'hp_trees';

// Get DNA group details
$group_query = "SELECT g.*, t.treename
                FROM $dna_groups_table g
                LEFT JOIN $trees_table t ON t.gedcom = g.gedcom
                WHERE g.dna_group = %s AND g.gedcom = %s";
$group_data = $wpdb->get_row($wpdb->prepare($group_query, $dna_group_id, $tree), ARRAY_A);

if (!$group_data) {
  echo '<div class="notice notice-error"><p>' . __('DNA Group not found.', 'heritagepress') . '</p></div>';
  echo '<p><a href="?page=heritagepress-dna-groups" class="button">' . __('Back to DNA Groups', 'heritagepress') . '</a></p>';
  return;
}

// Get test count
$dna_tests_table = $wpdb->prefix . 'hp_dna_tests';
$test_count = $wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM $dna_tests_table WHERE dna_group = %s AND gedcom = %s",
  $dna_group_id,
  $tree
));

?>

<div class="admin-block">

  <h2>
    <?php _e('Edit DNA Group', 'heritagepress'); ?> -
    <strong><?php echo esc_html($group_data['dna_group']); ?></strong>
  </h2>

  <form id="edit-dna-group-form" class="dna-group-form">
    <?php wp_nonce_field('hp_dna_nonce', 'nonce'); ?>
    <input type="hidden" name="dna_group" value="<?php echo esc_attr($group_data['dna_group']); ?>">
    <input type="hidden" name="gedcom" value="<?php echo esc_attr($group_data['gedcom']); ?>">

    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label><?php _e('Tree', 'heritagepress'); ?></label>
          </th>
          <td>
            <strong><?php echo esc_html($group_data['treename'] ?: $group_data['gedcom']); ?></strong>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label><?php _e('Group ID', 'heritagepress'); ?></label>
          </th>
          <td>
            <strong><?php echo esc_html($group_data['dna_group']); ?></strong>
            <p class="description">
              <?php _e('The group ID cannot be changed after creation.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="test_type"><?php _e('Test Type', 'heritagepress'); ?></label>
          </th>
          <td>
            <select id="test_type" name="test_type" required>
              <option value=""><?php _e('Select Test Type', 'heritagepress'); ?></option>
              <option value="atDNA" <?php selected($group_data['test_type'], 'atDNA'); ?>>
                <?php _e('Autosomal DNA (atDNA)', 'heritagepress'); ?>
              </option>
              <option value="Y-DNA" <?php selected($group_data['test_type'], 'Y-DNA'); ?>>
                <?php _e('Y-Chromosome DNA (Y-DNA)', 'heritagepress'); ?>
              </option>
              <option value="mtDNA" <?php selected($group_data['test_type'], 'mtDNA'); ?>>
                <?php _e('Mitochondrial DNA (mtDNA)', 'heritagepress'); ?>
              </option>
              <option value="X-DNA" <?php selected($group_data['test_type'], 'X-DNA'); ?>>
                <?php _e('X-Chromosome DNA (X-DNA)', 'heritagepress'); ?>
              </option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="description"><?php _e('Description', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" id="description" name="description"
              value="<?php echo esc_attr($group_data['description']); ?>"
              class="large-text" required>
            <p class="description">
              <?php _e('Enter a descriptive name for this DNA group.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label><?php _e('DNA Tests', 'heritagepress'); ?></label>
          </th>
          <td>
            <strong><?php echo intval($test_count); ?></strong>
            <?php
            printf(
              _n('test associated with this group', 'tests associated with this group', $test_count, 'heritagepress')
            );
            ?>
            <?php if ($test_count > 0): ?>
              <p class="description">
                <a href="?page=heritagepress-dna-tests&group=<?php echo urlencode($group_data['dna_group']); ?>&tree=<?php echo urlencode($group_data['gedcom']); ?>">
                  <?php _e('View DNA tests in this group', 'heritagepress'); ?>
                </a>
              </p>
            <?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit">
      <button type="submit" class="button button-primary">
        <?php _e('Update DNA Group', 'heritagepress'); ?>
      </button>
      <button type="button" class="button" onclick="window.location.href='?page=heritagepress-dna-groups'">
        <?php _e('Cancel', 'heritagepress'); ?>
      </button>
      <?php if (current_user_can('delete_genealogy') && $test_count == 0): ?>
        <button type="button" class="button button-secondary"
          onclick="deleteDNAGroupConfirm('<?php echo esc_js($group_data['dna_group']); ?>', '<?php echo esc_js($group_data['gedcom']); ?>')"
          style="margin-left: 20px; color: #d63638;">
          <?php _e('Delete Group', 'heritagepress'); ?>
        </button>
      <?php elseif ($test_count > 0): ?>
    <p class="description" style="margin-top: 10px; color: #666;">
      <?php _e('This DNA group cannot be deleted because it has associated DNA tests. Remove all tests first.', 'heritagepress'); ?>
    </p>
  <?php endif; ?>
  </p>
  </form>

</div>

<!-- Edit DNA Group JavaScript -->
<script>
  jQuery(document).ready(function($) {

    // Edit DNA group form submission
    $('#edit-dna-group-form').on('submit', function(e) {
      e.preventDefault();
      updateDNAGroup();
    });

    /**
     * Update DNA group
     */
    function updateDNAGroup() {
      var $form = $('#edit-dna-group-form');
      var $submit = $form.find('button[type="submit"]');

      $submit.prop('disabled', true).text('<?php echo esc_js(__('Updating...', 'heritagepress')); ?>');

      $.post(ajaxurl, {
          action: 'hp_update_dna_group',
          nonce: $('#nonce').val(),
          dna_group: $('input[name="dna_group"]').val(),
          gedcom: $('input[name="gedcom"]').val(),
          test_type: $('#test_type').val(),
          description: $('#description').val()
        })
        .done(function(response) {
          if (response.success) {
            showSuccess('<?php echo esc_js(__('DNA Group updated successfully!', 'heritagepress')); ?>');
            // Redirect to browse tab after a delay
            setTimeout(function() {
              window.location.href = '?page=heritagepress-dna-groups';
            }, 1500);
          } else {
            showError(response.data || '<?php echo esc_js(__('Failed to update DNA group.', 'heritagepress')); ?>');
          }
        })
        .fail(function() {
          showError('<?php echo esc_js(__('Error updating DNA group.', 'heritagepress')); ?>');
        })
        .always(function() {
          $submit.prop('disabled', false).text('<?php echo esc_js(__('Update DNA Group', 'heritagepress')); ?>');
        });
    }

    /**
     * Delete DNA group with confirmation
     */
    window.deleteDNAGroupConfirm = function(dnaGroupId, gedcom) {
      if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this DNA group? This action cannot be undone.', 'heritagepress')); ?>')) {
        return;
      }

      $.post(ajaxurl, {
          action: 'hp_delete_dna_group',
          nonce: $('#nonce').val(),
          dna_group: dnaGroupId,
          gedcom: gedcom
        })
        .done(function(response) {
          if (response.success) {
            showSuccess('<?php echo esc_js(__('DNA Group deleted successfully!', 'heritagepress')); ?>');
            // Redirect to browse tab
            setTimeout(function() {
              window.location.href = '?page=heritagepress-dna-groups';
            }, 1500);
          } else {
            showError(response.data || '<?php echo esc_js(__('Failed to delete DNA group.', 'heritagepress')); ?>');
          }
        })
        .fail(function() {
          showError('<?php echo esc_js(__('Error deleting DNA group.', 'heritagepress')); ?>');
        });
    };

    /**
     * Utility functions
     */
    function showSuccess(message) {
      // Add WordPress admin notice
      $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>')
        .insertAfter('.wrap h1')
        .delay(3000)
        .fadeOut();
    }

    function showError(message) {
      // Add WordPress admin notice
      $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>')
        .insertAfter('.wrap h1')
        .delay(5000)
        .fadeOut();
    }

  });
</script>
