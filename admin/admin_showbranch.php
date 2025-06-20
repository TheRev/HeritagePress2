<?php
// HeritagePress: Show Branch admin page (WordPress-native, ported from TNG admin_showbranch.php)
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_submenu_page(
    null, // Hidden from menu, accessible by link
    __('Show Branch', 'heritagepress'),
    __('Show Branch', 'heritagepress'),
    'manage_options',
    'heritagepress-showbranch',
    'heritagepress_admin_showbranch_page'
  );
});

function heritagepress_admin_showbranch_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  global $wpdb;
  $trees_table = $wpdb->prefix . 'tng_trees';
  $branches_table = $wpdb->prefix . 'tng_branches';
  $people_table = $wpdb->prefix . 'tng_people';

  $tree = isset($_GET['tree']) ? sanitize_text_field(wp_unslash($_GET['tree'])) : '';
  $branch = isset($_GET['branch']) ? sanitize_text_field(wp_unslash($_GET['branch'])) : '';
  if (!$tree || !$branch) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Missing tree or branch parameter.', 'heritagepress') . '</p></div>';
    return;
  }

  // Fetch tree name
  $treename = $wpdb->get_var($wpdb->prepare("SELECT treename FROM $trees_table WHERE gedcom = %s", $tree));
  // Fetch branch description
  $branchdesc = $wpdb->get_var($wpdb->prepare("SELECT description FROM $branches_table WHERE gedcom = %s AND branch = %s", $tree, $branch));
  // Fetch people in branch
  $people = $wpdb->get_results($wpdb->prepare("SELECT personID, firstname, lastname, lnprefix, prefix, suffix, title, branch, gedcom, nameorder, living, private, birthdate, birthdatetr, altbirthdate, altbirthdatetr, deathdate, deathdatetr FROM $people_table WHERE gedcom = %s AND branch LIKE %s ORDER BY lastname, firstname", $tree, '%' . $wpdb->esc_like($branch) . '%'));

  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Show Branch', 'heritagepress') . '</h1>';
  echo '<table class="form-table"><tr>';
  echo '<td><strong>' . esc_html__('Tree:', 'heritagepress') . '</strong></td>';
  echo '<td>' . esc_html($treename) . '</td>';
  echo '</tr><tr>';
  echo '<td><strong>' . esc_html__('Branch:', 'heritagepress') . '</strong></td>';
  echo '<td>' . esc_html($branchdesc) . '</td>';
  echo '</tr></table>';

  echo '<p><a href="' . esc_url(admin_url('admin.php?page=heritagepress-branchmenu&tree=' . urlencode($tree) . '&branch=' . urlencode($branch))) . '" class="button">' . esc_html__('Back to Branch Menu', 'heritagepress') . '</a></p>';

  echo '<h2>' . esc_html__('People in this Branch', 'heritagepress') . '</h2>';
  if ($people) {
    echo '<ul>';
    foreach ($people as $person) {
      // TODO: Implement privacy/living logic if needed
      $person_name = heritagepress_get_person_name($person); // Helper for display name
      $edit_url = admin_url('admin.php?page=heritagepress-editperson&personID=' . urlencode($person->personID) . '&tree=' . urlencode($person->gedcom) . '&cw=1');
      echo '<li><a href="' . esc_url($edit_url) . '" target="_blank">' . esc_html($person_name) . ' (' . esc_html($person->personID) . ')</a></li>';
    }
    echo '</ul>';
  } else {
    echo '<p>' . esc_html__('No people found in this branch.', 'heritagepress') . '</p>';
  }
  echo '</div>';
}

// Helper: Get display name for a person (stub, can be improved for privacy/living)
if (!function_exists('heritagepress_get_person_name')) {
  function heritagepress_get_person_name($person)
  {
    $parts = [];
    if (!empty($person->title)) $parts[] = $person->title;
    if (!empty($person->firstname)) $parts[] = $person->firstname;
    if (!empty($person->lnprefix)) $parts[] = $person->lnprefix;
    if (!empty($person->lastname)) $parts[] = $person->lastname;
    if (!empty($person->suffix)) $parts[] = $person->suffix;
    return implode(' ', $parts);
  }
}
