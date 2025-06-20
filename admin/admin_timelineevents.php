<?php

/**
 * HeritagePress Timeline Events Admin Page
 * WordPress-native admin page for managing timeline events (ported from TNG)
 */
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
  add_submenu_page(
    'heritagepress',
    __('Timeline Events', 'heritagepress'),
    __('Timeline Events', 'heritagepress'),
    'manage_options',
    'heritagepress-timelineevents',
    'heritagepress_admin_timelineevents_page'
  );
});

function heritagepress_admin_timelineevents_page()
{
  global $wpdb;
  $table = $wpdb->prefix . 'hp_timeline_events';
  $per_page = 20;
  $paged = max(1, intval($_GET['paged'] ?? 1));
  $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
  $where = '';
  $params = [];
  if ($search) {
    $where = "WHERE evyear LIKE %s OR evtitle LIKE %s OR evdetail LIKE %s";
    $like = '%' . $wpdb->esc_like($search) . '%';
    $params = [$like, $like, $like];
  }
  $offset = ($paged - 1) * $per_page;
  $total = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table " . ($where ? $where : ''),
    ...$params
  ));
  $events = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table " . ($where ? $where : '') . " ORDER BY ABS(evyear), evmonth, evday LIMIT %d OFFSET %d",
    ...$params,
    $per_page,
    $offset
  ));
  echo '<div class="wrap"><h1>' . esc_html__('Timeline Events', 'heritagepress') . '</h1>';
  echo '<form method="get"><input type="hidden" name="page" value="heritagepress-timelineevents" />';
  echo '<input type="search" name="s" value="' . esc_attr($search) . '" placeholder="' . esc_attr__('Search events...', 'heritagepress') . '" /> ';
  submit_button(__('Search'), 'secondary', '', false);
  echo '</form>';
  echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-newtlevent')) . '" class="page-title-action">' . esc_html__('Add New', 'heritagepress') . '</a>';
  if ($events) {
    echo '<form method="post">';
    wp_nonce_field('hp_bulk_delete_tlevents', 'hp_bulk_delete_nonce');
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th class="manage-column column-cb check-column"><input type="checkbox" onclick="jQuery(\'.hp-check-all\').prop(\'checked\', this.checked);" /></th>';
    echo '<th>' . esc_html__('Year', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('End Year', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Title', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Detail', 'heritagepress') . '</th>';
    echo '<th>' . esc_html__('Actions', 'heritagepress') . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($events as $event) {
      echo '<tr>';
      echo '<th class="check-column"><input type="checkbox" name="delete[]" value="' . esc_attr($event->tleventID) . '" class="hp-check-all" /></th>';
      echo '<td>' . esc_html($event->evyear) . '</td>';
      echo '<td>' . esc_html($event->endyear) . '</td>';
      echo '<td>' . esc_html($event->evtitle) . '</td>';
      echo '<td>' . esc_html($event->evdetail) . '</td>';
      echo '<td>';
      echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-edittlevent&tleventID=' . intval($event->tleventID))) . '" class="button button-small">' . esc_html__('Edit', 'heritagepress') . '</a> ';
      echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=heritagepress-timelineevents&delete=' . intval($event->tleventID)), 'hp_delete_tlevent_' . intval($event->tleventID))) . '" class="button button-small delete">' . esc_html__('Delete', 'heritagepress') . '</a>';
      echo '</td>';
      echo '</tr>';
    }
    echo '</tbody></table>';
    submit_button(__('Delete Selected', 'heritagepress'), 'delete');
    echo '</form>';
    // Pagination
    $page_links = paginate_links([
      'base' => add_query_arg('paged', '%#%'),
      'format' => '',
      'prev_text' => __('&laquo;'),
      'next_text' => __('&raquo;'),
      'total' => ceil($total / $per_page),
      'current' => $paged
    ]);
    if ($page_links) {
      echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
    }
  } else {
    echo '<p>' . esc_html__('No timeline events found.', 'heritagepress') . '</p>';
  }
  echo '</div>';
}
