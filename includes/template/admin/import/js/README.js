/**
 * HeritagePress Import/Export JavaScript Modules
 *
 * This directory contains the modularized JavaScript files for the HeritagePress
 * import/export functionality. The original monolithic import-export.js file
 * (1,608 lines) has been refactored into these smaller, focused modules.
 *
 * Load Order:
 * 1. hp-import-constants.js    - Base utilities and constants
 * 2. hp-chunked-uploader.js    - File upload handling
 * 3. hp-file-selection.js      - Modern file selection UI
 * 4. hp-form-validation.js     - Form validation and controls
 * 5. hp-add-tree-modal.js      - Tree management functionality
 * 6. hp-import-main.js         - Main coordinator and initialization
 *
 * Each module is self-contained and exports its functions to the global
 * window object for backwards compatibility with existing inline handlers.
 *
 * For detailed documentation, see: /docs/JS-MODULARIZATION-COMPLETE.md
 */

console.log("HeritagePress Import/Export Modules Directory");
console.log("See /docs/JS-MODULARIZATION-COMPLETE.md for details");
