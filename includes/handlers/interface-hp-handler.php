<?php

/**
 * Handler Interface
 */

if (!defined('ABSPATH')) {
  exit;
}

interface HP_Handler_Interface
{
  /**
   * Initialize hooks
   */
  public function init_hooks();

  /**
   * Handle nonce verification
   */
  public function verify_nonce($nonce_name);

  /**
   * Check user capabilities
   */
  public function check_capabilities($capability = 'manage_options');
}
