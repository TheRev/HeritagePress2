<?php

/**
 * HeritagePress GEDCOM Adapter
 *
 * This is a simple adapter class that provides the HP_GEDCOM_Importer class
 * by wrapping the HP_GEDCOM_Importer_Controller class.
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

// Main importer class for backward compatibility with existing code
class HP_GEDCOM_Importer
{
  /**
   * Controller instance
   *
   * @var HP_GEDCOM_Importer_Controller
   */
  private $controller;

  /**
   * GEDCOM file path
   */
  private $file_path;

  /**
   * Tree ID
   */
  private $tree_id;

  /**
   * Import options
   */
  private $options = array();

  /**
   * Constructor
   *
   * @param string $file_path GEDCOM file path
   * @param string $tree_id Tree ID (default: 'main')
   * @param array $options Import options
   */
  public function __construct($file_path = '', $tree_id = 'main', $options = array())
  {
    $this->file_path = $file_path;
    $this->tree_id = $tree_id;
    $this->options = $options;

    // Initialize the controller - do this in a method so it can be overridden
    $this->init_controller();
  }

  /**
   * Initialize the controller
   */
  protected function init_controller()
  {
    // Only instantiate if class exists to avoid errors
    if (class_exists('HP_GEDCOM_Importer_Controller')) {
      $this->controller = new HP_GEDCOM_Importer_Controller(
        $this->file_path,
        $this->tree_id,
        $this->options
      );
    }
  }

  /**
   * Run the import process
   *
   * @return array Import results
   */
  public function import()
  {
    if ($this->controller) {
      return $this->controller->import();
    }

    return array(
      'success' => false,
      'message' => 'Controller not initialized',
    );
  }

  /**
   * Configure the importer
   *
   * @param array $options Configuration options
   * @return self
   */
  public function configure($options)
  {
    $this->options = array_merge($this->options, $options);

    if ($this->controller && method_exists($this->controller, 'configure')) {
      $this->controller->configure($options);
    }

    return $this;
  }

  /**
   * Set a progress callback
   *
   * @param callable $callback Function to call when progress updates
   * @return self
   */
  public function set_progress_callback($callback)
  {
    if ($this->controller && method_exists($this->controller, 'set_progress_callback')) {
      $this->controller->set_progress_callback($callback);
    }

    return $this;
  }

  /**
   * Run the import process with UTF-8 support
   *
   * @return array Import results
   */
  public function import_with_utf8_support()
  {
    // Set UTF-8 encoding option
    $this->options['encoding'] = 'UTF-8';

    // Run the import process
    return $this->import();
  }

  /**
   * Get import statistics
   *
   * @return array Import statistics
   */
  public function get_stats()
  {
    if ($this->controller && method_exists($this->controller, 'get_stats')) {
      return $this->controller->get_stats();
    }

    return array();
  }
}
