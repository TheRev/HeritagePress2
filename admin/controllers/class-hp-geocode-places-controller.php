<?php

/**
 * Geocode Places Controller
 * Provides an admin tool to batch geocode places without coordinates.
 */
if (!defined('ABSPATH')) exit;
class HP_Geocode_Places_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_page'));
    add_action('wp_ajax_hp_geocode_places', array($this, 'ajax_geocode_places'));
  }
  public function register_page()
  {
    add_submenu_page(
      'hp_places',
      __('Geocode Places', 'heritagepress'),
      __('Geocode Places', 'heritagepress'),
      'manage_options',
      'hp_geocode_places',
      array($this, 'render_page')
    );
  }
  public function render_page()
  {
    include dirname(__FILE__) . '/../views/geocode-places.php';
  }
  public function ajax_geocode_places()
  {
    check_ajax_referer('hp_geocode_places');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Insufficient permissions.')]);
    }
    global $wpdb;
    $tree = isset($_POST['tree']) ? sanitize_text_field($_POST['tree']) : '';
    $where = "(latitude = '' OR latitude IS NULL) AND (longitude = '' OR longitude IS NULL) AND geoignore != '1'";
    if ($tree) {
      $where .= $wpdb->prepare(' AND gedcom = %s', $tree);
    }
    $places = $wpdb->get_results("SELECT ID, place FROM {$wpdb->prefix}hp_places WHERE $where");
    $geocoded = 0;
    $errors = 0;
    $error_list = [];
    foreach ($places as $row) {
      $address = trim($row->place);
      if ($address) {
        // Example: Use OpenStreetMap Nominatim API (replace with your API key/service as needed)
        $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($address);
        $response = wp_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($response)) {
          $errors++;
          $error_list[] = $address;
          continue;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
          $wpdb->update(
            $wpdb->prefix . 'hp_places',
            ['latitude' => $data[0]['lat'], 'longitude' => $data[0]['lon']],
            ['ID' => $row->ID],
            ['%s', '%s'],
            ['%d']
          );
          $geocoded++;
        } else {
          $errors++;
          $error_list[] = $address;
        }
      }
    }
    wp_send_json_success(['message' => __('Geocoded: ') . $geocoded . '<br>Errors: ' . $errors . ($errors ? '<br>Failed:<br>' . implode('<br>', $error_list) : '')]);
  }
}
new HP_Geocode_Places_Controller();
