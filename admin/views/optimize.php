<?php

/**
 * Optimize Database (Admin View)
 * WordPress admin page for optimizing database tables
 *
 * @package HeritagePress
 */
if (!defined('ABSPATH')) {
  exit;
}
?>
<div class="wrap">
  <h1><?php _e('Optimize Database', 'heritagepress'); ?></h1>
  <form id="hp-optimize-form" method="post">
    <?php wp_nonce_field('hp_optimize_tables', 'hp_optimize_tables_nonce'); ?>
    <p><?php _e('Click the button below to optimize all HeritagePress database tables.', 'heritagepress'); ?></p>
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php esc_attr_e('Optimize Tables', 'heritagepress'); ?>">
    </p>
  </form>
</div>
