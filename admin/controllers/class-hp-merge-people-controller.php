<?php

/**
 * Merge People Controller for HeritagePress
 *
 * Provides admin UI and logic for merging two individuals (people) in the genealogy database.
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Merge_People_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_menu'));
  }

  public function register_menu()
  {
    add_submenu_page(
      'heritagepress',
      __('Merge People', 'heritagepress'),
      __('Merge People', 'heritagepress'),
      'edit_genealogy',
      'heritagepress-merge-people',
      array($this, 'display_page')
    );
  }

  public function display_page()
  {
    if (!current_user_can('edit_genealogy')) {
      wp_die(__('You do not have permission to access this page.', 'heritagepress'));
    }
    // Handle merge operation
    if (
      $_SERVER['REQUEST_METHOD'] === 'POST' &&
      isset($_POST['hp_merge_people_nonce']) &&
      wp_verify_nonce($_POST['hp_merge_people_nonce'], 'hp_merge_people') &&
      isset($_POST['merge_people']) &&
      isset($_POST['keep']) &&
      isset($_POST['person_id_1']) &&
      isset($_POST['person_id_2'])
    ) {
      $person_id_1 = sanitize_text_field($_POST['person_id_1']);
      $person_id_2 = sanitize_text_field($_POST['person_id_2']);
      $keep = array_map('sanitize_text_field', $_POST['keep']);
      $result = $this->merge_people($person_id_1, $person_id_2, $keep);
      include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/merge-people.php';
      echo $result;
      return;
    }
    // Handle compare
    if (
      $_SERVER['REQUEST_METHOD'] === 'POST' &&
      isset($_POST['hp_merge_people_nonce']) &&
      wp_verify_nonce($_POST['hp_merge_people_nonce'], 'hp_merge_people')
    ) {
      $person_id_1 = sanitize_text_field($_POST['person_id_1'] ?? '');
      $person_id_2 = sanitize_text_field($_POST['person_id_2'] ?? '');
      $result = $this->compare_people($person_id_1, $person_id_2);
      include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/merge-people.php';
      echo $result;
      return;
    }
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/merge-people.php';
  }

  /**
   * Compare two people and return HTML table for review/merge
   */
  private function compare_people($person_id_1, $person_id_2)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'hp_people';
    $p1 = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE personID = %s", $person_id_1), ARRAY_A);
    $p2 = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE personID = %s", $person_id_2), ARRAY_A);
    if (!$p1 || !$p2) {
      return '<div class="notice notice-error"><p>' . esc_html__('One or both Person IDs not found.', 'heritagepress') . '</p></div>';
    }
    $fields = array_unique(array_merge(array_keys($p1), array_keys($p2)));
    $html = '<h2>' . esc_html__('Compare People', 'heritagepress') . '</h2>';
    $html .= '<form method="post">';
    $html .= wp_nonce_field('hp_merge_people', 'hp_merge_people_nonce', true, false);
    $html .= '<input type="hidden" name="person_id_1" value="' . esc_attr($person_id_1) . '">';
    $html .= '<input type="hidden" name="person_id_2" value="' . esc_attr($person_id_2) . '">';
    $html .= '<table class="widefat striped"><thead><tr><th>' . esc_html__('Field', 'heritagepress') . '</th><th>' . esc_html($person_id_1) . '</th><th>' . esc_html($person_id_2) . '</th><th>' . esc_html__('Keep', 'heritagepress') . '</th></tr></thead><tbody>';
    foreach ($fields as $field) {
      $html .= '<tr>';
      $html .= '<td>' . esc_html($field) . '</td>';
      $html .= '<td>' . esc_html($p1[$field] ?? '') . '</td>';
      $html .= '<td>' . esc_html($p2[$field] ?? '') . '</td>';
      $html .= '<td>';
      $html .= '<select name="keep[' . esc_attr($field) . ']">';
      $html .= '<option value="1">' . esc_html($person_id_1) . '</option>';
      $html .= '<option value="2">' . esc_html($person_id_2) . '</option>';
      $html .= '</select>';
      $html .= '</td>';
      $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<p class="submit"><input type="submit" class="button button-primary" name="merge_people" value="' . esc_attr__('Merge', 'heritagepress') . '"></p>';
    $html .= '</form>';
    return $html;
  }

  /**
   * Merge two people based on selected fields
   */
  private function merge_people($person_id_1, $person_id_2, $keep)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'hp_people';
    $p1 = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE personID = %s", $person_id_1), ARRAY_A);
    $p2 = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE personID = %s", $person_id_2), ARRAY_A);
    if (!$p1 || !$p2) {
      return '<div class="notice notice-error"><p>' . esc_html__('One or both Person IDs not found.', 'heritagepress') . '</p></div>';
    }
    // Build merged data
    $merged = [];
    foreach ($keep as $field => $which) {
      $merged[$field] = ($which == '1') ? ($p1[$field] ?? '') : ($p2[$field] ?? '');
    }
    // Always keep personID_1 as the surviving record
    $merged['personID'] = $person_id_1;
    // Update surviving record
    $wpdb->update($table, $merged, ['personID' => $person_id_1]);
    // Update all related tables to point to person_id_1 instead of person_id_2
    $related_tables = [
      $wpdb->prefix . 'hp_families' => ['husband', 'wife'],
      $wpdb->prefix . 'hp_medialinks' => ['personID'],
      $wpdb->prefix . 'hp_events' => ['persfamID'],
      // Add more as needed
    ];
    foreach ($related_tables as $rel_table => $fields) {
      foreach ($fields as $field) {
        $wpdb->update($rel_table, [$field => $person_id_1], [$field => $person_id_2]);
      }
    }
    // Delete the duplicate record
    $wpdb->delete($table, ['personID' => $person_id_2]);
    return '<div class="notice notice-success"><p>' . esc_html__('People merged successfully.', 'heritagepress') . '</p></div>';
  }
}

// Initialize controller
new HP_Merge_People_Controller();
