<?php

/**
 * Debug script for advanced search toggle issue
 * Place in the plugin root and access via browser
 */

// Basic WordPress bootstrap - minimal version for testing
define('WP_USE_THEMES', false);
require_once(dirname(dirname(dirname(__DIR__))) . '/wp-load.php');

?>
<!DOCTYPE html>
<html>

<head>
  <title>Advanced Search Debug - HeritagePress</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 40px;
    }

    .test-section {
      margin: 20px 0;
      padding: 20px;
      border: 1px solid #ccc;
      background: #f9f9f9;
    }

    #toggle-advanced {
      background: #0073aa;
      color: white;
      padding: 8px 15px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    #advanced-options {
      border: 1px solid #ddd;
      padding: 20px;
      margin-top: 10px;
      background: #fff;
    }

    #debug-log {
      background: #f0f0f0;
      padding: 10px;
      font-family: monospace;
      font-size: 12px;
      border: 1px solid #ccc;
      height: 300px;
      overflow-y: auto;
      margin: 10px 0;
    }

    .form-test {
      border: 2px solid #007cba;
      padding: 15px;
      margin: 10px 0;
    }
  </style>
</head>

<body>
  <h1>Advanced Search Toggle Debug</h1>

  <div class="test-section">
    <h2>Test 1: Standalone Button (should work)</h2>
    <button type="button" id="toggle-advanced">Advanced Options ↓</button>
    <div id="advanced-options" style="display: none;">
      <h3>Advanced Options Content</h3>
      <p>This should toggle correctly.</p>
    </div>
  </div>

  <div class="test-section">
    <h2>Test 2: Button Inside Form (potential issue)</h2>
    <form method="get" action="">
      <input type="text" name="search" placeholder="Search..." />
      <button type="button" id="toggle-advanced-2">Advanced Options ↓</button>
      <div id="advanced-options-2" style="display: none;">
        <h3>Advanced Options in Form</h3>
        <p>This might have different behavior.</p>
        <label><input type="checkbox" name="test" /> Test checkbox</label>
      </div>
      <button type="submit">Search</button>
    </form>
  </div>

  <div class="test-section">
    <h2>Debug Log</h2>
    <div id="debug-log"></div>
    <button onclick="clearLog()">Clear Log</button>
  </div>

  <script>
    var eventCount = 0;

    function log(message) {
      eventCount++;
      const time = new Date().toLocaleTimeString();
      const debugLog = document.getElementById('debug-log');
      debugLog.innerHTML += '[' + eventCount + '] ' + time + ': ' + message + '\n';
      debugLog.scrollTop = debugLog.scrollHeight;
      console.log(message);
    }

    function clearLog() {
      document.getElementById('debug-log').innerHTML = '';
      eventCount = 0;
    }

    jQuery(document).ready(function($) {
      log('Page ready, jQuery version: ' + $.fn.jquery);

      // Test 1: Simple toggle
      $('#toggle-advanced').on('click', function(e) {
        log('Test 1 button clicked');
        e.preventDefault();
        $('#advanced-options').slideToggle();
      });

      // Test 2: Form toggle
      $('#toggle-advanced-2').on('click', function(e) {
        log('Test 2 button clicked (in form)');
        e.preventDefault();
        e.stopPropagation();
        $('#advanced-options-2').slideToggle();
      });

      // Monitor all click events on the page
      $(document).on('click', function(e) {
        if (e.target.id.includes('toggle-advanced')) {
          log('Document click detected on: ' + e.target.id + ' (target: ' + e.target.tagName + ')');
        }
      });

      // Monitor form submissions
      $('form').on('submit', function(e) {
        log('Form submission detected');
      });

      log('Event handlers bound successfully');
    });
  </script>
</body>

</html>
