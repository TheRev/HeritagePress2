<?php

/**
 * Review Management Page (Temp Events) for HeritagePress
 */
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'hp_temp_events';
$reviews = $wpdb->get_results("SELECT * FROM $table ORDER BY tempID DESC");

// Nonce for AJAX delete
$nonce = wp_create_nonce('hp_review_nonce');
?>
<div class="wrap">
  <h1><?php _e('Review Data', 'heritagepress'); ?></h1>
  <?php if (empty($reviews)): ?>
    <p><?php _e('No review data found.', 'heritagepress'); ?></p>
  <?php else: ?>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php _e('ID', 'heritagepress'); ?></th>
          <th><?php _e('Type', 'heritagepress'); ?></th>
          <th><?php _e('Person/Family', 'heritagepress'); ?></th>
          <th><?php _e('Tree', 'heritagepress'); ?></th>
          <th><?php _e('Action', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reviews as $review): ?>
          <tr id="review-row-<?php echo esc_attr($review->tempID); ?>">
            <td><?php echo esc_html($review->tempID); ?></td>
            <td><?php echo esc_html($review->type); ?></td>
            <td><?php echo esc_html($review->type === 'F' ? $review->familyID : $review->personID); ?></td>
            <td><?php echo esc_html($review->gedcom); ?></td>
            <td>
              <button class="button button-small approve-review" data-id="<?php echo esc_attr($review->tempID); ?>">Approve</button>
              <button class="button button-small delete-review" data-id="<?php echo esc_attr($review->tempID); ?>">Reject</button>
              <button class="button button-small view-review" data-id="<?php echo esc_attr($review->tempID); ?>">View</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<script>
  jQuery(document).ready(function($) {
    $('.delete-review').on('click', function() {
      if (!confirm('<?php echo esc_js(__('Are you sure you want to reject this review?', 'heritagepress')); ?>')) return;
      var btn = $(this);
      var row = btn.closest('tr');
      var tempID = btn.data('id');
      $.post(ajaxurl, {
        action: 'hp_delete_review',
        tempID: tempID,
        nonce: '<?php echo esc_js($nonce); ?>'
      }, function(response) {
        if (response.success) {
          row.fadeOut(300, function() {
            $(this).remove();
          });
        } else {
          alert(response.data && response.data.message ? response.data.message : 'Delete failed');
        }
      });
    });
    $('.approve-review').on('click', function() {
      if (!confirm('<?php echo esc_js(__('Approve and publish this change?', 'heritagepress')); ?>')) return;
      var btn = $(this);
      var row = btn.closest('tr');
      var tempID = btn.data('id');
      $.post(ajaxurl, {
        action: 'hp_approve_review',
        tempID: tempID,
        nonce: '<?php echo esc_js($nonce); ?>'
      }, function(response) {
        if (response.success) {
          row.fadeOut(300, function() {
            $(this).remove();
          });
        } else {
          alert(response.data && response.data.message ? response.data.message : 'Approve failed');
        }
      });
    });
    $('.view-review').on('click', function() {
      var tempID = $(this).data('id');
      window.open('admin.php?page=heritagepress-review&view=' + tempID, '_blank');
    });
  });
</script>
