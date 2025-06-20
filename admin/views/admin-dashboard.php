<?php
if (!current_user_can('manage_options')) {
  wp_die('You do not have sufficient permissions to access this page.');
}
global $wpdb;
$people = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_people");
$families = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families");
$media = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_media");
$sources = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources");
$repos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_repositories");
$trees = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_trees");
?>
<div class="wrap">
  <h1><?php esc_html_e('HeritagePress Dashboard'); ?></h1>
  <div style="display:flex;gap:2em;flex-wrap:wrap;">
    <div style="min-width:200px;">
      <h2><?php esc_html_e('Quick Links'); ?></h2>
      <ul>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=hp_people')); ?>"><?php esc_html_e('People'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=hp_families')); ?>"><?php esc_html_e('Families'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=hp_media')); ?>"><?php esc_html_e('Media'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=hp_sources')); ?>"><?php esc_html_e('Sources'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=hp_repositories')); ?>"><?php esc_html_e('Repositories'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=hp_trees')); ?>"><?php esc_html_e('Trees'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=hp_config')); ?>"><?php esc_html_e('Configuration'); ?></a></li>
      </ul>
    </div>
    <div style="min-width:200px;">
      <h2><?php esc_html_e('Stats'); ?></h2>
      <ul>
        <li><?php printf(esc_html__('People: %d'), $people); ?></li>
        <li><?php printf(esc_html__('Families: %d'), $families); ?></li>
        <li><?php printf(esc_html__('Media: %d'), $media); ?></li>
        <li><?php printf(esc_html__('Sources: %d'), $sources); ?></li>
        <li><?php printf(esc_html__('Repositories: %d'), $repos); ?></li>
        <li><?php printf(esc_html__('Trees: %d'), $trees); ?></li>
      </ul>
    </div>
  </div>
</div>
