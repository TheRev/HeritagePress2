<?php

/**
 * Album Management Main View for HeritagePress
 *
 * This file provides the album management interface for the WordPress admin.
 * It allows users to view, search, and manage albums, including pagination and media count.
 * It also includes AJAX handling for album deletion.
 * This view is part of the HeritagePress plugin.
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../classes/class-hp-album-view.php';

// Initialize view helper and get albums
$view = new HP_Album_View();
$result = $view->get_albums();

// Ensure all variables are set to prevent undefined variable errors
if (!is_array($result)) {
  $result = array(
    'total_rows' => 0,
    'albums' => array(),
    'offset' => 0,
    'per_page' => 25,
    'search_string' => ''
  );
}

extract($result); // Get total_rows, albums, offset, per_page, search_string

// Ensure variables are set with defaults
$total_rows = isset($total_rows) ? (int)$total_rows : 0;
$albums = isset($albums) ? $albums : array();
$offset = isset($offset) ? (int)$offset : 0;
$per_page = isset($per_page) ? (int)$per_page : 25;
$search_string = isset($search_string) ? (string)$search_string : '';

// Calculate pagination
$total_pages = ceil($total_rows / $per_page);
$current_page = floor($offset / $per_page) + 1;
$start_item = $offset + 1;
$end_item = min($offset + $per_page, $total_rows);
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Albums', 'heritagepress'); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=add'); ?>" class="page-title-action">
    <?php esc_html_e('Add New Album', 'heritagepress'); ?>
  </a>
  <hr class="wp-header-end">

  <!-- Search Form -->
  <div class="album-search-container">
    <form method="get" action="">
      <input type="hidden" name="page" value="heritagepress">
      <input type="hidden" name="section" value="albums">

      <p class="search-box">
        <label class="screen-reader-text" for="album-search-input"><?php esc_html_e('Search Albums', 'heritagepress'); ?>:</label>
        <input type="search" id="album-search-input" name="search" value="<?php echo esc_attr($search_string); ?>" placeholder="<?php esc_attr_e('Search albums...', 'heritagepress'); ?>">
        <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Albums', 'heritagepress'); ?>">
        <?php if (!empty($search_string)): ?>
          <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums'); ?>" class="button">
            <?php esc_html_e('Clear Search', 'heritagepress'); ?>
          </a>
        <?php endif; ?>
      </p>
    </form>
  </div>

  <!-- Results Summary -->
  <?php if ($total_rows > 0): ?>
    <div class="tablenav top">
      <div class="alignleft actions">
        <span class="displaying-num">
          <?php printf(
            esc_html__('Showing items %d to %d of %d', 'heritagepress'),
            $start_item,
            $end_item,
            $total_rows
          ); ?>
        </span>
      </div>

      <?php if ($total_pages > 1): ?>
        <div class="tablenav-pages">
          <span class="pagination-links">
            <?php
            // Previous page link
            if ($current_page > 1) {
              $prev_offset = max(0, $offset - $per_page);
              $prev_url = add_query_arg(array('offset' => $prev_offset, 'search' => $search_string), admin_url('admin.php?page=heritagepress&section=albums'));
              echo '<a class="button" href="' . esc_url($prev_url) . '">&laquo; ' . esc_html__('Previous', 'heritagepress') . '</a> ';
            }

            // Page numbers
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            if ($start_page > 1) {
              $first_url = add_query_arg(array('offset' => 0, 'search' => $search_string), admin_url('admin.php?page=heritagepress&section=albums'));
              echo '<a class="button" href="' . esc_url($first_url) . '">1</a> ';
              if ($start_page > 2) echo '... ';
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
              $page_offset = ($i - 1) * $per_page;
              $page_url = add_query_arg(array('offset' => $page_offset, 'search' => $search_string), admin_url('admin.php?page=heritagepress&section=albums'));

              if ($i == $current_page) {
                echo '<span class="button button-primary">' . $i . '</span> ';
              } else {
                echo '<a class="button" href="' . esc_url($page_url) . '">' . $i . '</a> ';
              }
            }

            if ($end_page < $total_pages) {
              if ($end_page < $total_pages - 1) echo '... ';
              $last_offset = ($total_pages - 1) * $per_page;
              $last_url = add_query_arg(array('offset' => $last_offset, 'search' => $search_string), admin_url('admin.php?page=heritagepress&section=albums'));
              echo '<a class="button" href="' . esc_url($last_url) . '">' . $total_pages . '</a> ';
            }

            // Next page link
            if ($current_page < $total_pages) {
              $next_offset = $offset + $per_page;
              $next_url = add_query_arg(array('offset' => $next_offset, 'search' => $search_string), admin_url('admin.php?page=heritagepress&section=albums'));
              echo '<a class="button" href="' . esc_url($next_url) . '">' . esc_html__('Next', 'heritagepress') . ' &raquo;</a>';
            }
            ?>
          </span>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Albums Table -->
  <div class="hp-albums-container">
    <?php if ($albums): ?>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th scope="col" class="manage-column"><?php esc_html_e('Album Name', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php esc_html_e('Description', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php esc_html_e('Keywords', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php esc_html_e('Status', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php esc_html_e('Media Count', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php esc_html_e('Linked To', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php esc_html_e('Actions', 'heritagepress'); ?></th>
          </tr>
        </thead>
        <tbody>          <?php foreach ($albums as $album): ?>
            <?php
            // Ensure album properties are never null to prevent deprecated warnings
            $albumname = isset($album->albumname) && $album->albumname !== null ? $album->albumname : '';
            $description = isset($album->description) && $album->description !== null ? $album->description : '';
            $keywords = isset($album->keywords) && $album->keywords !== null ? $album->keywords : '';
            $albumID = isset($album->albumID) ? (int)$album->albumID : 0;

            // Get media count for this album
            $media_count = $wpdb->get_var($wpdb->prepare(
              "SELECT COUNT(*) FROM {$wpdb->prefix}hp_albumlinks WHERE albumID = %d",
              $albumID
            ));

            // Get linked entities for this album
            $linked_entities = $wpdb->get_results($wpdb->prepare(
              "SELECT e.entityID, e.entityType, p.personID, p.lastname, p.firstname, p.suffix, p.prefix,
                      f.familyID, s.sourceID, s.title as stitle, r.repoID, r.reponame
               FROM {$wpdb->prefix}hp_album2entities e
               LEFT JOIN {$wpdb->prefix}hp_people p ON e.entityID = p.personID AND e.entityType = 'person'
               LEFT JOIN {$wpdb->prefix}hp_families f ON e.entityID = f.familyID AND e.entityType = 'family'
               LEFT JOIN {$wpdb->prefix}hp_sources s ON e.entityID = s.sourceID AND e.entityType = 'source'
               LEFT JOIN {$wpdb->prefix}hp_repositories r ON e.entityID = r.repoID AND e.entityType = 'repository'
               WHERE e.albumID = %d
               LIMIT 10",
              $album->albumID
            ));
            ?>
            <tr>
              <td>
                <strong>
                  <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=edit&albumID=' . $album->albumID); ?>">
                    <?php echo esc_html($album->albumname); ?>
                  </a>
                </strong>
              </td>
              <td>
                <?php
                $description = $album->description;
                if (strlen($description) > 100) {
                  $description = substr($description, 0, 100) . '...';
                }
                echo esc_html($description);
                ?>
              </td>
              <td><?php echo esc_html($album->keywords); ?></td>
              <td>
                <?php if ($album->active): ?>
                  <span class="status-active"><?php esc_html_e('Active', 'heritagepress'); ?></span>
                  <?php if ($album->alwayson): ?>
                    <span class="status-always-on"> - <?php esc_html_e('Always On', 'heritagepress'); ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="status-inactive"><?php esc_html_e('Inactive', 'heritagepress'); ?></span>
                <?php endif; ?>
              </td>
              <td><?php echo esc_html($media_count); ?></td>
              <td>
                <?php if ($linked_entities): ?>
                  <ul class="linked-entities-list">
                    <?php foreach ($linked_entities as $entity): ?>
                      <?php if ($entity->personID): ?>
                        <li>
                          <a href="<?php echo admin_url('admin.php?page=heritagepress&section=people&action=edit&id=' . $entity->personID); ?>">
                            <?php echo esc_html($entity->firstname . ' ' . $entity->lastname); ?> (<?php echo esc_html($entity->personID); ?>)
                          </a>
                        </li>
                      <?php elseif ($entity->familyID): ?>
                        <li>
                          <a href="<?php echo admin_url('admin.php?page=heritagepress&section=families&action=edit&id=' . $entity->familyID); ?>">
                            <?php esc_html_e('Family', 'heritagepress'); ?>: <?php echo esc_html($entity->familyID); ?>
                          </a>
                        </li>
                      <?php elseif ($entity->sourceID): ?>
                        <li>
                          <a href="<?php echo admin_url('admin.php?page=heritagepress&section=sources&action=edit&id=' . $entity->sourceID); ?>">
                            <?php esc_html_e('Source', 'heritagepress'); ?>: <?php echo esc_html($entity->stitle ? $entity->stitle : $entity->sourceID); ?>
                          </a>
                        </li>
                      <?php elseif ($entity->repoID): ?>
                        <li>
                          <a href="<?php echo admin_url('admin.php?page=heritagepress&section=repositories&action=edit&id=' . $entity->repoID); ?>">
                            <?php esc_html_e('Repository', 'heritagepress'); ?>: <?php echo esc_html($entity->reponame ? $entity->reponame : $entity->repoID); ?>
                          </a>
                        </li>
                      <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (count($linked_entities) >= 10): ?>
                      <li>...</li>
                    <?php endif; ?>
                  </ul>
                <?php else: ?>
                  <em><?php esc_html_e('No linked entities', 'heritagepress'); ?></em>
                <?php endif; ?>
              </td>
              <td>
                <div class="row-actions">
                  <span class="edit">
                    <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=edit&albumID=' . $album->albumID); ?>"
                      aria-label="<?php esc_attr_e('Edit this album', 'heritagepress'); ?>">
                      <?php esc_html_e('Edit', 'heritagepress'); ?>
                    </a> |
                  </span>
                  <span class="manage">
                    <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=manage&albumID=' . $album->albumID); ?>"
                      aria-label="<?php esc_attr_e('Manage media in this album', 'heritagepress'); ?>">
                      <?php esc_html_e('Manage Media', 'heritagepress'); ?>
                    </a> |
                  </span>
                  <span class="test">
                    <a href="<?php echo home_url('?hp_view=album&album_id=' . $album->albumID); ?>"
                      target="_blank" aria-label="<?php esc_attr_e('View this album in the frontend', 'heritagepress'); ?>">
                      <?php esc_html_e('Test', 'heritagepress'); ?>
                    </a> |
                  </span>
                  <span class="trash">
                    <a href="#"
                      class="delete-album"
                      data-album-id="<?php echo esc_attr($album->albumID); ?>"
                      data-album-name="<?php echo esc_attr($album->albumname); ?>"
                      aria-label="<?php esc_attr_e('Delete this album', 'heritagepress'); ?>">
                      <?php esc_html_e('Delete', 'heritagepress'); ?>
                    </a>
                  </span>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Bottom pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
          <div class="tablenav-pages">
            <span class="pagination-links">
              <?php
              // Same pagination as above
              if ($current_page > 1) {
                $prev_offset = max(0, $offset - $per_page);
                $prev_url = add_query_arg(array('offset' => $prev_offset, 'search' => $search_string), admin_url('admin.php?page=heritagepress&section=albums'));
                echo '<a class="button" href="' . esc_url($prev_url) . '">&laquo; ' . esc_html__('Previous', 'heritagepress') . '</a> ';
              }

              for ($i = $start_page; $i <= $end_page; $i++) {
                $page_offset = ($i - 1) * $per_page;
                $page_url = add_query_arg(array('offset' => $page_offset, 'search' => $search_string), admin_url('admin.php?page=heritagepress&section=albums'));

                if ($i == $current_page) {
                  echo '<span class="button button-primary">' . $i . '</span> ';
                } else {
                  echo '<a class="button" href="' . esc_url($page_url) . '">' . $i . '</a> ';
                }
              }

              if ($current_page < $total_pages) {
                $next_offset = $offset + $per_page;
                $next_url = add_query_arg(array('offset' => $next_offset, 'search' => $search_string), admin_url('admin.php?page=heritagepress&section=albums'));
                echo '<a class="button" href="' . esc_url($next_url) . '">' . esc_html__('Next', 'heritagepress') . ' &raquo;</a>';
              }
              ?>
            </span>
          </div>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="notice notice-info">
        <p>
          <?php if (!empty($search_string)): ?>
            <?php esc_html_e('No albums found matching your search criteria.', 'heritagepress'); ?>
          <?php else: ?>
            <?php esc_html_e('No albums found. Create your first album to get started.', 'heritagepress'); ?>
          <?php endif; ?>
        </p>
      </div>
    <?php endif; ?>
  </div>

  <div id="album-messages"></div>
</div>

<style>
  .hp-albums-container {
    margin-top: 20px;
  }

  .album-search-container {
    margin: 20px 0;
  }

  .status-active {
    color: #00a32a;
    font-weight: 600;
  }

  .status-inactive {
    color: #d63638;
    font-weight: 600;
  }

  .status-always-on {
    color: #0073aa;
    font-style: italic;
  }

  .button-small {
    padding: 2px 8px;
    font-size: 11px;
    line-height: 1.5;
    height: auto;
    margin-right: 5px;
  }

  #album-messages {
    margin-top: 20px;
  }

  .notice {
    margin: 5px 0 15px;
    padding: 1px 12px;
  }

  .tablenav {
    clear: both;
    margin: 12px 0;
  }

  .tablenav .alignleft {
    float: left;
    margin: 1px 8px 0 0;
  }

  .tablenav-pages {
    float: right;
    margin: 0;
  }

  .pagination-links .button {
    margin-right: 5px;
  }

  .displaying-num {
    color: #646970;
    font-size: 13px;
    font-style: italic;
  }

  .linked-entities-list {
    margin: 0;
    padding: 0;
    list-style-type: none;
  }

  .linked-entities-list li {
    margin: 0;
    padding: 0;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Delete album
    $('.delete-album').click(function() {
      var albumId = $(this).data('album-id');
      var albumName = $(this).data('album-name');
      var $row = $(this).closest('tr');

      if (!confirm('<?php echo esc_js(__('Are you sure you want to delete the album', 'heritagepress')); ?> "' + albumName + '"? <?php echo esc_js(__('This will also remove all media associations with this album.', 'heritagepress')); ?>')) {
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_delete_album',
          albumID: albumId,
          nonce: '<?php echo wp_create_nonce('hp_delete_album'); ?>'
        },
        success: function(response) {
          if (response.success) {
            $row.fadeOut(300, function() {
              $(this).remove();
            });
            showMessage(response.data.message, 'success');

            // Update counters and refresh if no items left
            setTimeout(function() {
              location.reload();
            }, 1000);
          } else {
            showMessage(response.data, 'error');
          }
        },
        error: function() {
          showMessage('<?php echo esc_js(__('Error deleting album.', 'heritagepress')); ?>', 'error');
        }
      });
    });

    function showMessage(message, type) {
      var className = type === 'success' ? 'notice-success' : 'notice-error';
      $('#album-messages').html('<div class="notice ' + className + '"><p>' + message + '</p></div>');

      setTimeout(function() {
        $('#album-messages .notice').fadeOut();
      }, 5000);
    }
  });
</script>
