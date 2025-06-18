<?php

/**
 * Association Management Admin Template
 *
 * Provides interface for managing associations between people and families
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="wrap heritagepress-associations">
  <h1><?php _e('Person Associations', 'heritagepress'); ?></h1>

  <div class="association-management">
    <div class="association-form-container">
      <h2><?php _e('Add New Association', 'heritagepress'); ?></h2>

      <form id="add-association-form" class="association-form">
        <?php wp_nonce_field('hp_association_nonce', 'nonce'); ?>

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="gedcom"><?php _e('Tree', 'heritagepress'); ?></label>
              </th>
              <td>
                <select id="gedcom" name="gedcom" required>
                  <option value=""><?php _e('Select Tree', 'heritagepress'); ?></option>
                  <?php
                  // Get available trees
                  global $wpdb;
                  $trees_table = $wpdb->prefix . 'hp_trees';
                  $trees = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY tree_name");
                  foreach ($trees as $tree) {
                    echo '<option value="' . esc_attr($tree->gedcom) . '">' .
                      esc_html($tree->tree_name) . '</option>';
                  }
                  ?>
                </select>
                <p class="description"><?php _e('Select the genealogy tree for this association.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="person_id"><?php _e('Main Person ID', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="person_id" name="person_id" class="regular-text" required>
                <button type="button" id="search-person" class="button"><?php _e('Search', 'heritagepress'); ?></button>
                <p class="description"><?php _e('Enter the ID of the main person for this association.', 'heritagepress'); ?></p>
                <div id="person-search-results" class="search-results"></div>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="associated_id"><?php _e('Associated Person/Family ID', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="associated_id" name="associated_id" class="regular-text" required>
                <button type="button" id="search-associated" class="button"><?php _e('Search', 'heritagepress'); ?></button>
                <p class="description"><?php _e('Enter the ID of the person or family to associate with.', 'heritagepress'); ?></p>
                <div id="associated-search-results" class="search-results"></div>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="rel_type"><?php _e('Association Type', 'heritagepress'); ?></label>
              </th>
              <td>
                <select id="rel_type" name="rel_type" required>
                  <option value="I"><?php _e('Individual', 'heritagepress'); ?></option>
                  <option value="F"><?php _e('Family', 'heritagepress'); ?></option>
                </select>
                <p class="description"><?php _e('Select whether associating with an individual person or a family.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="relationship"><?php _e('Relationship Description', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="relationship" name="relationship" class="regular-text" required placeholder="<?php _e('e.g., Godparent, Witness, Friend', 'heritagepress'); ?>">
                <p class="description"><?php _e('Describe the relationship between the people/families.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="create_reverse"><?php _e('Create Reverse Association', 'heritagepress'); ?></label>
              </th>
              <td>
                <label>
                  <input type="checkbox" id="create_reverse" name="create_reverse" value="1">
                  <?php _e('Also create the reverse association', 'heritagepress'); ?>
                </label>
                <p class="description"><?php _e('If checked, also creates an association from the associated person back to the main person.', 'heritagepress'); ?></p>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="submit">
          <button type="submit" class="button button-primary"><?php _e('Add Association', 'heritagepress'); ?></button>
          <button type="button" id="reset-form" class="button"><?php _e('Reset Form', 'heritagepress'); ?></button>
        </p>
      </form>
    </div>

    <div class="association-list-container">
      <h2><?php _e('Existing Associations', 'heritagepress'); ?></h2>

      <div class="association-filters">
        <label for="filter-tree"><?php _e('Filter by Tree:', 'heritagepress'); ?></label>
        <select id="filter-tree" name="filter-tree">
          <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
          <?php
          foreach ($trees as $tree) {
            echo '<option value="' . esc_attr($tree->gedcom) . '">' .
              esc_html($tree->tree_name) . '</option>';
          }
          ?>
        </select>

        <label for="filter-person"><?php _e('Filter by Person:', 'heritagepress'); ?></label>
        <input type="text" id="filter-person" name="filter-person" placeholder="<?php _e('Enter Person ID', 'heritagepress'); ?>">

        <button type="button" id="load-associations" class="button"><?php _e('Load Associations', 'heritagepress'); ?></button>
      </div>

      <div id="associations-table-container">
        <p><?php _e('Select a tree and/or person to view associations.', 'heritagepress'); ?></p>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Add association form submission
    $('#add-association-form').on('submit', function(e) {
      e.preventDefault();

      var formData = {
        action: 'hp_add_association',
        nonce: $('#nonce').val(),
        gedcom: $('#gedcom').val(),
        person_id: $('#person_id').val(),
        associated_id: $('#associated_id').val(),
        relationship: $('#relationship').val(),
        rel_type: $('#rel_type').val(),
        create_reverse: $('#create_reverse').is(':checked') ? 1 : 0
      };

      $.post(ajaxurl, formData, function(response) {
        if (response.success) {
          alert('<?php _e('Association added successfully!', 'heritagepress'); ?>');
          $('#add-association-form')[0].reset();
          // Reload associations if viewing same tree/person
          if ($('#filter-tree').val() === formData.gedcom &&
            ($('#filter-person').val() === formData.person_id || $('#filter-person').val() === '')) {
            loadAssociations();
          }
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    });

    // Reset form
    $('#reset-form').on('click', function() {
      $('#add-association-form')[0].reset();
      $('.search-results').empty();
    });

    // Load associations
    $('#load-associations').on('click', function() {
      loadAssociations();
    });

    // Load associations function
    function loadAssociations() {
      var gedcom = $('#filter-tree').val();
      var personId = $('#filter-person').val();

      if (!gedcom && !personId) {
        alert('<?php _e('Please select a tree or enter a person ID.', 'heritagepress'); ?>');
        return;
      }

      var data = {
        action: 'hp_get_person_associations',
        nonce: '<?php echo wp_create_nonce('hp_association_nonce'); ?>',
        gedcom: gedcom,
        person_id: personId
      };

      $.post(ajaxurl, data, function(response) {
        if (response.success) {
          displayAssociations(response.data.associations);
        } else {
          $('#associations-table-container').html('<p class="error">' + response.data + '</p>');
        }
      });
    }

    // Display associations table
    function displayAssociations(associations) {
      if (associations.length === 0) {
        $('#associations-table-container').html('<p><?php _e('No associations found.', 'heritagepress'); ?></p>');
        return;
      }

      var html = '<table class="wp-list-table widefat fixed striped">';
      html += '<thead><tr>';
      html += '<th><?php _e('ID', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Person', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Associated With', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Relationship', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Type', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Tree', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Actions', 'heritagepress'); ?></th>';
      html += '</tr></thead><tbody>';

      associations.forEach(function(assoc) {
        html += '<tr>';
        html += '<td>' + assoc.id + '</td>';
        html += '<td>' + assoc.person_id + '</td>';
        html += '<td>' + assoc.display_name + '</td>';
        html += '<td>' + assoc.relationship + '</td>';
        html += '<td>' + (assoc.rel_type === 'I' ? '<?php _e('Individual', 'heritagepress'); ?>' : '<?php _e('Family', 'heritagepress'); ?>') + '</td>';
        html += '<td>' + assoc.tree + '</td>';
        html += '<td><button type="button" class="delete-association button button-small" data-id="' + assoc.id + '"><?php _e('Delete', 'heritagepress'); ?></button></td>';
        html += '</tr>';
      });

      html += '</tbody></table>';
      $('#associations-table-container').html(html);
    }

    // Delete association
    $(document).on('click', '.delete-association', function() {
      if (!confirm('<?php _e('Are you sure you want to delete this association?', 'heritagepress'); ?>')) {
        return;
      }

      var associationId = $(this).data('id');
      var data = {
        action: 'hp_delete_association',
        nonce: '<?php echo wp_create_nonce('hp_association_nonce'); ?>',
        association_id: associationId
      };

      $.post(ajaxurl, data, function(response) {
        if (response.success) {
          alert('<?php _e('Association deleted successfully!', 'heritagepress'); ?>');
          loadAssociations();
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    });

    // Person search functionality (basic implementation)
    $('#search-person, #search-associated').on('click', function() {
      var inputId = $(this).prev('input').attr('id');
      var resultsId = $(this).siblings('.search-results').attr('id');
      alert('<?php _e('Person search functionality would be implemented here.', 'heritagepress'); ?>');
      // TODO: Implement person search AJAX
    });
  });
</script>

<style>
  .heritagepress-associations .association-management {
    display: flex;
    gap: 30px;
    margin-top: 20px;
  }

  .association-form-container,
  .association-list-container {
    flex: 1;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .association-form-container h2,
  .association-list-container h2 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
  }

  .search-results {
    max-height: 150px;
    overflow-y: auto;
    border: 1px solid #ddd;
    background: #f9f9f9;
    padding: 10px;
    margin-top: 5px;
    display: none;
  }

  .search-results.has-results {
    display: block;
  }

  .association-filters {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
  }

  .association-filters label {
    margin-right: 10px;
    font-weight: bold;
  }

  .association-filters select,
  .association-filters input[type="text"] {
    margin-right: 15px;
  }

  #associations-table-container {
    min-height: 200px;
  }

  .error {
    color: #d63638;
    font-weight: bold;
  }
</style>
