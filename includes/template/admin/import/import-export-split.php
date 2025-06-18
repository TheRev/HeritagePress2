<?php

/**
 * Import/Export Admin View - Main Template
 * Wrapper that includes the individual tab files
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'import';
?>

<div class="wrap">
  <h1><?php _e('Import / Export', 'heritagepress'); ?></h1>

  <?php
  settings_errors('heritagepress_import');
  settings_errors('heritagepress_export');
  settings_errors('heritagepress_post_import');
  ?>

  <!-- Tab Navigation -->
  <h2 class="nav-tab-wrapper">
    <a href="?page=heritagepress-import&tab=import" class="nav-tab <?php echo $current_tab === 'import' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Import', 'heritagepress'); ?>
    </a>
    <a href="?page=heritagepress-import&tab=export" class="nav-tab <?php echo $current_tab === 'export' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Export', 'heritagepress'); ?>
    </a>
    <a href="?page=heritagepress-import&tab=post-import" class="nav-tab <?php echo $current_tab === 'post-import' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Post-Import', 'heritagepress'); ?>
    </a>
  </h2>
  <div class="tab-content">
    <?php if ($current_tab === 'import'): ?>
      <?php include __DIR__ . '/import.php'; ?>
    <?php elseif ($current_tab === 'export'): ?>
      <?php include __DIR__ . '/export.php'; ?>
    <?php elseif ($current_tab === 'post-import'): ?>
      <?php include __DIR__ . '/post-import.php'; ?>
    <?php endif; ?>
  </div>
</div>

<script type="text/javascript">
  // JavaScript functions for import/export functionality

  function toggleSections(eventsOnly) {
    // Toggle sections based on events only checkbox
    var sections = ['desttree', 'replace'];
    for (var i = 0; i < sections.length; i++) {
      var element = document.getElementById(sections[i]);
      if (element) {
        element.style.display = eventsOnly ? 'none' : '';
      }
    }
  }

  function toggleNorecalcdiv(show) {
    var div = document.getElementById('norecalcdiv');
    if (div) {
      div.style.display = show ? '' : 'none';
    }
  }

  function toggleAppenddiv(show) {
    var div = document.getElementById('appenddiv');
    if (div) {
      div.style.display = show ? '' : 'none';
    }
  }

  function toggleTarget(form) {
    // Toggle form target for legacy import
    if (form.old.checked) {
      form.target = 'results';
      document.getElementById('results').style.display = 'block';
      document.getElementById('results').height = '300';
      document.getElementById('results').width = '100%';
    } else {
      form.target = '';
      document.getElementById('results').style.display = 'none';
    }
  }

  function getBranches(selectElement, selectedIndex) {
    // Load branches for selected tree
    var tree = selectElement.value;
    var branchSelect = document.getElementById('branch1');

    if (branchSelect) {
      // Clear existing options
      branchSelect.innerHTML = '<option value="">All branches</option>';

      // Show/hide branch selection
      var branchRow = document.getElementById('destbranch');
      if (branchRow) {
        branchRow.style.display = tree ? '' : 'none';
      }

      // AJAX call to load branches would go here
      if (tree) {
        // Placeholder for actual AJAX implementation
        console.log('Loading branches for tree: ' + tree);
      }
    }
  }

  function swapBranches(form) {
    // Swap branches for export tree selection
    var tree = form.tree.value;
    var branchSelect = document.getElementById('branch');

    if (branchSelect && tree) {
      // Clear existing options
      branchSelect.innerHTML = '<option value="">All branches</option>';

      // AJAX call to load branches would go here
      console.log('Loading branches for export tree: ' + tree);
    }
  }

  function toggleStuff() {
    // Toggle media export options
    var exportMedia = document.getElementById('exportmedia');
    var exportMediaFiles = document.getElementById('exportmediafiles');
    var expRows = document.getElementById('exprows');

    if (exportMedia && exportMediaFiles && expRows) {
      if (exportMedia.checked) {
        exportMediaFiles.disabled = false;
        expRows.style.display = 'block';
      } else {
        exportMediaFiles.disabled = true;
        exportMediaFiles.checked = false;
        expRows.style.display = 'none';
      }
    }
  }

  function iframeLoaded() {
    // Handle iframe load for import progress
    console.log('Import iframe loaded');
  }

  function runPostImportUtility(utility) {
    if (confirm('Run post-import utility: ' + utility + '?\n\nThis may take several minutes for large databases.')) {
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = window.location.href;

      var nonceField = document.createElement('input');
      nonceField.type = 'hidden';
      nonceField.name = '_wpnonce';
      nonceField.value = '<?php echo wp_create_nonce('heritagepress_post_import'); ?>';
      form.appendChild(nonceField);

      var actionField = document.createElement('input');
      actionField.type = 'hidden';
      actionField.name = 'secaction';
      actionField.value = utility;
      form.appendChild(actionField);

      var treeField = document.createElement('input');
      treeField.type = 'hidden';
      treeField.name = 'tree';
      treeField.value = document.getElementById('treequeryselect').value;
      form.appendChild(treeField);

      document.body.appendChild(form);
      form.submit();
    }
  }

  // Enhanced radio option selection
  function updateRadioSelection() {
    var radioOptions = document.querySelectorAll('.radio-option');
    radioOptions.forEach(function(option) {
      var radio = option.querySelector('input[type="radio"]');
      if (radio && radio.checked) {
        option.classList.add('selected');
      } else {
        option.classList.remove('selected');
      }
    });
  }

  // Initialize page
  jQuery(document).ready(function($) {
    // Initialize form elements
    <?php
    global $wpdb;
    $import_config = array('defimpopt' => 0); // Default config for JS
    ?>
    toggleNorecalcdiv(<?php echo $import_config['defimpopt'] ? 1 : 0; ?>);
    toggleAppenddiv(<?php echo $import_config['defimpopt'] == 3 ? 1 : 0; ?>);

    // Add radio option click handlers
    $('.radio-option').on('click', function() {
      var radio = $(this).find('input[type="radio"]');
      if (radio.length) {
        radio.prop('checked', true).trigger('change');
        updateRadioSelection();
      }
    });

    // Add change handlers for radio buttons
    $('input[type="radio"]').on('change', function() {
      updateRadioSelection();
    });

    // Initialize radio selection state
    updateRadioSelection();

    // Add hover effects for utility cards
    $('.utility-card').on('mouseenter', function() {
      $(this).css('transform', 'translateY(-2px)');
    }).on('mouseleave', function() {
      $(this).css('transform', 'translateY(0)');
    }); // Form validation
    $('#gedcom-import-form').on('submit', function(e) {
      var uploadMethod = $('input[name="upload_method"]:checked').val();
      var hasUploadedFile = $('#uploaded-file-path').val();
      var hasServerFile = $('#server-file-select').val();
      var hasTree = $('#tree1').val();

      // Check if file is selected based on upload method
      if (uploadMethod === 'computer') {
        if (!hasUploadedFile) {
          alert('Please upload a GEDCOM file or select the server option.');
          e.preventDefault();
          return false;
        }
      } else if (uploadMethod === 'server') {
        if (!hasServerFile) {
          alert('Please select a GEDCOM file from the server.');
          e.preventDefault();
          return false;
        }
      }

      if (!hasTree) {
        alert('Please select a destination tree.');
        e.preventDefault();
        return false;
      }

      // Show processing message
      if (hasUploadedFile || hasServerFile) {
        $('input[type="submit"]').prop('disabled', true).val('Processing...');
        $('.wrap').prepend('<div class="notice notice-info"><p><strong>Processing:</strong> Importing GEDCOM data. This may take several minutes for large files...</p></div>');
      }

      return true;
    });
  });
</script>
