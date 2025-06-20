<?php

/**
 * Custom Text Update Admin View
 *
 * Admin page for updating custom text files across language directories
 * Replicates functionality from HeritagePress admin_cust_text_update.php
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

// Include the controller
require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-custom-text-controller.php';

$controller = new HP_Custom_Text_Controller();

// Check for update results
$update_results = get_transient('hp_custom_text_update_results');
if ($update_results) {
  delete_transient('hp_custom_text_update_results');
}

// Get current status
$status = $controller->get_update_status();
$language_dirs = $controller->get_language_directories();

?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php _e('Custom Text Update Utility', 'heritagepress'); ?></h1>

  <hr class="wp-header-end">

  <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php _e('Custom text files have been updated successfully!', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>

  <?php if ($update_results): ?>
    <div class="notice notice-info">
      <h3><?php _e('Update Results', 'heritagepress'); ?></h3>
      <div class="update-log">
        <?php foreach ($update_results['log_messages'] as $message): ?>
          <p><?php echo esc_html($message); ?></p>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Description Card -->
  <div class="card">
    <h2 class="title"><?php _e('About Custom Text Updates', 'heritagepress'); ?></h2>
    <p><?php _e('This utility updates custom text files (cust_text.php) in all language directories with standard comments and format improvements.', 'heritagepress'); ?></p>

    <h3><?php _e('What this utility does:', 'heritagepress'); ?></h3>
    <ul>
      <li><?php _e('Scans all language directories for custom text files', 'heritagepress'); ?></li>
      <li><?php _e('Adds standardized comments to help users understand where to put custom messages', 'heritagepress'); ?></li>
      <li><?php _e('Updates message format from old style to new standardized format', 'heritagepress'); ?></li>
      <li><?php _e('Creates missing custom text files for languages that don\'t have them', 'heritagepress'); ?></li>
      <li><?php _e('Creates backups of modified files (saved as cust_text.bak)', 'heritagepress'); ?></li>
    </ul>
  </div>

  <!-- Current Status -->
  <div class="card">
    <h2 class="title"><?php _e('Current Status', 'heritagepress'); ?></h2>

    <div class="status-overview">
      <div class="status-item">
        <span class="status-number"><?php echo esc_html($status['total_languages']); ?></span>
        <span class="status-label"><?php _e('Total Languages', 'heritagepress'); ?></span>
      </div>

      <div class="status-item<?php echo ($status['up_to_date'] > 0) ? ' status-good' : ''; ?>">
        <span class="status-number"><?php echo esc_html($status['up_to_date']); ?></span>
        <span class="status-label"><?php _e('Up to Date', 'heritagepress'); ?></span>
      </div>

      <div class="status-item<?php echo ($status['need_update'] > 0) ? ' status-warning' : ''; ?>">
        <span class="status-number"><?php echo esc_html($status['need_update']); ?></span>
        <span class="status-label"><?php _e('Need Update', 'heritagepress'); ?></span>
      </div>

      <div class="status-item<?php echo ($status['missing_files'] > 0) ? ' status-error' : ''; ?>">
        <span class="status-number"><?php echo esc_html($status['missing_files']); ?></span>
        <span class="status-label"><?php _e('Missing Files', 'heritagepress'); ?></span>
      </div>
    </div>
  </div>

  <!-- Language Details -->
  <div class="card">
    <h2 class="title"><?php _e('Language Directory Details', 'heritagepress'); ?></h2>

    <?php if (empty($language_dirs)): ?>
      <p class="notice-warning"><?php _e('No language directories found.', 'heritagepress'); ?></p>
    <?php else: ?>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th scope="col" class="manage-column"><?php _e('Language', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php _e('Custom Text File', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php _e('Language File', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php _e('Status', 'heritagepress'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($language_dirs as $lang): ?>
            <tr>
              <td><strong><?php echo esc_html($lang['name']); ?></strong></td>
              <td>
                <?php if ($lang['has_cust_text']): ?>
                  <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                  <?php _e('Present', 'heritagepress'); ?>
                <?php else: ?>
                  <span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
                  <?php _e('Missing', 'heritagepress'); ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($lang['has_language_file']): ?>
                  <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                  <?php _e('Present', 'heritagepress'); ?>
                <?php else: ?>
                  <span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
                  <?php _e('Missing', 'heritagepress'); ?>
                <?php endif; ?>
              </td>
              <td>
                <?php
                $lang_status = $status['details'][$lang['name']]['status'];
                switch ($lang_status) {
                  case 'up_to_date':
                    echo '<span class="status-badge status-good">' . __('Up to Date', 'heritagepress') . '</span>';
                    break;
                  case 'needs_update':
                    echo '<span class="status-badge status-warning">' . __('Needs Update', 'heritagepress') . '</span>';
                    break;
                  case 'missing':
                    echo '<span class="status-badge status-error">' . __('Missing File', 'heritagepress') . '</span>';
                    break;
                }
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Update Action -->
  <div class="card">
    <h2 class="title"><?php _e('Update Custom Text Files', 'heritagepress'); ?></h2>

    <?php if ($status['need_update'] > 0 || $status['missing_files'] > 0): ?>
      <div class="notice notice-warning inline">
        <p><?php _e('<strong>Important:</strong> This will modify files in your language directories. Backups will be created automatically.', 'heritagepress'); ?></p>
      </div>

      <form method="post" action="" id="custom-text-update-form">
        <?php wp_nonce_field('hp_update_custom_text'); ?>
        <input type="hidden" name="update_custom_text" value="1">

        <p class="description">
          <?php _e('Click the button below to update all custom text files to the current standard format.', 'heritagepress'); ?>
        </p>

        <div class="update-actions">
          <input type="submit" name="submit" class="button button-primary button-large"
            value="<?php esc_attr_e('Update Custom Text Files', 'heritagepress'); ?>"
            onclick="return confirm('<?php echo esc_js(__('Are you sure you want to update all custom text files? Backups will be created automatically.', 'heritagepress')); ?>');">

          <span class="description" style="margin-left: 15px;">
            <?php _e('This will process all language directories and may take a few moments.', 'heritagepress'); ?>
          </span>
        </div>
      </form>
    <?php else: ?>
      <div class="notice notice-success inline">
        <p><?php _e('All custom text files are up to date! No action is needed.', 'heritagepress'); ?></p>
      </div>

      <form method="post" action="" id="custom-text-update-form">
        <?php wp_nonce_field('hp_update_custom_text'); ?>
        <input type="hidden" name="update_custom_text" value="1">

        <p class="description">
          <?php _e('You can still run the update process to refresh all files if needed.', 'heritagepress'); ?>
        </p>

        <div class="update-actions">
          <input type="submit" name="submit" class="button button-secondary"
            value="<?php esc_attr_e('Refresh All Files', 'heritagepress'); ?>"
            onclick="return confirm('<?php echo esc_js(__('Are you sure you want to refresh all custom text files?', 'heritagepress')); ?>');">
        </div>
      </form>
    <?php endif; ?>
  </div>

  <!-- Help Section -->
  <div class="card">
    <h2 class="title"><?php _e('Help & Information', 'heritagepress'); ?></h2>

    <h3><?php _e('Custom Text File Structure', 'heritagepress'); ?></h3>
    <p><?php _e('After running this utility, your custom text files will have this standardized structure:', 'heritagepress'); ?></p>

    <pre class="code-example"><code>&lt;?php
//Mods should put their changes before this line, local changes should come after it.
//Put your own custom messages here, like this:
//$text['messagename'] = "This is the message"

// Your custom text additions go here
$text['welcome'] = "Welcome to our genealogy site";
$text['custom_greeting'] = "Hello, researcher!";

?&gt;</code></pre>

    <h3><?php _e('Adding Custom Messages', 'heritagepress'); ?></h3>
    <ul>
      <li><?php _e('Add your custom messages after the standard comments', 'heritagepress'); ?></li>
      <li><?php _e('Use the new format: $text[\'messagename\'] = "Your message"', 'heritagepress'); ?></li>
      <li><?php _e('Each language directory can have its own custom messages', 'heritagepress'); ?></li>
      <li><?php _e('Modified files are automatically backed up as cust_text.bak', 'heritagepress'); ?></li>
    </ul>

    <h3><?php _e('File Locations', 'heritagepress'); ?></h3>
    <p><?php _e('Custom text files are located in:', 'heritagepress'); ?></p>
    <code><?php echo esc_html(HERITAGEPRESS_PLUGIN_DIR . 'languages/[language]/cust_text.php'); ?></code>
  </div>
</div>

<style>
  .status-overview {
    display: flex;
    gap: 20px;
    margin: 15px 0;
    flex-wrap: wrap;
  }

  .status-item {
    text-align: center;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 120px;
    background: #f9f9f9;
  }

  .status-item.status-good {
    border-color: #46b450;
    background: #f0f9f0;
  }

  .status-item.status-warning {
    border-color: #ffb900;
    background: #fff8e5;
  }

  .status-item.status-error {
    border-color: #dc3232;
    background: #ffeaea;
  }

  .status-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
  }

  .status-label {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
  }

  .status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
  }

  .status-badge.status-good {
    background: #46b450;
    color: white;
  }

  .status-badge.status-warning {
    background: #ffb900;
    color: white;
  }

  .status-badge.status-error {
    background: #dc3232;
    color: white;
  }

  .code-example {
    background: #f1f1f1;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
  }

  .update-actions {
    margin: 20px 0;
  }

  .notice.inline {
    margin: 15px 0;
  }

  .update-log {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 4px;
    max-height: 300px;
    overflow-y: auto;
  }

  .update-log p {
    margin: 5px 0;
    font-family: monospace;
    font-size: 12px;
  }
</style>
