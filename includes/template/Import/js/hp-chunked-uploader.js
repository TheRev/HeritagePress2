/**
 * Chunked GEDCOM Uploader
 * HeritagePress Plugin - File Upload Module
 */

/**
 * Chunked GEDCOM Uploader Class
 */
class ChunkedGedcomUploader {
  constructor(options = {}) {
    this.file = null;
    this.chunkSize = options.chunkSize || 2 * 1024 * 1024; // 2MB chunks
    this.maxRetries = options.maxRetries || 3;
    this.progressCallback = options.onProgress || function () {};
    this.completeCallback = options.onComplete || function () {};
    this.errorCallback = options.onError || function () {};
    this.speedCallback = options.onSpeed || function () {};

    this.totalChunks = 0;
    this.currentChunk = 0;
    this.uploadId = "";
    this.startTime = 0;
    this.uploadedBytes = 0;
    this.cancelled = false;
    this.retryCount = 0;
  }

  /**
   * Start file upload
   * @param {File} file - File to upload
   */
  upload(file) {
    this.file = file;
    this.totalChunks = Math.ceil(file.size / this.chunkSize);
    this.currentChunk = 0;
    this.uploadId = this.generateUploadId();
    this.startTime = Date.now();
    this.uploadedBytes = 0;
    this.cancelled = false;
    this.retryCount = 0;

    this.uploadNextChunk();
  }

  /**
   * Cancel ongoing upload
   */
  cancel() {
    this.cancelled = true;

    // Send cancel request to server
    jQuery.ajax({
      url: hp_ajax.ajax_url,
      type: "POST",
      data: {
        action: "hp_cancel_upload",
        nonce: hp_ajax.nonce,
        upload_id: this.uploadId,
      },
    });
  }

  /**
   * Upload next chunk in sequence
   */
  uploadNextChunk() {
    if (this.cancelled) {
      this.errorCallback("Upload cancelled");
      return;
    }

    if (this.currentChunk >= this.totalChunks) {
      this.finalizeUpload();
      return;
    }

    const start = this.currentChunk * this.chunkSize;
    const end = Math.min(start + this.chunkSize, this.file.size);
    const chunk = this.file.slice(start, end);

    const formData = new FormData();
    formData.append("action", "hp_upload_gedcom_chunk");
    formData.append("nonce", hp_ajax.nonce);
    formData.append("chunk", chunk);
    formData.append("chunk_number", this.currentChunk);
    formData.append("total_chunks", this.totalChunks);
    formData.append("upload_id", this.uploadId);
    formData.append("filename", this.file.name);

    const chunkStartTime = Date.now();

    jQuery.ajax({
      url: hp_ajax.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      timeout: 30000, // 30 second timeout
      success: (response) => {
        if (this.cancelled) return;

        if (response.success) {
          this.currentChunk++;
          this.uploadedBytes += chunk.size;
          this.retryCount = 0;

          // Calculate progress and speed
          const progress = (this.currentChunk / this.totalChunks) * 100;
          const elapsed = (Date.now() - this.startTime) / 1000;
          const speed = this.uploadedBytes / elapsed; // bytes per second
          const remaining = (this.file.size - this.uploadedBytes) / speed;

          this.progressCallback(progress);
          this.speedCallback({
            speed: speed,
            remaining: remaining,
            uploaded: this.uploadedBytes,
            total: this.file.size,
          });

          this.uploadNextChunk();
        } else {
          this.handleUploadError(response.data || "Upload failed");
        }
      },
      error: (xhr, status, error) => {
        if (this.cancelled) return;
        this.handleUploadError(`Upload error: ${error}`);
      },
    });
  }

  /**
   * Handle upload error with retry logic
   * @param {string} error - Error message
   */
  handleUploadError(error) {
    this.retryCount++;

    if (this.retryCount <= this.maxRetries) {
      // Retry after a delay
      setTimeout(() => {
        this.uploadNextChunk();
      }, 1000 * this.retryCount);
    } else {
      this.errorCallback(error);
    }
  }

  /**
   * Finalize upload after all chunks are sent
   */
  finalizeUpload() {
    jQuery.ajax({
      url: hp_ajax.ajax_url,
      type: "POST",
      data: {
        action: "hp_finalize_gedcom_upload",
        nonce: hp_ajax.nonce,
        upload_id: this.uploadId,
        filename: this.file.name,
      },
      success: (response) => {
        if (response.success) {
          this.completeCallback(response.data);
        } else {
          this.errorCallback(response.data || "Finalization failed");
        }
      },
      error: (xhr, status, error) => {
        this.errorCallback(`Finalization error: ${error}`);
      },
    });
  }

  /**
   * Generate unique upload ID
   * @returns {string} - Unique upload identifier
   */
  generateUploadId() {
    return (
      "upload_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9)
    );
  }
}

/**
 * Initialize chunked upload interface
 */
function initChunkedUpload() {
  // Tab switching functionality
  const tabButtons = document.querySelectorAll(".method-tab-button");
  const tabContents = document.querySelectorAll(".upload-tab-content");
  const radioInputs = document.querySelectorAll('input[name="upload_method"]');

  tabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const method = this.dataset.method;

      // Update tab appearances
      tabButtons.forEach((btn) => {
        btn.classList.remove("active");
        btn.setAttribute("aria-selected", "false");
        btn.setAttribute("tabindex", "-1");
      });

      this.classList.add("active");
      this.setAttribute("aria-selected", "true");
      this.setAttribute("tabindex", "0");

      // Update content visibility
      tabContents.forEach((content) => {
        content.style.display = "none";
      });

      const targetContent = document.getElementById(method + "-upload-tab");
      if (targetContent) {
        targetContent.style.display = "block";
      }

      // Update radio button
      radioInputs.forEach((radio) => {
        radio.checked = radio.value === method;
      });
    });
  });

  // File input and upload button functionality
  const fileInput = document.getElementById("gedcom-file-input");
  const browseButton = document.getElementById("browse-button");
  const selectedFileDisplay = document.getElementById("selected-file-display");

  // Browse button click
  if (browseButton && fileInput) {
    browseButton.addEventListener("click", function (e) {
      e.preventDefault();
      fileInput.click();
    });
  }

  // File input change
  if (fileInput) {
    fileInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        handleFileSelection(file);
      } else {
        // Hide file information if no file selected
        if (selectedFileDisplay) {
          selectedFileDisplay.style.display = "none";
        }
      }
    });
  }

  // Cancel upload
  jQuery("#cancel-upload").click(function () {
    if (currentUploader) {
      currentUploader.cancel();
      resetUploadInterface();
    }
  });

  // Remove uploaded file
  jQuery("#remove-file").click(function () {
    resetUploadInterface();
    jQuery("#uploaded-file-path").val("");
  });

  // Server file selection
  jQuery("#server-file-select").change(function () {
    const selectedFile = jQuery(this).val();
    const selectedOption = jQuery(this).find("option:selected");

    if (selectedFile) {
      const size = selectedOption.data("size");
      const sizeMB = (size / 1024 / 1024).toFixed(2);

      jQuery("#server-file-name").text(selectedFile);
      jQuery("#server-file-stats").html(`Size: ${sizeMB} MB`);
      jQuery("#server-file-info").show();
    } else {
      jQuery("#server-file-info").hide();
    }
  });

  // Refresh server files
  jQuery("#refresh-server-files").click(function () {
    refreshServerFiles();
  });
}

/**
 * Reset upload interface to initial state
 */
function resetUploadInterface() {
  const selectedFileDisplay = document.getElementById("selected-file-display");
  const fileInput = document.getElementById("gedcom-file-input");
  const uploadedFilePathInput = document.getElementById("uploaded-file-path");
  const selectedUploadMethod = document.getElementById(
    "selected-upload-method"
  );
  const serverFileSelect = document.getElementById("server-file-select");

  if (selectedFileDisplay) {
    selectedFileDisplay.style.display = "none";
  }
  if (fileInput) {
    fileInput.value = "";
  }
  if (uploadedFilePathInput) {
    uploadedFilePathInput.value = "";
  }
  if (selectedUploadMethod) {
    selectedUploadMethod.value = "";
  }
  if (serverFileSelect) {
    serverFileSelect.value = "";
  }

  // Cancel any active uploader
  if (currentUploader) {
    currentUploader.cancel();
    currentUploader = null;
  }
}

// Export for use in other modules
window.ChunkedGedcomUploader = ChunkedGedcomUploader;
window.initChunkedUpload = initChunkedUpload;
window.resetUploadInterface = resetUploadInterface;
