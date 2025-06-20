<?php
// Polyfills for WordPress template functions (if needed)
if (!function_exists('esc_html')) {
  function esc_html($text)
  {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
}
if (!function_exists('esc_html_e')) {
  function esc_html_e($text)
  {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
}
if (!function_exists('esc_attr')) {
  function esc_attr($text)
  {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
}
if (!function_exists('admin_url')) {
  function admin_url($path = '')
  {
    return '/wp-admin/' . ltrim($path, '/');
  }
}
if (!function_exists('submit_button')) {
  function submit_button($text)
  {
    echo '<p class="submit"><button type="submit" class="button button-primary">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</button></p>';
  }
}
if (!function_exists('wp_create_nonce')) {
  function wp_create_nonce($action = '')
  {
    // Simple nonce polyfill for non-WP: use a hash of action and session or time
    return md5($action . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest') . date('Ymd'));
  }
}
if (!function_exists('esc_js')) {
  function esc_js($text)
  {
    // Escape for JS context
    return str_replace([
      "\\",
      "'",
      "\n",
      "\r",
      '"',
      "<",
      ">"
    ], [
      "\\\\",
      "\\'",
      "\\n",
      "\\r",
      '\\"',
      "\\x3C",
      "\\x3E"
    ], $text);
  }
}
?>
<div class="wrap">
  <h1><?php esc_html_e('Edit Family'); ?></h1>
  <div id="hp-notification" style="display:none;padding:10px;margin-bottom:10px;border-radius:4px;"></div>
  <div id="hp-loader" style="display:none;text-align:center;margin:10px 0;">
    <span class="hp-dots-loader"><span></span><span></span><span></span></span>
  </div>
  <form method="post" action="">
    <input type="hidden" name="action" value="update_family">
    <input type="hidden" name="family_id" value="<?php echo esc_attr($family['id'] ?? ''); ?>">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="husband_id"><?php esc_html_e('Husband ID'); ?></label></th>
        <td><input name="husband_id" type="text" id="husband_id" value="<?php echo esc_attr($family['husband_id'] ?? ''); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="wife_id"><?php esc_html_e('Wife ID'); ?></label></th>
        <td><input name="wife_id" type="text" id="wife_id" value="<?php echo esc_attr($family['wife_id'] ?? ''); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="marriage_date"><?php esc_html_e('Marriage Date'); ?></label></th>
        <td><input name="marriage_date" type="text" id="marriage_date" value="<?php echo esc_attr($family['marriage_date'] ?? ''); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="marriage_place"><?php esc_html_e('Marriage Place'); ?></label></th>
        <td><input name="marriage_place" type="text" id="marriage_place" value="<?php echo esc_attr($family['marriage_place'] ?? ''); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="family_id"><?php esc_html_e('Family ID'); ?></label></th>
        <td><input name="family_id" type="text" id="family_id" value="<?php echo esc_attr($family['familyID'] ?? ''); ?>" class="regular-text" required></td>
      </tr>
      <tr>
        <th scope="row"><label for="gedcom"><?php esc_html_e('Tree (GEDCOM)'); ?></label></th>
        <td><input name="gedcom" type="text" id="gedcom" value="<?php echo esc_attr($family['gedcom'] ?? ''); ?>" class="regular-text" required></td>
      </tr>
      <tr>
        <th scope="row"><label for="living"><?php esc_html_e('Living'); ?></label></th>
        <td><input name="living" type="checkbox" id="living" value="1" <?php if (!empty($family['living'])) echo 'checked'; ?> data-ajax-privacy></td>
      </tr>
      <tr>
        <th scope="row"><label for="private"><?php esc_html_e('Private'); ?></label></th>
        <td><input name="private" type="checkbox" id="private" value="1" <?php if (!empty($family['private'])) echo 'checked'; ?> data-ajax-privacy></td>
      </tr>
      <tr>
        <th scope="row"><label for="branch"><?php esc_html_e('Branch'); ?></label></th>
        <td><input name="branch" type="text" id="branch" value="<?php echo esc_attr($family['branch'] ?? ''); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="divorce_date"><?php esc_html_e('Divorce Date'); ?></label></th>
        <td><input name="divorce_date" type="text" id="divorce_date" value="<?php echo esc_attr($family['divdate'] ?? ''); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="divorce_place"><?php esc_html_e('Divorce Place'); ?></label></th>
        <td><input name="divorce_place" type="text" id="divorce_place" value="<?php echo esc_attr($family['divplace'] ?? ''); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="notes"><?php esc_html_e('Notes'); ?></label></th>
        <td><textarea name="notes" id="notes" class="large-text" rows="4"><?php echo esc_attr($family['notes'] ?? ''); ?></textarea></td>
      </tr>
      <!-- Add more fields as needed for full HeritagePress parity -->
    </table>
    <h2><?php esc_html_e('Children'); ?></h2>
    <?php
    if (isset($family['familyID'], $family['gedcom'])) {
      $controller = isset($controller) ? $controller : (class_exists('HP_Families_Controller') ? new HP_Families_Controller() : null);
      $children = $controller ? $controller->get_family_children($family['familyID'], $family['gedcom']) : array();
    } else {
      $children = array();
    }
    // Add a nonce for AJAX child order update
    $child_order_nonce = wp_create_nonce('hp_update_child_order');
    ?>
    <style>
      .hp-draggable-row {
        cursor: move;
      }

      .hp-children-table tbody tr.dragging {
        opacity: 0.5;
      }
    </style>
    <div style="margin-bottom:8px;">
      <button type="button" id="hp-remove-selected-children" class="button button-small" disabled>Remove Selected</button>
    </div>
    <table class="widefat hp-children-table" id="hp-children-table">
      <thead>
        <tr>
          <th><input type="checkbox" id="hp-select-all-children"></th>
          <th><?php esc_html_e('Person ID'); ?></th>
          <th><?php esc_html_e('Name'); ?></th>
          <th><?php esc_html_e('Birthdate'); ?></th>
          <th><?php esc_html_e('Father Rel'); ?></th>
          <th><?php esc_html_e('Mother Rel'); ?></th>
          <th><?php esc_html_e('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($children)): ?>
          <tr>
            <td colspan="7"><?php esc_html_e('No children linked to this family.'); ?></td>
          </tr>
          <?php else: foreach ($children as $child): ?>
            <tr data-person-id="<?php echo esc_attr($child['personID']); ?>" class="hp-draggable-row">
              <td><input type="checkbox" class="hp-child-checkbox"></td>
              <td><?php echo esc_html($child['personID']); ?></td>
              <td><?php echo esc_html(trim(($child['firstname'] ?? '') . ' ' . ($child['lastname'] ?? ''))); ?></td>
              <td><?php echo esc_html($child['birthdate'] ?? ''); ?></td>
              <td><?php echo esc_html($child['frel'] ?? ''); ?></td>
              <td><?php echo esc_html($child['mrel'] ?? ''); ?></td>
              <td>
                <form method="post" action="" style="display:inline;">
                  <input type="hidden" name="action" value="remove_child">
                  <input type="hidden" name="person_id" value="<?php echo esc_attr($child['personID']); ?>">
                  <input type="hidden" name="family_id" value="<?php echo esc_attr($family['familyID']); ?>">
                  <input type="hidden" name="gedcom" value="<?php echo esc_attr($family['gedcom']); ?>">
                  <button type="submit" class="button button-small">Remove</button>
                </form>
              </td>
            </tr>
        <?php endforeach;
        endif; ?>
      </tbody>
    </table>
    <input type="hidden" id="hp-child-order-nonce" value="<?php echo esc_attr($child_order_nonce); ?>">
    <script>
      // Simple HTML5 drag-and-drop for child ordering
      (function() {
        const table = document.getElementById('hp-children-table');
        if (!table) return;
        let draggingRow = null;
        let startIndex = null;
        table.querySelectorAll('tbody tr').forEach(row => {
          row.draggable = true;
          row.addEventListener('dragstart', function(e) {
            draggingRow = this;
            startIndex = Array.from(table.tBodies[0].rows).indexOf(this);
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
          });
          row.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            draggingRow = null;
          });
          row.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
          });
          row.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggingRow && draggingRow !== this) {
              const rows = Array.from(table.tBodies[0].rows);
              const dropIndex = rows.indexOf(this);
              if (startIndex < dropIndex) {
                this.after(draggingRow);
              } else {
                this.before(draggingRow);
              }
              updateChildOrder();
            }
          });
        });

        function updateChildOrder() {
          const order = Array.from(table.tBodies[0].rows).map(row => row.getAttribute('data-person-id'));
          const nonce = document.getElementById('hp-child-order-nonce').value;
          const data = {
            action: 'hp_update_child_order',
            family_id: '<?php echo esc_js($family['familyID'] ?? ''); ?>',
            gedcom: '<?php echo esc_js($family['gedcom'] ?? ''); ?>',
            order: order,
            nonce: nonce
          };
          fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(json => {
              if (json.success) {
                hpShowNotification('Updated successfully.', 'success');
              } else {
                hpShowNotification(json.data || 'Failed to update child order.', 'error');
              }
            })
            .catch(() => hpShowNotification('AJAX error updating child order.', 'error'));
        }
      })();
    </script>
    <h3><?php esc_html_e('Add Child'); ?></h3>
    <form id="hp-add-child-form">
      <input type="hidden" name="action" value="add_child">
      <input type="hidden" name="family_id" value="<?php echo esc_attr($family['familyID']); ?>">
      <input type="hidden" name="gedcom" value="<?php echo esc_attr($family['gedcom']); ?>">
      <label for="person_search">Search Person:</label>
      <input type="text" id="person_search" placeholder="Type name or ID..." autocomplete="off">
      <div id="person_search_results" style="position:relative;z-index:10;"></div>
      <label for="person_id">Person ID:</label>
      <input type="text" name="person_id" id="person_id" required>
      <label for="frel">Father Rel:</label>
      <input type="text" name="frel" id="frel">
      <label for="mrel">Mother Rel:</label>
      <input type="text" name="mrel" id="mrel">
      <button type="submit" class="button">Add Child</button>
    </form>
    <script>
      (function() {
          const addChildForm = document.getElementById('hp-add-child-form');
          const childrenTableWrapper = document.getElementById('hp-children-table').parentNode;
          if (addChildForm) {
            addChildForm.addEventListener('submit', function(e) {
                e.preventDefault();
                hpShowLoader(true);
                const data = new URLSearchParams(new FormData(addChildForm));
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: data
                  })
                  .then(r => r.json())
                  .then(json => {
                    if (json.success) {
                      // Fetch updated children table
                      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                          method: 'POST',
                          headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                          },
                          body: new URLSearchParams({
                            action: 'hp_get_children_table',
                            family_id: '<?php echo esc_js($family['familyID']); ?>',
                            gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                          })
                        })
                        .then(r2 => r2.json())
                        .then(json2 => {
                          if (json2.success && json2.html) {
                            childrenTableWrapper.innerHTML = json2.html;
                            hpShowNotification('Child added successfully.', 'success');
                          } else {
                            hpShowNotification('Child added, but failed to update table.', 'error');
                          }
                          hpShowLoader(false);
                        });
                    } else {
                      hpShowLoader(false);
                      hpShowNotification(json.data || 'Failed to add child.', 'error');
                    }
                  });
              }
            })();
    </script>
    <h2><?php esc_html_e('Sources / Citations'); ?></h2>
    <div id="hp-citations-section">
      <table class="widefat hp-citations-table" id="hp-citations-table">
        <thead>
          <tr>
            <th></th>
            <th><?php esc_html_e('Source ID'); ?></th>
            <th><?php esc_html_e('Title'); ?></th>
            <th><?php esc_html_e('Page'); ?></th>
            <th><?php esc_html_e('Description'); ?></th>
            <th><?php esc_html_e('Actions'); ?></th>
          </tr>
        </thead>
        <tbody id="hp-citations-tbody">
          <?php if (empty($citations)): ?>
            <tr>
              <td colspan="6"><?php esc_html_e('No sources/citations linked to this family.'); ?></td>
            </tr>
            <?php else: foreach ($citations as $citation): ?>
              <tr data-citation-id="<?php echo esc_attr($citation['citationID']); ?>" class="hp-draggable-citation-row">
                <td class="hp-drag-handle">&#9776;</td>
                <td class="citation-source-view"><?php echo esc_html($citation['sourceID']); ?></td>
                <td><?php echo esc_html($citation['title'] ?? ''); ?></td>
                <td class="citation-page-view"><?php echo esc_html($citation['page'] ?? ''); ?></td>
                <td class="citation-description-view"><?php echo esc_html($citation['description'] ?? ''); ?></td>
                <td>
                  <button type="button" class="button button-small hp-edit-citation">Edit</button>
                  <button type="button" class="button button-small hp-save-citation" style="display:none;">Save</button>
                  <button type="button" class="button button-small hp-remove-citation" data-citation-id="<?php echo esc_attr($citation['citationID']); ?>">Remove</button>
                </td>
              </tr>
          <?php endforeach;
          endif; ?>
        </tbody>
      </table>
      <h3><?php esc_html_e('Add Source / Citation'); ?></h3>
      <form id="hp-add-citation-form">
        <input type="hidden" name="action" value="add_citation">
        <input type="hidden" name="family_id" value="<?php echo esc_attr($family['familyID']); ?>">
        <input type="hidden" name="gedcom" value="<?php echo esc_attr($family['gedcom']); ?>">
        <label for="source_id">Source ID:</label>
        <input type="text" name="source_id" id="source_id" required>
        <label for="page">Page:</label>
        <input type="text" name="page" id="page">
        <label for="description">Description:</label>
        <input type="text" name="description" id="description">
        <button type="submit" class="button">Add Citation</button>
      </form>
    </div>
    <script>
      (function() {
          // AJAX add citation
          const addCitationForm = document.getElementById('hp-add-citation-form');
          const citationsTableWrapper = document.getElementById('hp-citations-table').parentNode;
          if (addCitationForm) {
            addCitationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                hpShowLoader(true);
                const data = new URLSearchParams(new FormData(addCitationForm));
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: data
                  })
                  .then(r => r.json())
                  .then(json => {
                    if (json.success) {
                      // Fetch updated citations table
                      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                          method: 'POST',
                          headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                          },
                          body: new URLSearchParams({
                            action: 'hp_get_citations_table',
                            family_id: '<?php echo esc_js($family['familyID']); ?>',
                            gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                          })
                        })
                        .then(r2 => r2.json())
                        .then(json2 => {
                          if (json2.success && json2.html) {
                            citationsTableWrapper.innerHTML = json2.html;
                            hpShowNotification('Citation added successfully.', 'success');
                          } else {
                            hpShowNotification('Citation added, but failed to update table.', 'error');
                          }
                          hpShowLoader(false);
                        });
                    } else {
                      hpShowLoader(false);
                      hpShowNotification(json.data || 'Failed to add citation.', 'error');
                    }
                  });
              }
              // AJAX remove citation
              document.querySelectorAll('.hp-remove-citation').forEach(function(btn) {
                btn.addEventListener('click', function() {
                  if (!confirm('Remove this citation?')) return;
                  const citationId = this.getAttribute('data-citation-id');
                  const data = new URLSearchParams({
                    action: 'remove_citation',
                    citation_id: citationId,
                    family_id: '<?php echo esc_js($family['familyID']); ?>',
                    gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                  });
                  fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                      method: 'POST',
                      headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                      },
                      body: data
                    })
                    .then(r => r.json())
                    .then(json => {
                      if (json.success) {
                        // Remove row from table
                        const row = document.querySelector('tr[data-citation-id="' + citationId + '"]');
                        if (row) row.remove();
                      } else {
                        hpShowNotification(json.data || 'Failed to remove citation.', 'error');
                      }
                    });
                });
              });
              // Inline edit for citations
              document.querySelectorAll('#hp-citations-tbody tr').forEach(function(row) {
                const editBtn = row.querySelector('.hp-edit-citation');
                const saveBtn = row.querySelector('.hp-save-citation');
                if (!editBtn || !saveBtn) return;
                editBtn.addEventListener('click', function() {
                  // Replace view cells with inputs
                  ['citation-source-view', 'citation-page-view', 'citation-description-view'].forEach(function(cls, idx) {
                    const cell = row.querySelector('.' + cls);
                    if (!cell) return;
                    const val = cell.textContent;
                    let input = document.createElement('input');
                    input.type = 'text';
                    input.value = val;
                    input.className = cls.replace('-view', '-edit');
                    cell.innerHTML = '';
                    cell.appendChild(input);
                  });
                  editBtn.style.display = 'none';
                  saveBtn.style.display = '';
                });
                saveBtn.addEventListener('click', function() {
                  const citationId = row.getAttribute('data-citation-id');
                  const sourceID = row.querySelector('.citation-source-edit').value;
                  const page = row.querySelector('.citation-page-edit').value;
                  const description = row.querySelector('.citation-description-edit').value;
                  const data = new URLSearchParams({
                    action: 'hp_update_citation',
                    citationID: citationId,
                    sourceID: sourceID,
                    page: page,
                    description: description,
                    nonce: '<?php echo esc_js(wp_create_nonce('hp_update_citation')); ?>'
                  });
                  fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                      method: 'POST',
                      headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                      },
                      body: data
                    })
                    .then(r => r.json())
                    .then(json => {
                      if (json.success) {
                        row.querySelector('.citation-source-view').textContent = sourceID;
                        row.querySelector('.citation-page-view').textContent = page;
                        row.querySelector('.citation-description-view').textContent = description;
                        saveBtn.style.display = 'none';
                        editBtn.style.display = '';
                      } else {
                        hpShowNotification(json.data || 'Failed to update citation.', 'error');
                      }
                    });
                });
              });
            })();
    </script>
    <h2><?php esc_html_e('Events'); ?></h2>
    <div id="hp-events-section">
      <div style="margin-bottom:8px;">
        <button type="button" id="hp-remove-selected-events" class="button button-small" disabled>Remove Selected</button>
      </div>
      <table class="widefat hp-events-table" id="hp-events-table">
        <thead>
          <tr>
            <th><input type="checkbox" id="hp-select-all-events"></th>
            <th></th>
            <th><?php esc_html_e('Type'); ?></th>
            <th><?php esc_html_e('Date'); ?></th>
            <th><?php esc_html_e('Place'); ?></th>
            <th><?php esc_html_e('Details'); ?></th>
            <th><?php esc_html_e('Actions'); ?></th>
          </tr>
        </thead>
        <tbody id="hp-events-tbody">
          <?php if (empty($events)): ?>
            <tr>
              <td colspan="7"><?php esc_html_e('No events linked to this family.'); ?></td>
            </tr>
            <?php else: foreach ($events as $event): ?>
              <tr data-event-id="<?php echo esc_attr($event['eventID']); ?>" class="hp-draggable-event-row">
                <td><input type="checkbox" class="hp-event-checkbox"></td>
                <td class="hp-drag-handle">&#9776;</td>
                <td class="event-type-view"><?php echo esc_html($event['tag'] ?? $event['display'] ?? ''); ?></td>
                <td class="event-date-view"><?php echo esc_html($event['eventdate'] ?? ''); ?></td>
                <td class="event-place-view"><?php echo esc_html($event['eventplace'] ?? ''); ?></td>
                <td class="event-info-view"><?php echo esc_html($event['info'] ?? ''); ?></td>
                <td>
                  <button type="button" class="button button-small hp-edit-event">Edit</button>
                  <button type="button" class="button button-small hp-save-event" style="display:none;">Save</button>
                  <button type="button" class="button button-small hp-remove-event" data-event-id="<?php echo esc_attr($event['eventID']); ?>">Remove</button>
                </td>
              </tr>
          <?php endforeach;
          endif; ?>
        </tbody>
      </table>
      <script>
        (function() {
          // Bulk select logic for events
          const selectAll = document.getElementById('hp-select-all-events');
          const checkboxes = () => document.querySelectorAll('.hp-event-checkbox');
          const removeBtn = document.getElementById('hp-remove-selected-events');

          function updateRemoveBtn() {
            removeBtn.disabled = !Array.from(checkboxes()).some(cb => cb.checked);
          }
          if (selectAll) {
            selectAll.addEventListener('change', function() {
              checkboxes().forEach(cb => {
                cb.checked = selectAll.checked;
              });
              updateRemoveBtn();
            });
          }
          document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('hp-event-checkbox')) {
              updateRemoveBtn();
            }
          });
          if (removeBtn) {
            removeBtn.addEventListener('click', function() {
              const selected = Array.from(checkboxes()).filter(cb => cb.checked).map(cb => cb.closest('tr').getAttribute('data-event-id'));
              if (!selected.length || !confirm('Remove selected events?')) return;
              hpShowLoader(true);
              fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                  },
                  body: new URLSearchParams({
                    action: 'hp_bulk_remove_events',
                    family_id: '<?php echo esc_js($family['familyID']); ?>',
                    gedcom: '<?php echo esc_js($family['gedcom']); ?>',
                    event_ids: selected.join(',')
                  })
                })
                .then(r => r.json())
                .then(json => {
                  if (json.success) {
                    // Fetch updated events table
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                          'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                          action: 'hp_get_events_table',
                          family_id: '<?php echo esc_js($family['familyID']); ?>',
                          gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                        })
                      })
                      .then(r2 => r2.json())
                      .then(json2 => {
                        if (json2.success && json2.html) {
                          document.getElementById('hp-events-table').parentNode.innerHTML = json2.html;
                          hpShowNotification('Selected events removed.', 'success');
                        } else {
                          hpShowNotification('Removed, but failed to update table.', 'error');
                        }
                        hpShowLoader(false);
                      });
                  } else {
                    hpShowLoader(false);
                    hpShowNotification(json.data || 'Failed to remove selected events.', 'error');
                  }
                });
            });
          }
        })();
      </script>
    </div>
  </form>
</div>
<style>
  .hp-dots-loader {
    display: inline-block;
    width: 40px;
    height: 16px;
    vertical-align: middle;
  }

  .hp-dots-loader span {
    display: inline-block;
    width: 8px;
    height: 8px;
    margin: 0 2px;
    background: #888;
    border-radius: 50%;
    opacity: 0.5;
    animation: hp-dots-bounce 1.2s infinite both;
  }

  .hp-dots-loader span:nth-child(2) {
    animation-delay: 0.2s;
  }

  .hp-dots-loader span:nth-child(3) {
    animation-delay: 0.4s;
  }

  @keyframes hp-dots-bounce {

    0%,
    80%,
    100% {
      opacity: 0.5;
      transform: translateY(0);
    }

    40% {
      opacity: 1;
      transform: translateY(-6px);
    }
  }
</style>
<script>
  function hpShowNotification(msg, type) {
    var n = document.getElementById('hp-notification');
    n.textContent = msg;
    n.style.display = 'block';
    n.style.background = type === 'success' ? '#d4edda' : '#f8d7da';
    n.style.color = type === 'success' ? '#155724' : '#721c24';
    setTimeout(function() {
      n.style.display = 'none';
    }, 3000);
  }

  function hpShowLoader(show) {
    var l = document.getElementById('hp-loader');
    l.style.display = show ? 'block' : 'none';
  }
  // Privacy toggles
  (function() {
    document.querySelectorAll('input[data-ajax-privacy]').forEach(function(checkbox) {
      checkbox.addEventListener('change', function() {
        const field = this.id;
        const value = this.checked ? 1 : 0;
        const data = new URLSearchParams({
          action: 'hp_update_family_privacy',
          family_id: '<?php echo esc_js($family['familyID'] ?? ''); ?>',
          gedcom: '<?php echo esc_js($family['gedcom'] ?? ''); ?>',
          field: field,
          value: value,
          nonce: '<?php echo esc_js(wp_create_nonce('hp_update_family_privacy')); ?>'
        });
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data
          })
          .then(r => r.json())
          .then(json => {
            if (json.success) {
              hpShowNotification('Updated successfully.', 'success');
            } else {
              hpShowNotification(json.data || 'Failed to update privacy setting.', 'error');
            }
          });
      });
    });
  })();
  // Child order update (drag-and-drop)
  (function() {
    const table = document.getElementById('hp-children-table');
    if (!table) return;
    let draggingRow = null;
    let startIndex = null;
    table.querySelectorAll('tbody tr').forEach(row => {
      row.draggable = true;
      row.addEventListener('dragstart', function(e) {
        draggingRow = this;
        startIndex = Array.from(table.tBodies[0].rows).indexOf(this);
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
      });
      row.addEventListener('dragend', function() {
        this.classList.remove('dragging');
        draggingRow = null;
      });
      row.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
      });
      row.addEventListener('drop', function(e) {
        e.preventDefault();
        if (draggingRow && draggingRow !== this) {
          const rows = Array.from(table.tBodies[0].rows);
          const dropIndex = rows.indexOf(this);
          if (startIndex < dropIndex) {
            this.after(draggingRow);
          } else {
            this.before(draggingRow);
          }
          updateChildOrder();
        }
      });
    });

    function updateChildOrder() {
      const order = Array.from(table.tBodies[0].rows).map(row => row.getAttribute('data-person-id'));
      const nonce = document.getElementById('hp-child-order-nonce').value;
      const data = {
        action: 'hp_update_child_order',
        family_id: '<?php echo esc_js($family['familyID'] ?? ''); ?>',
        gedcom: '<?php echo esc_js($family['gedcom'] ?? ''); ?>',
        order: order,
        nonce: nonce
      };
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(json => {
          if (json.success) {
            hpShowNotification('Updated successfully.', 'success');
          } else {
            hpShowNotification(json.data || 'Failed to update child order.', 'error');
          }
        })
        .catch(() => hpShowNotification('AJAX error updating child order.', 'error'));
    }
  })();
  document.addEventListener('click', function(e) {
    if (e.target && e.target.matches('.hp-children-table .button-small')) {
      if (!confirm('Remove this child?')) return;
      e.preventDefault();
      hpShowLoader(true);
      const row = e.target.closest('tr');
      const personId = row.getAttribute('data-person-id');
      const data = new URLSearchParams({
        action: 'remove_child',
        person_id: personId,
        family_id: '<?php echo esc_js($family['familyID']); ?>',
        gedcom: '<?php echo esc_js($family['gedcom']); ?>'
      });
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: data
        })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            // Fetch updated children table
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                  action: 'hp_get_children_table',
                  family_id: '<?php echo esc_js($family['familyID']); ?>',
                  gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                })
              })
              .then(r2 => r2.json())
              .then(json2 => {
                if (json2.success && json2.html) {
                  document.getElementById('hp-children-table').parentNode.innerHTML = json2.html;
                  hpShowNotification('Child removed successfully.', 'success');
                } else {
                  hpShowNotification('Child removed, but failed to update table.', 'error');
                }
                hpShowLoader(false);
              });
          } else {
            hpShowLoader(false);
            hpShowNotification(json.data || 'Failed to remove child.', 'error');
          }
        });
    }
    // Remove Event
    if (e.target && e.target.matches('.hp-events-table .hp-remove-event')) {
      if (!confirm('Remove this event?')) return;
      e.preventDefault();
      hpShowLoader(true);
      const row = e.target.closest('tr');
      const eventId = row.getAttribute('data-event-id');
      const data = new URLSearchParams({
        action: 'remove_event',
        event_id: eventId,
        family_id: '<?php echo esc_js($family['familyID']); ?>',
        gedcom: '<?php echo esc_js($family['gedcom']); ?>'
      });
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: data
        })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                  action: 'hp_get_events_table',
                  family_id: '<?php echo esc_js($family['familyID']); ?>',
                  gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                })
              })
              .then(r2 => r2.json())
              .then(json2 => {
                if (json2.success && json2.html) {
                  document.getElementById('hp-events-table').parentNode.innerHTML = json2.html;
                  hpShowNotification('Event removed successfully.', 'success');
                } else {
                  hpShowNotification('Event removed, but failed to update table.', 'error');
                }
                hpShowLoader(false);
              });
          } else {
            hpShowLoader(false);
            hpShowNotification(json.data || 'Failed to remove event.', 'error');
          }
        });
    }
    // Remove Citation
    if (e.target && e.target.matches('.hp-citations-table .hp-remove-citation')) {
      if (!confirm('Remove this citation?')) return;
      e.preventDefault();
      hpShowLoader(true);
      const row = e.target.closest('tr');
      const citationId = row.getAttribute('data-citation-id');
      const data = new URLSearchParams({
        action: 'remove_citation',
        citation_id: citationId,
        family_id: '<?php echo esc_js($family['familyID']); ?>',
        gedcom: '<?php echo esc_js($family['gedcom']); ?>'
      });
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: data
        })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                  action: 'hp_get_citations_table',
                  family_id: '<?php echo esc_js($family['familyID']); ?>',
                  gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                })
              })
              .then(r2 => r2.json())
              .then(json2 => {
                if (json2.success && json2.html) {
                  document.getElementById('hp-citations-table').parentNode.innerHTML = json2.html;
                  hpShowNotification('Citation removed successfully.', 'success');
                } else {
                  hpShowNotification('Citation removed, but failed to update table.', 'error');
                }
                hpShowLoader(false);
              });
          } else {
            hpShowLoader(false);
            hpShowNotification(json.data || 'Failed to remove citation.', 'error');
          }
        });
    }
    // Inline Save Event
    if (e.target && e.target.matches('.hp-save-event')) {
      e.preventDefault();
      hpShowLoader(true);
      const row = e.target.closest('tr');
      const eventId = row.getAttribute('data-event-id');
      const type = row.querySelector('.event-type-edit').value;
      const date = row.querySelector('.event-date-edit').value;
      const place = row.querySelector('.event-place-edit').value;
      const info = row.querySelector('.event-info-edit').value;
      const data = new URLSearchParams({
        action: 'hp_update_event',
        eventID: eventId,
        event_type: type,
        event_date: date,
        event_place: place,
        event_info: info,
        nonce: '<?php echo esc_js(wp_create_nonce('hp_update_event')); ?>'
      });
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: data
        })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            // Fetch updated events table
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                  action: 'hp_get_events_table',
                  family_id: '<?php echo esc_js($family['familyID']); ?>',
                  gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                })
              })
              .then(r2 => r2.json())
              .then(json2 => {
                if (json2.success && json2.html) {
                  document.getElementById('hp-events-table').parentNode.innerHTML = json2.html;
                  hpShowNotification('Event updated successfully.', 'success');
                } else {
                  hpShowNotification('Event updated, but failed to update table.', 'error');
                }
                hpShowLoader(false);
              });
          } else {
            hpShowLoader(false);
            hpShowNotification(json.data || 'Failed to update event.', 'error');
          }
        });
    }
    // Inline Save Citation
    if (e.target && e.target.matches('.hp-save-citation')) {
      e.preventDefault();
      hpShowLoader(true);
      const row = e.target.closest('tr');
      const citationId = row.getAttribute('data-citation-id');
      const sourceID = row.querySelector('.citation-source-edit').value;
      const page = row.querySelector('.citation-page-edit').value;
      const description = row.querySelector('.citation-description-edit').value;
      const data = new URLSearchParams({
        action: 'hp_update_citation',
        citationID: citationId,
        sourceID: sourceID,
        page: page,
        description: description,
        nonce: '<?php echo esc_js(wp_create_nonce('hp_update_citation')); ?>'
      });
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: data
        })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            // Fetch updated citations table
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                  action: 'hp_get_citations_table',
                  family_id: '<?php echo esc_js($family['familyID']); ?>',
                  gedcom: '<?php echo esc_js($family['gedcom']); ?>'
                })
              })
              .then(r2 => r2.json())
              .then(json2 => {
                if (json2.success && json2.html) {
                  document.getElementById('hp-citations-table').parentNode.innerHTML = json2.html;
                  hpShowNotification('Citation updated successfully.', 'success');
                } else {
                  hpShowNotification('Citation updated, but failed to update table.', 'error');
                }
                hpShowLoader(false);
              });
          } else {
            hpShowLoader(false);
            hpShowNotification(json.data || 'Failed to update citation.', 'error');
          }
        });
    }
  });
</script>
