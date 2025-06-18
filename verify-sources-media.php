<?php
define('ABSPATH', 'c:/MAMP/htdocs/HeritagePress2/');
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';

global $wpdb;

echo "=== Sources and Media Verification ===\n";

// Check sources
$sources = $wpdb->get_results("SELECT sourceID, title, author, publisher FROM {$wpdb->prefix}hp_sources WHERE gedcom = 'test' LIMIT 5");
if ($sources) {
  echo "\nSources found:\n";
  foreach ($sources as $source) {
    echo "- {$source->sourceID}: {$source->title}\n";
    echo "  Author: {$source->author}\n";
    echo "  Publisher: {$source->publisher}\n\n";
  }
} else {
  echo "No sources found\n";
}

// Check media
$media = $wpdb->get_results("SELECT mediakey, description, form, path FROM {$wpdb->prefix}hp_media WHERE gedcom = 'test' LIMIT 5");
if ($media) {
  echo "\nMedia found:\n";
  foreach ($media as $medium) {
    echo "- {$medium->mediakey}: {$medium->description} ({$medium->form})\n";
    echo "  Path: " . substr($medium->path, 0, 80) . "...\n\n";
  }
} else {
  echo "No media found\n";
}

// Check repositories
$repos = $wpdb->get_results("SELECT repoID, reponame FROM {$wpdb->prefix}hp_repositories WHERE gedcom = 'test' LIMIT 5");
if ($repos) {
  echo "\nRepositories found:\n";
  foreach ($repos as $repo) {
    echo "- {$repo->repoID}: {$repo->reponame}\n";
  }
} else {
  echo "No repositories found\n";
}

// Check individuals
$people = $wpdb->get_results("SELECT personID, firstname, lastname FROM {$wpdb->prefix}hp_people WHERE gedcom = 'test' LIMIT 5");
if ($people) {
  echo "\nIndividuals found:\n";
  foreach ($people as $person) {
    echo "- {$person->personID}: {$person->firstname} {$person->lastname}\n";
  }
} else {
  echo "No individuals found\n";
}
