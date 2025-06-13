<?php

/**
 * HeritagePress Public Class
 *
 * Handles all public-facing functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Public
{
  /**
   * Constructor
   */
  public function __construct()
  {
    $this->init_hooks();
  }

  /**
   * Initialize public hooks
   */
  private function init_hooks()
  {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('init', array($this, 'init_rewrite_rules'));
    add_shortcode('heritagepress_tree', array($this, 'tree_shortcode'));
    add_shortcode('heritagepress_person', array($this, 'person_shortcode'));
  }

  /**
   * Enqueue public scripts and styles
   */
  public function enqueue_scripts()
  {
    wp_enqueue_style(
      'heritagepress-public',
      HERITAGEPRESS_PLUGIN_URL . 'public/css/public.css',
      array(),
      HERITAGEPRESS_VERSION
    );
    wp_enqueue_script(
      'heritagepress-public',
      HERITAGEPRESS_PLUGIN_URL . 'public/js/public.js',
      array('jquery'),
      HERITAGEPRESS_VERSION,
      true
    );

    wp_localize_script(
      'heritagepress-public',
      'heritagepress_public',
      array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('heritagepress_public_nonce'),
        'strings' => array(
          'loading' => __('Loading...', 'heritagepress'),
          'searching' => __('Searching...', 'heritagepress'),
          'no_results' => __('No results found.', 'heritagepress'),
          'error' => __('An error occurred. Please try again.', 'heritagepress'),
        )
      )
    );
  }

  /**
   * Initialize rewrite rules for genealogy pages
   */
  public function init_rewrite_rules()
  {
    add_rewrite_rule(
      '^genealogy/person/([^/]+)/?$',
      'index.php?genealogy_person=$matches[1]',
      'top'
    );

    add_rewrite_rule(
      '^genealogy/family/([^/]+)/?$',
      'index.php?genealogy_family=$matches[1]',
      'top'
    );

    add_rewrite_tag('%genealogy_person%', '([^&]+)');
    add_rewrite_tag('%genealogy_family%', '([^&]+)');
  }

  /**
   * Family tree shortcode
   */
  public function tree_shortcode($atts)
  {
    $atts = shortcode_atts(array(
      'tree_id' => 'main',
      'root_person' => '',
      'generations' => 4,
      'style' => 'standard'
    ), $atts, 'heritagepress_tree');

    ob_start();
    echo '<div class="heritagepress-tree">';
    echo '<p>' . __('Family tree display coming soon...', 'heritagepress') . '</p>';
    echo '</div>';
    return ob_get_clean();
  }

  /**
   * Person profile shortcode
   */
  public function person_shortcode($atts)
  {
    $atts = shortcode_atts(array(
      'person_id' => '',
      'tree_id' => 'main',
      'show_events' => true,
      'show_media' => true
    ), $atts, 'heritagepress_person');

    ob_start();
    echo '<div class="heritagepress-person">';
    echo '<p>' . __('Person profile display coming soon...', 'heritagepress') . '</p>';
    echo '</div>';
    return ob_get_clean();
  }
}
