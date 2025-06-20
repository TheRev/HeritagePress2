<?php
if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
$ajax_nonce = wp_create_nonce('hp_generate_thumbnails');
?>
<div class="wrap">
  <h1><?php esc_html_e('Generate Media Thumbnails', 'heritagepress'); ?></h1>
  <p><?php esc_html_e('This tool will scan all media and generate missing or outdated thumbnails. This may take a while on large sites.', 'heritagepress'); ?></p>
  <button id="hp-generate-thumbnails-btn" class="button button-primary"><?php esc_html_e('Generate Thumbnails', 'heritagepress'); ?></button>
  <span id="hp-generate-thumbnails-spinner" style="display:none;"><img src="<?php echo esc_attr(admin_url('images/spinner.gif')); ?>" alt="Loading" style="vertical-align:middle;width:16px;height:16px;"></span>
  <div id="hp-generate-thumbnails-results" style="margin-top:2em;"></div>
</div>
<script>
  document.getElementById('hp-generate-thumbnails-btn').addEventListener('click', function() {
    var btn = this;
    var spinner = document.getElementById('hp-generate-thumbnails-spinner');
    var results = document.getElementById('hp-generate-thumbnails-results');
    btn.disabled = true;
    spinner.style.display = '';
    results.innerHTML = '';
    fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=hp_generate_thumbnails&_ajax_nonce=<?php echo esc_js($ajax_nonce); ?>'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success && data.data && data.data.message) {
          results.innerHTML = '<div class="notice notice-success">' + data.data.message + '</div>';
        } else {
          results.innerHTML = '<div class="notice notice-error">Failed to start thumbnail generation.</div>';
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
