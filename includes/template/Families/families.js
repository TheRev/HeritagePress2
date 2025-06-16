/**
 * Families Section JavaScript
 * Interactive functionality for family management interfaces
 */

(function($) {
    'use strict';

    // Global variables
    window.HP_Families = {
        currentPersonField: '',
        currentPlaceField: '',
        currentSourceField: '',
        currentFamilyField: '',
        ajaxUrl: heritagepress_ajax.ajax_url,
        nonce: heritagepress_ajax.nonce
    };

    // Initialize when document is ready
    $(document).ready(function() {
        initializeFamilies();
    });

    /**
     * Initialize all family functionality
     */
    function initializeFamilies() {
        // Initialize form validation
        initializeFormValidation();
        
        // Initialize AJAX handlers
        initializeAjaxHandlers();
        
        // Initialize modal handlers
        initializeModalHandlers();
        
        // Initialize bulk actions
        initializeBulkActions();
        
        // Initialize sorting and pagination
        initializeSortingPagination();
        
        // Initialize search functionality
        initializeSearchFunctionality();
        
        // Auto-generate family ID on page load if needed
        if ($('#familyID').length && !$('#familyID').val()) {
            generateFamilyID();
        }
    }

    /**
     * Form validation for add/edit family forms
     */
    function initializeFormValidation() {
        // Family form validation
        $('#add-family-form, #edit-family-form').on('submit', function(e) {
            if (!validateFamilyForm(this)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Real-time Family ID checking
        $('#familyID').on('blur', function() {
            checkFamilyID();
        });
        
        // Tree change handlers
        $('#gedcom').on('change', function() {
            updateBranches($(this).val());
            generateFamilyID();
        });
    }

    /**
     * Validate family form before submission
     */
    function validateFamilyForm(form) {
        var $form = $(form);
        var familyID = $form.find('#familyID').val().trim();
        var husband = $form.find('#husband').val();
        var wife = $form.find('#wife').val();
        
        // Clear previous errors
        $form.find('.error-message').remove();
        
        // Check Family ID
        if (!familyID) {
            showFieldError('#familyID', 'Please enter a Family ID.');
            return false;
        }
        
        // Check at least one spouse
        if (!husband && !wife) {
            showFieldError('#husband_display', 'Please select at least one spouse.');
            return false;
        }
        
        return true;
    }

    /**
     * Show field error message
     */
    function showFieldError(fieldSelector, message) {
        var $field = $(fieldSelector);
        var $error = $('<span class="error-message" style="color: #d63384; font-size: 12px; display: block; margin-top: 5px;">' + message + '</span>');
        $field.after($error);
        $field.focus();
    }

    /**
     * Generate unique Family ID
     */
    window.generateFamilyID = function() {
        var tree = $('#gedcom').val();
        if (!tree) return;
        
        $.ajax({
            url: HP_Families.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hp_generate_family_id',
                tree: tree,
                nonce: HP_Families.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#familyID').val(response.data.familyID);
                    checkFamilyID();
                } else {
                    console.error('Error generating Family ID:', response.data.message);
                }
            },
            error: function() {
                console.error('AJAX error generating Family ID');
            }
        });
    };

    /**
     * Check Family ID availability
     */
    window.checkFamilyID = function() {
        var familyID = $('#familyID').val().trim();
        var tree = $('#gedcom').val();
        var originalID = $('#original_familyID').val(); // For edit form
        var $status = $('#family-id-status');
        
        if (!familyID || !tree || (originalID && familyID === originalID)) {
            $status.html('');
            return;
        }
        
        $status.html('<span style="color: #646970;">Checking...</span>');
        
        $.ajax({
            url: HP_Families.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hp_check_family_id',
                familyID: familyID,
                tree: tree,
                nonce: HP_Families.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.available) {
                        $status.html('<span style="color: #00a32a;">✓ Available</span>');
                    } else {
                        $status.html('<span style="color: #d63384;">✗ Already exists</span>');
                    }
                } else {
                    $status.html('<span style="color: #d63384;">Error checking</span>');
                }
            },
            error: function() {
                $status.html('<span style="color: #d63384;">Error checking</span>');
            }
        });
    };

    /**
     * Update branches when tree changes
     */
    window.updateBranches = function(tree) {
        var $container = $('#branch-container');
        
        $.ajax({
            url: HP_Families.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hp_get_tree_branches',
                tree: tree,
                nonce: HP_Families.nonce
            },
            success: function(response) {
                if (response.success) {
                    $container.html(response.data.html);
                } else {
                    console.error('Error updating branches:', response.data.message);
                }
            },
            error: function() {
                console.error('AJAX error updating branches');
            }
        });
    };

    /**
     * Person finder functionality
     */
    window.findPerson = function(field, gender) {
        HP_Families.currentPersonField = field;
        var tree = $('#gedcom').val();
        
        var $modal = $('#person-finder-modal');
        var $content = $('#person-finder-content');
        
        $content.html('<div style="text-align: center; padding: 40px;">Loading...</div>');
        $modal.show();
        
        $.ajax({
            url: HP_Families.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hp_person_finder',
                tree: tree,
                gender: gender,
                nonce: HP_Families.nonce
            },
            success: function(response) {
                $content.html(response);
            },
            error: function() {
                $content.html('<div style="text-align: center; padding: 40px; color: #d63384;">Error loading person finder</div>');
            }
        });
    };

    /**
     * Select person from finder
     */
    window.selectPerson = function(personID, personName) {
        $('#' + HP_Families.currentPersonField).val(personID);
        $('#' + HP_Families.currentPersonField + '_display').val(personName + ' - ' + personID);
        closePersonFinder();
    };

    /**
     * Create new person
     */
    window.createPerson = function(field, gender) {
        var tree = $('#gedcom').val();
        var returnPage = 'families';
        if ($('#edit-family-form').length) {
            returnPage += '&tab=edit&familyID=' + encodeURIComponent($('#original_familyID').val()) + '&tree=' + encodeURIComponent(tree);
        }
        var url = 'admin.php?page=heritagepress-people&tab=add&tree=' + encodeURIComponent(tree) + '&gender=' + encodeURIComponent(gender) + '&return_to=' + returnPage;
        window.open(url, '_blank');
    };

    /**
     * Edit existing person
     */
    window.editPerson = function(field) {
        var personID = $('#' + field).val();
        var tree = $('#gedcom').val();
        
        if (!personID) {
            alert('No person selected to edit.');
            return;
        }
        
        var url = 'admin.php?page=heritagepress-people&tab=edit&personID=' + encodeURIComponent(personID) + '&tree=' + encodeURIComponent(tree);
        window.open(url, '_blank');
    };

    /**
     * Remove person selection
     */
    window.removePerson = function(field) {
        $('#' + field).val('');
        $('#' + field + '_display').val('Click Find to select');
    };

    /**
     * Close person finder modal
     */
    window.closePersonFinder = function() {
        $('#person-finder-modal').hide();
    };

    /**
     * Place finder functionality
     */
    window.findPlace = function(field) {
        HP_Families.currentPlaceField = field;
        
        var $modal = $('#place-finder-modal');
        var $content = $('#place-finder-content');
        
        $content.html('<div style="text-align: center; padding: 40px;">Loading...</div>');
        $modal.show();
        
        $.ajax({
            url: HP_Families.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hp_place_finder',
                nonce: HP_Families.nonce
            },
            success: function(response) {
                $content.html(response);
            },
            error: function() {
                $content.html('<div style="text-align: center; padding: 40px; color: #d63384;">Error loading place finder</div>');
            }
        });
    };

    /**
     * Select place from finder
     */
    window.selectPlace = function(placeName) {
        $('#' + HP_Families.currentPlaceField).val(placeName);
        closePlaceFinder();
    };

    /**
     * Close place finder modal
     */
    window.closePlaceFinder = function() {
        $('#place-finder-modal').hide();
    };

    /**
     * Source finder functionality
     */
    window.findSource = function(field) {
        HP_Families.currentSourceField = field;
        
        var $modal = $('#source-finder-modal');
        var $content = $('#source-finder-content');
        
        $content.html('<div style="text-align: center; padding: 40px;">Loading...</div>');
        $modal.show();
        
        $.ajax({
            url: HP_Families.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hp_source_finder',
                nonce: HP_Families.nonce
            },
            success: function(response) {
                $content.html(response);
            },
            error: function() {
                $content.html('<div style="text-align: center; padding: 40px; color: #d63384;">Error loading source finder</div>');
            }
        });
    };

    /**
     * Select source from finder
     */
    window.selectSource = function(sourceID, sourceTitle) {
        $('#' + HP_Families.currentSourceField).val(sourceID);
        closeSourceFinder();
    };

    /**
     * Close source finder modal
     */
    window.closeSourceFinder = function() {
        $('#source-finder-modal').hide();
    };

    /**
     * Family finder functionality (for utilities)
     */
    window.findFamily = function(field) {
        HP_Families.currentFamilyField = field;
        
        var $modal = $('#family-finder-modal');
        var $content = $('#family-finder-content');
        
        $content.html('<div style="text-align: center; padding: 40px;">Loading...</div>');
        $modal.show();
        
        $.ajax({
            url: HP_Families.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hp_family_finder',
                nonce: HP_Families.nonce
            },
            success: function(response) {
                $content.html(response);
            },
            error: function() {
                $content.html('<div style="text-align: center; padding: 40px; color: #d63384;">Error loading family finder</div>');
            }
        });
    };

    /**
     * Select family from finder
     */
    window.selectFamily = function(familyID, familyDisplay) {
        $('#' + HP_Families.currentFamilyField).val(familyID);
        $('#' + HP_Families.currentFamilyField + '_display').val(familyDisplay);
        closeFamilyFinder();
    };

    /**
     * Close family finder modal
     */
    window.closeFamilyFinder = function() {
        $('#family-finder-modal').hide();
    };

    /**
     * Initialize modal handlers
     */
    function initializeModalHandlers() {
        // Close modal when clicking outside
        $(document).on('click', '.modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
        
        // Close modal with escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.modal:visible').hide();
            }
        });
    }

    /**
     * Initialize bulk actions
     */
    function initializeBulkActions() {
        // Select all checkbox
        $('#cb-select-all').on('change', function() {
            var checked = $(this).is(':checked');
            $('input[name="family_ids[]"]').prop('checked', checked);
        });
        
        // Toggle all checkboxes function
        window.toggleAllCheckboxes = function(checked) {
            $('input[name="family_ids[]"]').prop('checked', checked);
            $('#cb-select-all').prop('checked', checked);
        };
    }

    /**
     * Initialize AJAX handlers
     */
    function initializeAjaxHandlers() {
        // Handle AJAX form submissions
        $(document).on('submit', '.ajax-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submit = $form.find('input[type="submit"]');
            var originalValue = $submit.val();
            
            $submit.val('Processing...').prop('disabled', true);
            
            $.ajax({
                url: HP_Families.ajaxUrl,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else if (response.data.reload) {
                            location.reload();
                        } else {
                            showNotice(response.data.message, 'success');
                        }
                    } else {
                        showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $submit.val(originalValue).prop('disabled', false);
                }
            });
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap > h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
    }

    /**
     * Delete family with confirmation
     */
    window.deleteFamily = function(familyId, familyName) {
        if (confirm('Are you sure you want to delete family ' + familyName + '? This action cannot be undone.')) {
            $.ajax({
                url: HP_Families.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hp_delete_family',
                    family_id: familyId,
                    nonce: HP_Families.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#family-' + familyId).fadeOut(function() {
                            $(this).remove();
                        });
                        showNotice(response.data.message, 'success');
                    } else {
                        showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice('Error deleting family. Please try again.', 'error');
                }
            });
        }
    };

    /**
     * View family (placeholder)
     */
    window.viewFamily = function(familyID, tree) {
        // This would open the public family view
        alert('Family group sheet functionality to be implemented');
    };

    /**
     * Initialize search functionality
     */
    function initializeSearchFunctionality() {
        // Auto-submit search form when Enter is pressed
        $('.families-search-form input[type="text"]').on('keypress', function(e) {
            if (e.which === 13) {
                $(this).closest('form').submit();
            }
        });
    }

    /**
     * Initialize sorting and pagination
     */
    function initializeSortingPagination() {
        // Add loading indicator for sort links
        $('th a[href*="order="]').on('click', function() {
            $(this).append(' <span class="spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>');
        });
    }

    /**
     * Child management functions
     */
    window.removeChild = function(personID) {
        if (confirm('Remove this child from the family?')) {
            var familyID = $('#original_familyID').val();
            var tree = $('#gedcom').val();
            
            $.ajax({
                url: HP_Families.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hp_remove_child',
                    familyID: familyID,
                    tree: tree,
                    personID: personID,
                    nonce: HP_Families.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error removing child: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error removing child. Please try again.');
                }
            });
        }
    };

    window.addChild = function() {
        var childID = $('#new_child_id').val();
        var familyID = $('#original_familyID').val();
        var tree = $('#gedcom').val();
        
        if (!childID) {
            alert('Please select a child first.');
            return;
        }
        
        $.ajax({
            url: HP_Families.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hp_add_child',
                familyID: familyID,
                tree: tree,
                personID: childID,
                nonce: HP_Families.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error adding child: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error adding child. Please try again.');
            }
        });
    };

    window.createChild = function() {
        var tree = $('#gedcom').val();
        var familyID = $('#original_familyID').val();
        var url = 'admin.php?page=heritagepress-people&tab=add&tree=' + encodeURIComponent(tree) + '&family=' + encodeURIComponent(familyID) + '&return_to=families';
        window.open(url, '_blank');
    };

})(jQuery);
