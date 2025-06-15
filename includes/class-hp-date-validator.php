<?php

/**
 * HeritagePress Date Validator
 *
 * Provides real-time date validation and user interface helpers
 *
 * @package HeritagePress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Date_Validator
{

  /**
   * Initialize date validation
   */
  public static function init()
  {
    add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    add_action('wp_ajax_hp_validate_date', [__CLASS__, 'ajax_validate_date']);
    add_action('wp_ajax_nopriv_hp_validate_date', [__CLASS__, 'ajax_validate_date']);
  }

  /**
   * Enqueue validation scripts
   */
  public static function enqueue_scripts()
  {
    wp_enqueue_script(
      'hp-date-validator',
      plugin_dir_url(__FILE__) . '../public/js/date-validator.js',
      ['jquery'],
      '1.0.0',
      true
    );

    wp_localize_script('hp-date-validator', 'hp_date_ajax', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('hp_date_validation'),
      'messages' => [
        'validating' => __('Validating date...', 'heritagepress'),
        'valid' => __('Valid date format', 'heritagepress'),
        'invalid' => __('Invalid date format', 'heritagepress'),
        'warning' => __('Please verify this date', 'heritagepress')
      ]
    ]);

    wp_enqueue_style(
      'hp-date-validator',
      plugin_dir_url(__FILE__) . '../public/css/date-validator.css',
      [],
      '1.0.0'
    );
  }

  /**
   * AJAX handler for date validation
   */
  public static function ajax_validate_date()
  {
    check_ajax_referer('hp_date_validation', 'nonce');

    $date_string = sanitize_text_field($_POST['date_string'] ?? '');

    if (empty($date_string)) {
      wp_send_json_success([
        'is_valid' => true,
        'message' => '',
        'suggestions' => [],
        'warnings' => [],
        'formatted' => ''
      ]);
    }

    $validation = HP_Date_Parser::validate_and_suggest($date_string);

    $response = [
      'is_valid' => $validation['is_valid'],
      'message' => $validation['is_valid'] ?
        __('Valid date format', 'heritagepress') :
        __('Invalid date format', 'heritagepress'),
      'suggestions' => $validation['suggestions'],
      'warnings' => $validation['warnings'],
      'formatted' => $validation['is_valid'] ?
        HP_Date_Parser::format_for_display($validation['parsed']) : '',
      'sortable' => $validation['parsed']['sortable'] ?? '',
      'precision' => $validation['is_valid'] ?
        HP_Date_Parser::get_precision($validation['parsed']) : ''
    ];

    wp_send_json_success($response);
  }

  /**
   * Render date input field with validation
   *
   * @param array $args Field arguments
   * @return string HTML output
   */
  public static function render_date_field($args)
  {
    $defaults = [
      'id' => '',
      'name' => '',
      'value' => '',
      'label' => '',
      'placeholder' => __('DD MMM YYYY or partial dates', 'heritagepress'),
      'required' => false,
      'help_text' => '',
      'show_examples' => true
    ];

    $args = wp_parse_args($args, $defaults);

    ob_start();
?>
    <div class="hp-date-field-wrapper">
      <?php if ($args['label']): ?>
        <label for="<?php echo esc_attr($args['id']); ?>" class="hp-date-label">
          <?php echo esc_html($args['label']); ?>
          <?php if ($args['required']): ?>
            <span class="required">*</span>
          <?php endif; ?>
        </label>
      <?php endif; ?>

      <div class="hp-date-input-wrapper">
        <input
          type="text"
          id="<?php echo esc_attr($args['id']); ?>"
          name="<?php echo esc_attr($args['name']); ?>"
          value="<?php echo esc_attr($args['value']); ?>"
          placeholder="<?php echo esc_attr($args['placeholder']); ?>"
          class="hp-date-input"
          <?php echo $args['required'] ? 'required' : ''; ?>
          data-hp-date-field="true" />
        <div class="hp-date-feedback" id="<?php echo esc_attr($args['id']); ?>-feedback">
          <div class="hp-date-status"></div>
          <div class="hp-date-suggestions"></div>
          <div class="hp-date-warnings"></div>
        </div>
      </div>

      <?php if ($args['help_text']): ?>
        <div class="hp-date-help">
          <?php echo wp_kses_post($args['help_text']); ?>
        </div>
      <?php endif; ?>

      <?php if ($args['show_examples']): ?>
        <div class="hp-date-examples">
          <strong><?php _e('Examples:', 'heritagepress'); ?></strong>
          <span class="hp-date-example" data-example="2 OCT 1822">2 OCT 1822</span>,
          <span class="hp-date-example" data-example="OCT 1822">OCT 1822</span>,
          <span class="hp-date-example" data-example="1822">1822</span>,
          <span class="hp-date-example" data-example="ABT 1820">ABT 1820</span>,
          <span class="hp-date-example" data-example="BEF 1828">BEF 1828</span>
        </div>
      <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
  }

  /**
   * Get date configuration options
   *
   * @return array
   */
  public static function get_config_options()
  {
    return [
      'default_date_format' => get_option('hp_default_date_format', 'genealogy'),
      'show_date_precision' => get_option('hp_show_date_precision', true),
      'strict_validation' => get_option('hp_strict_date_validation', false),
      'allow_future_dates' => get_option('hp_allow_future_dates', false),
      'date_separator' => get_option('hp_date_separator', ' '),
      'month_format' => get_option('hp_month_format', 'abbreviated') // full, abbreviated, numeric
    ];
  }

  /**
   * Update date in database with dual storage
   *
   * @param string $table_name
   * @param array $date_fields Array of date field names
   * @param array $data Form data
   * @param array $where Where conditions
   * @return bool
   */
  public static function update_dates_in_db($table_name, $date_fields, $data, $where)
  {
    global $wpdb;

    $update_data = [];

    foreach ($date_fields as $field) {
      if (isset($data[$field])) {
        $display_date = sanitize_text_field($data[$field]);
        $sortable_date = HP_Date_Parser::to_sortable($display_date);

        $update_data[$field] = $display_date;
        $update_data[$field . 'tr'] = $sortable_date ?: '0000-00-00';
      }
    }

    if (empty($update_data)) {
      return true;
    }

    $result = $wpdb->update($table_name, $update_data, $where);

    return $result !== false;
  }

  /**
   * Insert dates in database with dual storage
   *
   * @param string $table_name
   * @param array $date_fields Array of date field names
   * @param array $data Form data
   * @return bool
   */
  public static function insert_dates_in_db($table_name, $date_fields, $data)
  {
    global $wpdb;

    $insert_data = $data;

    foreach ($date_fields as $field) {
      if (isset($data[$field])) {
        $display_date = sanitize_text_field($data[$field]);
        $sortable_date = HP_Date_Parser::to_sortable($display_date);

        $insert_data[$field] = $display_date;
        $insert_data[$field . 'tr'] = $sortable_date ?: '0000-00-00';
      }
    }

    $result = $wpdb->insert($table_name, $insert_data);

    return $result !== false;
  }
}
