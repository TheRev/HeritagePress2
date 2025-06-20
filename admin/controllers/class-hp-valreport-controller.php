<?php

/**
 * HeritagePress Data Validation Report Controller
 * Handles display of individual data validation reports.
 */
if (!defined('ABSPATH')) exit;

class HeritagePress_ValReport_Controller
{
  public function display_page()
  {
    global $wpdb;
    $report = isset($_GET['report']) ? sanitize_text_field($_GET['report']) : '';
    $tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
    $reports = array(
      'wr_gender' => __('Wrong Gender', 'heritagepress'),
      'unk_gender' => __('Unknown Gender', 'heritagepress'),
      'marr_young' => __('Married Too Young', 'heritagepress'),
      'marr_aft_death' => __('Married After Death', 'heritagepress'),
      'marr_bef_birth' => __('Married Before Birth', 'heritagepress'),
      'died_bef_birth' => __('Died Before Birth', 'heritagepress'),
      'parents_younger' => __('Parents Younger Than Children', 'heritagepress'),
      'children_late' => __('Children Born Too Late', 'heritagepress'),
      'not_living' => __('Not Marked Living', 'heritagepress'),
      'not_dead' => __('Not Marked Dead', 'heritagepress'),
    );
    $report_label = isset($reports[$report]) ? $reports[$report] : __('Unknown Report', 'heritagepress');
    echo '<div class="wrap">';
    echo '<h1>' . esc_html($report_label) . '</h1>';
    echo '<p><a href="admin.php?page=heritagepress-data-validation&tree=' . esc_attr($tree) . '" class="button">' . esc_html__('Back to Data Validation', 'heritagepress') . '</a></p>';

    if ($report === 'wr_gender') {
      $people_table = $wpdb->prefix . 'people';
      $families_table = $wpdb->prefix . 'families';
      $tree_sql = $tree ? $wpdb->prepare("AND {$people_table}.gedcom = %s", $tree) : '';
      $sql = "(
                SELECT {$people_table}.personID, {$people_table}.lastname, {$people_table}.firstname, {$people_table}.sex, {$families_table}.familyID, {$people_table}.gedcom
                FROM {$families_table}
                JOIN {$people_table} ON {$people_table}.gedcom = {$families_table}.gedcom AND {$people_table}.personID = {$families_table}.husband
                WHERE {$people_table}.sex != 'M' $tree_sql
            )
            UNION
            (
                SELECT {$people_table}.personID, {$people_table}.lastname, {$people_table}.firstname, {$people_table}.sex, {$families_table}.familyID, {$people_table}.gedcom
                FROM {$families_table}
                JOIN {$people_table} ON {$people_table}.gedcom = {$families_table}.gedcom AND {$people_table}.personID = {$families_table}.wife
                WHERE {$people_table}.sex != 'F' $tree_sql
            )
            ORDER BY lastname, firstname";
      $results = $wpdb->get_results($sql);
      if ($results) {
        echo '<table class="widefat fixed striped"><thead><tr>';
        echo '<th>' . esc_html__('Person ID', 'heritagepress') . '</th>';
        echo '<th>' . esc_html__('Name', 'heritagepress') . '</th>';
        echo '<th>' . esc_html__('Sex', 'heritagepress') . '</th>';
        echo '<th>' . esc_html__('Family ID', 'heritagepress') . '</th>';
        echo '<th>' . esc_html__('Tree', 'heritagepress') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($results as $row) {
          $edit_url = 'admin.php?page=heritagepress-edit-person&personID=' . urlencode($row->personID) . '&tree=' . urlencode($row->gedcom);
          echo '<tr>';
          echo '<td><a href="' . esc_url($edit_url) . '">' . esc_html($row->personID) . '</a></td>';
          echo '<td>' . esc_html($row->lastname . ', ' . $row->firstname) . '</td>';
          echo '<td>' . esc_html($row->sex) . '</td>';
          echo '<td>' . esc_html($row->familyID) . '</td>';
          echo '<td>' . esc_html($row->gedcom) . '</td>';
          echo '</tr>';
        }
        echo '</tbody></table>';
      } else {
        echo '<div class="notice notice-success"><p>' . esc_html__('No wrong gender entries found.', 'heritagepress') . '</p></div>';
      }
    } else {
      // TODO: Implement actual report logic for each type
      echo '<div class="notice notice-info"><p>' . esc_html__('Report logic not yet implemented. This is a placeholder.', 'heritagepress') . '</p></div>';
    }
    echo '</div>';
  }
}
