<?php
// Investigate family relationship issues and tree assignments
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

global $wpdb;

echo "=== INVESTIGATING FAMILY RELATIONSHIP ISSUES ===\n\n";

// Check the current family that has missing spouse references
echo "1. CHECKING FAMILY WITH MISSING SPOUSE REFERENCES\n";
$families_with_issues = $wpdb->get_results("
    SELECT f.familyID, f.husband, f.wife, f.gedcom,
           h.personID as husband_exists, w.personID as wife_exists
    FROM {$wpdb->prefix}hp_families f
    LEFT JOIN {$wpdb->prefix}hp_people h ON f.husband = h.personID AND f.gedcom = h.gedcom
    LEFT JOIN {$wpdb->prefix}hp_people w ON f.wife = w.personID AND f.gedcom = w.gedcom
    WHERE f.gedcom = 'comprehensive_test'
    AND ((f.husband IS NOT NULL AND f.husband != '' AND h.personID IS NULL)
         OR (f.wife IS NOT NULL AND f.wife != '' AND w.personID IS NULL))
");

if (!empty($families_with_issues)) {
  echo "Families with missing spouse references:\n";
  foreach ($families_with_issues as $family) {
    echo "- Family {$family->familyID}: ";
    if ($family->husband && !$family->husband_exists) {
      echo "Missing husband {$family->husband} ";
    }
    if ($family->wife && !$family->wife_exists) {
      echo "Missing wife {$family->wife} ";
    }
    echo "\n";
  }
} else {
  echo "✓ No families with missing spouse references found\n";
}

echo "\n2. CHECKING ALL INDIVIDUALS AND FAMILIES\n";
$people = $wpdb->get_results("SELECT personID, firstname, lastname FROM {$wpdb->prefix}hp_people WHERE gedcom = 'comprehensive_test' ORDER BY personID");
echo "Individuals in database:\n";
foreach ($people as $person) {
  echo "- {$person->personID}: {$person->firstname} {$person->lastname}\n";
}

$families = $wpdb->get_results("SELECT familyID, husband, wife FROM {$wpdb->prefix}hp_families WHERE gedcom = 'comprehensive_test' ORDER BY familyID");
echo "\nFamilies in database:\n";
foreach ($families as $family) {
  echo "- {$family->familyID}: Husband={$family->husband}, Wife={$family->wife}\n";
}

echo "\n3. CHECKING TREE ASSIGNMENTS\n";
$tree_exists = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_trees WHERE gedcom = 'comprehensive_test'");
echo "Tree record exists: " . ($tree_exists ? "YES" : "NO") . "\n";

if (!$tree_exists) {
  echo "Creating tree record...\n";
  $wpdb->insert($wpdb->prefix . 'hp_trees', [
    'gedcom' => 'comprehensive_test',
    'treename' => 'Comprehensive Test Tree',
    'description' => 'Test tree for comprehensive GEDCOM validation'
  ]);
  echo "✓ Tree record created\n";
}

echo "\n4. CHECKING CHILDREN RELATIONSHIPS\n";
$children = $wpdb->get_results("
    SELECT c.familyID, c.personID, f.husband, f.wife, p.firstname, p.lastname
    FROM {$wpdb->prefix}hp_children c
    JOIN {$wpdb->prefix}hp_families f ON c.familyID = f.familyID AND c.gedcom = f.gedcom
    JOIN {$wpdb->prefix}hp_people p ON c.personID = p.personID AND c.gedcom = p.gedcom
    WHERE c.gedcom = 'comprehensive_test'
    ORDER BY c.familyID, c.ordernum
");

if (!empty($children)) {
  echo "Children relationships:\n";
  foreach ($children as $child) {
    echo "- {$child->firstname} {$child->lastname} ({$child->personID}) is child of family {$child->familyID} (Parents: {$child->husband}, {$child->wife})\n";
  }
} else {
  echo "No children relationships found\n";
}

echo "\n5. CHECKING SOURCES AND REPOSITORIES\n";
$sources = $wpdb->get_results("SELECT sourceID, title, author, repoID FROM {$wpdb->prefix}hp_sources WHERE gedcom = 'comprehensive_test'");
echo "Sources:\n";
foreach ($sources as $source) {
  echo "- {$source->sourceID}: {$source->title} by {$source->author} (Repo: {$source->repoID})\n";
}

$repos = $wpdb->get_results("SELECT repoID, reponame FROM {$wpdb->prefix}hp_repositories WHERE gedcom = 'comprehensive_test'");
echo "\nRepositories:\n";
foreach ($repos as $repo) {
  echo "- {$repo->repoID}: {$repo->reponame}\n";
}

echo "\n6. CHECKING CITATIONS AND SOURCE LINKS\n";
$citations = $wpdb->get_results("
    SELECT c.citationID, c.persfamID, c.sourceID, c.page, s.title
    FROM {$wpdb->prefix}hp_citations c
    LEFT JOIN {$wpdb->prefix}hp_sources s ON c.sourceID = s.sourceID AND c.gedcom = s.gedcom
    WHERE c.gedcom = 'comprehensive_test'
");

if (!empty($citations)) {
  echo "Citations:\n";
  foreach ($citations as $citation) {
    echo "- Citation {$citation->citationID}: {$citation->persfamID} -> {$citation->sourceID} ({$citation->title}) Page: {$citation->page}\n";
  }
} else {
  echo "No citations found\n";
}

echo "\n7. CHECKING NOTES AND NOTE LINKS\n";
$notes = $wpdb->get_results("SELECT ID, noteID, note FROM {$wpdb->prefix}hp_xnotes WHERE gedcom = 'comprehensive_test'");
echo "Notes in xnotes table:\n";
foreach ($notes as $note) {
  $truncated = substr($note->note, 0, 50) . (strlen($note->note) > 50 ? '...' : '');
  echo "- Note {$note->noteID} (ID: {$note->ID}): {$truncated}\n";
}

$notelinks = $wpdb->get_results("
    SELECT nl.persfamID, nl.eventID, nl.xnoteID, xn.noteID
    FROM {$wpdb->prefix}hp_notelinks nl
    JOIN {$wpdb->prefix}hp_xnotes xn ON nl.xnoteID = xn.ID AND nl.gedcom = xn.gedcom
    WHERE nl.gedcom = 'comprehensive_test'
");

if (!empty($notelinks)) {
  echo "\nNote links:\n";
  foreach ($notelinks as $link) {
    echo "- {$link->persfamID} (event: {$link->eventID}) -> Note {$link->noteID} (xnoteID: {$link->xnoteID})\n";
  }
} else {
  echo "No note links found\n";
}

echo "\n=== INVESTIGATION COMPLETED ===\n";
