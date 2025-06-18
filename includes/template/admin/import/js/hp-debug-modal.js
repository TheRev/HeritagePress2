/**
 * Debug helper for Add Tree Modal
 * Call this in browser console: debugAddTreeModal()
 */
function debugAddTreeModal() {
  console.log("=== Add Tree Modal Debug Info ===");

  // Check if modal exists
  const modal = jQuery("#add-tree-modal");
  console.log("Modal element found:", modal.length > 0);

  // Check if button exists
  const button = jQuery("#create-tree-btn");
  console.log("Create button found:", button.length > 0);

  // Check if hp_ajax is available
  console.log("hp_ajax available:", typeof hp_ajax !== "undefined");
  if (typeof hp_ajax !== "undefined") {
    console.log("hp_ajax.ajax_url:", hp_ajax.ajax_url);
    console.log("hp_ajax.nonce:", hp_ajax.nonce);
  }

  // Check if jQuery is available
  console.log("jQuery version:", jQuery ? jQuery.fn.jquery : "Not available");

  // Check if all required functions are available
  const functions = [
    "openAddTreeModal",
    "closeAddTreeModal",
    "submitNewTree",
    "validateTreeForm",
    "initializeAddTreeModal",
  ];

  functions.forEach((func) => {
    console.log(
      `Function ${func} available:`,
      typeof window[func] === "function"
    );
  });

  // Test modal opening
  console.log("Testing modal open...");
  try {
    openAddTreeModal();
    console.log("Modal opened successfully");
  } catch (e) {
    console.error("Error opening modal:", e);
  }

  console.log("=== End Debug Info ===");
}

// Make available globally
window.debugAddTreeModal = debugAddTreeModal;
