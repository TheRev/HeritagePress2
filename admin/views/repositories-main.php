<?php

/**
 * Repository Main/Search View for HeritagePress
 *
 * This file provides the search and listing interface for repositories.
 * Ported from admin_repositories.php functionality
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get search parameters
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$gedcom_filter = isset($_GET['gedcom']) ? sanitize_text_field($_GET['gedcom']) : '';
$exact_match = isset($_GET['exact_match']) ? $_GET['exact_match'] : '';
$page_num = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 25;
$offset = ($page_num - 1) * $per_page;

// Get repository controller
$controller = new HP_Repository_Controller();

// Get repositories
$repositories = $controller->search_repositories($search_term, $gedcom_filter, $per_page, $offset);
$total_repositories = $controller->get_repositories_count($search_term, $gedcom_filter);

// Get available trees
$trees = $controller->get_available_trees();

// Calculate pagination
$total_pages = ceil($total_repositories / $per_page);
$start_item = $offset + 1;
$end_item = min($offset + $per_page, $total_repositories);
?>

<div class="repository-search-section">
  <div class="hp-admin-block">
    <h3><?php _e('Search Repositories', 'heritagepress'); ?></h3>

    <form method="get" id="repository-search-form" class="hp-search-form">
      <input type="hidden" name="page" value="heritagepress-repositories">
      <input type="hidden" name="tab" value="search">

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="search"><?php _e('Search for:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="search" id="search" value="<?php echo esc_attr($search_term); ?>"
              class="regular-text" placeholder="<?php esc_attr_e('Repository ID or name...', 'heritagepress'); ?>">
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="gedcom" id="gedcom">
              <option value=""><?php _e('All trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>"
                  <?php selected($gedcom_filter, $tree['gedcom']); ?>>
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>

        <tr>
          <td></td>
          <td>
            <label>
              <input type="checkbox" name="exact_match" value="yes" <?php checked($exact_match, 'yes'); ?>>
              <?php _e('Exact match', 'heritagepress'); ?>
            </label>
          </td>
        </tr>

        <tr>
          <td></td>
          <td>
            <input type="submit" name="search_repositories" value="<?php esc_attr_e('Search', 'heritagepress'); ?>" class="button-primary">
            <input type="submit" name="reset_search" value="<?php esc_attr_e('Reset', 'heritagepress'); ?>" class="button"
              onclick="document.getElementById('search').value=''; document.getElementById('gedcom').selectedIndex=0; document.querySelector('[name=exact_match]').checked=false;">
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>

<div class="repository-results-section">
  <div class="hp-admin-block">
    <?php if ($total_repositories > 0): ?>
      <div class="tablenav top">
        <div class="alignleft actions">
          <p class="search-results">
            <?php
            printf(
              __('Showing %d-%d of %d repositories', 'heritagepress'),
              $start_item,
              $end_item,
              $total_repositories
            );
            ?>
          </p>
        </div>

        <?php if ($total_pages > 1): ?>
          <div class="tablenav-pages">
            <?php
            $pagination_args = array(
              'base' => add_query_arg('paged', '%#%'),
              'format' => '',
              'prev_text' => __('&laquo;'),
              'next_text' => __('&raquo;'),
              'total' => $total_pages,
              'current' => $page_num
            );
            echo paginate_links($pagination_args);
            ?>
          </div>
        <?php endif; ?>
      </div>

      <table class="wp-list-table widefat fixed striped repositories">
        <thead>
          <tr>
            <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-repoid"><?php _e('Repository ID', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-name"><?php _e('Name', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column column-location"><?php _e('Location', 'heritagepress'); ?></th>
            <?php if (count($trees) > 1): ?>
              <th scope="col" class="manage-column column-tree"><?php _e('Tree', 'heritagepress'); ?></th>
            <?php endif; ?>
            <th scope="col" class="manage-column column-modified"><?php _e('Last Modified', 'heritagepress'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($repositories as $repo): ?>
            <tr id="repository-<?php echo esc_attr($repo['ID']); ?>">
              <td class="column-actions">
                <div class="row-actions">
                  <span class="edit">
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=edit&repoID=' . urlencode($repo['repoID']) . '&gedcom=' . urlencode($repo['gedcom'])); ?>"
                      title="<?php esc_attr_e('Edit this repository', 'heritagepress'); ?>">
                      <?php _e('Edit', 'heritagepress'); ?>
                    </a>
                  </span>
                  |
                  <span class="delete">
                    <a href="#" onclick="return confirmDelete('<?php echo esc_js($repo['ID']); ?>', '<?php echo esc_js($repo['repoID']); ?>');"
                      title="<?php esc_attr_e('Delete this repository', 'heritagepress'); ?>" class="submitdelete">
                      <?php _e('Delete', 'heritagepress'); ?>
                    </a>
                  </span>
                  |
                  <span class="view">
                    <a href="#" onclick="return viewRepository('<?php echo esc_js($repo['repoID']); ?>', '<?php echo esc_js($repo['gedcom']); ?>');"
                      title="<?php esc_attr_e('View repository details', 'heritagepress'); ?>">
                      <?php _e('View', 'heritagepress'); ?>
                    </a>
                  </span>
                </div>
              </td>
              <td class="column-repoid">
                <strong>
                  <a href="<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=edit&repoID=' . urlencode($repo['repoID']) . '&gedcom=' . urlencode($repo['gedcom'])); ?>"
                    title="<?php esc_attr_e('Edit this repository', 'heritagepress'); ?>">
                    <?php echo esc_html($repo['repoID']); ?>
                  </a>
                </strong>
              </td>
              <td class="column-name">
                <?php echo esc_html($repo['reponame']); ?>
              </td>
              <td class="column-location">
                <?php
                $location_parts = array();
                if (!empty($repo['city'])) $location_parts[] = $repo['city'];
                if (!empty($repo['state'])) $location_parts[] = $repo['state'];
                if (!empty($repo['country'])) $location_parts[] = $repo['country'];
                echo esc_html(implode(', ', $location_parts));
                ?>
              </td>
              <?php if (count($trees) > 1): ?>
                <td class="column-tree">
                  <?php
                  // Find tree name
                  foreach ($trees as $tree) {
                    if ($tree['gedcom'] === $repo['gedcom']) {
                      echo esc_html($tree['treename']);
                      break;
                    }
                  }
                  ?>
                </td>
              <?php endif; ?>
              <td class="column-modified">
                <?php echo esc_html($repo['formatted_date']); ?>
                <br>
                <small><?php echo esc_html($repo['changedby']); ?></small>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
          <div class="tablenav-pages">
            <?php echo paginate_links($pagination_args); ?>
          </div>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="hp-no-results">
        <p><?php _e('No repositories found.', 'heritagepress'); ?></p>
        <?php if (!empty($search_term) || !empty($gedcom_filter)): ?>
          <p>
            <a href="<?php echo admin_url('admin.php?page=heritagepress-repositories&tab=search'); ?>" class="button">
              <?php _e('Clear search', 'heritagepress'); ?>
            </a>
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Repository Details Modal -->
<div id="repository-details-modal" class="hp-modal" style="display: none;">
  <div class="hp-modal-content">
    <div class="hp-modal-header">
      <h3><?php _e('Repository Details', 'heritagepress'); ?></h3>
      <span class="hp-modal-close">&times;</span>
    </div>
    <div class="hp-modal-body">
      <div id="repository-details-content">
        <!-- Content loaded via AJAX -->
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Confirm delete function
    window.confirmDelete = function(repositoryId, repoID) {
      if (confirm('<?php echo esc_js(__('Are you sure you want to delete this repository?', 'heritagepress')); ?>')) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_delete_repository',
            repository_id: repositoryId,
            nonce: '<?php echo wp_create_nonce('hp_repository_nonce'); ?>'
          },
          success: function(response) {
            if (response.success) {
              $('#repository-' + repositoryId).fadeOut();
              alert('<?php echo esc_js(__('Repository deleted successfully.', 'heritagepress')); ?>');
              location.reload();
            } else {
              alert('<?php echo esc_js(__('Error:', 'heritagepress')); ?> ' + response.data);
            }
          },
          error: function() {
            alert('<?php echo esc_js(__('An error occurred while deleting the repository.', 'heritagepress')); ?>');
          }
        });
      }
      return false;
    };

    // View repository function
    window.viewRepository = function(repoID, gedcom) {
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_get_repository',
          repoID: repoID,
          gedcom: gedcom,
          nonce: '<?php echo wp_create_nonce('hp_repository_nonce'); ?>'
        },
        success: function(response) {
          if (response.success) {
            displayRepositoryDetails(response.data.repository);
            $('#repository-details-modal').show();
          } else {
            alert('<?php echo esc_js(__('Error loading repository details:', 'heritagepress')); ?> ' + response.data);
          }
        },
        error: function() {
          alert('<?php echo esc_js(__('An error occurred while loading repository details.', 'heritagepress')); ?>');
        }
      });
      return false;
    };

    // Display repository details in modal
    function displayRepositoryDetails(repository) {
      var html = '<table class="form-table">';
      html += '<tr><th><?php echo esc_js(__('Repository ID:', 'heritagepress')); ?></th><td>' + repository.repoID + '</td></tr>';
      html += '<tr><th><?php echo esc_js(__('Name:', 'heritagepress')); ?></th><td>' + repository.reponame + '</td></tr>';

      if (repository.address1 || repository.city || repository.state || repository.country) {
        html += '<tr><th><?php echo esc_js(__('Address:', 'heritagepress')); ?></th><td>';
        if (repository.address1) html += repository.address1 + '<br>';
        if (repository.address2) html += repository.address2 + '<br>';
        if (repository.city) html += repository.city;
        if (repository.state) html += (repository.city ? ', ' : '') + repository.state;
        if (repository.zip) html += ' ' + repository.zip;
        if (repository.country) html += '<br>' + repository.country;
        html += '</td></tr>';
      }

      if (repository.phone) {
        html += '<tr><th><?php echo esc_js(__('Phone:', 'heritagepress')); ?></th><td>' + repository.phone + '</td></tr>';
      }

      if (repository.email) {
        html += '<tr><th><?php echo esc_js(__('Email:', 'heritagepress')); ?></th><td><a href="mailto:' + repository.email + '">' + repository.email + '</a></td></tr>';
      }

      if (repository.www) {
        html += '<tr><th><?php echo esc_js(__('Website:', 'heritagepress')); ?></th><td><a href="' + repository.www + '" target="_blank">' + repository.www + '</a></td></tr>';
      }

      html += '<tr><th><?php echo esc_js(__('Last Modified:', 'heritagepress')); ?></th><td>' + repository.formatted_date + ' by ' + repository.changedby + '</td></tr>';
      html += '</table>';

      $('#repository-details-content').html(html);
    }

    // Close modal
    $('.hp-modal-close').click(function() {
      $('.hp-modal').hide();
    });

    $(window).click(function(event) {
      if (event.target.classList.contains('hp-modal')) {
        $('.hp-modal').hide();
      }
    });

  });
</script>

<style>
  .repository-search-section,
  .repository-results-section {
    margin-bottom: 20px;
  }

  .hp-admin-block {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
  }

  .hp-search-form .form-table th {
    width: 150px;
  }

  .column-actions {
    width: 120px;
  }

  .column-repoid {
    width: 100px;
  }

  .column-name {
    width: 200px;
  }

  .column-location {
    width: 150px;
  }

  .column-tree {
    width: 120px;
  }

  .column-modified {
    width: 150px;
  }

  .hp-no-results {
    text-align: center;
    padding: 40px 20px;
    color: #666;
  }

  /* Modal styles */
  .hp-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .hp-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    border-radius: 4px;
    width: 80%;
    max-width: 600px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .hp-modal-header {
    background: #f1f1f1;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    border-radius: 4px 4px 0 0;
    position: relative;
  }

  .hp-modal-header h3 {
    margin: 0;
    font-size: 18px;
  }

  .hp-modal-close {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .hp-modal-close:hover,
  .hp-modal-close:focus {
    color: black;
  }

  .hp-modal-body {
    padding: 20px;
  }

  .search-results {
    margin: 0;
    font-style: italic;
    color: #666;
  }
</style>
