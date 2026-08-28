<?php

use com\elmakers\svenska\SvenskaDatabase;

require_once '../data/config.inc.php';
require_once '../data/SvenskaDatabase.class.php';

function update($db, $labels, $languageId, $test) {
    $languageRecord = $db->getLanguage($languageId);
    if (!$languageRecord) {
        throw new Exception("Unknown language: $languageId");
    }
    $languageName = $languageRecord['name'];
    $uiText = $db->getUIText($languageId);
    $uiKey = 'Key';
    foreach ($labels as $row) {
        if (!isset($row[$languageName]) || !isset($row[$uiKey])) {
            throw new Exception("Failed to find required column");
        }

        $text = $row[$languageName];
        $key = $row[$uiKey];
        $ui = $uiText[$key] ?? null;
        if ($ui) {
            if ($ui['name']) {
                if ($ui['name'] != $text) {
                    throw new Exception("UI text mismatch for $key | {$ui['name']} => $text");
                }
                echo "Skipped: $key\n";
                continue;
            }
        } else {
            echo "Adding new UI key: $key\n";
            $uiText = array('id' => $key);
            if (!$test) {
                $db->insert('ui_text', $uiText);
            }
            $ui['name'] = null;
        }
        if (!$ui['name']) {
            echo "Adding new name for key $key: $text\n";
            $uiTextName = array('ui_text_id' => $key, 'name' => $text, 'language_id' => $languageId);
            if (!$test) {
                $db->insert('ui_text_name', $uiTextName);
            }
            $ui['name'] = $text;
        }
        $uiText[$key] = $ui;
    }
}

$file = null;
try {
    if (PHP_SAPI !== 'cli') {
        throw new Exception('What are you doing?');
    }

    if (count($argv) < 2) {
        throw new Exception("Usage: php import.php phrases.csv");
    }

    $filename = $argv[1];
    if (!file_exists($filename)) {
        throw new Exception("Input file not found: $filename");
    }

    $testMode = in_array('--test', $argv);
    $db = new SvenskaDatabase(true);
    $languages = $db->getLanguages();
    echo "Loading $filename\n";
    $file = fopen($filename, "r");
    if ($file === false) {
        throw new Exception("Failed to open file $filename");
    }
    $headers = fgetcsv($file, null, ',', '"', '\\');
    if (!$headers) {
        throw new Exception("Failed to find header row in file $filename");
    }
    $phrases = array();
    while (($row = fgetcsv($file, null, ',', '"', '\\')) !== FALSE) {
        $phrase = array();
        foreach ($headers as $index => $header) {
            $phrase[$header] = sanitize($row[$index]);
        }
        $phrases[] = $phrase;
    }

    fclose($file);
    $file = null;

    foreach ($languages as $language) {
        $languageId = $language['id'];
        echo "Updating $languageId\n";
        update($db, $phrases, $languageId, $testMode);
    }
} catch (Exception $ex) {
    echo "Unexpected exception: " . $ex->getMessage() . "\n";
    if ($file) {
        fclose($file);
    }
}

function sanitize($text) {
    return trim(str_replace('’', "'", $text));
}
