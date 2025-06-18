<?php

/**
 * Base Controller Interface
 *
 * Defines the contract that all HeritagePress admin controllers must follow
 * This ensures consistency across all admin controllers
 */

if (!defined('ABSPATH')) {
  exit;
}

interface HP_Controller_Interface
{
  /**
   * Initialize the controller
   * Called when the controller is instantiated
   */
  public function init();

  /**
   * Register hooks for this controller
   * Each controller handles its own WordPress hooks
   */
  public function register_hooks();

  /**
   * Handle AJAX requests for this controller
   * Controllers can implement their own AJAX handlers
   */
  public function handle_ajax();

  /**
   * Enqueue scripts and styles for this controller
   * Each controller manages its own assets
   */
  public function enqueue_assets();

  /**
   * Get the controller's capabilities
   * Returns array of required WordPress capabilities
   */
  public function get_capabilities();
}
