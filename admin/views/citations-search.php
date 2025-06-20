<?php

/**
 * Search Citations View for HeritagePress
 *
 * This file provides the search citations interface for the WordPress admin.
 * Ported from genealogy admin citations search functionality
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get search parameters
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$source_filter = isset($_GET['source_filter']) ? intval($_GET['source_filter']) : 0;
$quality_filter = isset($_GET['quality_filter']) ? sanitize_text_field($_GET['quality_filter']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// Get sources for filter dropdown
global $wpdb;
$sources = $wpdb->get_results("SELECT sourceID, title FROM {$wpdb->prefix}hp_sources ORDER BY title");

// Build search query
$where_conditions = array('1=1');
$query_params = array();

if ($search_term) {
  $where_conditions[] = "(c.description LIKE %s OR c.citetext LIKE %s OR c.page LIKE %s OR s.title LIKE %s)";
  $search_like = '%' . $wpdb->esc_like($search_term) . '%';
  $query_params[] = $search_like;
  $query_params[] = $search_like;
  $query_params[] = $search_like;
  $query_params[] = $search_like;
}

if ($source_filter) {
  $where_conditions[] = "c.sourceID = %d";
  $query_params[] = $source_filter;
}

if ($quality_filter !== '') {
  $where_conditions[] = "c.quality = %s";
  $query_params[] = $quality_filter;
}

if ($date_from) {
  $where_conditions[] = "c.citedate >= %s";
  $query_params[] = $date_from;
}

if ($date_to) {
  $where_conditions[] = "c.citedate <= %s";
  $query_params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// Pagination
$per_page = 25;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get total count
$count_query = "
    SELECT COUNT(*)
    FROM {$wpdb->prefix}hp_citations c
    LEFT JOIN {$wpdb->prefix}hp_sources s ON c.sourceID = s.sourceID
    WHERE {$where_clause}
";

$total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
$total_pages = ceil($total_items / $per_page);

// Get citations
$citations_query = "
    SELECT c.*, s.title as source_title, s.author,
           p.firstname, p.lastname,
           e.eventtype, e.eventdate
    FROM {$wpdb->prefix}hp_citations c
    LEFT JOIN {$wpdb->prefix}hp_sources s ON c.sourceID = s.sourceID
    LEFT JOIN {$wpdb->prefix}hp_people p ON c.personID = p.personID
    LEFT JOIN {$wpdb->prefix}hp_events e ON c.eventID = e.eventID
    WHERE {$where_clause}
    ORDER BY c.citedate DESC, c.citationID DESC
    LIMIT %d OFFSET %d
";

$final_params = array_merge($query_params, array($per_page, $offset));
$citations = $wpdb->get_results($wpdb->prepare($citations_query, $final_params));
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Search Citations', 'heritagepress'); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=hp-citations'); ?>" class="page-title-action">
    <?php esc_html_e('Back to Citations', 'heritagepress'); ?>
  </a>
  <hr class="wp-header-end">

  <!-- Search Form -->
  <div class="search-form-container">
    <form method="get" id="citation-search-form">
      <input type="hidden" name="page" value="hp-citations-search">

      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="search"><?php esc_html_e('Search Text', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="search" id="search"
                value="<?php echo esc_attr($search_term); ?>"
                class="regular-text"
                placeholder="<?php esc_attr_e('Search in description, citation text, page, or source title...', 'heritagepress'); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="source_filter"><?php esc_html_e('Source', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="source_filter" id="source_filter" class="regular-text">
                <option value=""><?php esc_html_e('All sources', 'heritagepress'); ?></option>
                <?php foreach ($sources as $source): ?>
                  <option value="<?php echo esc_attr($source->sourceID); ?>"
                    <?php selected($source_filter, $source->sourceID); ?>>
                    <?php echo esc_html($source->title); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="quality_filter"><?php esc_html_e('Quality', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="quality_filter" id="quality_filter">
                <option value=""><?php esc_html_e('All qualities', 'heritagepress'); ?></option>
                <option value="3" <?php selected($quality_filter, '3'); ?>><?php esc_html_e('Primary evidence', 'heritagepress'); ?></option>
                <option value="2" <?php selected($quality_filter, '2'); ?>><?php esc_html_e('Secondary evidence', 'heritagepress'); ?></option>
                <option value="1" <?php selected($quality_filter, '1'); ?>><?php esc_html_e('Questionable evidence', 'heritagepress'); ?></option>
                <option value="0" <?php selected($quality_filter, '0'); ?>><?php esc_html_e('Unreliable evidence', 'heritagepress'); ?></option>
              </select>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label><?php esc_html_e('Date Range', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="date" name="date_from" id="date_from"
                value="<?php echo esc_attr($date_from); ?>"
                class="regular-text">
              <span style="margin: 0 10px;"><?php esc_html_e('to', 'heritagepress'); ?></span>
              <input type="date" name="date_to" id="date_to"
                value="<?php echo esc_attr($date_to); ?>"
                class="regular-text">
            </td>
          </tr>
        </tbody>
      </table>

      <p class="submit">
        <input type="submit" name="submit" class="button button-primary"
          value="<?php esc_attr_e('Search Citations', 'heritagepress'); ?>">
        <a href="<?php echo admin_url('admin.php?page=hp-citations-search'); ?>" class="button">
          <?php esc_html_e('Clear Filters', 'heritagepress'); ?>
        </a>
      </p>
    </form>
  </div>

  <!-- Search Results -->
  <?php if ($search_term || $source_filter || $quality_filter !== '' || $date_from || $date_to): ?>
    <div class="search-results">
      <h2><?php printf(esc_html__('Search Results (%d citations found)', 'heritagepress'), $total_items); ?></h2>

      <?php if ($citations): ?>
        <div class="tablenav top">
          <div class="tablenav-pages">
            <?php
            $pagination_args = array(
              'base' => add_query_arg('paged', '%#%'),
              'format' => '',
              'prev_text' => __('&laquo;'),
              'next_text' => __('&raquo;'),
              'total' => $total_pages,
              'current' => $current_page
            );
            echo paginate_links($pagination_args);
            ?>
          </div>
          <br class="clear">
        </div>

        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th scope="col" class="manage-column"><?php esc_html_e('ID', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Source', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Description', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Page', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Quality', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Linked To', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Date', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Actions', 'heritagepress'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($citations as $citation): ?>
              <tr>
                <td><?php echo esc_html($citation->citationID); ?></td>
                <td>
                  <strong><?php echo esc_html($citation->source_title); ?></strong>
                  <?php if ($citation->author): ?>
                    <br><small><?php echo esc_html($citation->author); ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php echo esc_html($citation->description); ?>
                  <?php if ($citation->citetext): ?>
                    <br><small><?php echo esc_html(wp_trim_words($citation->citetext, 10)); ?></small>
                  <?php endif; ?>
                </td>
                <td><?php echo esc_html($citation->page); ?></td>
                <td>
                  <?php
                  $quality_labels = array(
                    '3' => __('Primary', 'heritagepress'),
                    '2' => __('Secondary', 'heritagepress'),
                    '1' => __('Questionable', 'heritagepress'),
                    '0' => __('Unreliable', 'heritagepress')
                  );
                  echo esc_html($quality_labels[$citation->quality] ?? '');
                  ?>
                </td>
                <td>
                  <?php if ($citation->firstname && $citation->lastname): ?>
                    <strong><?php esc_html_e('Person:', 'heritagepress'); ?></strong>
                    <?php echo esc_html($citation->firstname . ' ' . $citation->lastname); ?><br>
                  <?php endif; ?>
                  <?php if ($citation->familyID): ?>
                    <strong><?php esc_html_e('Family:', 'heritagepress'); ?></strong>
                    <?php echo esc_html($citation->familyID); ?><br>
                  <?php endif; ?>
                  <?php if ($citation->eventtype): ?>
                    <strong><?php esc_html_e('Event:', 'heritagepress'); ?></strong>
                    <?php echo esc_html($citation->eventtype); ?>
                    <?php if ($citation->eventdate): ?>
                      (<?php echo esc_html($citation->eventdate); ?>)
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
                <td><?php echo esc_html($citation->citedate); ?></td>
                <td>
                  <a href="<?php echo admin_url('admin.php?page=hp-citations-edit&citation_id=' . $citation->citationID); ?>"
                    class="button button-small">
                    <?php esc_html_e('Edit', 'heritagepress'); ?>
                  </a>
                  <button type="button" class="button button-small button-link-delete delete-citation"
                    data-citation-id="<?php echo esc_attr($citation->citationID); ?>">
                    <?php esc_html_e('Delete', 'heritagepress'); ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="tablenav bottom">
          <div class="tablenav-pages">
            <?php echo paginate_links($pagination_args); ?>
          </div>
          <br class="clear">
        </div>

      <?php else: ?>
        <div class="notice notice-info">
          <p><?php esc_html_e('No citations found matching your search criteria.', 'heritagepress'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="notice notice-info">
      <p><?php esc_html_e('Enter search criteria above to find citations.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>

  <div id="citation-messages"></div>
</div>

<style>
  .search-form-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    margin: 20px 0;
    padding: 20px;
  }

  .search-results {
    margin-top: 20px;
  }

  .tablenav-pages {
    float: right;
  }

  .wp-list-table th {
    vertical-align: top;
  }

  .wp-list-table td {
    vertical-align: top;
  }

  .button-small {
    padding: 2px 8px;
    font-size: 11px;
    line-height: 1.5;
    height: auto;
  }

  #citation-messages {
    margin-top: 20px;
  }

  .notice {
    margin: 5px 0 15px;
    padding: 1px 12px;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Delete citation functionality
    $('.delete-citation').click(function() {
      var citationId = $(this).data('citation-id');
      var $row = $(this).closest('tr');

      if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this citation?', 'heritagepress')); ?>')) {
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_delete_citation',
          citation_id: citationId,
          nonce: '<?php echo wp_create_nonce('hp_delete_citation'); ?>'
        },
        success: function(response) {
          if (response.success) {
            $row.fadeOut(300, function() {
              $(this).remove();
            });
            $('#citation-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
          } else {
            $('#citation-messages').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
          }
        },
        error: function() {
          $('#citation-messages').html('<div class="notice notice-error"><p><?php echo esc_js(__('Error deleting citation.', 'heritagepress')); ?></p></div>');
        }
      });
    });

    // Clear messages after a delay
    setTimeout(function() {
      $('.notice').fadeOut();
    }, 5000);
  });
</script>
