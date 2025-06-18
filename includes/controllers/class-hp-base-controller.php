<?php

/**
 * Base Controller Abstract Class
 *
 * Provides common functionality for all HeritagePress admin controllers
 * Implements shared methods and enforces the controller interface
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . 'interface-hp-controller.php';

abstract class HP_Base_Controller implements HP_Controller_Interface
{
  /**
   * Controller name/slug
   */
  protected $controller_name;

  /**
   * Controller capabilities
   */
  protected $capabilities = array();

  /**
   * Admin notices for this controller
   */
  protected $notices = array();

  /**
   * Constructor
   */
  public function __construct($controller_name)
  {
    $this->controller_name = $controller_name;
    $this->init();
    $this->register_hooks();
  }

  /**
   * Initialize the controller
   * Override in child classes for specific initialization
   */
  public function init()
  {
    // Base initialization - can be extended by child classes
  }

  /**
   * Register common hooks
   * Child classes should call parent::register_hooks() then add their own
   */
  public function register_hooks()
  {
    // Common hooks for all controllers
    add_action('admin_notices', array($this, 'display_notices'));
  }

  /**
   * Base AJAX handling
   * Child classes should implement their specific AJAX handlers
   */
  public function handle_ajax()
  {
    // Base AJAX handling - extended by child classes
  }

  /**
   * Base asset enqueuing
   * Child classes should implement their specific assets
   */
  public function enqueue_assets()
  {
    // Base asset enqueuing - extended by child classes
  }

  /**
   * Get controller capabilities
   */
  public function get_capabilities()
  {
    return $this->capabilities;
  }

  /**
   * Check if current user has required capability
   */
  protected function check_capability($capability)
  {
    return current_user_can($capability);
  }

  /**
   * Add admin notice
   */
  protected function add_notice($message, $type = 'success', $dismissible = true)
  {
    $this->notices[] = array(
      'message' => $message,
      'type' => $type,
      'dismissible' => $dismissible
    );
  }

  /**
   * Display admin notices
   */
  public function display_notices()
  {
    foreach ($this->notices as $notice) {
      $dismissible_class = $notice['dismissible'] ? 'is-dismissible' : '';
      printf(
        '<div class="notice notice-%s %s"><p>%s</p></div>',
        esc_attr($notice['type']),
        esc_attr($dismissible_class),
        wp_kses_post($notice['message'])
      );
    }
    $this->notices = array(); // Clear notices after displaying
  }

  /**
   * Verify nonce for security
   */
  protected function verify_nonce($nonce_value, $action = 'heritagepress_admin_nonce')
  {
    return wp_verify_nonce($nonce_value, $action);
  }

  /**
   * Sanitize and validate form data
   */
  protected function sanitize_form_data($data, $fields = array())
  {
    $sanitized = array();

    foreach ($fields as $field => $type) {
      if (isset($data[$field])) {
        switch ($type) {
          case 'text':
            $sanitized[$field] = sanitize_text_field($data[$field]);
            break;
          case 'email':
            $sanitized[$field] = sanitize_email($data[$field]);
            break;
          case 'url':
            $sanitized[$field] = esc_url_raw($data[$field]);
            break;
          case 'textarea':
            $sanitized[$field] = sanitize_textarea_field($data[$field]);
            break;
          case 'int':
            $sanitized[$field] = intval($data[$field]);
            break;
          case 'float':
            $sanitized[$field] = floatval($data[$field]);
            break;
          default:
            $sanitized[$field] = sanitize_text_field($data[$field]);
        }
      }
    }

    return $sanitized;
  }

  /**
   * Handle bulk actions
   */
  protected function handle_bulk_action($action, $items, $callback)
  {
    if (!is_callable($callback)) {
      return false;
    }

    $success_count = 0;
    $error_count = 0;

    foreach ($items as $item) {
      $result = call_user_func($callback, $item, $action);
      if ($result) {
        $success_count++;
      } else {
        $error_count++;
      }
    }

    // Add appropriate notices
    if ($success_count > 0) {
      $this->add_notice(
        sprintf(__('%d items processed successfully.', 'heritagepress'), $success_count),
        'success'
      );
    }

    if ($error_count > 0) {
      $this->add_notice(
        sprintf(__('%d items failed to process.', 'heritagepress'), $error_count),
        'error'
      );
    }

    return array('success' => $success_count, 'errors' => $error_count);
  }

  /**
   * Get view path for controller
   */
  protected function get_view_path()
  {
    return HERITAGEPRESS_PLUGIN_DIR . 'admin/views/' . $this->controller_name . '/';
  }

  /**
   * Load view file
   */
  protected function load_view($view, $data = array())
  {
    if (!empty($data)) {
      extract($data);
    }

    $view_file = $this->get_view_path() . $view . '.php';

    if (file_exists($view_file)) {
      include $view_file;
    }
  }

  /**
   * Load template partial
   */
  protected function load_template($template, $data = array())
  {
    if (!empty($data)) {
      extract($data);
    }

    $template_file = HERITAGEPRESS_PLUGIN_DIR . 'admin/views/templates/' . $template . '.php';

    if (file_exists($template_file)) {
      include $template_file;
    }
  }
}
