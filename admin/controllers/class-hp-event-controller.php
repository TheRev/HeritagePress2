<?php

/**
 * Event Controller
 *
 * Handles all event management functionality including CRUD operations,
 * event validation, and event-related AJAX requests.
 * This controller extends the base controller for common functionality.
 * It manages events for both people and families,
 * and supports place management.
 * It also handles bulk actions and event duplication.
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_Event_Controller extends HP_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('event');
    $this->capabilities = array(
      'manage_events' => 'manage_genealogy',
      'edit_events' => 'edit_genealogy',
      'delete_events' => 'delete_genealogy'
    );
  }

  /**
   * Register hooks for event management
   */
  public function register_hooks()
  {
    parent::register_hooks();

    // AJAX handlers for events
    add_action('wp_ajax_hp_add_event', array($this, 'ajax_add_event'));
    add_action('wp_ajax_hp_update_event', array($this, 'ajax_update_event'));
    add_action('wp_ajax_hp_delete_event', array($this, 'ajax_delete_event'));
    add_action('wp_ajax_hp_get_event', array($this, 'ajax_get_event'));
    add_action('wp_ajax_hp_get_person_events', array($this, 'ajax_get_person_events'));
    add_action('wp_ajax_hp_get_family_events', array($this, 'ajax_get_family_events'));
    add_action('wp_ajax_hp_get_event_types', array($this, 'ajax_get_event_types'));
    add_action('wp_ajax_hp_update_event_with_address', array($this, 'ajax_update_event_with_address'));
  }

  /**
   * Handle event page actions
   */
  public function handle_event_actions($tab)
  {
    if (!$this->check_capability('edit_genealogy')) {
      return;
    }

    // Handle form submissions
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_event':
          $this->handle_add_event();
          break;
        case 'update_event':
          $this->handle_update_event();
          break;
        case 'delete_event':
          $this->handle_delete_event();
          break;
        case 'bulk_action':
          $this->handle_bulk_event_actions();
          break;
      }
    }
  }

  /**
   * Handle form submission - delegates to individual action handlers
   */
  public function handle_form_submission()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
      $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';
      $this->handle_event_actions($current_tab);
    }
  }

  /**
   * Handle adding a new event
   */
  private function handle_add_event()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    // Sanitize and validate form data
    $event_data = $this->sanitize_event_data($_POST);

    // Validate required fields
    if (empty($event_data['eventtypeID']) || empty($event_data['persfamID']) || empty($event_data['gedcom'])) {
      $this->add_notice(__('Event type, Person/Family ID, and Tree are required.', 'heritagepress'), 'error');
      return;
    }

    // Parse date if provided
    if (!empty($event_data['eventdate'])) {
      $event_data['eventdatetr'] = $this->convert_date($event_data['eventdate']);
    }

    $result = $this->create_event($event_data);

    if ($result) {
      $this->add_notice(__('Event created successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to create event. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle updating an existing event
   */
  private function handle_update_event()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('edit_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $event_id = intval($_POST['eventID']);
    $event_data = $this->sanitize_event_data($_POST);

    // Parse date if provided
    if (!empty($event_data['eventdate'])) {
      $event_data['eventdatetr'] = $this->convert_date($event_data['eventdate']);
    }

    $result = $this->update_event($event_id, $event_data);

    if ($result) {
      $this->add_notice(__('Event updated successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to update event. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle deleting an event
   */
  private function handle_delete_event()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    if (!$this->check_capability('delete_genealogy')) {
      $this->add_notice(__('Insufficient permissions.', 'heritagepress'), 'error');
      return;
    }

    $event_id = intval($_POST['eventID']);

    $result = $this->delete_event($event_id);

    if ($result) {
      $this->add_notice(__('Event deleted successfully!', 'heritagepress'), 'success');
    } else {
      $this->add_notice(__('Failed to delete event. Please try again.', 'heritagepress'), 'error');
    }
  }

  /**
   * Handle bulk event actions
   */
  private function handle_bulk_event_actions()
  {
    if (!$this->verify_nonce($_POST['_wpnonce'])) {
      $this->add_notice(__('Security check failed.', 'heritagepress'), 'error');
      return;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $event_ids = isset($_POST['event_ids']) ? array_map('intval', $_POST['event_ids']) : array();

    if (empty($event_ids)) {
      $this->add_notice(__('No events selected.', 'heritagepress'), 'error');
      return;
    }

    $success_count = 0;
    $error_count = 0;

    foreach ($event_ids as $event_id) {
      switch ($action) {
        case 'delete':
          if ($this->check_capability('delete_genealogy')) {
            $result = $this->delete_event($event_id);
            if ($result) {
              $success_count++;
            } else {
              $error_count++;
            }
          }
          break;
      }
    }

    if ($success_count > 0) {
      $this->add_notice(sprintf(__('%d events processed successfully.', 'heritagepress'), $success_count), 'success');
    }
    if ($error_count > 0) {
      $this->add_notice(sprintf(__('%d events failed to process.', 'heritagepress'), $error_count), 'error');
    }
  }

  /**
   * Create a new event
   */
  private function create_event($event_data)
  {
    global $wpdb;

    try {
      $wpdb->query('START TRANSACTION');

      // Handle place insertion with place management
      if (!empty($event_data['eventplace'])) {
        $this->handle_place_insertion($event_data['eventplace'], $event_data['gedcom']);
      }

      // Handle address insertion if address data provided
      $address_id = null;
      if ($this->has_address_data($event_data)) {
        $address_id = $this->create_address($event_data);
      }

      // Insert the event
      $events_table = $wpdb->prefix . 'hp_events';
      $result = $wpdb->insert(
        $events_table,
        array(
          'eventtypeID' => $event_data['eventtypeID'],
          'persfamID' => $event_data['persfamID'],
          'eventdate' => $event_data['eventdate'],
          'eventdatetr' => $event_data['eventdatetr'] ?? '',
          'eventplace' => $event_data['eventplace'],
          'age' => $event_data['age'],
          'agency' => $event_data['agency'],
          'cause' => $event_data['cause'],
          'addressID' => $address_id,
          'info' => $event_data['info'],
          'gedcom' => $event_data['gedcom'],
          'parenttag' => ''
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
      );

      if ($result === false) {
        throw new Exception('Failed to insert event');
      }

      $event_id = $wpdb->insert_id;

      // Handle duplicate IDs (adding same event to multiple people/families)
      if (!empty($event_data['dupIDs'])) {
        $this->handle_duplicate_events($event_id, $event_data);
      }

      $wpdb->query('COMMIT');

      // Log admin action
      $this->log_admin_action('Added new event: ' . $event_data['eventtypeID'] . '/' . $event_data['gedcom'] . '/' . $event_data['persfamID']);

      return $event_id;
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      error_log('HeritagePress Event Creation Error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Update an existing event
   */
  private function update_event($event_id, $event_data)
  {
    global $wpdb;

    try {
      $wpdb->query('START TRANSACTION');

      // Get current event data
      $events_table = $wpdb->prefix . 'hp_events';
      $current_event = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $events_table WHERE eventID = %d",
        $event_id
      ), ARRAY_A);

      if (!$current_event) {
        throw new Exception('Event not found');
      }

      // Handle place insertion for new places
      if (!empty($event_data['eventplace'])) {
        $this->handle_place_insertion($event_data['eventplace'], $current_event['gedcom']);
      }

      // Handle address updates
      $address_id = $current_event['addressID'];
      if ($address_id && $this->has_address_data($event_data)) {
        // Update existing address
        $this->update_address($address_id, $event_data);
      } elseif ($address_id && !$this->has_address_data($event_data)) {
        // Delete address if no address data provided
        $this->delete_address($address_id);
        $address_id = null;
      } elseif (!$address_id && $this->has_address_data($event_data)) {
        // Create new address
        $address_id = $this->create_address($event_data);
      }

      // Update the event
      $result = $wpdb->update(
        $events_table,
        array(
          'eventdate' => $event_data['eventdate'],
          'eventdatetr' => $event_data['eventdatetr'] ?? '',
          'eventplace' => $event_data['eventplace'],
          'age' => $event_data['age'],
          'agency' => $event_data['agency'],
          'cause' => $event_data['cause'],
          'addressID' => $address_id,
          'info' => $event_data['info']
        ),
        array('eventID' => $event_id),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s'),
        array('%d')
      );

      if ($result === false) {
        throw new Exception('Failed to update event');
      }

      // Handle duplicate IDs if provided
      if (!empty($event_data['dupIDs'])) {
        $this->handle_duplicate_events($event_id, $event_data);
      }

      $wpdb->query('COMMIT');

      // Log admin action
      $this->log_admin_action('Modified event: ' . $event_id);

      return true;
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      error_log('HeritagePress Event Update Error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Delete an event and all related data
   */
  private function delete_event($event_id)
  {
    global $wpdb;

    try {
      $wpdb->query('START TRANSACTION');

      $events_table = $wpdb->prefix . 'hp_events';
      $address_table = $wpdb->prefix . 'hp_addresses';
      $citations_table = $wpdb->prefix . 'hp_citations';
      $medialinks_table = $wpdb->prefix . 'hp_medialinks';
      $notelinks_table = $wpdb->prefix . 'hp_notelinks';
      $xnotes_table = $wpdb->prefix . 'hp_xnotes';

      // Get event data
      $event = $wpdb->get_row($wpdb->prepare(
        "SELECT addressID FROM $events_table WHERE eventID = %d",
        $event_id
      ), ARRAY_A);

      if (!$event) {
        throw new Exception('Event not found');
      }

      // Delete associated address
      if ($event['addressID']) {
        $wpdb->delete($address_table, array('addressID' => $event['addressID']), array('%d'));
      }

      // Delete citations
      $wpdb->delete($citations_table, array('eventID' => $event_id), array('%d'));

      // Delete media links
      $wpdb->delete($medialinks_table, array('eventID' => $event_id), array('%d'));

      // Handle notes (delete note if only linked to this event)
      $note_links = $wpdb->get_results($wpdb->prepare(
        "SELECT xnoteID FROM $notelinks_table WHERE eventID = %d",
        $event_id
      ));

      foreach ($note_links as $note_link) {
        $note_count = $wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(ID) FROM $notelinks_table WHERE xnoteID = %d",
          $note_link->xnoteID
        ));

        if ($note_count == 1) {
          // Only linked to this event, safe to delete the note
          $wpdb->delete($xnotes_table, array('ID' => $note_link->xnoteID), array('%d'));
        }
      }

      // Delete note links
      $wpdb->delete($notelinks_table, array('eventID' => $event_id), array('%d'));

      // Delete the event
      $result = $wpdb->delete($events_table, array('eventID' => $event_id), array('%d'));

      if ($result === false) {
        throw new Exception('Failed to delete event');
      }

      $wpdb->query('COMMIT');

      // Log admin action
      $this->log_admin_action('Deleted event: ' . $event_id);

      return true;
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');
      error_log('HeritagePress Event Deletion Error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Handle place insertion
   */
  private function handle_place_insertion($place, $gedcom)
  {
    global $wpdb;

    if (empty(trim($place))) {
      return;
    }

    $places_table = $wpdb->prefix . 'hp_places';

    // Check if place exists
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $places_table WHERE gedcom = %s AND place = %s",
      $gedcom,
      $place
    ));

    if ($existing == 0) {
      // Determine if this is a temple (5 uppercase characters)
      $temple = (strlen($place) == 5 && $place == strtoupper($place)) ? 1 : 0;

      // Insert new place
      $wpdb->insert(
        $places_table,
        array(
          'gedcom' => $gedcom,
          'place' => $place,
          'placelevel' => 0,
          'zoom' => 0,
          'geoignore' => 0,
          'temple' => $temple
        ),
        array('%s', '%s', '%d', '%d', '%d', '%d')
      );

      // TODO: Add geocoding support if enabled
      // if ($auto_geocoding_enabled && $wpdb->insert_id) {
      //   $this->trigger_geocoding($place, $wpdb->insert_id);
      // }
    }
  }

  /**
   * Check if event data contains address information
   */
  private function has_address_data($event_data)
  {
    return !empty($event_data['address1']) || !empty($event_data['address2']) ||
      !empty($event_data['city']) || !empty($event_data['state']) ||
      !empty($event_data['zip']) || !empty($event_data['country']) ||
      !empty($event_data['phone']) || !empty($event_data['email']) ||
      !empty($event_data['www']);
  }

  /**
   * Create address record
   */
  private function create_address($event_data)
  {
    global $wpdb;

    $address_table = $wpdb->prefix . 'hp_addresses';

    $result = $wpdb->insert(
      $address_table,
      array(
        'address1' => $event_data['address1'] ?? '',
        'address2' => $event_data['address2'] ?? '',
        'city' => $event_data['city'] ?? '',
        'state' => $event_data['state'] ?? '',
        'zip' => $event_data['zip'] ?? '',
        'country' => $event_data['country'] ?? '',
        'gedcom' => $event_data['gedcom'],
        'phone' => $event_data['phone'] ?? '',
        'email' => $event_data['email'] ?? '',
        'www' => $event_data['www'] ?? ''
      ),
      array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    return $result ? $wpdb->insert_id : null;
  }

  /**
   * Update address record
   */
  private function update_address($address_id, $event_data)
  {
    global $wpdb;

    $address_table = $wpdb->prefix . 'hp_addresses';

    return $wpdb->update(
      $address_table,
      array(
        'address1' => $event_data['address1'] ?? '',
        'address2' => $event_data['address2'] ?? '',
        'city' => $event_data['city'] ?? '',
        'state' => $event_data['state'] ?? '',
        'zip' => $event_data['zip'] ?? '',
        'country' => $event_data['country'] ?? '',
        'gedcom' => $event_data['gedcom'],
        'phone' => $event_data['phone'] ?? '',
        'email' => $event_data['email'] ?? '',
        'www' => $event_data['www'] ?? ''
      ),
      array('addressID' => $address_id),
      array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
      array('%d')
    );
  }

  /**
   * Delete address record
   */
  private function delete_address($address_id)
  {
    global $wpdb;

    $address_table = $wpdb->prefix . 'hp_addresses';
    return $wpdb->delete($address_table, array('addressID' => $address_id), array('%d'));
  }

  /**
   * Handle duplicate events (adding same event to multiple people/families)
   */
  private function handle_duplicate_events($source_event_id, $event_data)
  {
    global $wpdb;

    if (empty($event_data['dupIDs'])) {
      return;
    }

    $events_table = $wpdb->prefix . 'hp_events';
    $citations_table = $wpdb->prefix . 'hp_citations';
    $notelinks_table = $wpdb->prefix . 'hp_notelinks';
    $xnotes_table = $wpdb->prefix . 'hp_xnotes';
    $medialinks_table = $wpdb->prefix . 'hp_medialinks';
    $people_table = $wpdb->prefix . 'hp_people';
    $families_table = $wpdb->prefix . 'hp_families';
    $sources_table = $wpdb->prefix . 'hp_sources';

    // Get source event data
    $source_event = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $events_table WHERE eventID = %d",
      $source_event_id
    ), ARRAY_A);

    if (!$source_event) {
      return;
    }

    $ids = array_filter(array_map('trim', explode(',', $event_data['dupIDs'])));

    foreach ($ids as $id) {
      // Verify the ID exists in people, families, or sources table
      $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $people_table WHERE personID = %s AND gedcom = %s
         UNION
         SELECT COUNT(*) FROM $families_table WHERE familyID = %s AND gedcom = %s
         UNION
         SELECT COUNT(*) FROM $sources_table WHERE sourceID = %s AND gedcom = %s",
        $id,
        $event_data['gedcom'],
        $id,
        $event_data['gedcom'],
        $id,
        $event_data['gedcom']
      ));

      if ($exists > 0) {
        // Create duplicate event
        $wpdb->insert(
          $events_table,
          array(
            'eventtypeID' => $source_event['eventtypeID'],
            'persfamID' => $id,
            'eventdate' => $source_event['eventdate'],
            'eventdatetr' => $source_event['eventdatetr'],
            'eventplace' => $source_event['eventplace'],
            'age' => $source_event['age'],
            'agency' => $source_event['agency'],
            'cause' => $source_event['cause'],
            'addressID' => $source_event['addressID'],
            'info' => $source_event['info'],
            'gedcom' => $event_data['gedcom'],
            'parenttag' => ''
          ),
          array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        $new_event_id = $wpdb->insert_id;

        if ($new_event_id) {
          // Copy citations
          $this->copy_event_citations($source_event_id, $new_event_id, $id);

          // Copy notes
          $this->copy_event_notes($source_event_id, $new_event_id, $id, $event_data['gedcom']);

          // Copy media links
          $this->copy_event_media($source_event_id, $new_event_id, $id);
        }
      }
    }
  }

  /**
   * Copy event citations to duplicate event
   */
  private function copy_event_citations($source_event_id, $new_event_id, $person_id)
  {
    global $wpdb;

    $citations_table = $wpdb->prefix . 'hp_citations';

    $citations = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $citations_table WHERE eventID = %d",
      $source_event_id
    ), ARRAY_A);

    foreach ($citations as $citation) {
      $wpdb->insert(
        $citations_table,
        array(
          'gedcom' => $citation['gedcom'],
          'persfamID' => $person_id,
          'eventID' => $new_event_id,
          'sourceID' => $citation['sourceID'],
          'ordernum' => $citation['ordernum'],
          'description' => $citation['description'],
          'citedate' => $citation['citedate'],
          'citedatetr' => $citation['citedatetr'],
          'citetext' => $citation['citetext'],
          'page' => $citation['page'],
          'quay' => $citation['quay'],
          'note' => $citation['note']
        ),
        array('%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
      );
    }
  }

  /**
   * Copy event notes to duplicate event
   */
  private function copy_event_notes($source_event_id, $new_event_id, $person_id, $gedcom)
  {
    global $wpdb;

    $notelinks_table = $wpdb->prefix . 'hp_notelinks';
    $xnotes_table = $wpdb->prefix . 'hp_xnotes';
    $citations_table = $wpdb->prefix . 'hp_citations';

    $note_links = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $notelinks_table WHERE eventID = %d",
      $source_event_id
    ), ARRAY_A);

    foreach ($note_links as $note_link) {
      // Copy the note itself
      $original_note = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $xnotes_table WHERE ID = %d",
        $note_link['xnoteID']
      ), ARRAY_A);

      if ($original_note) {
        $wpdb->insert(
          $xnotes_table,
          array(
            'gedcom' => $original_note['gedcom'],
            'note' => $original_note['note'],
            'noteID' => $original_note['noteID']
          ),
          array('%s', '%s', '%s')
        );

        $new_note_id = $wpdb->insert_id;

        // Create note link
        $wpdb->insert(
          $notelinks_table,
          array(
            'persfamID' => $person_id,
            'gedcom' => $note_link['gedcom'],
            'xnoteID' => $new_note_id,
            'eventID' => $new_event_id,
            'ordernum' => $note_link['ordernum'],
            'secret' => $note_link['secret']
          ),
          array('%s', '%s', '%d', '%d', '%d', '%d')
        );

        // Copy citations attached to this note
        $note_citations = $wpdb->get_results($wpdb->prepare(
          "SELECT * FROM $citations_table WHERE gedcom = %s AND eventID = %s",
          $gedcom,
          'N' . $note_link['ID']
        ), ARRAY_A);

        foreach ($note_citations as $citation) {
          $wpdb->insert(
            $citations_table,
            array(
              'gedcom' => $citation['gedcom'],
              'persfamID' => $person_id,
              'eventID' => 'N' . $new_note_id,
              'sourceID' => $citation['sourceID'],
              'ordernum' => $citation['ordernum'],
              'description' => $citation['description'],
              'citedate' => $citation['citedate'],
              'citedatetr' => $citation['citedatetr'],
              'citetext' => $citation['citetext'],
              'page' => $citation['page'],
              'quay' => $citation['quay'],
              'note' => $citation['note']
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
          );
        }
      }
    }
  }

  /**
   * Copy event media links to duplicate event
   */
  private function copy_event_media($source_event_id, $new_event_id, $person_id)
  {
    global $wpdb;

    $medialinks_table = $wpdb->prefix . 'hp_medialinks';

    $media_links = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $medialinks_table WHERE eventID = %d",
      $source_event_id
    ), ARRAY_A);

    foreach ($media_links as $media_link) {
      $wpdb->insert(
        $medialinks_table,
        array(
          'gedcom' => $media_link['gedcom'],
          'linktype' => $media_link['linktype'],
          'personID' => $person_id,
          'eventID' => $new_event_id,
          'mediaID' => $media_link['mediaID'],
          'altdescription' => $media_link['altdescription'],
          'altnotes' => $media_link['altnotes'],
          'ordernum' => $media_link['ordernum'],
          'dontshow' => $media_link['dontshow'],
          'defphoto' => $media_link['defphoto']
        ),
        array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d')
      );
    }
  }

  /**
   * Sanitize event form data
   */
  private function sanitize_event_data($data)
  {
    return array(
      'eventtypeID' => sanitize_text_field($data['eventtypeID'] ?? ''),
      'persfamID' => sanitize_text_field($data['persfamID'] ?? ''),
      'eventdate' => sanitize_text_field($data['eventdate'] ?? ''),
      'eventplace' => sanitize_text_field($data['eventplace'] ?? ''),
      'info' => sanitize_textarea_field($data['info'] ?? ''),
      'age' => sanitize_text_field($data['age'] ?? ''),
      'agency' => sanitize_text_field($data['agency'] ?? ''),
      'cause' => sanitize_text_field($data['cause'] ?? ''),
      'address1' => sanitize_text_field($data['address1'] ?? ''),
      'address2' => sanitize_text_field($data['address2'] ?? ''),
      'city' => sanitize_text_field($data['city'] ?? ''),
      'state' => sanitize_text_field($data['state'] ?? ''),
      'zip' => sanitize_text_field($data['zip'] ?? ''),
      'country' => sanitize_text_field($data['country'] ?? ''),
      'phone' => sanitize_text_field($data['phone'] ?? ''),
      'email' => sanitize_email($data['email'] ?? ''),
      'www' => esc_url_raw($data['www'] ?? ''),
      'gedcom' => sanitize_text_field($data['gedcom'] ?? ''),
      'dupIDs' => sanitize_text_field($data['dupIDs'] ?? '')
    );
  }

  /**
   * Convert date (placeholder for date conversion function)
   */
  private function convert_date($date)
  {
    // TODO: Implement date conversion logic
    // For now, return the date as-is
    return $date;
  }

  /**
   * Get event types for a specific prefix (person/family)
   */
  private function get_event_types($prefix = 'I')
  {
    global $wpdb;

    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $eventtypes_table WHERE keep = 1 AND type = %s ORDER BY tag",
      $prefix
    ), ARRAY_A);
  }

  /**
   * Get event display name (placeholder for event display function)
   */
  private function get_event_display($display)
  {
    // TODO: Implement event display logic
    return $display;
  }

  /**
   * Log admin action
   */
  private function log_admin_action($message)
  {
    // TODO: Implement admin logging if not already available
    error_log('HeritagePress Event Action: ' . $message);
  }

  /**
   * AJAX: Add event
   */
  public function ajax_add_event()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      // Sanitize and validate form data
      $event_data = $this->sanitize_event_data($_POST);

      // Validate required fields
      if (empty($event_data['eventtypeID']) || empty($event_data['persfamID']) || empty($event_data['gedcom'])) {
        wp_send_json_error('Event type, Person/Family ID, and Tree are required');
      }

      // Parse date if provided
      if (!empty($event_data['eventdate'])) {
        $event_data['eventdatetr'] = $this->convert_date($event_data['eventdate']);
      }

      $event_id = $this->create_event($event_data);

      if ($event_id) {
        // Get event display for response
        $event_types = $this->get_event_types();
        $display = '';
        foreach ($event_types as $type) {
          if ($type['eventtypeID'] == $event_data['eventtypeID']) {
            $display = $this->get_event_display($type['display']);
            break;
          }
        }

        // Format info for display (truncate for table)
        $info = preg_replace("/\r/", " ", $event_data['info']);
        $info = preg_replace("/\n/", " ", $info);
        $truncated = substr($info, 0, 90);
        $info = strlen($info) > 90 ? substr($truncated, 0, strrpos($truncated, ' ')) . '&hellip;' : $info;
        $info = preg_replace("/\t/", " ", $info);

        wp_send_json_success(array(
          'id' => $event_id,
          'persfamID' => $event_data['persfamID'],
          'tree' => $event_data['gedcom'],
          'display' => $display,
          'eventdate' => $event_data['eventdate'],
          'eventplace' => $event_data['eventplace'],
          'info' => $info,
          'allow_edit' => $this->check_capability('edit_genealogy'),
          'allow_delete' => $this->check_capability('delete_genealogy')
        ));
      } else {
        wp_send_json_error('Failed to create event');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Event Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while creating the event');
    }
  }

  /**
   * AJAX: Update event
   */
  public function ajax_update_event()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      $event_id = intval($_POST['eventID'] ?? 0);
      if ($event_id <= 0) {
        wp_send_json_error('Invalid event ID');
      }

      $event_data = $this->sanitize_event_data($_POST);

      // Parse date if provided
      if (!empty($event_data['eventdate'])) {
        $event_data['eventdatetr'] = $this->convert_date($event_data['eventdate']);
      }

      $result = $this->update_event($event_id, $event_data);

      if ($result) {
        // Get event display for response
        $event_types = $this->get_event_types();
        $display = '';
        foreach ($event_types as $type) {
          if ($type['eventtypeID'] == $event_data['eventtypeID']) {
            $display = $this->get_event_display($type['display']);
            break;
          }
        }

        // Format info for display (truncate for table)
        $info = preg_replace("/\r/", " ", $event_data['info']);
        $info = preg_replace("/\n/", " ", $info);
        $truncated = substr($info, 0, 90);
        $info = strlen($info) > 90 ? substr($truncated, 0, strrpos($truncated, ' ')) . '&hellip;' : $info;
        $info = preg_replace("/\t/", " ", $info);

        wp_send_json_success(array(
          'display' => $display,
          'eventdate' => $event_data['eventdate'],
          'eventplace' => $event_data['eventplace'],
          'info' => $info
        ));
      } else {
        wp_send_json_error('Failed to update event');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Event Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while updating the event');
    }
  }

  /**
   * AJAX: Delete event
   */
  public function ajax_delete_event()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    if (!$this->check_capability('delete_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }

    try {
      $event_id = intval($_POST['eventID'] ?? 0);

      if ($event_id <= 0) {
        wp_send_json_error('Invalid event ID');
      }

      $result = $this->delete_event($event_id);

      if ($result) {
        wp_send_json_success('Event deleted successfully');
      } else {
        wp_send_json_error('Failed to delete event');
      }
    } catch (Exception $e) {
      error_log('HeritagePress Event Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while deleting the event');
    }
  }

  /**
   * AJAX: Get event details
   */
  public function ajax_get_event()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    try {
      $event_id = intval($_POST['eventID'] ?? 0);

      if ($event_id <= 0) {
        wp_send_json_error('Invalid event ID');
      }

      global $wpdb;
      $events_table = $wpdb->prefix . 'hp_events';
      $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';
      $address_table = $wpdb->prefix . 'hp_addresses';

      $event = $wpdb->get_row($wpdb->prepare("
        SELECT e.*, et.display, et.tag, et.type,
               a.address1, a.address2, a.city, a.state, a.zip, a.country, a.phone, a.email, a.www
        FROM $events_table e
        LEFT JOIN $eventtypes_table et ON e.eventtypeID = et.eventtypeID
        LEFT JOIN $address_table a ON e.addressID = a.addressID
        WHERE e.eventID = %d
      ", $event_id), ARRAY_A);

      if (!$event) {
        wp_send_json_error('Event not found');
      }

      // Escape quotes in event data
      foreach ($event as $key => $value) {
        if (is_string($value)) {
          $event[$key] = str_replace('"', '&#34;', $value ?? '');
        }
      }

      wp_send_json_success($event);
    } catch (Exception $e) {
      error_log('HeritagePress Event Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while retrieving the event');
    }
  }

  /**
   * AJAX: Get events for a person
   */
  public function ajax_get_person_events()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    try {
      $person_id = sanitize_text_field($_POST['personID'] ?? '');
      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

      if (empty($person_id) || empty($gedcom)) {
        wp_send_json_error('Person ID and Tree are required');
      }

      global $wpdb;
      $events_table = $wpdb->prefix . 'hp_events';
      $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

      $events = $wpdb->get_results($wpdb->prepare("
        SELECT e.eventID, e.eventdate, e.eventplace, e.info, et.display, et.tag
        FROM $events_table e
        LEFT JOIN $eventtypes_table et ON e.eventtypeID = et.eventtypeID
        WHERE e.persfamID = %s AND e.gedcom = %s
        ORDER BY e.eventdate, et.tag
      ", $person_id, $gedcom), ARRAY_A);

      wp_send_json_success(array('events' => $events));
    } catch (Exception $e) {
      error_log('HeritagePress Event Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while retrieving events');
    }
  }

  /**
   * AJAX: Get events for a family
   */
  public function ajax_get_family_events()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    try {
      $family_id = sanitize_text_field($_POST['familyID'] ?? '');
      $gedcom = sanitize_text_field($_POST['gedcom'] ?? '');

      if (empty($family_id) || empty($gedcom)) {
        wp_send_json_error('Family ID and Tree are required');
      }

      global $wpdb;
      $events_table = $wpdb->prefix . 'hp_events';
      $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

      $events = $wpdb->get_results($wpdb->prepare("
        SELECT e.eventID, e.eventdate, e.eventplace, e.info, et.display, et.tag
        FROM $events_table e
        LEFT JOIN $eventtypes_table et ON e.eventtypeID = et.eventtypeID
        WHERE e.persfamID = %s AND e.gedcom = %s
        ORDER BY e.eventdate, et.tag
      ", $family_id, $gedcom), ARRAY_A);

      wp_send_json_success(array('events' => $events));
    } catch (Exception $e) {
      error_log('HeritagePress Event Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while retrieving events');
    }
  }

  /**
   * AJAX: Get event types
   */
  public function ajax_get_event_types()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }

    try {
      $prefix = sanitize_text_field($_POST['prefix'] ?? 'I');
      $event_types = $this->get_event_types($prefix);

      wp_send_json_success(array('event_types' => $event_types));
    } catch (Exception $e) {
      error_log('HeritagePress Event Error: ' . $e->getMessage());
      wp_send_json_error('An error occurred while retrieving event types');
    }
  }

  /**
   * AJAX: Update event and address in a single call (HeritagePress compatibility)
   */
  public function ajax_update_event_with_address()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }
    if (!$this->check_capability('edit_genealogy')) {
      wp_send_json_error('Insufficient permissions');
    }
    global $wpdb;
    $events_table = $wpdb->prefix . 'hp_events';
    $address_table = $wpdb->prefix . 'hp_addresses';
    $rval = 0;
    $addressID = intval($_POST['addressID'] ?? 0);
    $eventID = intval($_POST['eventID'] ?? 0);
    $tree = sanitize_text_field($_POST['gedcom'] ?? '');
    $persfamID = sanitize_text_field($_POST['persfamID'] ?? '');
    $eventtypeID = intval($_POST['eventtypeID'] ?? 0);
    $info = sanitize_text_field($_POST['info'] ?? '');
    $age = sanitize_text_field($_POST['age'] ?? '');
    $agency = sanitize_text_field($_POST['agency'] ?? '');
    $cause = sanitize_text_field($_POST['cause'] ?? '');
    $address1 = sanitize_text_field($_POST['address1'] ?? '');
    $address2 = sanitize_text_field($_POST['address2'] ?? '');
    $city = sanitize_text_field($_POST['city'] ?? '');
    $state = sanitize_text_field($_POST['state'] ?? '');
    $zip = sanitize_text_field($_POST['zip'] ?? '');
    $country = sanitize_text_field($_POST['country'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $www = esc_url_raw($_POST['www'] ?? '');
    // Address logic
    if ($addressID) {
      if ($address1 || $address2 || $city || $state || $zip || $country || $phone || $email || $www) {
        $wpdb->update(
          $address_table,
          [
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => $country,
            'phone' => $phone,
            'email' => $email,
            'www' => $www
          ],
          ['addressID' => $addressID],
          ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
          ['%d']
        );
        $rval = 1;
      } else {
        $wpdb->delete($address_table, ['addressID' => $addressID], ['%d']);
        $addressID = 0;
      }
    } elseif ($address1 || $address2 || $city || $state || $zip || $country || $phone || $email || $www) {
      $wpdb->insert($address_table, [
        'address1' => $address1,
        'address2' => $address2,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
        'country' => $country,
        'gedcom' => $tree,
        'phone' => $phone,
        'email' => $email,
        'www' => $www
      ], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);
      $addressID = $wpdb->insert_id;
      $rval = 1;
    }
    // Event logic
    if ($eventID) {
      if ($age || $agency || $cause || $addressID || $info) {
        $wpdb->update(
          $events_table,
          [
            'age' => $age,
            'agency' => $agency,
            'cause' => $cause,
            'addressID' => $addressID,
            'info' => $info
          ],
          ['eventID' => $eventID],
          ['%s', '%s', '%s', '%d', '%s'],
          ['%d']
        );
        $rval = 1;
      } else {
        $wpdb->delete($events_table, ['eventID' => $eventID], ['%d']);
      }
    } else {
      $wpdb->insert($events_table, [
        'eventtypeID' => $eventtypeID,
        'persfamID' => $persfamID,
        'age' => $age,
        'agency' => $agency,
        'cause' => $cause,
        'addressID' => $addressID,
        'info' => $info,
        'gedcom' => $tree,
        'parenttag' => sanitize_text_field($_POST['parenttag'] ?? ''),
        'eventdate' => sanitize_text_field($_POST['eventdate'] ?? ''),
        'eventdatetr' => sanitize_text_field($_POST['eventdatetr'] ?? ''),
        'eventplace' => sanitize_text_field($_POST['eventplace'] ?? '')
      ], ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s']);
      $rval = 1;
    }
    wp_send_json_success(['result' => $rval, 'addressID' => $addressID]);
  }

  /**
   * Display the event management page
   */
  public function display_page()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submissions first
    $this->handle_form_submission();

    // Display any notices
    $this->display_notices();

    // Get current tab and determine view
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

    // Include the event management view
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/event-management.php';
  }

  /**
   * Get all events for a family (by familyID and gedcom)
   */
  public function get_family_events($family_id, $gedcom)
  {
    global $wpdb;
    $events_table = $wpdb->prefix . 'hp_events';
    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';
    $sql = $wpdb->prepare("
      SELECT e.eventID, e.eventdate, e.eventplace, e.info, et.display, et.tag
      FROM $events_table e
      LEFT JOIN $eventtypes_table et ON e.eventtypeID = et.eventtypeID
      WHERE e.persfamID = %s AND e.gedcom = %s
      ORDER BY e.eventdate, et.tag
    ", $family_id, $gedcom);
    return $wpdb->get_results($sql, ARRAY_A);
  }
}
