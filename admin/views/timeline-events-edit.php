<?php

/**
 * Admin view for Timeline Events CRUD (HeritagePress)
 *
 * @var array $events List of timeline events
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
  <h1><?php _e('Timeline Events', 'heritagepress'); ?></h1>
  <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php _e('Timeline event saved.', 'heritagepress'); ?></p>
    </div>
  <?php elseif (!empty($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php _e('Timeline event deleted.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>

  <h2><?php _e('Add/Edit Timeline Event', 'heritagepress'); ?></h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('hp_save_timeline_event'); ?>
    <input type="hidden" name="action" value="hp_save_timeline_event">
    <input type="hidden" name="tleventID" value="<?php echo isset($_GET['edit']) ? intval($_GET['edit']) : ''; ?>">
    <table class="form-table">
      <tr>
        <th><label for="evtitle"><?php _e('Title', 'heritagepress'); ?></label></th>
        <td><input type="text" name="evtitle" id="evtitle" class="regular-text" value="<?php echo isset($edit_event['evtitle']) ? esc_attr($edit_event['evtitle']) : ''; ?>" required></td>
      </tr>
      <tr>
        <th><label for="evdetail"><?php _e('Details', 'heritagepress'); ?></label></th>
        <td><textarea name="evdetail" id="evdetail" class="large-text" rows="4"><?php echo isset($edit_event['evdetail']) ? esc_textarea($edit_event['evdetail']) : ''; ?></textarea></td>
      </tr>
      <tr>
        <th><?php _e('Start Date', 'heritagepress'); ?></th>
        <td>
          <input type="number" name="evday" min="1" max="31" placeholder="DD" style="width:60px;" value="<?php echo isset($edit_event['evday']) ? intval($edit_event['evday']) : ''; ?>">
          <input type="number" name="evmonth" min="1" max="12" placeholder="MM" style="width:60px;" value="<?php echo isset($edit_event['evmonth']) ? intval($edit_event['evmonth']) : ''; ?>">
          <input type="text" name="evyear" placeholder="YYYY" style="width:80px;" value="<?php echo isset($edit_event['evyear']) ? esc_attr($edit_event['evyear']) : ''; ?>">
        </td>
      </tr>
      <tr>
        <th><?php _e('End Date', 'heritagepress'); ?></th>
        <td>
          <input type="number" name="endday" min="1" max="31" placeholder="DD" style="width:60px;" value="<?php echo isset($edit_event['endday']) ? intval($edit_event['endday']) : ''; ?>">
          <input type="number" name="endmonth" min="1" max="12" placeholder="MM" style="width:60px;" value="<?php echo isset($edit_event['endmonth']) ? intval($edit_event['endmonth']) : ''; ?>">
          <input type="text" name="endyear" placeholder="YYYY" style="width:80px;" value="<?php echo isset($edit_event['endyear']) ? esc_attr($edit_event['endyear']) : ''; ?>">
        </td>
      </tr>
    </table>
    <p><input type="submit" class="button button-primary" value="<?php _e('Save Event', 'heritagepress'); ?>"></p>
  </form>

  <h2><?php _e('Existing Timeline Events', 'heritagepress'); ?></h2>
  <table class="widefat fixed striped">
    <thead>
      <tr>
        <th><?php _e('Title', 'heritagepress'); ?></th>
        <th><?php _e('Start Date', 'heritagepress'); ?></th>
        <th><?php _e('End Date', 'heritagepress'); ?></th>
        <th><?php _e('Actions', 'heritagepress'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($events)): ?>
        <?php foreach ($events as $event): ?>
          <tr>
            <td><?php echo esc_html($event['evtitle']); ?></td>
            <td><?php echo esc_html(sprintf('%02d-%02d-%s', $event['evday'], $event['evmonth'], $event['evyear'])); ?></td>
            <td><?php echo esc_html(sprintf('%02d-%02d-%s', $event['endday'], $event['endmonth'], $event['endyear'])); ?></td>
            <td>
              <a href="<?php echo esc_url(add_query_arg(['page' => 'hp-timeline-events', 'edit' => $event['tleventID']], admin_url('admin.php'))); ?>" class="button">Edit</a>
              <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'hp_delete_timeline_event', 'tleventID' => $event['tleventID']], admin_url('admin-post.php')), 'hp_delete_timeline_event')); ?>" class="button delete" onclick="return confirm('<?php _e('Delete this event?', 'heritagepress'); ?>');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4"><?php _e('No timeline events found.', 'heritagepress'); ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
