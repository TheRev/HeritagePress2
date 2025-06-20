<?php

/**
 * HeritagePress Branch Manager
 *
 * Handles management of genealogy branches within trees.
 * A branch represents a subset of people within a tree, typically based on
 * a starting person and generation parameters.
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Branch_Manager
{

  private $wpdb;
  private $table_prefix;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table_prefix = $wpdb->prefix . 'hp_';
  }

  /**
   * Add a new branch to a tree
   *
   * @param string $gedcom Tree identifier
   * @param string $branch Branch identifier/code
   * @param string $description Branch description/name
   * @param string $person_id Starting person for the branch
   * @param int $ancestor_generations Number of ancestor generations to include
   * @param int $descendant_generations Number of descendant generations to include
   * @param int $descendant_ancestor_generations Descendant ancestor generations
   * @param bool $include_spouses Whether to include spouses
   * @return bool True on success, false on failure
   */
  public function add_branch($gedcom, $branch, $description, $person_id, $ancestor_generations = 0, $descendant_generations = 0, $descendant_ancestor_generations = 0, $include_spouses = false)
  {
    // Validate inputs
    if (empty($gedcom) || empty($branch) || empty($description) || empty($person_id)) {
      return false;
    }

    // Sanitize inputs
    $gedcom = sanitize_text_field($gedcom);
    $branch = sanitize_text_field($branch);
    $description = sanitize_text_field($description);
    $person_id = sanitize_text_field($person_id);
    $ancestor_generations = max(0, intval($ancestor_generations));
    $descendant_generations = max(0, intval($descendant_generations));
    $descendant_ancestor_generations = max(0, intval($descendant_ancestor_generations));
    $include_spouses = $include_spouses ? 1 : 0;

    // Check if branch already exists
    if ($this->branch_exists($gedcom, $branch)) {
      return false;
    }

    // Verify that the person exists in the tree
    if (!$this->person_exists_in_tree($gedcom, $person_id)) {
      return false;
    }

    // Insert branch
    $table_name = $this->table_prefix . 'branches';
    $result = $this->wpdb->insert(
      $table_name,
      [
        'gedcom' => $gedcom,
        'branch' => $branch,
        'description' => $description,
        'personID' => $person_id,
        'agens' => $ancestor_generations,
        'dgens' => $descendant_generations,
        'dagens' => $descendant_ancestor_generations,
        'inclspouses' => $include_spouses,
        'action' => 2  // Uses '2' for new/active branches
      ],
      ['%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d']
    );

    if ($result === false) {
      error_log('HeritagePress: Failed to insert branch - ' . $this->wpdb->last_error);
      return false;
    }

    // Log the action
    $this->log_branch_action('add', $gedcom, $branch, $description);

    return true;
  }

  /**
   * Update an existing branch
   *
   * @param string $gedcom Tree identifier
   * @param string $branch Branch identifier
   * @param array $data Updated branch data
   * @return bool True on success, false on failure
   */
  public function update_branch($gedcom, $branch, $data)
  {
    if (empty($gedcom) || empty($branch) || !is_array($data)) {
      return false;
    }

    // Sanitize data
    $update_data = [];
    $format = [];

    if (isset($data['description'])) {
      $update_data['description'] = sanitize_text_field($data['description']);
      $format[] = '%s';
    }

    if (isset($data['personID'])) {
      $person_id = sanitize_text_field($data['personID']);
      if ($this->person_exists_in_tree($gedcom, $person_id)) {
        $update_data['personID'] = $person_id;
        $format[] = '%s';
      }
    }

    if (isset($data['agens'])) {
      $update_data['agens'] = max(0, intval($data['agens']));
      $format[] = '%d';
    }

    if (isset($data['dgens'])) {
      $update_data['dgens'] = max(0, intval($data['dgens']));
      $format[] = '%d';
    }

    if (isset($data['dagens'])) {
      $update_data['dagens'] = max(0, intval($data['dagens']));
      $format[] = '%d';
    }

    if (isset($data['inclspouses'])) {
      $update_data['inclspouses'] = $data['inclspouses'] ? 1 : 0;
      $format[] = '%d';
    }

    if (empty($update_data)) {
      return false;
    }

    $table_name = $this->table_prefix . 'branches';
    $result = $this->wpdb->update(
      $table_name,
      $update_data,
      ['gedcom' => $gedcom, 'branch' => $branch],
      $format,
      ['%s', '%s']
    );

    if ($result !== false) {
      $this->log_branch_action('update', $gedcom, $branch, $update_data['description'] ?? '');
    }

    return $result !== false;
  }

  /**
   * Delete a branch
   *
   * @param string $gedcom Tree identifier
   * @param string $branch Branch identifier
   * @return bool True on success, false on failure
   */
  public function delete_branch($gedcom, $branch)
  {
    if (empty($gedcom) || empty($branch)) {
      return false;
    }

    $table_name = $this->table_prefix . 'branches';

    // Get branch details for logging
    $branch_data = $this->get_branch($gedcom, $branch);

    $result = $this->wpdb->delete(
      $table_name,
      ['gedcom' => $gedcom, 'branch' => $branch],
      ['%s', '%s']
    );

    if ($result !== false && $branch_data) {
      $this->log_branch_action('delete', $gedcom, $branch, $branch_data['description']);

      // Also delete branch links
      $this->delete_branch_links($gedcom, $branch);
    }

    return $result !== false;
  }

  /**
   * Get a specific branch
   *
   * @param string $gedcom Tree identifier
   * @param string $branch Branch identifier
   * @return array|null Branch data or null if not found
   */
  public function get_branch($gedcom, $branch)
  {
    $table_name = $this->table_prefix . 'branches';

    return $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE gedcom = %s AND branch = %s",
        $gedcom,
        $branch
      ),
      ARRAY_A
    );
  }

  /**
   * Get all branches for a tree
   *
   * @param string $gedcom Tree identifier
   * @return array Array of branches
   */
  public function get_tree_branches($gedcom)
  {
    $table_name = $this->table_prefix . 'branches';

    return $this->wpdb->get_results(
      $this->wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE gedcom = %s ORDER BY description",
        $gedcom
      ),
      ARRAY_A
    );
  }

  /**
   * Check if a branch exists
   *
   * @param string $gedcom Tree identifier
   * @param string $branch Branch identifier
   * @return bool True if exists, false otherwise
   */
  public function branch_exists($gedcom, $branch)
  {
    $table_name = $this->table_prefix . 'branches';

    $count = $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE gedcom = %s AND branch = %s",
        $gedcom,
        $branch
      )
    );

    return intval($count) > 0;
  }

  /**
   * Check if a person exists in a tree
   *
   * @param string $gedcom Tree identifier
   * @param string $person_id Person identifier
   * @return bool True if exists, false otherwise
   */
  private function person_exists_in_tree($gedcom, $person_id)
  {
    $people_table = $this->table_prefix . 'people';

    $count = $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT COUNT(*) FROM {$people_table} WHERE gedcom = %s AND personID = %s",
        $gedcom,
        $person_id
      )
    );

    return intval($count) > 0;
  }

  /**
   * Delete branch links for a branch
   *
   * @param string $gedcom Tree identifier
   * @param string $branch Branch identifier
   */
  private function delete_branch_links($gedcom, $branch)
  {
    $branchlinks_table = $this->table_prefix . 'branchlinks';

    $this->wpdb->delete(
      $branchlinks_table,
      ['gedcom' => $gedcom, 'branch' => $branch],
      ['%s', '%s']
    );
  }

  /**
   * Generate a unique branch ID
   *
   * @param string $gedcom Tree identifier
   * @param string $base_name Base name for the branch ID
   * @return string Unique branch ID
   */
  public function generate_branch_id($gedcom, $base_name = 'BRANCH')
  {
    $base_name = strtoupper(sanitize_text_field($base_name));
    $counter = 1;

    do {
      $branch_id = $base_name . $counter;
      $counter++;
    } while ($this->branch_exists($gedcom, $branch_id) && $counter <= 1000);

    return $branch_id;
  }

  /**
   * Validate branch data
   *
   * @param array $data Branch data to validate
   * @return array Validation result with 'valid' boolean and 'errors' array
   */
  public function validate_branch_data($data)
  {
    $errors = [];

    if (empty($data['gedcom'])) {
      $errors[] = 'Tree identifier is required';
    }

    if (empty($data['branch'])) {
      $errors[] = 'Branch identifier is required';
    }

    if (empty($data['description'])) {
      $errors[] = 'Branch description is required';
    }

    if (empty($data['personID'])) {
      $errors[] = 'Starting person ID is required';
    }

    // Validate branch ID format (alphanumeric, underscores, hyphens)
    if (!empty($data['branch']) && !preg_match('/^[a-zA-Z0-9_-]+$/', $data['branch'])) {
      $errors[] = 'Branch identifier can only contain letters, numbers, underscores, and hyphens';
    }

    // Check if branch already exists
    if (!empty($data['gedcom']) && !empty($data['branch'])) {
      if ($this->branch_exists($data['gedcom'], $data['branch'])) {
        $errors[] = 'Branch identifier already exists in this tree';
      }
    }

    // Validate person exists
    if (!empty($data['gedcom']) && !empty($data['personID'])) {
      if (!$this->person_exists_in_tree($data['gedcom'], $data['personID'])) {
        $errors[] = 'Starting person not found in the specified tree';
      }
    }

    // Validate generation numbers
    if (isset($data['agens']) && (!is_numeric($data['agens']) || intval($data['agens']) < 0)) {
      $errors[] = 'Ancestor generations must be a non-negative number';
    }

    if (isset($data['dgens']) && (!is_numeric($data['dgens']) || intval($data['dgens']) < 0)) {
      $errors[] = 'Descendant generations must be a non-negative number';
    }

    if (isset($data['dagens']) && (!is_numeric($data['dagens']) || intval($data['dagens']) < 0)) {
      $errors[] = 'Descendant ancestor generations must be a non-negative number';
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors
    ];
  }

  /**
   * Log branch actions for audit trail
   *
   * @param string $action Action performed (add, update, delete)
   * @param string $gedcom Tree identifier
   * @param string $branch Branch identifier
   * @param string $description Branch description
   */
  private function log_branch_action($action, $gedcom, $branch, $description)
  {
    $user = wp_get_current_user();
    $user_name = $user->user_login ?? 'unknown';

    $log_message = sprintf(
      '%s branch: %s/%s (%s) by user %s',
      ucfirst($action),
      $gedcom,
      $branch,
      $description,
      $user_name
    );

    error_log('HeritagePress Branch Log: ' . $log_message);

    // You could also store this in a dedicated log table if needed
    do_action('heritagepress_branch_logged', $action, $gedcom, $branch, $description);
  }
}
