<?php
// HeritagePress: Mod Manager Log admin page (WordPress-native, ported from TNG admin_showmodslog.php)
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function () {
  add_menu_page(
    __('Mod Manager Log', 'heritagepress'),
    __('Mod Manager Log', 'heritagepress'),
    'manage_options',
    'heritagepress-modslog',
    'heritagepress_admin_modslog_page',
    'dashicons-list-view',
    58
  );
});

function heritagepress_admin_modslog_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
  }
  $logfile = apply_filters('heritagepress_modslog_file', WP_CONTENT_DIR . '/uploads/modmgrlog.txt');
  $cleared = false;
  if (!empty($_GET['action']) && $_GET['action'] === 'clear' && check_admin_referer('heritagepress_modslog_clear')) {
    file_put_contents($logfile, '#### Mod Manager Log created ' . date('D d M Y h:i:s A') . " ####\n");
    $cleared = true;
  }
  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('Mod Manager Log', 'heritagepress') . '</h1>';
  if ($cleared) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Log cleared.', 'heritagepress') . '</p></div>';
  }
  echo '<p>';
  echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=heritagepress-modslog&action=clear'), 'heritagepress_modslog_clear')) . '" class="button" onclick="return confirm(\'' . esc_js(__('Are you sure you want to clear the log?', 'heritagepress')) . '\');">' . esc_html__('Clear Log', 'heritagepress') . '</a> ';
  echo '<a href="https://tng.lythgoes.net/wiki/index.php?title=Mod_Manager_Syntax" target="_blank" class="button">' . esc_html__('Mod Syntax', 'heritagepress') . '</a> ';
  echo '<a href="https://tng.lythgoes.net/wiki/index.php?title=Mod_Guidelines" target="_blank" class="button">' . esc_html__('Mod Guidelines', 'heritagepress') . '</a>';
  echo '</p>';

  if (!file_exists($logfile)) {
    echo '<div class="notice notice-warning"><p>' . esc_html__('Log file not found.', 'heritagepress') . '</p></div>';
    echo '</div>';
    return;
  }
  $lines = file($logfile);
  if (!$lines) {
    echo '<p>' . esc_html__('No log entries found.', 'heritagepress') . '</p>';
    echo '</div>';
    return;
  }
  echo '<table class="widefat fixed striped">';
  echo '<thead><tr><th>' . esc_html__('Recent Actions', 'heritagepress') . '</th></tr></thead><tbody>';
  $actionCount = 0;
  $logEntryDetails = '';
  $type1Or2EntryIsActive = false;
  $type3EntryIsActive = false;
  $nColumns = 1;
  foreach ($lines as $line) {
    $newLogFormat = false;
    if (preg_match("/^\w{3} \d{2} \w{3} \d{4} \d{2}:\d{2}:\d{2} \w{2}/i", $line)) {
      $br = strpos($line, "<br />");
      if ($br !== false) {
        if (strlen($line) - $br > 20 || $br < 150) $newLogFormat = true;
      }
    }
    if ($newLogFormat) {
      if ($logEntryDetails) {
        echo '<tr class="moddetails" style="display:none;" id="data' . $actionCount . '"><td>' . $logEntryDetails . '</td></tr>';
        $type3EntryIsActive = false;
      }
      $actionCount++;
      $heading = str_replace('<hr />', '', substr($line, 0, $br));
      $logEntryDetails = substr($line, $br + 6);
      $dynoclass = '';
      if (false !== strpos($heading, 'errors')) $dynoclass = 'msgerror';
      elseif (false !== stripos($heading, 'Clean Up')) $dynoclass = 'partinst';
      elseif (false !== stripos($heading, 'modrem')) $dynoclass = 'ready';
      elseif (false !== stripos($heading, 'installed')) $dynoclass = 'installed';
      elseif (false !== stripos($heading, 'updated')) $dynoclass = 'installed';
      elseif (false !== stripos($heading, 'filedel')) $dynoclass = '';
      else $dynoclass = 'lightback';
      echo '<tr class="' . esc_attr($dynoclass) . '"><td class="action ' . esc_attr($dynoclass) . '" id="action' . $actionCount . '">' . $actionCount . '. ' . $heading . '</td></tr>';
      $type1Or2EntryIsActive = true;
    } else {
      $match = preg_match("/^(\w{3} \d{2} \w{3} \d{4} \d{2}:\d{2}:\d{2} \w{2}) ([.\w_-]*)\.cfg(.*)\(([\w: ]*)\)/i", $line, $matches);
      if ($match) {
        if ($logEntryDetails) {
          echo '<tr class="moddetails" style="display:none;" id="data' . $actionCount . '"><td>' . $logEntryDetails . '</td></tr>';
          $type1Or2EntryIsActive = false;
          $logEntryDetails = '';
        }
        $actionCount++;
        echo '<tr><td class="action" id="action' . $actionCount . '">' . $actionCount . '. ' . $line . '</td></tr>';
        $type3EntryIsActive = true;
      } else {
        if ($type3EntryIsActive) {
          $logEntryDetails = $line . "</span><br />\n" . $logEntryDetails;
        } elseif ($type1Or2EntryIsActive) {
          $logEntryDetails .= "<br />" . $line;
        } else {
          echo '<tr><td><b>?? </b>' . $line . '</td></tr>';
        }
      }
    }
  }
  if ($logEntryDetails) {
    echo '<tr class="moddetails" style="display:none;" id="data' . $actionCount . '"><td>' . $logEntryDetails . '</td></tr>';
  }
  echo '</tbody></table>';
  echo '<style>.action{cursor:pointer;}.moddetails{background:#f9f9f9;}</style>';
  echo '<script>jQuery(document).ready(function(){jQuery(".action").click(function(){var myId=jQuery(this).attr("id");var dataId=myId.replace("action","data");if(jQuery(this).hasClass("collapse")){jQuery("#"+dataId).hide();jQuery(this).removeClass("collapse");}else{jQuery(this).addClass("collapse");jQuery("#"+dataId).show();}});});</script>';
  echo '</div>';
}
