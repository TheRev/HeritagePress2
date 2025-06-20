<?php

/**
 * HeritagePress GEDCOM Export Controller
 *
 * Handles backend logic for exporting genealogy data to GEDCOM format.
 */
if (!defined('ABSPATH')) {
  exit;
}

class HP_Export_Controller
{
  public function handle_export_request()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_gedcom') {
      check_admin_referer('heritagepress_export', '_wpnonce');
      $tree = sanitize_text_field($_POST['tree'] ?? '');
      $branch = sanitize_text_field($_POST['branch'] ?? '');
      $exclude_living = !empty($_POST['exliving']);
      $exclude_private = !empty($_POST['exprivate']);
      $exclude_notes = !empty($_POST['exnotes']);
      $export_media = !empty($_POST['exportmedia']);
      // ...other options as needed...
      if (empty($tree)) {
        wp_die(__('Please select a tree to export.', 'heritagepress'));
      }
      $filename = 'heritagepress-' . $tree . '-' . date('Ymd-His') . '.ged';
      header('Content-Type: text/plain; charset=UTF-8');
      header('Content-Disposition: attachment; filename=' . $filename);
      $this->output_gedcom($tree, $branch, $exclude_living, $exclude_private, $exclude_notes, $export_media);
      exit;
    }
  }

  private function output_gedcom($tree, $branch, $exclude_living, $exclude_private, $exclude_notes, $export_media)
  {
    global $wpdb;
    // Output GEDCOM header
    echo "0 HEAD\n";
    echo "1 SOUR HeritagePress\n";
    echo "1 GEDC\n2 VERS 5.5.1\n";
    echo "1 CHAR UTF-8\n";
    // Export individuals
    $people_table = $wpdb->prefix . 'hp_people';
    $query = $wpdb->prepare("SELECT * FROM $people_table WHERE gedcom = %s", $tree);
    $people = $wpdb->get_results($query, ARRAY_A);
    foreach ($people as $person) {
      if ($exclude_living && $person['living']) continue;
      if ($exclude_private && $person['private']) continue;
      echo "0 @{$person['personID']}@ INDI\n";
      echo "1 NAME {$person['firstname']} /{$person['lastname']}/\n";
      if (!empty($person['sex'])) echo "1 SEX {$person['sex']}\n";
      if (!empty($person['birthdate'])) echo "1 BIRT\n2 DATE {$person['birthdate']}\n";
      if (!empty($person['birthplace'])) echo "2 PLAC {$person['birthplace']}\n";
      if (!empty($person['deathdate'])) echo "1 DEAT\n2 DATE {$person['deathdate']}\n";
      if (!empty($person['deathplace'])) echo "2 PLAC {$person['deathplace']}\n";
      // ...other fields/events...
      if (!$exclude_notes && !empty($person['notes'])) echo "1 NOTE {$person['notes']}\n";
    }
    // Export families
    $families_table = $wpdb->prefix . 'hp_families';
    $query = $wpdb->prepare("SELECT * FROM $families_table WHERE gedcom = %s", $tree);
    $families = $wpdb->get_results($query, ARRAY_A);
    foreach ($families as $family) {
      echo "0 @{$family['familyID']}@ FAM\n";
      if (!empty($family['husband'])) echo "1 HUSB @{$family['husband']}@\n";
      if (!empty($family['wife'])) echo "1 WIFE @{$family['wife']}@\n";
      // ...children, marriage, divorce, etc...
    }
    // ...export sources, media, notes, events, etc...
    echo "0 TRLR\n";
  }
}
