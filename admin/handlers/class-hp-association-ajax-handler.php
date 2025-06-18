<?php

/**
 * HeritagePress Association AJAX Handler
 *
 * Handles AJAX requests for association management
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Association_Ajax_Handler
{

  private $association_manager;

  public function __construct()
  {
    $this->association_manager = new HP_Association_Manager();
    $this->init_hooks();
  }

  /**
   * Initialize WordPress hooks
   */
  private function init_hooks()
  {
    add_action('wp_ajax_hp_add_association', [$this, 'handle_add_association']);
    add_action('wp_ajax_hp_delete_association', [$this, 'handle_delete_association']);
    add_action('wp_ajax_hp_get_person_associations', [$this, 'handle_get_person_associations']);
  }

  /**
   * Handle AJAX request to add association
   */
  public function handle_add_association()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hp_association_nonce')) {
      wp_die('Security check failed');
    }

    // Check permissions
    if (!current_user_can('edit_posts')) {
      wp_die('Insufficient permissions');
    }

    try {
      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
      $person_id = sanitize_text_field($_POST['person_id'] ?? '');
      $associated_id = sanitize_text_field($_POST['associated_id'] ?? '');
      $relationship = sanitize_text_field($_POST['relationship'] ?? '');
      $rel_type = sanitize_text_field($_POST['rel_type'] ?? 'I');
      $create_reverse = !empty($_POST['create_reverse']);

      // Validate data
      $validation = $this->association_manager->validate_association_data([
        'gedcom' => $gedcom,
        'person_id' => $person_id,
        'associated_id' => $associated_id,
        'relationship' => $relationship,
        'rel_type' => $rel_type
      ]);

      if (!$validation['valid']) {
        wp_send_json_error([
          'message' => 'Validation failed',
          'errors' => $validation['errors']
        ]);
        return;
      }

      // Add association
      $association_id = $this->association_manager->add_association(
        $gedcom,
        $person_id,
        $associated_id,
        $relationship,
        $rel_type,
        $create_reverse
      );

      if ($association_id === false) {
        wp_send_json_error(['message' => 'Failed to create association']);
        return;
      }

      // Get display name for response
      $display_name = $this->association_manager->get_associated_display_name(
        $gedcom,
        $associated_id,
        $rel_type
      );

      // Format display with relationship
      $display_string = $this->truncate_string(
        $display_name . ': ' . stripslashes($relationship),
        75
      );

      wp_send_json_success([
        'id' => $association_id,
        'person_id' => $person_id,
        'tree' => $gedcom,
        'display' => $display_string,
        'allow_edit' => current_user_can('edit_posts'),
        'allow_delete' => current_user_can('delete_posts')
      ]);
    } catch (Exception $e) {
      error_log('HeritagePress Association Error: ' . $e->getMessage());
      wp_send_json_error(['message' => 'An error occurred while adding the association']);
    }
  }

  /**
   * Handle AJAX request to delete association
   */
  public function handle_delete_association()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hp_association_nonce')) {
      wp_die('Security check failed');
    }

    // Check permissions
    if (!current_user_can('delete_posts')) {
      wp_die('Insufficient permissions');
    }

    try {
      $association_id = intval($_POST['association_id'] ?? 0);

      if ($association_id <= 0) {
        wp_send_json_error(['message' => 'Invalid association ID']);
        return;
      }

      $result = $this->association_manager->delete_association($association_id);

      if ($result) {
        wp_send_json_success(['message' => 'Association deleted successfully']);
      } else {
        wp_send_json_error(['message' => 'Failed to delete association']);
      }
    } catch (Exception $e) {
      error_log('HeritagePress Association Error: ' . $e->getMessage());
      wp_send_json_error(['message' => 'An error occurred while deleting the association']);
    }
  }

  /**
   * Handle AJAX request to get person associations
   */
  public function handle_get_person_associations()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hp_association_nonce')) {
      wp_die('Security check failed');
    }

    // Check permissions
    if (!current_user_can('read')) {
      wp_die('Insufficient permissions');
    }

    try {
      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');
      $person_id = sanitize_text_field($_POST['person_id'] ?? '');

      if (empty($gedcom) || empty($person_id)) {
        wp_send_json_error(['message' => 'Tree and person ID are required']);
        return;
      }

      $associations = $this->association_manager->get_person_associations($gedcom, $person_id);

      // Format associations for display
      $formatted_associations = [];
      foreach ($associations as $assoc) {
        $display_name = $this->association_manager->get_associated_display_name(
          $assoc['gedcom'],
          $assoc['passocID'],
          $assoc['reltype']
        );

        $formatted_associations[] = [
          'id' => $assoc['assocID'],
          'person_id' => $assoc['personID'],
          'associated_id' => $assoc['passocID'],
          'relationship' => $assoc['relationship'],
          'rel_type' => $assoc['reltype'],
          'display_name' => $display_name,
          'tree' => $assoc['gedcom']
        ];
      }

      wp_send_json_success(['associations' => $formatted_associations]);
    } catch (Exception $e) {
      error_log('HeritagePress Association Error: ' . $e->getMessage());
      wp_send_json_error(['message' => 'An error occurred while retrieving associations']);
    }
  }

  /**
   * Truncate string to specified length
   *
   * @param string $string String to truncate
   * @param int $length Maximum length
   * @return string Truncated string
   */
  private function truncate_string($string, $length)
  {
    if (strlen($string) <= $length) {
      return $string;
    }

    return substr($string, 0, $length - 3) . '...';
  }
}
