<?php

/**
 * HeritagePress Add/Edit Timeline Event Admin Page
 * Handles both adding and editing timeline events.
 */
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
  add_submenu_page(
    null, // Hidden from menu, accessed via direct link
    __('Add/Edit Timeline Event', 'heritagepress'),
    __('Add/Edit Timeline Event', 'heritagepress'),
    'manage_options',
    'heritagepress-edittlevent',
    'heritagepress_admin_edittlevent_page'
  );
});

function heritagepress_admin_edittlevent_page()
{
  global $wpdb;
  $table = $wpdb->prefix . 'hp_timeline_events';
  $is_edit = isset($_GET['tleventID']);
  $tleventID = $is_edit ? intval($_GET['tleventID']) : 0;
  $event = $is_edit ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE tleventID = %d", $tleventID)) : null;
  $error = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('hp_save_tlevent')) {
    $evyear = sanitize_text_field($_POST['evyear'] ?? '');
    $endyear = sanitize_text_field($_POST['endyear'] ?? '');
    $evtitle = sanitize_text_field($_POST['evtitle'] ?? '');
    $evdetail = sanitize_textarea_field($_POST['evdetail'] ?? '');
    if ($evyear && $evtitle) {
      if ($is_edit) {
        $wpdb->update($table, [
          'evyear' => $evyear,
          'endyear' => $endyear,
          'evtitle' => $evtitle,
          'evdetail' => $evdetail
        ], ['tleventID' => $tleventID]);
        $msg = __('Timeline event updated.', 'heritagepress');
      } else {
        $wpdb->insert($table, [
          'evyear' => $evyear,
          'endyear' => $endyear,
          'evtitle' => $evtitle,
          'evdetail' => $evdetail
        ]);
        $msg = __('Timeline event added.', 'heritagepress');
      }
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
      echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-timelineevents')) . '" class="button">' . esc_html__('Back to Timeline Events', 'heritagepress') . '</a>';
      return;
    } else {
      $error = __('Year and Title are required.', 'heritagepress');
    }
  }
  echo '<div class="wrap">';
  echo '<h1>' . esc_html($is_edit ? __('Edit Timeline Event', 'heritagepress') : __('Add Timeline Event', 'heritagepress')) . '</h1>';
  if ($error) {
    echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
  }
  echo '<form method="post">';
  wp_nonce_field('hp_save_tlevent');
  echo '<table class="form-table">';
  echo '<tr><th><label for="evyear">' . esc_html__('Year', 'heritagepress') . '</label></th><td><input type="text" name="evyear" id="evyear" value="' . esc_attr($event->evyear ?? '') . '" required></td></tr>';
  echo '<tr><th><label for="endyear">' . esc_html__('End Year', 'heritagepress') . '</label></th><td><input type="text" name="endyear" id="endyear" value="' . esc_attr($event->endyear ?? '') . '"></td></tr>';
  echo '<tr><th><label for="evtitle">' . esc_html__('Title', 'heritagepress') . '</label></th><td><input type="text" name="evtitle" id="evtitle" value="' . esc_attr($event->evtitle ?? '') . '" required></td></tr>';
  echo '<tr><th><label for="evdetail">' . esc_html__('Detail', 'heritagepress') . '</label></th><td><textarea name="evdetail" id="evdetail" rows="4">' . esc_textarea($event->evdetail ?? '') . '</textarea></td></tr>';
  echo '</table>';
  submit_button($is_edit ? __('Update Event', 'heritagepress') : __('Add Event', 'heritagepress'));
  echo '</form>';
  echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-timelineevents')) . '" class="button">' . esc_html__('Back to Timeline Events', 'heritagepress') . '</a>';
  echo '</div>';
}
