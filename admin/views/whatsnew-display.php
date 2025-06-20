<?php
// Frontend display for the "What's New" message
$message = get_option('hp_whatsnew_message', '');
if (!empty($message)) {
  echo '<div class="heritagepress-whatsnew">' . wpautop(esc_html($message)) . '</div>';
}
