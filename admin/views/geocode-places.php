<?php
if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
$ajax_nonce = wp_create_nonce('hp_geocode_places');
// Fetch trees for dropdown
$trees = $GLOBALS['wpdb']->get_results("SELECT gedcom, treename FROM {$GLOBALS['wpdb']->prefix}hp_trees ORDER BY treename");
$selected_tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
?>
<div class="wrap">
  <h1><?php esc_html_e('Geocode Places', 'heritagepress'); ?></h1>
  <p><?php esc_html_e('This tool will attempt to geocode all places without coordinates using OpenStreetMap. This may take a while on large sites.', 'heritagepress'); ?></p>
  <form id="hp-geocode-places-form" method="get" action="">
    <label for="tree-select"><strong><?php esc_html_e('Tree (optional):', 'heritagepress'); ?></strong></label>
    <select id="tree-select" name="tree">
      <option value=""><?php esc_html_e('All Trees', 'heritagepress'); ?></option>
      <?php foreach ($trees as $tree): ?>
        <option value="<?php echo esc_attr($tree->gedcom); ?>" <?php selected($selected_tree, $tree->gedcom); ?>><?php echo esc_html($tree->treename); ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" id="hp-geocode-places-btn" class="button button-primary"><?php esc_html_e('Geocode Places', 'heritagepress'); ?></button>
    <span id="hp-geocode-places-spinner" style="display:none;"><img src="<?php echo esc_attr(admin_url('images/spinner.gif')); ?>" alt="Loading" style="vertical-align:middle;width:16px;height:16px;"></span>
  </form>
  <div id="hp-geocode-places-results" style="margin-top:2em;"></div>
</div>
<script>
  document.getElementById('hp-geocode-places-btn').addEventListener('click', function() {
    var btn = this;
    var spinner = document.getElementById('hp-geocode-places-spinner');
    var results = document.getElementById('hp-geocode-places-results');
    var tree = document.getElementById('tree-select').value;
    btn.disabled = true;
    spinner.style.display = '';
    results.innerHTML = '';
    var data = 'action=hp_geocode_places&_ajax_nonce=<?php echo esc_js($ajax_nonce); ?>';
    if (tree) data += '&tree=' + encodeURIComponent(tree);
    fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: data
      })
      .then(response => response.json())
      .then(data => {
        if (data.success && data.data && data.data.message) {
          results.innerHTML = '<div class="notice notice-success">' + data.data.message + '</div>';
        } else {
          results.innerHTML = '<div class="notice notice-error">Failed to start geocoding.</div>';
        }
      })
      .catch(() => {
        results.innerHTML = '<div class="notice notice-error">AJAX error.</div>';
      })
      .finally(() => {
        btn.disabled = false;
        spinner.style.display = 'none';
      });
  });
</script>
