<?php
// Polyfills for WordPress template functions (if needed)
if (!function_exists('esc_html_e')) {
  function esc_html_e($text)
  {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
}
if (!function_exists('esc_attr')) {
  function esc_attr($text)
  {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
}
if (!function_exists('admin_url')) {
  function admin_url($path = '')
  {
    return '/wp-admin/' . ltrim($path, '/');
  }
}
if (!function_exists('submit_button')) {
  function submit_button($text)
  {
    echo '<p class="submit"><button type="submit" class="button button-primary">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</button></p>';
  }
}
?>
<div class="wrap">
  <h1><?php esc_html_e('Add New Repository'); ?></h1>
  <form method="post" action="">
    <input type="hidden" name="action" value="add_repository">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="repo_id"><?php esc_html_e('Repository ID'); ?></label></th>
        <td>
          <input name="repo_id" type="text" id="repo_id" value="" class="regular-text" required>
          <button type="button" class="button" id="generate-repo-id" style="margin-left:8px;"><?php esc_html_e('Generate ID'); ?></button>
          <span id="generate-repo-id-spinner" style="display:none;"><img src="<?php echo esc_attr(admin_url('images/spinner.gif')); ?>" alt="Loading" style="vertical-align:middle;width:16px;height:16px;"></span>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="gedcom"><?php esc_html_e('Tree (GEDCOM)'); ?></label></th>
        <td><input name="gedcom" type="text" id="gedcom" value="" class="regular-text" required></td>
      </tr>
      <!-- Add more fields as needed for full TNG parity -->
    </table>
    <?php submit_button(__('Add Repository')); ?>
  </form>
  <p><a href="<?php echo esc_attr(admin_url('admin.php?page=hp_repositories')); ?>">&larr; <?php esc_html_e('Back to Repositories'); ?></a></p>
</div>
<?php
$ajax_nonce = wp_create_nonce('hp_admin_ajax');
?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('generate-repo-id');
    var spinner = document.getElementById('generate-repo-id-spinner');
    btn.addEventListener('click', function() {
      var gedcom = document.getElementById('gedcom').value;
      if (!gedcom) {
        alert('Please enter/select a Tree (GEDCOM) first.');
        return;
      }
      btn.disabled = true;
      spinner.style.display = '';
      var data = new FormData();
      data.append('action', 'hp_generate_repository_id');
      data.append('gedcom', gedcom);
      data.append('nonce', '<?php echo esc_js($ajax_nonce); ?>');
      fetch(ajaxurl, {
          method: 'POST',
          credentials: 'same-origin',
          body: data
        })
        .then(response => response.json())
        .then(result => {
          if (result.success && result.data && result.data.repo_id) {
            document.getElementById('repo_id').value = result.data.repo_id;
          } else {
            alert('Failed to generate Repository ID.');
          }
        })
        .catch(() => alert('AJAX error.'))
        .finally(() => {
          btn.disabled = false;
          spinner.style.display = 'none';
        });
    });
  });
</script>
