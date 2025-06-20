<?php

/**
 * HeritagePress Global Configuration Admin View
 *
 * Displays the global configuration settings page for HeritagePress.
 *
 */
if (!defined('ABSPATH')) {
  exit;
}

// Load current settings (to be implemented)
//$settings = get_option('heritagepress_config', array());

?>
<div class="wrap">
  <h1><?php _e('Global Configuration', 'heritagepress'); ?></h1>
  <?php if (isset($_GET['settings-updated'])): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php _e('Settings saved.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('heritagepress_config_save'); ?>
    <input type="hidden" name="action" value="heritagepress_save_config" />
    <h2 class="nav-tab-wrapper">
      <a href="#folders" class="nav-tab nav-tab-active"><?php _e('Folders', 'heritagepress'); ?></a>
      <a href="#media" class="nav-tab"><?php _e('Media', 'heritagepress'); ?></a>
      <a href="#priv" class="nav-tab"><?php _e('Privacy', 'heritagepress'); ?></a>
      <a href="#names" class="nav-tab"><?php _e('Names', 'heritagepress'); ?></a>
      <a href="#cemeteries" class="nav-tab"><?php _e('Cemeteries', 'heritagepress'); ?></a>
      <a href="#mailreg" class="nav-tab"><?php _e('Mail/Registration', 'heritagepress'); ?></a>
      <a href="#pref" class="nav-tab"><?php _e('Preferences', 'heritagepress'); ?></a>
      <a href="#mobile" class="nav-tab"><?php _e('Mobile', 'heritagepress'); ?></a>
      <a href="#dna" class="nav-tab"><?php _e('DNA', 'heritagepress'); ?></a>
      <a href="#misc" class="nav-tab"><?php _e('Miscellaneous', 'heritagepress'); ?></a>
    </h2>
    <div id="folders" class="tab-section" style="display:block;">
      <h2><?php _e('Folder Paths', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Photos Path', 'heritagepress'); ?></th>
          <td><input type="text" name="photopath" value="<?php echo esc_attr($settings['photopath'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Documents Path', 'heritagepress'); ?></th>
          <td><input type="text" name="documentpath" value="<?php echo esc_attr($settings['documentpath'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Histories Path', 'heritagepress'); ?></th>
          <td><input type="text" name="historypath" value="<?php echo esc_attr($settings['historypath'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Headstones Path', 'heritagepress'); ?></th>
          <td><input type="text" name="headstonepath" value="<?php echo esc_attr($settings['headstonepath'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Media Path', 'heritagepress'); ?></th>
          <td><input type="text" name="mediapath" value="<?php echo esc_attr($settings['mediapath'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Mods Path', 'heritagepress'); ?></th>
          <td><input type="text" name="modspath" value="<?php echo esc_attr($settings['modspath'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Exts Path', 'heritagepress'); ?></th>
          <td><input type="text" name="extspath" value="<?php echo esc_attr($settings['extspath'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Gendex File', 'heritagepress'); ?></th>
          <td><input type="text" name="gendexfile" value="<?php echo esc_attr($settings['gendexfile'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Backup Path', 'heritagepress'); ?></th>
          <td><input type="text" name="backuppath" value="<?php echo esc_attr($settings['backuppath'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"></th>
          <td><label><input type="checkbox" name="saveconfig" value="1" <?php checked($settings['saveconfig'] ?? '', '1'); ?> /> <?php _e('Save in config file', 'heritagepress'); ?></label></td>
        </tr>
      </table>
    </div>
    <div id="media" class="tab-section" style="display:none;">
      <h2><?php _e('Media Settings', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Photo Extension', 'heritagepress'); ?></th>
          <td><select name="photosext">
              <option value="jpg" <?php selected($settings['photosext'] ?? '', 'jpg'); ?>>.jpg</option>
              <option value="gif" <?php selected($settings['photosext'] ?? '', 'gif'); ?>>.gif</option>
              <option value="png" <?php selected($settings['photosext'] ?? '', 'png'); ?>>.png</option>
              <option value="bmp" <?php selected($settings['photosext'] ?? '', 'bmp'); ?>>.bmp</option>
            </select></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Show Extended', 'heritagepress'); ?></th>
          <td><select name="showextended">
              <option value="1" <?php selected($settings['showextended'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
              <option value="0" <?php selected($settings['showextended'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Image Max Height', 'heritagepress'); ?></th>
          <td><input type="text" name="imgmaxh" value="<?php echo esc_attr($settings['imgmaxh'] ?? ''); ?>" size="20" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Image Max Width', 'heritagepress'); ?></th>
          <td><input type="text" name="imgmaxw" value="<?php echo esc_attr($settings['imgmaxw'] ?? ''); ?>" size="20" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Thumb Prefix', 'heritagepress'); ?></th>
          <td><input type="text" name="thumbprefix" value="<?php echo esc_attr($settings['thumbprefix'] ?? ''); ?>" size="20" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Thumb Suffix', 'heritagepress'); ?></th>
          <td><input type="text" name="thumbsuffix" value="<?php echo esc_attr($settings['thumbsuffix'] ?? ''); ?>" size="20" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Thumb Max Height', 'heritagepress'); ?></th>
          <td><input type="text" name="thumbmaxh" value="<?php echo esc_attr($settings['thumbmaxh'] ?? ''); ?>" size="20" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Thumb Max Width', 'heritagepress'); ?></th>
          <td><input type="text" name="thumbmaxw" value="<?php echo esc_attr($settings['thumbmaxw'] ?? ''); ?>" size="20" /></td>
        </tr>
        <!-- Add more media fields as needed -->
      </table>
    </div>
    <div id="priv" class="tab-section" style="display:none;">
      <h2><?php _e('Privacy', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Require Login', 'heritagepress'); ?></th>
          <td><select name="requirelogin">
              <option value="1" <?php selected($settings['requirelogin'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
              <option value="0" <?php selected($settings['requirelogin'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr id="treerestrict" style="display:none;">
          <th scope="row"><?php _e('Tree Restrict', 'heritagepress'); ?></th>
          <td><select name="treerestrict">
              <option value="0" <?php selected($settings['treerestrict'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['treerestrict'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr id="trdisabled" style="display:none;">
          <th scope="row"><?php _e('Tree Restrict (Disabled)', 'heritagepress'); ?></th>
          <td><input type="text" name="treerestrict_disabled" value="<?php echo esc_attr($settings['treerestrict_disabled'] ?? ''); ?>" class="regular-text" disabled /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('LDS Default', 'heritagepress'); ?></th>
          <td><select name="ldsdefault">
              <option value="0" <?php selected($settings['ldsdefault'] ?? '', '0'); ?>><?php _e('On', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['ldsdefault'] ?? '', '1'); ?>><?php _e('Off', 'heritagepress'); ?></option>
              <option value="2" <?php selected($settings['ldsdefault'] ?? '', '2'); ?>><?php _e('Permit', 'heritagepress'); ?></option>
            </select></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Living Default', 'heritagepress'); ?></th>
          <td><select name="livedefault">
              <option value="2" <?php selected($settings['livedefault'] ?? '', '2'); ?>><?php _e('On', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['livedefault'] ?? '', '1'); ?>><?php _e('Off', 'heritagepress'); ?></option>
            </select></td>
        </tr>
      </table>
    </div>
    <div id="names" class="tab-section" style="display:none;">
      <h2><?php _e('Names', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Name Order', 'heritagepress'); ?></th>
          <td>
            <select name="nameorder">
              <option value=""></option>
              <option value="1" <?php selected($settings['nameorder'] ?? '', '1'); ?>><?php _e('Western', 'heritagepress'); ?></option>
              <option value="2" <?php selected($settings['nameorder'] ?? '', '2'); ?>><?php _e('Oriental', 'heritagepress'); ?></option>
              <option value="3" <?php selected($settings['nameorder'] ?? '', '3'); ?>><?php _e('Surname First', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Uppercase Surnames', 'heritagepress'); ?></th>
          <td>
            <select name="ucsurnames">
              <option value="0" <?php selected($settings['ucsurnames'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['ucsurnames'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Last Name Prefixes', 'heritagepress'); ?></th>
          <td>
            <select name="lnprefixes">
              <option value="0" <?php selected($settings['lnprefixes'] ?? '', '0'); ?>><?php _e('Together', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['lnprefixes'] ?? '', '1'); ?>><?php _e('Separate', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="2"><?php _e('Detect prefixes in last names', 'heritagepress'); ?></td>
        </tr>
        <tr>
          <th scope="row">&nbsp;&nbsp;<?php _e('Prefix Count', 'heritagepress'); ?></th>
          <td><input type="text" name="lnpfxnum" value="<?php echo esc_attr($settings['lnpfxnum'] ?? ''); ?>" size="5" /></td>
        </tr>
        <tr>
          <th scope="row">&nbsp;&nbsp;<?php _e('Special Prefixes', 'heritagepress'); ?>*</th>
          <td><input type="text" name="specpfx" value="<?php echo esc_attr($settings['specpfx'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <td colspan="2">*<?php _e('Separate with commas', 'heritagepress'); ?></td>
        </tr>
      </table>
    </div>
    <div id="cemeteries" class="tab-section" style="display:none;">
      <h2><?php _e('Cemeteries', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Rows per Page', 'heritagepress'); ?></th>
          <td><input type="text" name="cemrows" value="<?php echo esc_attr($settings['cemrows'] ?? ''); ?>" size="5" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Allow Blank Locations', 'heritagepress'); ?></th>
          <td>
            <select name="cemblanks">
              <option value="0" <?php selected($settings['cemblanks'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['cemblanks'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
      </table>
    </div>
    <div id="mailreg" class="tab-section" style="display:none;">
      <h2><?php _e('Mail/Registration', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Admin Email Address', 'heritagepress'); ?></th>
          <td><input type="text" name="emailaddr" value="<?php echo esc_attr($settings['emailaddr'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Send From Admin', 'heritagepress'); ?></th>
          <td>
            <select name="fromadmin">
              <option value="0" <?php selected($settings['fromadmin'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['fromadmin'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Allow Registration', 'heritagepress'); ?></th>
          <td>
            <select name="disallowreg">
              <option value="0" <?php selected($settings['disallowreg'] ?? '', '0'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['disallowreg'] ?? '', '1'); ?>><?php _e('No', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Review Mail', 'heritagepress'); ?></th>
          <td>
            <select name="revmail">
              <option value="0" <?php selected($settings['revmail'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['revmail'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Auto Assign Tree', 'heritagepress'); ?></th>
          <td>
            <select name="autotree">
              <option value="0" <?php selected($settings['autotree'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['autotree'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <!-- Add more mail/registration fields as needed -->
      </table>
    </div>
    <div id="pref" class="tab-section" style="display:none;">
      <h2><?php _e('Preferences', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Default Number of Generations', 'heritagepress'); ?></th>
          <td><input type="text" name="pedigreegen" value="<?php echo esc_attr($settings['pedigreegen'] ?? ''); ?>" size="5" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Max Ancestor Generations', 'heritagepress'); ?></th>
          <td><input type="text" name="maxgedcom" value="<?php echo esc_attr($settings['maxgedcom'] ?? ''); ?>" size="5" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Max Descendant Generations', 'heritagepress'); ?></th>
          <td><input type="text" name="maxdesc" value="<?php echo esc_attr($settings['maxdesc'] ?? ''); ?>" size="5" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Default Chart Width', 'heritagepress'); ?></th>
          <td><input type="text" name="chartwidth" value="<?php echo esc_attr($settings['chartwidth'] ?? ''); ?>" size="5" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Default Chart Height', 'heritagepress'); ?></th>
          <td><input type="text" name="chartheight" value="<?php echo esc_attr($settings['chartheight'] ?? ''); ?>" size="5" /></td>
        </tr>
        <!-- Add more preferences fields as needed -->
      </table>
    </div>
    <div id="mobile" class="tab-section" style="display:none;">
      <h2><?php _e('Mobile Settings', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Enable Mobile Theme', 'heritagepress'); ?></th>
          <td>
            <select name="mobiletheme">
              <option value="1" <?php selected($settings['mobiletheme'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
              <option value="0" <?php selected($settings['mobiletheme'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Mobile Theme Name', 'heritagepress'); ?></th>
          <td><input type="text" name="mobilethemename" value="<?php echo esc_attr($settings['mobilethemename'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Mobile Logo', 'heritagepress'); ?></th>
          <td><input type="text" name="mobilelogo" value="<?php echo esc_attr($settings['mobilelogo'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Mobile Header', 'heritagepress'); ?></th>
          <td><input type="text" name="mobileheader" value="<?php echo esc_attr($settings['mobileheader'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Mobile Footer', 'heritagepress'); ?></th>
          <td><input type="text" name="mobilefooter" value="<?php echo esc_attr($settings['mobilefooter'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <!-- Add more mobile fields as needed -->
      </table>
    </div>
    <div id="dna" class="tab-section" style="display:none;">
      <h2><?php _e('DNA Settings', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Enable DNA Features', 'heritagepress'); ?></th>
          <td>
            <select name="enable_dna">
              <option value="1" <?php selected($settings['enable_dna'] ?? '', '1'); ?>><?php _e('Yes', 'heritagepress'); ?></option>
              <option value="0" <?php selected($settings['enable_dna'] ?? '', '0'); ?>><?php _e('No', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('DNA Kit Link', 'heritagepress'); ?></th>
          <td><input type="text" name="dna_kit_link" value="<?php echo esc_attr($settings['dna_kit_link'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('DNA Test Results Link', 'heritagepress'); ?></th>
          <td><input type="text" name="dna_results_link" value="<?php echo esc_attr($settings['dna_results_link'] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('DNA Privacy', 'heritagepress'); ?></th>
          <td>
            <select name="dna_privacy">
              <option value="0" <?php selected($settings['dna_privacy'] ?? '', '0'); ?>><?php _e('Private', 'heritagepress'); ?></option>
              <option value="1" <?php selected($settings['dna_privacy'] ?? '', '1'); ?>><?php _e('Public', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <!-- Add more DNA fields as needed -->
      </table>
    </div>
    <div id="misc" class="tab-section" style="display:none;">
      <h2><?php _e('Miscellaneous Settings', 'heritagepress'); ?></h2>
      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Custom CSS', 'heritagepress'); ?></th>
          <td><textarea name="custom_css" rows="5" cols="65"><?php echo esc_textarea($settings['custom_css'] ?? ''); ?></textarea></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Custom JavaScript', 'heritagepress'); ?></th>
          <td><textarea name="custom_js" rows="5" cols="65"><?php echo esc_textarea($settings['custom_js'] ?? ''); ?></textarea></td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Maintenance Mode', 'heritagepress'); ?></th>
          <td>
            <select name="maintenance_mode">
              <option value="1" <?php selected($settings['maintenance_mode'] ?? '', '1'); ?>><?php _e('On', 'heritagepress'); ?></option>
              <option value="0" <?php selected($settings['maintenance_mode'] ?? '', '0'); ?>><?php _e('Off', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Maintenance Message', 'heritagepress'); ?></th>
          <td><textarea name="maintenance_message" rows="5" cols="65"><?php echo esc_textarea($settings['maintenance_message'] ?? ''); ?></textarea></td>
        </tr>
        <!-- Add more miscellaneous fields as needed -->
      </table>
    </div>
    <p class="submit">
      <button type="submit" class="button button-primary"><?php _e('Save Settings', 'heritagepress'); ?></button>
    </p>
  </form>
</div>
<script>
  // Simple tab switcher
  jQuery(document).ready(function($) {
    $('.nav-tab').click(function(e) {
      e.preventDefault();
      $('.nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');
      var target = $(this).attr('href');
      $('.tab-section').hide();
      $(target).show();
    });

    // Privacy: Tree Restrict toggle
    $('select[name="requirelogin"]').on('change', function() {
      if ($(this).val() == '1') {
        $('#treerestrict').closest('tr').show();
        $('#trdisabled').hide();
      } else {
        $('#treerestrict').closest('tr').hide();
        $('#trdisabled').show();
      }
    }).trigger('change');

    // Mail/Registration: Enable/disable fields based on Allow Registration
    $('select[name="disallowreg"]').on('change', function() {
      var off = $(this).val() == '1';
      $('#autoapp, #autotree, #ackemail, #omitpwd').prop('disabled', off);
    }).trigger('change');
  });
</script>
