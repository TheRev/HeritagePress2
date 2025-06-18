/**
 * Main Import/Export Module
 * HeritagePress Plugin - Main Coordinator
 *
 * This module coordinates all import/export functionality and initializes
 * all the modular components.
 */

/**
 * Initialize all import/export functionality
 */
function initializeImportExport() {
  console.log("Initializing HeritagePress Import/Export modules...");

  try {
    // Initialize form validation and controls
    if (typeof initializeFormValidation === "function") {
      initializeFormValidation();
      console.log("✓ Form validation initialized");
    }

    // Initialize chunked upload functionality
    if (typeof initChunkedUpload === "function") {
      initChunkedUpload();
      console.log("✓ Chunked upload initialized");
    }

    // Initialize the new two-button file selection interface
    if (typeof initializeFileSelectionButtons === "function") {
      initializeFileSelectionButtons();
      console.log("✓ File selection interface initialized");
    }

    // Initialize upload tabs
    if (typeof initializeUploadTabs === "function") {
      initializeUploadTabs();
      console.log("✓ Upload tabs initialized");
    }

    // Initialize keyboard navigation
    if (typeof initializeTabKeyboardNavigation === "function") {
      initializeTabKeyboardNavigation();
      console.log("✓ Keyboard navigation initialized");
    }

    // Initialize Add Tree Modal
    if (typeof initializeAddTreeModal === "function") {
      initializeAddTreeModal();
      console.log("✓ Add Tree Modal initialized");
    }

    console.log(
      "All HeritagePress Import/Export modules initialized successfully!"
    );
  } catch (error) {
    console.error("Error initializing Import/Export modules:", error);
  }
}

/**
 * Document ready initialization
 */
jQuery(document).ready(function () {
  console.log("HeritagePress Import/Export: Document ready");

  // Initialize all modules
  initializeImportExport();

  // Legacy compatibility - maintain backward compatibility
  initializeLegacySupport();
});

/**
 * Initialize legacy support for backwards compatibility
 */
function initializeLegacySupport() {
  // Ensure global functions are available for inline onclick handlers
  window.openAddTreeModal = openAddTreeModal;
  window.checkFile = checkFile;
  window.toggleSections = toggleSections;
  window.toggleNorecalcdiv = toggleNorecalcdiv;
  window.toggleAppenddiv = toggleAppenddiv;
  window.toggleTarget = toggleTarget;
  window.getBranches = getBranches;
  window.swapBranches = swapBranches;
  window.toggleStuff = toggleStuff;
  window.FilePicker = FilePicker;
  window.runPostImportUtility = runPostImportUtility;
  window.iframeLoaded = iframeLoaded;

  // Ensure classes are available globally
  window.ChunkedGedcomUploader = ChunkedGedcomUploader;

  console.log("✓ Legacy support initialized");
}

/**
 * Module health check - verify all dependencies are loaded
 * @returns {Object} - Health check results
 */
function moduleHealthCheck() {
  const results = {
    status: "healthy",
    modules: {},
    errors: [],
  };

  const requiredFunctions = [
    "initializeFormValidation",
    "initChunkedUpload",
    "initializeFileSelectionButtons",
    "initializeUploadTabs",
    "initializeTabKeyboardNavigation",
    "initializeAddTreeModal",
    "formatFileSize",
    "showUploadMessage",
  ];

  const requiredClasses = ["ChunkedGedcomUploader"];

  // Check functions
  requiredFunctions.forEach((funcName) => {
    if (typeof window[funcName] === "function") {
      results.modules[funcName] = "loaded";
    } else {
      results.modules[funcName] = "missing";
      results.errors.push(`Function ${funcName} is not available`);
      results.status = "unhealthy";
    }
  });

  // Check classes
  requiredClasses.forEach((className) => {
    if (typeof window[className] === "function") {
      results.modules[className] = "loaded";
    } else {
      results.modules[className] = "missing";
      results.errors.push(`Class ${className} is not available`);
      results.status = "unhealthy";
    }
  });

  // Check jQuery availability
  if (typeof jQuery === "undefined") {
    results.errors.push("jQuery is not available");
    results.status = "critical";
  } else {
    results.modules["jQuery"] = "loaded";
  }

  // Check AJAX configuration
  if (typeof hp_ajax === "undefined") {
    results.errors.push("hp_ajax configuration is not available");
    results.status = "unhealthy";
  } else {
    results.modules["hp_ajax"] = "loaded";
  }

  return results;
}

/**
 * Debug function to check module status
 */
function debugModuleStatus() {
  const health = moduleHealthCheck();
  console.log("Module Health Check:", health);

  if (health.status !== "healthy") {
    console.warn("Some modules are not functioning properly:");
    health.errors.forEach((error) => console.warn("- " + error));
  }

  return health;
}

// Export main functions
window.initializeImportExport = initializeImportExport;
window.moduleHealthCheck = moduleHealthCheck;
window.debugModuleStatus = debugModuleStatus;
