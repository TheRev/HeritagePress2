<?php

/**
 * Album Media Management View for HeritagePress
 *
 * This file provides the media selection interface for albums.
 * It allows users to search for media, filter by type and tree, and add/remove media from albums.
 * It includes AJAX handling for searching and managing media, as well as responsive design for better usability.
 * * The current album's media is displayed, and users can add new media to the album or remove existing media.
 * * The interface is designed to be user-friendly and integrates seamlessly with the WordPress admin area.
 * * This file is part of the HeritagePress plugin, which enhances the WordPress experience for genealogy and heritage sites.
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get album ID from URL
$album_id = isset($_GET['albumID']) ? intval($_GET['albumID']) : 0;

if (!$album_id) {
  wp_redirect(admin_url('admin.php?page=heritagepress&section=albums'));
  exit;
}

// Get album data
global $wpdb;
$album = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM {$wpdb->prefix}hp_albums WHERE albumID = %d",
  $album_id
));

if (!$album) {
  wp_redirect(admin_url('admin.php?page=heritagepress&section=albums'));
  exit;
}

// Get media types for filter
$media_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_mediatypes ORDER BY display ASC");

// Get trees for filter
$trees = $wpdb->get_results("SELECT DISTINCT gedcom FROM {$wpdb->prefix}hp_trees WHERE gedcom != '' ORDER BY gedcom ASC");
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Manage Album Media', 'heritagepress'); ?>: <?php echo esc_html($album->albumname); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=edit&albumID=' . $album_id); ?>" class="page-title-action">
    <?php esc_html_e('Back to Album', 'heritagepress'); ?>
  </a>
  <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums'); ?>" class="page-title-action">
    <?php esc_html_e('All Albums', 'heritagepress'); ?>
  </a>
  <hr class="wp-header-end">

  <div class="hp-album-media-container">

    <!-- Media Search and Filter -->
    <div class="media-search-panel">
      <h3><?php esc_html_e('Search and Add Media', 'heritagepress'); ?></h3>

      <div class="search-controls">
        <div class="search-filters">
          <div class="filter-group">
            <label for="media-search"><?php esc_html_e('Search:', 'heritagepress'); ?></label>
            <input type="text" id="media-search" placeholder="<?php esc_attr_e('Search media by ID, description, path, notes...', 'heritagepress'); ?>" class="regular-text">
          </div>

          <div class="filter-group">
            <label for="mediatype-filter"><?php esc_html_e('Media Type:', 'heritagepress'); ?></label>
            <select id="mediatype-filter">
              <option value=""><?php esc_html_e('All Types', 'heritagepress'); ?></option>
              <?php foreach ($media_types as $type): ?>
                <option value="<?php echo esc_attr($type->mediatypeID); ?>">
                  <?php echo esc_html($type->display); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-group">
            <label for="tree-filter"><?php esc_html_e('Tree:', 'heritagepress'); ?></label>
            <select id="tree-filter">
              <option value=""><?php esc_html_e('All Trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree->gedcom); ?>">
                  <?php echo esc_html($tree->gedcom); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-group">
            <button type="button" id="search-media-btn" class="button button-primary">
              <?php esc_html_e('Search Media', 'heritagepress'); ?>
            </button>
            <button type="button" id="clear-search-btn" class="button">
              <?php esc_html_e('Clear', 'heritagepress'); ?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Results -->
    <div id="media-search-results" class="media-results-panel" style="display: none;">
      <div class="results-header">
        <h4><?php esc_html_e('Search Results', 'heritagepress'); ?></h4>
        <div id="results-summary" class="results-summary"></div>
      </div>

      <div id="media-results-table-container">
        <!-- Results will be loaded here via AJAX -->
      </div>

      <div id="media-pagination" class="pagination-container">
        <!-- Pagination will be loaded here via AJAX -->
      </div>
    </div>

    <!-- Current Album Media -->
    <div class="current-media-panel">
      <h3><?php esc_html_e('Media Currently in Album', 'heritagepress'); ?></h3>

      <div id="current-album-media">
        <div class="loading-placeholder">
          <p><?php esc_html_e('Loading album media...', 'heritagepress'); ?></p>
        </div>
      </div>
    </div>

  </div>

  <div id="album-messages"></div>
</div>

<style>
  .hp-album-media-container {
    margin-top: 20px;
  }

  .media-search-panel,
  .media-results-panel,
  .current-media-panel {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
  }

  .search-controls {
    margin-bottom: 15px;
  }

  .search-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    min-width: 150px;
  }

  .filter-group label {
    font-weight: 600;
    margin-bottom: 5px;
  }

  .filter-group input,
  .filter-group select {
    padding: 5px 8px;
  }

  .results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
  }

  .results-summary {
    color: #646970;
    font-style: italic;
  }

  .media-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s;
  }

  .media-item:hover {
    background-color: #f9f9f9;
  }

  .media-thumbnail {
    width: 80px;
    height: 80px;
    margin-right: 15px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f6f7f7;
    border: 1px solid #ddd;
  }

  .media-thumbnail img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
  }

  .media-thumbnail .no-thumb {
    color: #666;
    font-size: 12px;
    text-align: center;
  }

  .media-details {
    flex-grow: 1;
  }

  .media-title {
    font-weight: 600;
    margin-bottom: 5px;
  }

  .media-title a {
    text-decoration: none;
    color: #0073aa;
  }

  .media-title a:hover {
    text-decoration: underline;
  }

  .media-meta {
    color: #646970;
    font-size: 13px;
    line-height: 1.4;
  }

  .media-actions {
    flex-shrink: 0;
    text-align: right;
  }

  .media-actions .button {
    margin-left: 5px;
  }

  .pagination-container {
    text-align: center;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
  }

  .pagination-links .button {
    margin: 0 2px;
  }

  .loading-placeholder {
    text-align: center;
    padding: 40px;
    color: #646970;
  }

  .loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 10px;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }

  #album-messages {
    margin-top: 20px;
  }

  .notice {
    margin: 5px 0 15px;
    padding: 1px 12px;
  }

  .status-in-album {
    color: #00a32a;
    font-weight: 600;
  }

  .status-not-in-album {
    color: #0073aa;
  }

  @media (max-width: 782px) {
    .search-filters {
      flex-direction: column;
      align-items: stretch;
    }

    .filter-group {
      min-width: auto;
    }

    .media-item {
      flex-direction: column;
      align-items: flex-start;
    }

    .media-thumbnail {
      margin-bottom: 10px;
      margin-right: 0;
    }

    .media-actions {
      margin-top: 10px;
      text-align: left;
    }
  }
</style>

<script>
  jQuery(document).ready(function($) {
    var albumID = <?php echo $album_id; ?>;
    var currentOffset = 0;
    var perPage = 25;
    var isLoading = false;

    // Initialize
    loadCurrentAlbumMedia();

    // Search button click
    $('#search-media-btn').click(function() {
      searchMedia();
    });

    // Clear search
    $('#clear-search-btn').click(function() {
      $('#media-search').val('');
      $('#mediatype-filter').val('');
      $('#tree-filter').val('');
      $('#media-search-results').hide();
    });

    // Enter key in search field
    $('#media-search').keypress(function(e) {
      if (e.which === 13) {
        searchMedia();
      }
    });

    // Search media function
    function searchMedia(offset = 0) {
      if (isLoading) return;

      isLoading = true;
      currentOffset = offset;

      var searchData = {
        action: 'hp_search_media_for_album',
        nonce: '<?php echo wp_create_nonce('hp_search_media'); ?>',
        albumID: albumID,
        searchstring: $('#media-search').val(),
        mediatypeID: $('#mediatype-filter').val(),
        tree: $('#tree-filter').val(),
        offset: offset,
        perpage: perPage
      };

      // Show loading
      $('#media-results-table-container').html('<div class="loading-placeholder"><div class="loading-spinner"></div><?php echo esc_js(__('Searching media...', 'heritagepress')); ?></div>');
      $('#media-search-results').show();

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: searchData,
        success: function(response) {
          isLoading = false;

          if (response.success) {
            displaySearchResults(response.data);
          } else {
            showMessage(response.data, 'error');
            $('#media-search-results').hide();
          }
        },
        error: function() {
          isLoading = false;
          showMessage('<?php echo esc_js(__('Error searching media.', 'heritagepress')); ?>', 'error');
          $('#media-search-results').hide();
        }
      });
    }

    // Display search results
    function displaySearchResults(data) {
      var results = data.results;
      var total = data.total;
      var offset = data.offset;
      var perPage = data.per_page;

      // Update summary
      var startItem = offset + 1;
      var endItem = Math.min(offset + perPage, total);
      $('#results-summary').text('<?php echo esc_js(__('Showing', 'heritagepress')); ?> ' + startItem + ' <?php echo esc_js(__('to', 'heritagepress')); ?> ' + endItem + ' <?php echo esc_js(__('of', 'heritagepress')); ?> ' + total + ' <?php echo esc_js(__('items', 'heritagepress')); ?>');

      // Build results HTML
      var html = '';
      if (results.length > 0) {
        results.forEach(function(media) {
          html += buildMediaItemHTML(media);
        });
      } else {
        html = '<div class="notice notice-info inline"><p><?php echo esc_js(__('No media found matching your search criteria.', 'heritagepress')); ?></p></div>';
      }

      $('#media-results-table-container').html(html);

      // Build pagination
      buildPagination(total, offset, perPage);
    }

    // Build media item HTML
    function buildMediaItemHTML(media) {
      var thumbnailHTML = '';
      if (media.thumbpath) {
        thumbnailHTML = '<img src="' + media.thumbpath + '" alt="' + escapeHtml(media.description) + '">';
      } else {
        thumbnailHTML = '<div class="no-thumb"><?php echo esc_js(__('No thumbnail', 'heritagepress')); ?></div>';
      }

      var truncatedNotes = media.notes ? (media.notes.length > 90 ? media.notes.substring(0, 90) + '...' : media.notes) : '';

      var actionHTML = '';
      if (media.already_added) {
        actionHTML = '<span class="status-in-album"><?php echo esc_js(__('In Album', 'heritagepress')); ?></span>';
        actionHTML += '<button type="button" class="button button-small remove-media" data-media-id="' + media.mediaID + '"><?php echo esc_js(__('Remove', 'heritagepress')); ?></button>';
      } else {
        actionHTML = '<button type="button" class="button button-primary button-small add-media" data-media-id="' + media.mediaID + '"><?php echo esc_js(__('Add to Album', 'heritagepress')); ?></button>';
      }

      return '<div class="media-item" data-media-id="' + media.mediaID + '">' +
        '<div class="media-thumbnail">' + thumbnailHTML + '</div>' +
        '<div class="media-details">' +
        '<div class="media-title"><a href="#" onclick="return false;">' + escapeHtml(media.description) + '</a></div>' +
        '<div class="media-meta">' +
        (truncatedNotes ? '<div>' + escapeHtml(truncatedNotes) + '</div>' : '') +
        (media.datetaken ? '<div><?php echo esc_js(__('Date:', 'heritagepress')); ?> ' + escapeHtml(media.datetaken) + '</div>' : '') +
        '<div><?php echo esc_js(__('ID:', 'heritagepress')); ?> ' + media.mediaID + '</div>' +
        '</div>' +
        '</div>' +
        '<div class="media-actions">' + actionHTML + '</div>' +
        '</div>';
    }

    // Build pagination
    function buildPagination(total, offset, perPage) {
      var totalPages = Math.ceil(total / perPage);
      var currentPage = Math.floor(offset / perPage) + 1;

      if (totalPages <= 1) {
        $('#media-pagination').html('');
        return;
      }

      var html = '<div class="pagination-links">';

      // Previous page
      if (currentPage > 1) {
        var prevOffset = Math.max(0, offset - perPage);
        html += '<button type="button" class="button pagination-btn" data-offset="' + prevOffset + '">&laquo; <?php echo esc_js(__('Previous', 'heritagepress')); ?></button>';
      }

      // Page numbers
      var startPage = Math.max(1, currentPage - 2);
      var endPage = Math.min(totalPages, currentPage + 2);

      if (startPage > 1) {
        html += '<button type="button" class="button pagination-btn" data-offset="0">1</button>';
        if (startPage > 2) html += '<span>...</span>';
      }

      for (var i = startPage; i <= endPage; i++) {
        var pageOffset = (i - 1) * perPage;
        if (i === currentPage) {
          html += '<span class="button button-primary">' + i + '</span>';
        } else {
          html += '<button type="button" class="button pagination-btn" data-offset="' + pageOffset + '">' + i + '</button>';
        }
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span>...</span>';
        var lastOffset = (totalPages - 1) * perPage;
        html += '<button type="button" class="button pagination-btn" data-offset="' + lastOffset + '">' + totalPages + '</button>';
      }

      // Next page
      if (currentPage < totalPages) {
        var nextOffset = offset + perPage;
        html += '<button type="button" class="button pagination-btn" data-offset="' + nextOffset + '"><?php echo esc_js(__('Next', 'heritagepress')); ?> &raquo;</button>';
      }

      html += '</div>';
      $('#media-pagination').html(html);
    }

    // Handle pagination clicks
    $(document).on('click', '.pagination-btn', function() {
      var offset = parseInt($(this).data('offset'));
      searchMedia(offset);
    });

    // Handle add media to album
    $(document).on('click', '.add-media', function() {
      var mediaID = $(this).data('media-id');
      var $item = $(this).closest('.media-item');
      var $button = $(this);

      $button.prop('disabled', true).text('<?php echo esc_js(__('Adding...', 'heritagepress')); ?>');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_add_media_to_album',
          nonce: '<?php echo wp_create_nonce('hp_manage_album_media'); ?>',
          albumID: albumID,
          mediaID: mediaID
        },
        success: function(response) {
          if (response.success) {
            // Update button state
            $button.removeClass('button-primary').addClass('status-in-album').text('<?php echo esc_js(__('In Album', 'heritagepress')); ?>').prop('disabled', false);

            // Add remove button
            var removeBtn = '<button type="button" class="button button-small remove-media" data-media-id="' + mediaID + '"><?php echo esc_js(__('Remove', 'heritagepress')); ?></button>';
            $button.after(removeBtn);

            showMessage(response.data.message, 'success');
            loadCurrentAlbumMedia(); // Refresh current album media
          } else {
            $button.prop('disabled', false).text('<?php echo esc_js(__('Add to Album', 'heritagepress')); ?>');
            showMessage(response.data, 'error');
          }
        },
        error: function() {
          $button.prop('disabled', false).text('<?php echo esc_js(__('Add to Album', 'heritagepress')); ?>');
          showMessage('<?php echo esc_js(__('Error adding media to album.', 'heritagepress')); ?>', 'error');
        }
      });
    });

    // Handle remove media from album
    $(document).on('click', '.remove-media', function() {
      var mediaID = $(this).data('media-id');
      var $item = $(this).closest('.media-item');
      var $button = $(this);

      $button.prop('disabled', true).text('<?php echo esc_js(__('Removing...', 'heritagepress')); ?>');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_remove_media_from_album',
          nonce: '<?php echo wp_create_nonce('hp_manage_album_media'); ?>',
          albumID: albumID,
          mediaID: mediaID
        },
        success: function(response) {
          if (response.success) {
            // Update button state - check if we're in search results or current media
            var $statusSpan = $item.find('.status-in-album');
            if ($statusSpan.length) {
              // In search results - convert back to add button
              $statusSpan.remove();
              $button.removeClass('remove-media').addClass('add-media button-primary').text('<?php echo esc_js(__('Add to Album', 'heritagepress')); ?>').prop('disabled', false);
            } else {
              // In current media list - remove the entire item
              $item.fadeOut(300, function() {
                $(this).remove();
              });
            }

            showMessage(response.data.message, 'success');
            loadCurrentAlbumMedia(); // Refresh current album media
          } else {
            $button.prop('disabled', false).text('<?php echo esc_js(__('Remove', 'heritagepress')); ?>');
            showMessage(response.data, 'error');
          }
        },
        error: function() {
          $button.prop('disabled', false).text('<?php echo esc_js(__('Remove', 'heritagepress')); ?>');
          showMessage('<?php echo esc_js(__('Error removing media from album.', 'heritagepress')); ?>', 'error');
        }
      });
    });

    // Load current album media
    function loadCurrentAlbumMedia() {
      // This would be implemented to show current media in the album
      // For now, show placeholder
      $('#current-album-media').html('<div class="loading-placeholder"><p><?php echo esc_js(__('Album media functionality will be completed in the full implementation.', 'heritagepress')); ?></p></div>');
    }

    // Utility functions
    function escapeHtml(text) {
      var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text ? text.replace(/[&<>"']/g, function(m) {
        return map[m];
      }) : '';
    }

    function showMessage(message, type) {
      var className = type === 'success' ? 'notice-success' : 'notice-error';
      $('#album-messages').html('<div class="notice ' + className + '"><p>' + message + '</p></div>');

      setTimeout(function() {
        $('#album-messages .notice').fadeOut();
      }, 5000);
    }
  });
</script>
