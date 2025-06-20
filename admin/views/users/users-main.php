<?php

/**
 * Users Main Admin Interface
 * List users, add/edit links, replicate HeritagePress user admin UI
 * @package HeritagePress
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
  <h1><?php _e('HeritagePress Users', 'heritagepress'); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=heritagepress-users&tab=add'); ?>" class="page-title-action"><?php _e('Add New', 'heritagepress'); ?></a>
  <table class="wp-list-table widefat fixed striped">
    <thead>
      <tr>
        <th><?php _e('Username', 'heritagepress'); ?></th>
        <th><?php _e('Email', 'heritagepress'); ?></th>
        <th><?php _e('Role', 'heritagepress'); ?></th>
        <th><?php _e('Genealogy Permissions', 'heritagepress'); ?></th>
        <th><?php _e('Actions', 'heritagepress'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $users = get_users();
      foreach ($users as $user) {
        $edit_url = admin_url('admin.php?page=heritagepress-users&tab=edit&user_id=' . $user->ID);
        echo '<tr>';
        echo '<td>' . esc_html($user->user_login) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';
        echo '<td>' . esc_html(ucfirst(implode(", ", $user->roles))) . '</td>';
        echo '<td>' . esc_html(get_user_meta($user->ID, 'hp_genealogy_permissions', true)) . '</td>';
        echo '<td><a href="' . esc_url($edit_url) . '" class="button">' . __('Edit', 'heritagepress') . '</a></td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
</div>
