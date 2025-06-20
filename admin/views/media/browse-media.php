<?php

/**
 * Browse Media View
 *
 * This sub-view provides the media browsing/listing interface
 * for the HeritagePress plugin. It includes search and filter
 * options, media type selection, and pagination.
 *
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}
?>

<div id="browse-media-tab" class="hp-tab-content">
  <!-- Search and Filter Section -->
  <div class="hp-media-search-section">
    <div class="hp-search-form">
      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="media-search"><?php esc_html_e('Search Media', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" id="media-search" name="search" class="regular-text" placeholder="<?php esc_attr_e('Search descriptions, notes, filenames...', 'heritagepress'); ?>">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="media-type-filter"><?php esc_html_e('Media Type', 'heritagepress'); ?></label>
          </th>
          <td>
            <select id="media-type-filter" name="media_type">
              <option value=""><?php esc_html_e('All Types', 'heritagepress'); ?></option>
              <?php foreach ($media_types as $type): ?>
                <option value="<?php echo esc_attr($type->mediatypeID); ?>">
                  <?php echo esc_html($type->display); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="tree-filter"><?php esc_html_e('Tree', 'heritagepress'); ?></label>
          </th>
          <td>
            <select id="tree-filter" name="tree">
              <option value=""><?php esc_html_e('All Trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree->gedcom); ?>">
                  <?php echo esc_html($tree->treename); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="file-extension-filter"><?php esc_html_e('File Type', 'heritagepress'); ?></label>
          </th>
          <td>
            <select id="file-extension-filter" name="file_extension">
              <option value=""><?php esc_html_e('All File Types', 'heritagepress'); ?></option>
              <option value="jpg,jpeg"><?php esc_html_e('JPEG Images', 'heritagepress'); ?></option>
              <option value="png"><?php esc_html_e('PNG Images', 'heritagepress'); ?></option>
              <option value="gif"><?php esc_html_e('GIF Images', 'heritagepress'); ?></option>
              <option value="pdf"><?php esc_html_e('PDF Documents', 'heritagepress'); ?></option>
              <option value="doc,docx"><?php esc_html_e('Word Documents', 'heritagepress'); ?></option>
              <option value="mp3,wav"><?php esc_html_e('Audio Files', 'heritagepress'); ?></option>
              <option value="mp4,avi"><?php esc_html_e('Video Files', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <?php esc_html_e('Filter Options', 'heritagepress'); ?>
          </th>
          <td>
            <label>
              <input type="checkbox" id="filter-unlinked" name="unlinked" value="1">
              <?php esc_html_e('Show only unlinked media', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" id="filter-no-thumbnail" name="no_thumbnail" value="1">
              <?php esc_html_e('Show only media without thumbnails', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
      </table>

      <p class="submit">
        <button type="button" id="search-media" class="button-primary">
          <?php esc_html_e('Search Media', 'heritagepress'); ?>
        </button>
        <button type="button" id="clear-search" class="button">
          <?php esc_html_e('Clear Search', 'heritagepress'); ?>
        </button>
      </p>
    </div>
  </div>

  <!-- Results Section -->
  <div class="hp-media-results-section">
    <div class="hp-results-header">
      <div class="hp-results-info">
        <span id="media-count">0</span> <?php esc_html_e('media items found', 'heritagepress'); ?>
      </div>
      <div class="hp-view-options">
        <label>
          <?php esc_html_e('View:', 'heritagepress'); ?>
          <select id="view-mode">
            <option value="list"><?php esc_html_e('List View', 'heritagepress'); ?></option>
            <option value="grid"><?php esc_html_e('Grid View', 'heritagepress'); ?></option>
          </select>
        </label>
        <label>
          <?php esc_html_e('Per page:', 'heritagepress'); ?>
          <select id="items-per-page">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </label>
      </div>
    </div>

    <div id="media-results" class="hp-media-list">
      <!-- Results loaded via AJAX -->
      <div class="hp-loading">
        <p><?php esc_html_e('Loading media...', 'heritagepress'); ?></p>
      </div>
    </div>

    <!-- Pagination -->
    <div id="media-pagination" class="hp-pagination">
      <!-- Pagination loaded via AJAX -->
    </div>
  </div>

  <!-- Bulk Actions -->
  <div class="hp-bulk-actions" style="display: none;">
    <select id="bulk-action">
      <option value=""><?php esc_html_e('Bulk Actions', 'heritagepress'); ?></option>
      <option value="delete"><?php esc_html_e('Delete Selected', 'heritagepress'); ?></option>
      <option value="change-type"><?php esc_html_e('Change Media Type', 'heritagepress'); ?></option>
      <option value="change-tree"><?php esc_html_e('Change Tree', 'heritagepress'); ?></option>
    </select>
    <button type="button" id="apply-bulk-action" class="button">
      <?php esc_html_e('Apply', 'heritagepress'); ?>
    </button>
  </div>
</div>

<style>
  .hp-media-search-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    margin-bottom: 20px;
    padding: 15px;
  }

  .hp-media-results-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    padding: 15px;
  }

  .hp-results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
  }

  .hp-view-options label {
    margin-left: 15px;
  }

  .hp-media-list {
    min-height: 200px;
  }

  .hp-media-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
  }

  .hp-media-item:hover {
    background-color: #f9f9f9;
  }

  .hp-media-thumbnail {
    width: 60px;
    height: 60px;
    margin-right: 15px;
    object-fit: cover;
    border: 1px solid #ddd;
  }

  .hp-media-details {
    flex: 1;
  }

  .hp-media-title {
    font-weight: bold;
    margin-bottom: 5px;
  }

  .hp-media-meta {
    color: #666;
    font-size: 12px;
  }

  .hp-media-actions {
    display: flex;
    gap: 5px;
  }

  .hp-loading {
    text-align: center;
    padding: 50px;
    color: #666;
  }

  .hp-pagination {
    text-align: center;
    margin-top: 20px;
  }

  .hp-bulk-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
  }

  /* Grid view styles */
  .hp-media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
  }

  .hp-media-grid .hp-media-item {
    flex-direction: column;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
  }

  .hp-media-grid .hp-media-thumbnail {
    width: 100px;
    height: 100px;
    margin: 0 0 10px 0;
  }
</style>
