/**
 * Import/Export JavaScript functionality
 * Based on TNG admin.js and dataimport.js
 */

// Global variables for import/export
var opening = "Opening file...";
var uploading = "Uploading...";
var peoplelbl = "People";
var familieslbl = "Families";
var sourceslbl = "Sources";
var noteslbl = "Notes";
var medialbl = "Media";
var placeslbl = "Places";
var stopmsg = "Stop";
var stoppedmsg = "Stopped";
var resumemsg = "Resume";
var reopenmsg = "Reopen";
var saveimport = "1";
var selectimportfile = "Please select an import file.";
var selectdesttree = "Please select a destination tree.";
var entertreeid = "Please enter a tree ID.";
var alphanum = "Tree ID must be alphanumeric.";
var entertreename = "Please enter a tree name.";
var confdeletefile = "Are you sure you want to delete this file?";
var finished_msg = "Finished importing!";
var importing_msg = "Importing GEDCOM...";
var removeged_msg = "Remove GEDCOM";
var close_msg = "Close Window";
var more_options = "More Options";

var branches = new Array();
var branchcounts = new Array();

/**
 * Check file selection before form submission
 */
function checkFile(form) {
  // Check if file is selected
  var remoteFile = form.remotefile;
  var databaseFile = form.database;

  if (
    (!remoteFile || !remoteFile.value) &&
    (!databaseFile || !databaseFile.value)
  ) {
    alert(selectimportfile);
    return false;
  }

  // Check if tree is selected (unless creating new)
  var tree = form.tree1;
  if (tree && !tree.value) {
    alert(selectdesttree);
    return false;
  }

  return true;
}

/**
 * Toggle import/export sections
 */
function toggleSections(eventsOnly) {
  var sections = ["desttree", "replace", "ioptions"];
  for (var i = 0; i < sections.length; i++) {
    var element = document.getElementById(sections[i]);
    if (element) {
      element.style.display = eventsOnly ? "none" : "";
    }
  }
}

/**
 * Toggle no recalculation options
 */
function toggleNorecalcdiv(show) {
  var div = document.getElementById("norecalcdiv");
  if (div) {
    div.style.display = show ? "" : "none";
  }
}

/**
 * Toggle append options
 */
function toggleAppenddiv(show) {
  var div = document.getElementById("appenddiv");
  if (div) {
    div.style.display = show ? "" : "none";
  }
}

/**
 * Toggle form target for legacy import
 */
function toggleTarget(form) {
  if (form.old && form.old.checked) {
    form.target = "results";
    var iframe = document.getElementById("results");
    if (iframe) {
      iframe.style.display = "block";
      iframe.height = "300";
      iframe.width = "100%";
    }
  } else {
    form.target = "";
    var iframe = document.getElementById("results");
    if (iframe) {
      iframe.style.display = "none";
    }
  }
}

/**
 * Get branches for selected tree
 */
function getBranches(selectElement, selectedIndex) {
  var tree = selectElement.value;
  var branchSelect =
    document.getElementById("branch1") || document.getElementById("branch");

  if (branchSelect) {
    // Clear existing options
    branchSelect.innerHTML = '<option value="">All branches</option>';

    // Show/hide branch selection
    var branchRow = document.getElementById("destbranch");
    if (branchRow) {
      branchRow.style.display = tree ? "" : "none";
    }

    // Load branches via AJAX if tree is selected
    if (tree && typeof jQuery !== "undefined") {
      jQuery.post(
        ajaxurl,
        {
          action: "hp_get_branches",
          tree: tree,
          nonce: hp_admin.nonce,
        },
        function (response) {
          if (response.success && response.data) {
            var options = '<option value="">All branches</option>';
            for (var i = 0; i < response.data.length; i++) {
              var branch = response.data[i];
              options +=
                '<option value="' +
                branch.branch +
                '">' +
                branch.description +
                "</option>";
            }
            branchSelect.innerHTML = options;
          }
        }
      );
    }
  }
}

/**
 * Swap branches for export tree selection
 */
function swapBranches(form) {
  if (form.tree) {
    getBranches(form.tree, form.tree.selectedIndex);
  }
}

/**
 * Toggle media export options
 */
function toggleStuff() {
  var exportMedia = document.getElementById("exportmedia");
  var exportMediaFiles = document.getElementById("exportmediafiles");
  var expRows = document.getElementById("exprows");

  if (exportMedia && exportMediaFiles && expRows) {
    if (exportMedia.checked) {
      exportMediaFiles.disabled = false;
      expRows.style.display = "block";
    } else {
      exportMediaFiles.disabled = true;
      exportMediaFiles.checked = false;
      expRows.style.display = "none";
    }
  }
}

/**
 * Handle iframe load for import progress
 */
function iframeLoaded() {
  console.log("Import iframe loaded");
  // Additional progress handling can be added here
}

/**
 * Run post-import utility
 */
function runPostImportUtility(utility) {
  if (confirm("Run post-import utility: " + utility + "?")) {
    var form = document.createElement("form");
    form.method = "POST";
    form.action = window.location.href;

    // Add nonce
    var nonceField = document.createElement("input");
    nonceField.type = "hidden";
    nonceField.name = "_wpnonce";
    nonceField.value = hp_admin.nonce;
    form.appendChild(nonceField);

    // Add action
    var actionField = document.createElement("input");
    actionField.type = "hidden";
    actionField.name = "secaction";
    actionField.value = utility;
    form.appendChild(actionField);

    // Add tree
    var treeSelect = document.getElementById("treequeryselect");
    if (treeSelect) {
      var treeField = document.createElement("input");
      treeField.type = "hidden";
      treeField.name = "tree";
      treeField.value = treeSelect.value;
      form.appendChild(treeField);
    }

    document.body.appendChild(form);
    form.submit();
  }
}

/**
 * File picker placeholder function
 */
function FilePicker(inputId, type) {
  alert(
    "File picker functionality will be implemented. For now, please type the file path manually."
  );
}

/**
 * Initialize import/export interface
 */
jQuery(document).ready(function ($) {
  // Initialize form validation
  $("#gedcom-import-form").on("submit", function (e) {
    if (!checkFile(this)) {
      e.preventDefault();
      return false;
    }
  });

  // Initialize branch loading for single tree
  var treeSelect = $("#tree1");
  if (treeSelect.length && treeSelect.find("option").length === 2) {
    // Auto-select if only one tree
    treeSelect.prop("selectedIndex", 1);
    getBranches(treeSelect[0], 1);
  }

  // Initialize export tree selection
  var exportTreeSelect = $("#treeselect");
  if (exportTreeSelect.length && exportTreeSelect.find("option").length === 1) {
    getBranches(exportTreeSelect[0], 0);
  }

  // Post-import utility links
  $(".secutil").on("click", function (e) {
    e.preventDefault();
    var utility = $(this).text();
    runPostImportUtility(utility);
  });

  // Initialize toggle states
  var defimpopt = 0; // Default import option
  toggleNorecalcdiv(defimpopt);
  toggleAppenddiv(defimpopt == 3);
});
