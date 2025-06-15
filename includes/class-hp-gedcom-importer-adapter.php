 <?php

  /**
   * HeritagePress Enhanced GEDCOM 5.5.1 Importer Adapter
   *
   * This is an adapter class that connects the legacy monolithic HP_GEDCOM_Importer
   * with the new modular GEDCOM import system. This allows for a smooth transition
   * without breaking existing code that might depend on the old class.
   *
   * @since 1.0.0
   */

  if (!defined('ABSPATH')) {
    exit;
  }

  // Load the modular GEDCOM importer
  require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

  // This class provides backward compatibility by exposing the original API
  // but using the new modular importer under the hood.
  //
  // Must be named HP_GEDCOM_Importer for backward compatibility
  class HP_GEDCOM_Importer
  {
    /**
     * Instance of the new modular importer
     */
    private $modular_importer;

    /**
     * GEDCOM file path
     */
    private $file_path;

    /**
     * Target tree ID for import
     */
    private $tree_id;

    /**
     * Import options
     */
    private $options = array();

    /**
     * Import stats
     */
    private $statistics = array();

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

      // Initialize the modular importer
      $this->modular_importer = new HP_GEDCOM_Importer_Controller($file_path, $tree_id, $options);
    }

    /**
     * Run the import process
     *
     * @return array Import results
     */
    public function import()
    {
      return $this->modular_importer->import();
    }

    /**
     * Run the import process with UTF-8 support
     *
     * @return array Import results
     */
    public function import_with_utf8_support()
    {
      return $this->modular_importer->import_with_utf8_support();
    }

    /**
     * Get import statistics
     *
     * @return array Import statistics
     */
    public function get_stats()
    {
      return $this->modular_importer->get_stats();
    }

    /**
     * Configure the importer
     *
     * @param array $options Configuration options
     * @return self
     */
    public function configure($options)
    {
      // Forward to modular importer if implemented
      if (method_exists($this->modular_importer, 'configure')) {
        $this->modular_importer->configure($options);
      }

      // Store options for our own reference
      $this->options = array_merge($this->options, $options);

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
      // Forward to modular importer if implemented
      if (method_exists($this->modular_importer, 'set_progress_callback')) {
        $this->modular_importer->set_progress_callback($callback);
      }

      return $this;
    }
  }
