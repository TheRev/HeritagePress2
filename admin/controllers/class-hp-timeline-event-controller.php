<?php

/**
 * HeritagePress Timeline Event Admin Controller
 * Handles CRUD for timeline events (admin interface)
 */
if (!defined('ABSPATH')) exit;

class HP_Timeline_Event_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_menu'));
    add_action('admin_post_hp_save_timeline_event', array($this, 'save_event'));
    add_action('admin_post_hp_delete_timeline_event', array($this, 'delete_event'));
  }

  public function register_menu()
  {
    add_submenu_page(
      'heritagepress',
      __('Timeline Events', 'heritagepress'),
      __('Timeline Events', 'heritagepress'),
      'manage_options',
      'hp-timeline-events',
      array($this, 'render_admin_page')
    );
  }

  public function render_admin_page()
  {
    global $wpdb;
    $table = $wpdb->prefix . 'hp_timelineevents';
    $edit_event = null;
    if (isset($_GET['edit'])) {
      $edit_id = intval($_GET['edit']);
      $edit_event = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE tleventID = %d", $edit_id), 'ARRAY_A');
    }
    $events = $wpdb->get_results("SELECT * FROM $table ORDER BY evyear, evmonth, evday", 'ARRAY_A');
    include dirname(__FILE__) . '/../views/timeline-events-edit.php';
  }

  public function save_event()
  {
    if (!current_user_can('manage_options') || !check_admin_referer('hp_save_timeline_event')) {
      wp_die(__('Permission denied or invalid nonce.', 'heritagepress'));
    }
    global $wpdb;
    $table = $wpdb->prefix . 'hp_timelineevents';
    $data = array(
      'evday'    => intval($_POST['evday']),
      'evmonth'  => intval($_POST['evmonth']),
      'evyear'   => sanitize_text_field($_POST['evyear']),
      'endday'   => intval($_POST['endday']),
      'endmonth' => intval($_POST['endmonth']),
      'endyear'  => sanitize_text_field($_POST['endyear']),
      'evtitle'  => sanitize_text_field($_POST['evtitle']),
      'evdetail' => wp_kses_post($_POST['evdetail'])
    );
    if (!empty($_POST['tleventID'])) {
      $wpdb->update($table, $data, array('tleventID' => intval($_POST['tleventID'])));
    } else {
      $wpdb->insert($table, $data);
    }
    wp_redirect(admin_url('admin.php?page=hp-timeline-events&msg=saved'));
    exit;
  }

  public function delete_event()
  {
    if (!current_user_can('manage_options') || !check_admin_referer('hp_delete_timeline_event')) {
      wp_die(__('Permission denied or invalid nonce.', 'heritagepress'));
    }
    global $wpdb;
    $table = $wpdb->prefix . 'hp_timelineevents';
    $wpdb->delete($table, array('tleventID' => intval($_GET['tleventID'])));
    wp_redirect(admin_url('admin.php?page=hp-timeline-events&msg=deleted'));
    exit;
  }
}

new HP_Timeline_Event_Controller();
