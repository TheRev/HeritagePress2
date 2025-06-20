<?php

/**
 * HeritagePress Branch Controller
 *
 * Handles branch management operations for genealogy trees
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

// Ensure WordPress functions are available.
if (!function_exists('sanitize_text_field')) {
  require_once ABSPATH . 'wp-includes/formatting.php';
}

class HP_Branch_Controller
{

  private $wpdb;
  private $table_prefix;
  private $branches_table;
  private $trees_table;
  private $people_table;
  private $families_table;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table_prefix = $wpdb->prefix . 'hp_';
    $this->branches_table = $this->table_prefix . 'branches';
    $this->trees_table = $this->table_prefix . 'trees';
    $this->people_table = $this->table_prefix . 'people';
    $this->families_table = $this->table_prefix . 'families';

    $this->register_hooks();
  }

  /**
   * Register hooks
   */  private function register_hooks()
  {    // AJAX handlers
    add_action('wp_ajax_hp_search_branches', array($this, 'ajax_search_branches'));
    add_action('wp_ajax_hp_add_branch', array($this, 'ajax_add_branch'));
    add_action('wp_ajax_hp_edit_branch', array($this, 'ajax_edit_branch'));
    add_action('wp_ajax_hp_update_branch', array($this, 'ajax_update_branch'));
    add_action('wp_ajax_hp_delete_branch', array($this, 'ajax_delete_branch'));
    add_action('wp_ajax_hp_delete_selected_branches', array($this, 'ajax_delete_selected_branches'));
    add_action('wp_ajax_hp_get_branch_counts', array($this, 'ajax_get_branch_counts'));
    add_action('wp_ajax_hp_get_branch_people', array($this, 'ajax_get_branch_people'));
    add_action('wp_ajax_hp_apply_branch_labels', array($this, 'ajax_apply_branch_labels'));
    add_action('wp_ajax_hp_get_tree_branches', array($this, 'ajax_get_tree_branches'));
    add_action('wp_ajax_hp_get_branch_options', array($this, 'ajax_get_branch_options'));
  }

  /**
   * Display branch management page
   */
  public function display_page()
  {
    // Handle any form submissions
    $this->handle_form_submission();

    // Include the branch management view
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/branches-main.php';
  }

  /**
   * Handle form submissions
   */
  public function handle_form_submission()
  {
    if (!isset($_POST['action']) || !wp_verify_nonce($_POST['nonce'], 'hp_branch_nonce')) {
      return;
    }

    switch ($_POST['action']) {
      case 'add_branch':
        $this->handle_add_branch();
        break;
      case 'update_branch':
        $this->handle_update_branch();
        break;
      case 'delete_branch':
        $this->handle_delete_branch();
        break;
    }
  }

  /**
   * AJAX: Search branches
   */
  public function ajax_search_branches()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $search_term = sanitize_text_field($_POST['search_term'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');
    $order = sanitize_text_field($_POST['order'] ?? 'desc');
    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 25);

    $results = $this->get_branches($search_term, $tree, $order, $offset, $limit);

    wp_send_json_success($results);
  }

  /**
   * AJAX: Add branch
   */
  public function ajax_add_branch()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    $branch = sanitize_text_field($_POST['branch']);
    $description = sanitize_text_field($_POST['description']);
    $person_id = sanitize_text_field($_POST['personID']);
    $agens = intval($_POST['agens'] ?? 0);
    $dgens = intval($_POST['dgens'] ?? 0);
    $dagens = intval($_POST['dagens'] ?? 0);
    $inclspouses = intval($_POST['inclspouses'] ?? 0);

    // Validate required fields
    if (empty($gedcom) || empty($description) || empty($person_id)) {
      wp_send_json_error(__('Required fields are missing', 'heritagepress'));
    }

    // Generate branch ID if not provided
    if (empty($branch)) {
      $branch = $this->generate_branch_id($gedcom);
    }

    $result = $this->add_branch($gedcom, $branch, $description, $person_id, $agens, $dgens, $dagens, $inclspouses);

    if ($result) {
      wp_send_json_success(__('Branch added successfully', 'heritagepress'));
    } else {
      wp_send_json_error(__('Failed to add branch', 'heritagepress'));
    }
  }

  /**
   * AJAX: Delete branch
   */
  public function ajax_delete_branch()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $branch_id = sanitize_text_field($_POST['branch_id']);
    $tree = sanitize_text_field($_POST['tree']);

    $result = $this->delete_branch($branch_id, $tree);

    if ($result) {
      wp_send_json_success(__('Branch deleted successfully', 'heritagepress'));
    } else {
      wp_send_json_error(__('Failed to delete branch', 'heritagepress'));
    }
  }

  /**
   * AJAX: Delete selected branches
   */
  public function ajax_delete_selected_branches()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $branches = $_POST['branches'] ?? array();
    $deleted_count = 0;

    foreach ($branches as $branch_data) {
      $parts = explode('&', $branch_data);
      if (count($parts) == 2) {
        $branch_id = sanitize_text_field($parts[0]);
        $tree = sanitize_text_field($parts[1]);

        if ($this->delete_branch($branch_id, $tree)) {
          $deleted_count++;
        }
      }
    }

    wp_send_json_success(sprintf(__('%d branches deleted successfully', 'heritagepress'), $deleted_count));
  }

  /**
   * AJAX: Update branch
   */
  public function ajax_update_branch()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $original_branch = sanitize_text_field($_POST['original_branch']);
    $original_tree = sanitize_text_field($_POST['original_tree']);
    $description = sanitize_text_field($_POST['description']);
    $person_id = sanitize_text_field($_POST['personID']);
    $agens = intval($_POST['agens'] ?? 0);
    $dgens = intval($_POST['dgens'] ?? 0);
    $dagens = intval($_POST['dagens'] ?? 1);
    $inclspouses = intval($_POST['inclspouses'] ?? 0);

    $result = $this->update_branch($original_branch, $original_tree, $description, $person_id, $agens, $dgens, $dagens, $inclspouses);

    if ($result) {
      wp_send_json_success(__('Branch updated successfully', 'heritagepress'));
    } else {
      wp_send_json_error(__('Failed to update branch', 'heritagepress'));
    }
  }

  /**
   * AJAX: Get branch people
   */
  public function ajax_get_branch_people()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $branch_id = sanitize_text_field($_POST['branch']);
    $tree = sanitize_text_field($_POST['tree']);

    $people = $this->get_branch_people($branch_id, $tree);
    wp_send_json_success($people);
  }

  /**
   * AJAX: Apply branch labels
   */  public function ajax_apply_branch_labels()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $branch_id = sanitize_text_field($_POST['branch']);
    $tree = sanitize_text_field($_POST['tree']);
    $action = sanitize_text_field($_POST['branchaction']);
    $apply_to = sanitize_text_field($_POST['set']);
    $overwrite = intval($_POST['overwrite'] ?? 0);

    // Get additional parameters for individual-based processing
    $person_id = sanitize_text_field($_POST['personID'] ?? '');
    $agens = intval($_POST['agens'] ?? 0);
    $dgens = intval($_POST['dgens'] ?? 0);
    $dagens = intval($_POST['dagens'] ?? 1);
    $include_spouses = isset($_POST['dospouses']) ? 1 : 0;

    $result = $this->apply_branch_labels($branch_id, $tree, $action, $apply_to, $overwrite, $person_id, $agens, $dgens, $dagens, $include_spouses);

    if ($result) {
      if (is_array($result)) {
        $message = sprintf(
          __('Branch labels applied successfully. Affected: %d people, %d families.', 'heritagepress'),
          $result['people'],
          $result['families']
        );
      } else {
        $message = __('Branch labels applied successfully', 'heritagepress');
      }
      wp_send_json_success($message);
    } else {
      wp_send_json_error(__('Failed to apply branch labels', 'heritagepress'));
    }
  }

  /**
   * AJAX: Get branches for a specific tree
   */
  public function ajax_get_tree_branches()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $tree = sanitize_text_field($_POST['tree'] ?? '');

    if (empty($tree)) {
      wp_send_json_error(__('Tree parameter is required', 'heritagepress'));
    }

    $branches = $this->wpdb->get_results($this->wpdb->prepare(
      "SELECT branch, description, personID, agens, dgens, dagens, inclspouses
       FROM {$this->branches_table}
       WHERE gedcom = %s
       ORDER BY branch, description",
      $tree
    ));

    wp_send_json_success($branches);
  }

  /**
   * AJAX: Get branch options as HTML for select dropdowns
   * Replicates TNG admin_branchoptions.php functionality
   */
  public function ajax_get_branch_options()
  {
    // Security check
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
      wp_die(__('Security check failed', 'heritagepress'));
    }

    $tree = sanitize_text_field($_POST['tree'] ?? '');

    if (empty($tree)) {
      echo "0";
      wp_die();
    }

    $branches = $this->wpdb->get_results($this->wpdb->prepare(
      "SELECT branch, description FROM {$this->branches_table} WHERE gedcom = %s ORDER BY description",
      $tree
    ));

    $numrows = count($branches);

    if (!$numrows) {
      echo "0";
    } else {
      echo "<option value=\"\"></option>\n";
      foreach ($branches as $row) {
        echo "<option value=\"" . esc_attr($row->branch) . "\">" . esc_html($row->description) . "</option>\n";
      }
    }

    wp_die();
  }

  /**
   * Get branches with search and pagination
   */
  public function get_branches($search_term = '', $tree = '', $order = 'desc', $offset = 0, $limit = 25)
  {
    $where_conditions = array();
    $params = array();

    // Search conditions
    if (!empty($search_term)) {
      $where_conditions[] = "(b.branch LIKE %s OR b.description LIKE %s)";
      $params[] = '%' . $search_term . '%';
      $params[] = '%' . $search_term . '%';
    }

    // Tree filter
    if (!empty($tree)) {
      $where_conditions[] = "b.gedcom = %s";
      $params[] = $tree;
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
      $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Order by
    $order_clause = '';
    switch ($order) {
      case 'id':
        $order_clause = 'ORDER BY b.branch, b.description';
        break;
      case 'idup':
        $order_clause = 'ORDER BY b.branch DESC, b.description DESC';
        break;
      case 'desc':
        $order_clause = 'ORDER BY b.description';
        break;
      case 'descup':
        $order_clause = 'ORDER BY b.description DESC';
        break;
      default:
        $order_clause = 'ORDER BY b.description';
    }

    // Build query
    $sql = "SELECT b.gedcom, b.branch, b.description, b.personID, t.tree_name
            FROM {$this->branches_table} b
            LEFT JOIN {$this->trees_table} t ON t.gedcom = b.gedcom
            $where_clause $order_clause LIMIT %d OFFSET %d";

    $params[] = $limit;
    $params[] = $offset;

    if (!empty($params)) {
      $prepared_sql = $this->wpdb->prepare($sql, $params);
    } else {
      $prepared_sql = $sql;
    }

    $branches = $this->wpdb->get_results($prepared_sql);

    // Get total count
    $count_sql = "SELECT COUNT(*) FROM {$this->branches_table} b
                  LEFT JOIN {$this->trees_table} t ON t.gedcom = b.gedcom
                  $where_clause";

    $count_params = array_slice($params, 0, -2); // Remove limit and offset
    if (!empty($count_params)) {
      $total_count = $this->wpdb->get_var($this->wpdb->prepare($count_sql, $count_params));
    } else {
      $total_count = $this->wpdb->get_var($count_sql);
    }

    // Add counts for each branch
    foreach ($branches as $branch) {
      $branch->people_count = $this->get_branch_count($branch->gedcom, $branch->branch, $this->people_table);
      $branch->families_count = $this->get_branch_count($branch->gedcom, $branch->branch, $this->families_table);
    }

    return array(
      'branches' => $branches,
      'total' => intval($total_count),
      'offset' => $offset,
      'limit' => $limit
    );
  }

  /**
   * Get count of records in a branch
   */
  private function get_branch_count($tree, $branch, $table)
  {
    $sql = $this->wpdb->prepare(
      "SELECT COUNT(ID) FROM $table WHERE gedcom = %s AND branch LIKE %s",
      $tree,
      '%' . $branch . '%'
    );

    $count = $this->wpdb->get_var($sql);
    return $count ? intval($count) : 0;
  }

  /**
   * Add a new branch
   */
  private function add_branch($gedcom, $branch, $description, $person_id, $agens = 0, $dgens = 0, $dagens = 1, $inclspouses = 0)
  {
    $result = $this->wpdb->insert(
      $this->branches_table,
      array(
        'gedcom' => $gedcom,
        'branch' => $branch,
        'description' => $description,
        'personID' => $person_id,
        'agens' => $agens,
        'dgens' => $dgens,
        'dagens' => $dagens,
        'inclspouses' => $inclspouses,
        'action' => '2'
      ),
      array('%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s')
    );

    return $result !== false;
  }

  /**
   * Delete a branch
   */
  private function delete_branch($branch_id, $tree)
  {
    $result = $this->wpdb->delete(
      $this->branches_table,
      array(
        'branch' => $branch_id,
        'gedcom' => $tree
      ),
      array('%s', '%s')
    );

    return $result !== false;
  }

  /**
   * Generate a unique branch ID
   */
  private function generate_branch_id($gedcom)
  {
    $base = 'BR';
    $counter = 1;

    do {
      $branch_id = $base . sprintf('%03d', $counter);
      $exists = $this->wpdb->get_var($this->wpdb->prepare(
        "SELECT COUNT(*) FROM {$this->branches_table} WHERE gedcom = %s AND branch = %s",
        $gedcom,
        $branch_id
      ));
      $counter++;
    } while ($exists > 0);

    // Ensure the method always returns a value.
    return $branch_id;
  }

  /**
   * Handle add branch form submission
   */
  private function handle_add_branch()
  {
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $branch = sanitize_text_field($_POST['branch']);
    $description = sanitize_text_field($_POST['description']);
    $person_id = sanitize_text_field($_POST['personID']);
    $agens = intval($_POST['agens'] ?? 0);
    $dgens = intval($_POST['dgens'] ?? 0);
    $dagens = intval($_POST['dagens'] ?? 1);
    $inclspouses = intval($_POST['inclspouses'] ?? 0);

    if (empty($branch)) {
      $branch = $this->generate_branch_id($gedcom);
    }

    $result = $this->add_branch($gedcom, $branch, $description, $person_id, $agens, $dgens, $dagens, $inclspouses);

    if ($result) {
      wp_redirect(admin_url('admin.php?page=hp-branch-management&message=added'));
      exit;
    }
  }

  /**
   * Handle update branch form submission
   */
  private function handle_update_branch()
  {
    // Implementation for updating branches
    // This would be used for editing existing branches
  }

  /**
   * Update a branch
   */
  private function update_branch($original_branch, $original_tree, $description, $person_id, $agens = 0, $dgens = 0, $dagens = 1, $inclspouses = 0)
  {
    $result = $this->wpdb->update(
      $this->branches_table,
      array(
        'description' => $description,
        'personID' => $person_id,
        'agens' => $agens,
        'dgens' => $dgens,
        'dagens' => $dagens,
        'inclspouses' => $inclspouses
      ),
      array(
        'branch' => $original_branch,
        'gedcom' => $original_tree
      ),
      array('%s', '%s', '%d', '%d', '%d', '%d'),
      array('%s', '%s')
    );

    return $result !== false;
  }

  /**
   * Get people in branch
   */
  private function get_branch_people($branch_id, $tree)
  {
    $sql = $this->wpdb->prepare(
      "SELECT personID, firstname, lastname, birthdate, deathdate
       FROM {$this->people_table}
       WHERE gedcom = %s AND branch LIKE %s
       ORDER BY lastname, firstname
       LIMIT 100",
      $tree,
      '%' . $branch_id . '%'
    );

    return $this->wpdb->get_results($sql);
  }
  /**
   * Apply branch labels
   */
  private function apply_branch_labels($branch_id, $tree, $action, $apply_to, $overwrite, $person_id = '', $agens = 0, $dgens = 0, $dagens = 1, $include_spouses = 0)
  {
    // Get branch details
    $branch = $this->wpdb->get_row($this->wpdb->prepare(
      "SELECT * FROM {$this->branches_table} WHERE branch = %s AND gedcom = %s",
      $branch_id,
      $tree
    ));

    if (!$branch) {
      return false;
    }

    $counter = 0;
    $fcounter = 0;

    try {
      // Start transaction for data consistency
      $this->wpdb->query('START TRANSACTION');

      if ($apply_to === 'all') {
        // Apply to all records
        if ($action === 'clear' || $action === 'remove') {
          $counter = $this->clear_branch_from_people($tree, $branch_id);
          $fcounter = $this->clear_branch_from_families($tree, $branch_id);
        } elseif ($action === 'delete') {
          $counter = $this->delete_branch_people($tree, $branch_id);
          $fcounter = $this->delete_branch_families($tree, $branch_id);
        }

        // Clean up branch links
        $this->wpdb->delete(
          $this->table_prefix . 'branchlinks',
          array('gedcom' => $tree, 'branch' => $branch_id),
          array('%s', '%s')
        );
      } else {
        // Apply to specific person/family relationships
        // Use the person_id from the form if provided, otherwise fall back to branch stored personID
        $starting_person = $person_id ?: ($branch->personID ?? '');

        if ($starting_person) {
          $gender = $this->get_person_gender($starting_person, $tree);

          // Process the starting person
          $this->set_person_label($starting_person, $tree, $branch_id, $action, $overwrite);
          $counter++;

          // Process ancestors if specified
          if ($agens > 0) {
            $this->process_ancestors($starting_person, $tree, $branch_id, $action, $overwrite, $gender, 1, $agens, $include_spouses);
          }

          // Process descendants if specified
          if ($dgens > 0) {
            $this->process_descendants($starting_person, $tree, $branch_id, $action, $overwrite, $gender, 1, $dgens, $include_spouses);
          }

          // Process descendants of ancestors if specified
          if ($agens > 0 && $dagens > 0) {
            $this->process_descendants_of_ancestors($starting_person, $tree, $branch_id, $action, $overwrite, $gender, $agens, $dagens, $include_spouses);
          }
        }
      }

      $this->wpdb->query('COMMIT');
      return array('people' => $counter, 'families' => $fcounter);
    } catch (Exception $e) {
      $this->wpdb->query('ROLLBACK');
      error_log('Branch labeling error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Process descendants of ancestors (TNG dagens functionality)
   */
  private function process_descendants_of_ancestors($person_id, $tree, $branch_id, $action, $overwrite, $gender, $ancestor_generations, $descendant_generations, $include_spouses)
  {
    // First, get all ancestors up to the specified number of generations
    $ancestors = array();
    $this->collect_ancestors($person_id, $tree, 1, $ancestor_generations, $ancestors);

    // For each ancestor, process their descendants
    foreach ($ancestors as $ancestor_id) {
      $ancestor_gender = $this->get_person_gender($ancestor_id, $tree);
      $this->process_descendants($ancestor_id, $tree, $branch_id, $action, $overwrite, $ancestor_gender, 1, $descendant_generations, $include_spouses);
    }
  }

  /**
   * Collect ancestors recursively
   */
  private function collect_ancestors($person_id, $tree, $generation, $max_generations, &$ancestors)
  {
    if ($generation > $max_generations) return;

    $children_table = $this->table_prefix . 'children';

    // Get parents through family relationships
    $families = $this->wpdb->get_results($this->wpdb->prepare(
      "SELECT f.familyID, f.husband, f.wife
       FROM {$children_table} c
       JOIN {$this->families_table} f ON c.familyID = f.familyID
       WHERE c.personID = %s AND c.gedcom = %s AND f.gedcom = %s",
      $person_id,
      $tree,
      $tree
    ));

    foreach ($families as $family) {
      if ($family->husband && !in_array($family->husband, $ancestors)) {
        $ancestors[] = $family->husband;
        $this->collect_ancestors($family->husband, $tree, $generation + 1, $max_generations, $ancestors);
      }

      if ($family->wife && !in_array($family->wife, $ancestors)) {
        $ancestors[] = $family->wife;
        $this->collect_ancestors($family->wife, $tree, $generation + 1, $max_generations, $ancestors);
      }
    }
  }

  /**
   * Get person gender
   */
  private function get_person_gender($person_id, $tree)
  {
    $sql = $this->wpdb->prepare(
      "SELECT gender FROM {$this->people_table} WHERE personID = %s AND gedcom = %s",
      $person_id,
      $tree
    );

    return $this->wpdb->get_var($sql);
  }

  // Added the `process_descendants` method to process descendants of a person.
  private function process_descendants($person_id, $tree, $branch_id, $action, $overwrite, $gender, $generation, $max_generations, $include_spouses)
  {
    if ($generation > $max_generations) return;

    $children_table = $this->table_prefix . 'children';

    // Get children of the person
    $children = $this->wpdb->get_results($this->wpdb->prepare(
      "SELECT personID FROM {$children_table} WHERE familyID IN (
            SELECT familyID FROM {$this->families_table} WHERE gedcom = %s AND (husband = %s OR wife = %s)
        )",
      $tree,
      $person_id,
      $person_id
    ));

    foreach ($children as $child) {
      $this->set_person_label($child->personID, $tree, $branch_id, $action, $overwrite);
      $this->process_descendants($child->personID, $tree, $branch_id, $action, $overwrite, $gender, $generation + 1, $max_generations, $include_spouses);
    }
  }

  /**
   * Handle delete branch form submission
   */
  private function handle_delete_branch()
  {
    $branch_id = sanitize_text_field($_POST['branch_id'] ?? '');
    $tree = sanitize_text_field($_POST['tree'] ?? '');

    if (empty($branch_id) || empty($tree)) {
      wp_send_json_error(__('Branch ID and Tree are required.', 'heritagepress'));
    }

    $result = $this->delete_branch($branch_id, $tree);

    if ($result) {
      wp_send_json_success(__('Branch deleted successfully.', 'heritagepress'));
    } else {
      wp_send_json_error(__('Failed to delete branch.', 'heritagepress'));
    }
  }
}
