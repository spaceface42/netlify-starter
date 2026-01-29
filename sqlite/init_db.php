<?php
$DB_FILE = __DIR__ . '/database.sqlite';
$SCHEMA_FILE = __DIR__ . '/schema.sql';

/* -------------------------
   Safety check
------------------------- */
if (file_exists($DB_FILE)) {
    die("Database already exists. Delete it first if you want to re-init.\n");
}

/* -------------------------
   Ensure folder exists
------------------------- */
if (!is_dir(dirname($DB_FILE))) {
    mkdir(dirname($DB_FILE), 0777, true);
}

/* -------------------------
   Read SQL schema
------------------------- */
$sql = file_get_contents($SCHEMA_FILE);
if ($sql === false) {
    die("Could not read schema.sql\n");
}

/* -------------------------
   Create DB + execute schema
------------------------- */
try {
    $db = new PDO("sqlite:$DB_FILE");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec($sql);
    echo "SQLite database created successfully.\n";
} catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}
