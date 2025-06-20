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
  <h1><?php esc_html_e('Add New Family'); ?></h1>
  <form method="post" action="">
    <input type="hidden" name="action" value="add_family">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="husband_id"><?php esc_html_e('Husband ID'); ?></label></th>
        <td><input name="husband_id" type="text" id="husband_id" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="wife_id"><?php esc_html_e('Wife ID'); ?></label></th>
        <td><input name="wife_id" type="text" id="wife_id" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="marriage_date"><?php esc_html_e('Marriage Date'); ?></label></th>
        <td><input name="marriage_date" type="text" id="marriage_date" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="marriage_place"><?php esc_html_e('Marriage Place'); ?></label></th>
        <td><input name="marriage_place" type="text" id="marriage_place" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="divorce_date"><?php esc_html_e('Divorce Date'); ?></label></th>
        <td><input name="divorce_date" type="text" id="divorce_date" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="divorce_place"><?php esc_html_e('Divorce Place'); ?></label></th>
        <td><input name="divorce_place" type="text" id="divorce_place" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="family_id"><?php esc_html_e('Family ID'); ?></label></th>
        <td>
          <input name="family_id" type="text" id="family_id" value="" class="regular-text" required>
          <button type="button" class="button" id="generate-family-id" style="margin-left:8px;"><?php esc_html_e('Generate ID'); ?></button>
          <span id="generate-family-id-spinner" style="display:none;"><img src="<?php echo esc_attr(admin_url('images/spinner.gif')); ?>" alt="Loading" style="vertical-align:middle;width:16px;height:16px;"></span>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="gedcom"><?php esc_html_e('Tree (GEDCOM)'); ?></label></th>
        <td><input name="gedcom" type="text" id="gedcom" value="" class="regular-text" required></td>
      </tr>
      <tr>
        <th scope="row"><label for="living"><?php esc_html_e('Living'); ?></label></th>
        <td><input name="living" type="checkbox" id="living" value="1"></td>
      </tr>
      <tr>
        <th scope="row"><label for="private"><?php esc_html_e('Private'); ?></label></th>
        <td><input name="private" type="checkbox" id="private" value="1"></td>
      </tr>
      <tr>
        <th scope="row"><label for="branch"><?php esc_html_e('Branch'); ?></label></th>
        <td><input name="branch" type="text" id="branch" value="" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="notes"><?php esc_html_e('Notes'); ?></label></th>
        <td><textarea name="notes" id="notes" class="large-text" rows="4"></textarea></td>
      </tr>
      <!-- Add more fields as needed for full HeritagePress parity -->
    </table>
    <?php submit_button(__('Add Family')); ?>
  </form>
  <p><a href="<?php echo esc_attr(admin_url('admin.php?page=hp_families')); ?>">&larr; <?php esc_html_e('Back to Families'); ?></a></p>
</div>
<?php
// Add AJAX for Generate Family ID
$ajax_nonce = wp_create_nonce('hp_admin_ajax');
?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('generate-family-id');
    var spinner = document.getElementById('generate-family-id-spinner');
    btn.addEventListener('click', function() {
      var gedcom = document.getElementById('gedcom').value;
      if (!gedcom) {
        alert('Please enter/select a Tree (GEDCOM) first.');
        return;
      }
      btn.disabled = true;
      spinner.style.display = '';
      var data = new FormData();
      data.append('action', 'hp_generate_family_id');
      data.append('gedcom', gedcom);
      data.append('nonce', '<?php echo esc_js($ajax_nonce); ?>');
      fetch(ajaxurl, {
          method: 'POST',
          credentials: 'same-origin',
          body: data
        })
        .then(response => response.json())
        .then(result => {
          if (result.success && result.data && result.data.family_id) {
            document.getElementById('family_id').value = result.data.family_id;
          } else {
            alert('Failed to generate Family ID.');
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
