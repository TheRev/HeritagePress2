<?php

/**
 * Check HeritagePress people data to debug browse page issues
 */

// WordPress database connection
define('DB_NAME', 'wordpress');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');

try {
  $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  echo "=== HERITAGEPRESS PEOPLE DATA DEBUG ===\n\n";

  // Check current data in hp_people table
  $stmt = $pdo->query("SELECT * FROM wp_hp_people ORDER BY changedate DESC LIMIT 10");
  $people = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($people)) {
    echo "No people found in wp_hp_people table.\n";
  } else {
    echo "Found " . count($people) . " people records:\n\n";

    foreach ($people as $person) {
      echo "==========================================\n";
      echo "Person ID: " . $person['personID'] . "\n";
      echo "Gedcom: " . $person['gedcom'] . "\n";
      echo "First Name: " . $person['firstname'] . "\n";
      echo "Last Name: " . $person['lastname'] . "\n";
      echo "Prefix: " . $person['prefix'] . "\n";
      echo "Suffix: " . $person['suffix'] . "\n";
      echo "Nickname: " . $person['nickname'] . "\n";
      echo "Title: " . $person['title'] . "\n";
      echo "Sex: " . $person['sex'] . "\n";
      echo "Birth Date: " . $person['birthdate'] . "\n";
      echo "Birth Place: " . $person['birthplace'] . "\n";
      echo "Death Date: " . $person['deathdate'] . "\n";
      echo "Death Place: " . $person['deathplace'] . "\n";
      echo "Living: " . $person['living'] . "\n";
      echo "Private: " . $person['private'] . "\n";
      echo "Changed By: " . $person['changedby'] . "\n";
      echo "Change Date: " . $person['changedate'] . "\n";
      echo "==========================================\n\n";
    }
  }

  // Check tree data
  echo "\n=== TREE DATA ===\n";
  $stmt = $pdo->query("SELECT * FROM wp_hp_trees");
  $trees = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($trees)) {
    echo "No trees found in wp_hp_trees table.\n";
  } else {
    foreach ($trees as $tree) {
      echo "Tree: " . $tree['gedcom'] . " - " . $tree['treename'] . "\n";
    }
  }
} catch (PDOException $e) {
  echo "Database connection failed: " . $e->getMessage() . "\n";
}
