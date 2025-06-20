<?php

/**
 * HeritagePress Data Validation Admin Controller
 * Provides the Data Validation admin page and report links.
 */
if (!defined('ABSPATH')) exit;

class HeritagePress_Data_Validation_Controller
{
  public function display_page()
  {
    global $wpdb;
    $trees = $wpdb->get_results("SELECT gedcom, treename FROM {$wpdb->prefix}hp_trees ORDER BY treename");
    $selected_tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
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
?>
    <div class="wrap">
      <h1><?php esc_html_e('Data Validation', 'heritagepress'); ?></h1>
      <form method="get" action="">
        <input type="hidden" name="page" value="heritagepress-data-validation" />
        <label for="treequeryselect"><?php esc_html_e('Tree:', 'heritagepress'); ?></label>
        <select name="tree" id="treequeryselect">
          <option value=""><?php esc_html_e('All Trees', 'heritagepress'); ?></option>
          <?php foreach ($trees as $tree): ?>
            <option value="<?php echo esc_attr($tree->gedcom); ?>" <?php selected($selected_tree, $tree->gedcom); ?>><?php echo esc_html($tree->treename); ?></option>
          <?php endforeach; ?>
        </select>
        <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'heritagepress'); ?>" />
      </form>
      <br />
      <table class="widefat fixed striped">
        <thead>
          <tr>
            <th style="width:40px;">#</th>
            <th><?php esc_html_e('Report', 'heritagepress'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1;
          foreach ($reports as $key => $label): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><a href="admin.php?page=heritagepress-valreport&report=<?php echo esc_attr($key); ?>&tree=<?php echo esc_attr($selected_tree); ?>" class="valreport"><?php echo esc_html($label); ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php
  }
}
