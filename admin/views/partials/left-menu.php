<?php
if (!function_exists('current_user_can')) {
  function current_user_can($cap)
  {
    return true;
  }
}
$menu = [
  ['page' => 'hp_people', 'label' => __('People', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_families', 'label' => __('Families', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_sources', 'label' => __('Sources', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_repositories', 'label' => __('Repositories', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_media', 'label' => __('Media', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_albums', 'label' => __('Albums', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_cemeteries', 'label' => __('Cemeteries', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_places', 'label' => __('Places', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_trees', 'label' => __('Trees', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_branches', 'label' => __('Branches', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_event_types', 'label' => __('Event Types', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_users', 'label' => __('Users', 'heritagepress'), 'cap' => 'manage_options'],
  ['page' => 'hp_languages', 'label' => __('Languages', 'heritagepress'), 'cap' => 'manage_options'],
  ['page' => 'hp_reports', 'label' => __('Reports', 'heritagepress'), 'cap' => 'edit_genealogy'],
  ['page' => 'hp_utilities', 'label' => __('Utilities', 'heritagepress'), 'cap' => 'manage_options'],
  ['page' => 'hp_config', 'label' => __('Configuration', 'heritagepress'), 'cap' => 'manage_options'],
];
?>
<aside class="heritagepress-admin-sidebar" style="float:left;width:220px;margin-right:2em;">
  <nav>
    <ul style="list-style:none;padding:0;">
      <?php foreach ($menu as $item): ?>
        <?php if (current_user_can($item['cap'])): ?>
          <li style="margin-bottom:8px;"><a href="<?php echo esc_url(admin_url('admin.php?page=' . $item['page'])); ?>"><?php echo esc_html($item['label']); ?></a></li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </nav>
</aside>
