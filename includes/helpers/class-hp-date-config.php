<?php

/**
 * HeritagePress Date Configuration
 *
 * Admin settings for date parsing and validation
 *
 * @package HeritagePress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Date_Config
{

  /**
   * Initialize date configuration
   */
  public static function init()
  {
    add_action('admin_init', [__CLASS__, 'register_settings']);
    // Removed admin menu for Date Settings to keep sidebar clean
    // add_action('admin_menu', [__CLASS__, 'add_settings_page']);
  }

  /**
   * Register date settings
   */
  public static function register_settings()
  {
    // Date format settings
    register_setting('hp_date_settings', 'hp_default_date_format');
    register_setting('hp_date_settings', 'hp_show_date_precision');
    register_setting('hp_date_settings', 'hp_strict_date_validation');
    register_setting('hp_date_settings', 'hp_allow_future_dates');
    register_setting('hp_date_settings', 'hp_date_separator');
    register_setting('hp_date_settings', 'hp_month_format');
    register_setting('hp_date_settings', 'hp_default_date_qualifier');
    register_setting('hp_date_settings', 'hp_auto_parse_dates');

    // Add settings sections
    add_settings_section(
      'hp_date_parsing',
      __('Date Parsing Settings', 'heritagepress'),
      [__CLASS__, 'parsing_section_callback'],
      'hp_date_settings'
    );

    add_settings_section(
      'hp_date_display',
      __('Date Display Settings', 'heritagepress'),
      [__CLASS__, 'display_section_callback'],
      'hp_date_settings'
    );

    add_settings_section(
      'hp_date_validation',
      __('Date Validation Settings', 'heritagepress'),
      [__CLASS__, 'validation_section_callback'],
      'hp_date_settings'
    );

    // Add settings fields
    add_settings_field(
      'hp_default_date_format',
      __('Default Date Format', 'heritagepress'),
      [__CLASS__, 'date_format_field'],
      'hp_date_settings',
      'hp_date_parsing'
    );

    add_settings_field(
      'hp_auto_parse_dates',
      __('Auto-Parse Dates', 'heritagepress'),
      [__CLASS__, 'auto_parse_field'],
      'hp_date_settings',
      'hp_date_parsing'
    );

    add_settings_field(
      'hp_month_format',
      __('Month Format', 'heritagepress'),
      [__CLASS__, 'month_format_field'],
      'hp_date_settings',
      'hp_date_display'
    );

    add_settings_field(
      'hp_show_date_precision',
      __('Show Date Precision', 'heritagepress'),
      [__CLASS__, 'show_precision_field'],
      'hp_date_settings',
      'hp_date_display'
    );

    add_settings_field(
      'hp_strict_date_validation',
      __('Strict Validation', 'heritagepress'),
      [__CLASS__, 'strict_validation_field'],
      'hp_date_settings',
      'hp_date_validation'
    );

    add_settings_field(
      'hp_allow_future_dates',
      __('Allow Future Dates', 'heritagepress'),
      [__CLASS__, 'allow_future_field'],
      'hp_date_settings',
      'hp_date_validation'
    );
  }

  /**
   * Add settings page to admin menu
   */
  public static function add_settings_page()
  {
    add_submenu_page(
      'heritagepress',
      __('Date Settings', 'heritagepress'),
      __('Date Settings', 'heritagepress'),
      'manage_options',
      'hp-date-settings',
      [__CLASS__, 'settings_page']
    );
  }

  /**
   * Render settings page
   */
  public static function settings_page()
  {
?>
    <div class="wrap">
      <h1><?php _e('HeritagePress Date Settings', 'heritagepress'); ?></h1>

      <form method="post" action="options.php">
        <?php
        settings_fields('hp_date_settings');
        do_settings_sections('hp_date_settings');
        submit_button();
        ?>
      </form>

      <div class="hp-date-test-section">
        <h2><?php _e('Date Parser Test', 'heritagepress'); ?></h2>
        <p><?php _e('Test how dates are parsed with your current settings:', 'heritagepress'); ?></p>

        <div class="hp-date-test-input">
          <label for="test-date"><?php _e('Test Date:', 'heritagepress'); ?></label>
          <input type="text" id="test-date" placeholder="<?php _e('Enter a date to test', 'heritagepress'); ?>" data-hp-date-field="true">
        </div>

        <div id="test-results" class="hp-date-test-results">
          <!-- Results will be populated by JavaScript -->
        </div>
      </div>

      <div class="hp-date-examples-section">
        <h2><?php _e('Supadapted Date Formats', 'heritagepress'); ?></h2>

        <div class="hp-date-format-examples">
          <h4><?php _e('Complete Dates', 'heritagepress'); ?></h4>
          <ul>
            <li><code>2 OCT 1822</code> - Full date with day, month, year</li>
            <li><code>15 DECEMBER 1850</code> - Full month name</li>
            <li><code>1822-10-02</code> - ISO format (YYYY-MM-DD)</li>
            <li><code>10/02/1822</code> - US format (MM/DD/YYYY)</li>
          </ul>

          <h4><?php _e('Partial Dates', 'heritagepress'); ?></h4>
          <ul>
            <li><code>OCT 1822</code> - Month and year only</li>
            <li><code>1822</code> - Year only</li>
          </ul>

          <h4><?php _e('Uncertain Dates', 'heritagepress'); ?></h4>
          <ul>
            <li><code>ABT 1820</code> - About/approximately</li>
            <li><code>BEF 1828</code> - Before</li>
            <li><code>AFT 1825</code> - After</li>
            <li><code>BET 1820 AND 1825</code> - Between two dates</li>
            <li><code>EST 1822</code> - Estimated</li>
            <li><code>CALC 1820</code> - Calculated</li>
          </ul>
        </div>
      </div>
    </div>

    <style>
      .hp-date-test-section {
        background: #f9f9f9;
        border: 1px solid #ddd;
        padding: 20px;
        margin: 20px 0;
        border-radius: 5px;
      }

      .hp-date-test-input {
        margin: 15px 0;
      }

      .hp-date-test-input label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
      }

      .hp-date-test-input input {
        width: 300px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 3px;
      }

      .hp-date-test-results {
        margin-top: 15px;
        padding: 10px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 3px;
        min-height: 50px;
      }

      .hp-date-examples-section {
        margin-top: 30px;
      }

      .hp-date-format-examples ul {
        list-style: disc;
        margin-left: 20px;
      }

      .hp-date-format-examples li {
        margin: 8px 0;
      }

      .hp-date-format-examples code {
        background: #f1f1f1;
        padding: 2px 5px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
      }
    </style>
  <?php
  }

  /**
   * Section callbacks
   */
  public static function parsing_section_callback()
  {
    echo '<p>' . __('Configure how dates are parsed and interpreted.', 'heritagepress') . '</p>';
  }

  public static function display_section_callback()
  {
    echo '<p>' . __('Configure how dates are displayed to users.', 'heritagepress') . '</p>';
  }

  public static function validation_section_callback()
  {
    echo '<p>' . __('Configure date validation rules and restrictions.', 'heritagepress') . '</p>';
  }

  /**
   * Field callbacks
   */
  public static function date_format_field()
  {
    $value = get_option('hp_default_date_format', 'genealogy');
  ?>
    <select name="hp_default_date_format" id="hp_default_date_format">
      <option value="genealogy" <?php selected($value, 'genealogy'); ?>><?php _e('Genealogy Standard (DD MMM YYYY)', 'heritagepress'); ?></option>
      <option value="iso" <?php selected($value, 'iso'); ?>><?php _e('ISO Format (YYYY-MM-DD)', 'heritagepress'); ?></option>
      <option value="us" <?php selected($value, 'us'); ?>><?php _e('US Format (MM/DD/YYYY)', 'heritagepress'); ?></option>
      <option value="flexible" <?php selected($value, 'flexible'); ?>><?php _e('Flexible (Accept All)', 'heritagepress'); ?></option>
    </select>
    <p class="description"><?php _e('Default format to expect when parsing dates.', 'heritagepress'); ?></p>
  <?php
  }

  public static function auto_parse_field()
  {
    $value = get_option('hp_auto_parse_dates', true);
  ?>
    <label>
      <input type="checkbox" name="hp_auto_parse_dates" value="1" <?php checked($value, true); ?>>
      <?php _e('Automatically parse and standardize dates on save', 'heritagepress'); ?>
    </label>
    <p class="description"><?php _e('When enabled, dates will be automatically parsed and stored in both display and sortable formats.', 'heritagepress'); ?></p>
  <?php
  }

  public static function month_format_field()
  {
    $value = get_option('hp_month_format', 'abbreviated');
  ?>
    <select name="hp_month_format" id="hp_month_format">
      <option value="abbreviated" <?php selected($value, 'abbreviated'); ?>><?php _e('Abbreviated (JAN, FEB, MAR)', 'heritagepress'); ?></option>
      <option value="full" <?php selected($value, 'full'); ?>><?php _e('Full Names (JANUARY, FEBRUARY)', 'heritagepress'); ?></option>
      <option value="numeric" <?php selected($value, 'numeric'); ?>><?php _e('Numeric (01, 02, 03)', 'heritagepress'); ?></option>
    </select>
    <p class="description"><?php _e('How month names should be displayed in formatted dates.', 'heritagepress'); ?></p>
  <?php
  }

  public static function show_precision_field()
  {
    $value = get_option('hp_show_date_precision', true);
  ?>
    <label>
      <input type="checkbox" name="hp_show_date_precision" value="1" <?php checked($value, true); ?>>
      <?php _e('Show date precision indicators in validation feedback', 'heritagepress'); ?>
    </label>
    <p class="description"><?php _e('Display precision level (day, month, year) when validating dates.', 'heritagepress'); ?></p>
  <?php
  }

  public static function strict_validation_field()
  {
    $value = get_option('hp_strict_date_validation', false);
  ?>
    <label>
      <input type="checkbox" name="hp_strict_date_validation" value="1" <?php checked($value, true); ?>>
      <?php _e('Enable strict date validation', 'heritagepress'); ?>
    </label>
    <p class="description"><?php _e('When enabled, only valid calendar dates will be accepted (no impossible dates like Feb 30).', 'heritagepress'); ?></p>
  <?php
  }

  public static function allow_future_field()
  {
    $value = get_option('hp_allow_future_dates', false);
  ?>
    <label>
      <input type="checkbox" name="hp_allow_future_dates" value="1" <?php checked($value, true); ?>>
      <?php _e('Allow future dates', 'heritagepress'); ?>
    </label>
    <p class="description"><?php _e('When disabled, dates in the future will generate warnings.', 'heritagepress'); ?></p>
<?php
  }
}
