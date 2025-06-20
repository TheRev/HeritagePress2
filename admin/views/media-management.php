<?php

/**
 * Media Management View for HeritagePress
 *
 * This file provides the media management interface for the WordPress admin.
 * Based on admin_addmedia.php, admin_editmedia.php, admin_media.php functionality
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get available media types and trees
global $wpdb;
$media_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_mediatypes ORDER BY ordernum ASC, display ASC");
$trees = $wpdb->get_results("SELECT gedcom, treename FROM {$wpdb->prefix}hp_trees ORDER BY treename ASC");

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';
$media_id = isset($_GET['media_id']) ? intval($_GET['media_id']) : 0;

// Get media item for edit tab
$media_item = null;
if ($current_tab === 'edit' && $media_id) {
  $media_item = $wpdb->get_row($wpdb->prepare(
    "SELECT m.*, mt.display as media_type_name
     FROM {$wpdb->prefix}hp_media m
     LEFT JOIN {$wpdb->prefix}hp_mediatypes mt ON m.mediatypeID = mt.mediatypeID
     WHERE m.mediaID = %d",
    $media_id
  ));
}
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Media Management', 'heritagepress'); ?></h1>

  <nav class="nav-tab-wrapper">
    <a href="<?php echo admin_url('admin.php?page=heritagepress-media&tab=browse'); ?>"
      class="nav-tab <?php echo ($current_tab === 'browse') ? 'nav-tab-active' : ''; ?>">
      <?php esc_html_e('Browse Media', 'heritagepress'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=heritagepress-media&tab=add'); ?>"
      class="nav-tab <?php echo ($current_tab === 'add') ? 'nav-tab-active' : ''; ?>">
      <?php esc_html_e('Add Media', 'heritagepress'); ?>
    </a>
    <?php if ($current_tab === 'edit' && $media_item): ?>
      <a href="<?php echo admin_url('admin.php?page=heritagepress-media&tab=edit&media_id=' . $media_id); ?>"
        class="nav-tab nav-tab-active">
        <?php esc_html_e('Edit Media', 'heritagepress'); ?>
      </a>
    <?php endif; ?>
  </nav>

  <hr class="wp-header-end">

  <!-- Success/Error Messages -->
  <?php if (isset($_GET['added']) && $_GET['added']): ?>
    <div class="notice notice-success">
      <p><?php esc_html_e('Media item added successfully!', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['updated']) && $_GET['updated']): ?>
    <div class="notice notice-success">
      <p><?php esc_html_e('Media item updated successfully!', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>

  <!-- Tab Content -->
  <?php if ($current_tab === 'browse'): ?>
    <?php include 'media/browse-media.php'; ?>
  <?php elseif ($current_tab === 'add'): ?>
    <?php include 'media/add-media.php'; ?>
  <?php elseif ($current_tab === 'edit'): ?>
    <?php include 'media/edit-media.php'; ?>
  <?php endif; ?>
</div>

<style>
  .media-form {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-top: 20px;
  }

  .media-upload-area {
    border: 2px dashed #ddd;
    padding: 40px;
    text-align: center;
    margin: 20px 0;
    background: #fafafa;
    transition: all 0.3s ease;
  }

  .media-upload-area:hover {
    border-color: #0073aa;
    background: #f0f8ff;
  }

  .media-upload-area.dragover {
    border-color: #0073aa;
    background: #e8f4fd;
  }

  .media-preview {
    max-width: 200px;
    max-height: 200px;
    border: 1px solid #ddd;
    padding: 5px;
    margin: 10px 0;
  }

  .media-metadata {
    background: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    margin: 15px 0;
  }

  .media-links-section {
    background: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 15px 0;
  }

  .media-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 1px solid #ddd;
    margin: 10px 0;
    background: #fff;
  }

  .media-item-thumbnail {
    width: 80px;
    height: 80px;
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #ddd;
    background: #f9f9f9;
  }

  .media-item-thumbnail img {
    max-width: 100%;
    max-height: 100%;
  }

  .media-item-info {
    flex: 1;
  }

  .media-item-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 5px;
  }

  .media-item-meta {
    color: #666;
    font-size: 12px;
  }

  .media-item-actions {
    display: flex;
    gap: 10px;
  }

  .media-filters {
    background: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    margin: 15px 0;
  }

  .media-filters .form-table {
    margin: 0;
  }

  .media-filters .form-table td {
    padding: 5px 10px 5px 0;
  }

  .media-pagination {
    text-align: center;
    margin: 20px 0;
  }

  .coordinates-section {
    background: #f0f8ff;
    border: 1px solid #0073aa;
    padding: 15px;
    margin: 15px 0;
  }

  .required {
    color: #d63638;
  }

  .help-text {
    font-style: italic;
    color: #666;
    font-size: 12px;
  }

  @media (max-width: 768px) {
    .media-item {
      flex-direction: column;
      align-items: flex-start;
    }

    .media-item-thumbnail {
      margin-right: 0;
      margin-bottom: 10px;
    }

    .media-filters .form-table,
    .media-filters .form-table tbody,
    .media-filters .form-table tr,
    .media-filters .form-table td {
      display: block;
      width: 100%;
    }

    .media-filters .form-table td {
      padding: 5px 0;
    }
  }
</style>

<script>
  jQuery(document).ready(function($) {

    // Media upload handling
    function initMediaUpload() {
      var $uploadArea = $('.media-upload-area');
      var $fileInput = $('#media_file');

      // Click to upload
      $uploadArea.on('click', function(e) {
        if (!$(e.target).is('input')) {
          $fileInput.click();
        }
      });

      // Drag and drop
      $uploadArea.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
      });

      $uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
      });

      $uploadArea.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');

        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
          $fileInput[0].files = files;
          handleFileSelect(files[0]);
        }
      });

      // File input change
      $fileInput.on('change', function(e) {
        if (this.files.length > 0) {
          handleFileSelect(this.files[0]);
        }
      });
    }

    function handleFileSelect(file) {
      var $preview = $('#media-preview');
      var $metadata = $('#media-metadata');

      // Show file info
      $('#selected-filename').text(file.name);
      $('#selected-filesize').text(formatFileSize(file.size));
      $('#selected-filetype').text(file.type);

      // Show preview for images
      if (file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(e) {
          $preview.html('<img src="' + e.target.result + '" class="media-preview" alt="Preview">');
        };
        reader.readAsDataURL(file);
      } else {
        $preview.html('<div class="media-preview-placeholder"><span class="dashicons dashicons-media-default"></span><br>' + file.name + '</div>');
      }

      $metadata.show();
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      var k = 1024;
      var sizes = ['Bytes', 'KB', 'MB', 'GB'];
      var i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Map integration
    function initMapIntegration() {
      $('#showmap').on('change', function() {
        if ($(this).is(':checked')) {
          $('.coordinates-section').show();
        } else {
          $('.coordinates-section').hide();
        }
      });

      // Auto-set zoom when coordinates are entered
      $('#latitude, #longitude').on('input', function() {
        var lat = $('#latitude').val();
        var lng = $('#longitude').val();
        var zoom = $('#zoom').val();

        if (lat && lng && !zoom) {
          $('#zoom').val(13);
        }
      });
    }

    // Media filtering and search
    function initMediaFilters() {
      var $searchForm = $('#media-search-form');
      var $resultsContainer = $('#media-results');
      var currentPage = 1;

      // Search form submission
      $searchForm.on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        searchMedia();
      });

      // Filter changes
      $('#media_type_filter, #tree_filter').on('change', function() {
        currentPage = 1;
        searchMedia();
      });

      // Pagination
      $(document).on('click', '.media-pagination a', function(e) {
        e.preventDefault();
        currentPage = parseInt($(this).data('page'));
        searchMedia();
      });

      function searchMedia() {
        var data = {
          action: 'hp_get_media_list',
          nonce: heritagepress_media.nonce,
          page: currentPage,
          per_page: 20,
          search: $('#media_search').val(),
          media_type: $('#media_type_filter').val(),
          tree: $('#tree_filter').val()
        };

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: data,
          beforeSend: function() {
            $resultsContainer.html('<div class="loading"><p>Loading...</p></div>');
          },
          success: function(response) {
            if (response.success) {
              displayMediaResults(response.data);
            } else {
              $resultsContainer.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
            }
          },
          error: function() {
            $resultsContainer.html('<div class="notice notice-error"><p>Error loading media items.</p></div>');
          }
        });
      }

      function displayMediaResults(data) {
        var html = '';

        if (data.items.length === 0) {
          html = '<div class="notice notice-info"><p>No media items found.</p></div>';
        } else {
          data.items.forEach(function(item) {
            html += buildMediaItemHtml(item);
          });

          // Add pagination
          if (data.total_pages > 1) {
            html += buildPaginationHtml(data);
          }
        }

        $resultsContainer.html(html);
      }

      function buildMediaItemHtml(item) {
        var thumbnail = '';
        if (item.thumbpath) {
          thumbnail = '<img src="' + heritagepress_media.upload_url + '/' + item.thumbpath + '" alt="Thumbnail">';
        } else if (item.form && ['JPG', 'JPEG', 'PNG', 'GIF'].includes(item.form.toUpperCase())) {
          thumbnail = '<img src="' + heritagepress_media.upload_url + '/' + item.path + '" alt="Image">';
        } else {
          thumbnail = '<span class="dashicons dashicons-media-default"></span>';
        }

        return '<div class="media-item">' +
          '<div class="media-item-thumbnail">' + thumbnail + '</div>' +
          '<div class="media-item-info">' +
          '<div class="media-item-title">' + (item.description || 'Untitled Media') + '</div>' +
          '<div class="media-item-meta">' +
          'Type: ' + (item.media_type_name || item.mediatypeID) + ' | ' +
          'Tree: ' + item.gedcom + ' | ' +
          'Added: ' + item.changedate +
          '</div>' +
          '</div>' +
          '<div class="media-item-actions">' +
          '<a href="admin.php?page=heritagepress-media&tab=edit&media_id=' + item.mediaID + '" class="button">Edit</a>' +
          '<button type="button" class="button button-link-delete delete-media" data-media-id="' + item.mediaID + '">Delete</button>' +
          '</div>' +
          '</div>';
      }

      function buildPaginationHtml(data) {
        var html = '<div class="media-pagination">';

        for (var i = 1; i <= data.total_pages; i++) {
          var activeClass = i === data.page ? ' current' : '';
          html += '<a href="#" class="page-link' + activeClass + '" data-page="' + i + '">' + i + '</a> ';
        }

        html += '</div>';
        return html;
      }

      // Load initial results
      searchMedia();
    }

    // Delete media confirmation
    $(document).on('click', '.delete-media', function(e) {
      e.preventDefault();

      if (!confirm('Are you sure you want to delete this media item? This action cannot be undone.')) {
        return;
      }

      var mediaId = $(this).data('media-id');
      var $mediaItem = $(this).closest('.media-item');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_delete_media',
          nonce: heritagepress_media.delete_nonce,
          media_id: mediaId
        },
        beforeSend: function() {
          $mediaItem.addClass('deleting');
        },
        success: function(response) {
          if (response.success) {
            $mediaItem.fadeOut(300, function() {
              $(this).remove();
            });
          } else {
            alert('Error: ' + response.data);
            $mediaItem.removeClass('deleting');
          }
        },
        error: function() {
          alert('Error deleting media item.');
          $mediaItem.removeClass('deleting');
        }
      });
    });

    // Initialize components based on current tab
    var currentTab = '<?php echo esc_js($current_tab); ?>';

    if (currentTab === 'add' || currentTab === 'edit') {
      initMediaUpload();
      initMapIntegration();
    }

    if (currentTab === 'browse') {
      initMediaFilters();
    }
  });
</script>
