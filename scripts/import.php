<?php

use com\elmakers\svenska\SvenskaDatabase;

require_once '../data/config.inc.php';
require_once '../data/SvenskaDatabase.class.php';

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

    $db = new SvenskaDatabase(true);

    $fromLanguage = 'en-us';
    $toLanguage = 'sv-se';

    $fromPhrases = $db->getLanguagePhrases($fromLanguage);
    $fromPhrases = $db->index($fromPhrases, 'text');
    $toPhrases = $db->getLanguagePhrases($toLanguage);
    $toPhrases = $db->index($toPhrases, 'text');

    $categories = $db->getCategories();
    $fromCategoryNames = $db->getCategoryNames($fromLanguage);
    $fromCategoryNames = $db->index($fromCategoryNames, 'name');
    $toCategoryNames = $db->getCategoryNames($toLanguage);
    $toCategoryNames = $db->index($toCategoryNames, 'name');

    echo "Loading $filename\n";

    $file = fopen($filename, "r");
    if ($file === false) {
        throw new Exception("Failed to open file $filename");
    }
    $headers = fgetcsv($file, null, ',', '"', '\\');
    if (!$headers) {
        throw new Exception("Failed to find header row in file $filename");
    }
    $headers = array_flip($headers);
    if (!isset($headers['English']) || !isset($headers['Svenska']) || !isset($headers['Category English']) || !isset($headers['Category Svenska'])) {
        throw new Exception("Failed to find required column in file $filename");
    }
    $fromIndex = $headers['English'];
    $toIndex = $headers['Svenska'];
    $fromCategoryIndex = $headers['Category English'];
    $toCategoryIndex = $headers['Category Svenska'];
    $phrases = array();
    while (($row = fgetcsv($file, null, ',', '"', '\\')) !== FALSE) {
        $fromText = sanitize($row[$fromIndex]);
        $toText = sanitize($row[$toIndex]);
        $fromCategory = $row[$fromCategoryIndex];
        $toCategory = $row[$toCategoryIndex];

        if (isset($fromPhrases[$fromText]) || isset($toPhrases[$toText])) {
            echo "Skipped: $fromText\n";
            continue;
        }

        if (isset($fromCategoryNames[$fromCategory])) {
            $categoryId = $fromCategoryNames[$fromCategory]['category_id'];
            if (!isset($toCategoryNames[$toCategory]) || $toCategoryNames[$toCategory]['category_id'] != $categoryId) {
                throw new Exception("Category mismatch for $fromCategory ($categoryId) and $toCategory");
            }
        } else {
            echo "Adding new category: $fromCategory\n";
            $categoryId = guidv4();
            $db->insert('category', array('id' => $categoryId));
            $db->insert('category_name', array('category_id' => $categoryId, 'language_id' => $fromLanguage, 'name' => $fromCategory));
            $db->insert('category_name', array('category_id' => $categoryId, 'language_id' => $toLanguage, 'name' => $toCategory));
            $fromCategoryNames[$fromCategory] = array('category_id' => $categoryId);
            $toCategoryNames[$toCategory] = array('category_id' => $categoryId);
            $categories[$categoryId] = array('id' => $categoryId);
        }

        echo "Adding: $fromText\n";
        $db->insert('phrase', array('language_id' => $fromLanguage, 'text' => $fromText, 'category_id' => $categoryId));
        $db->insert('phrase', array('language_id' => $toLanguage, 'text' => $toText, 'category_id' => $categoryId));

        $fromPhrase = $db->getUnique('phrase', array('language_id' => $fromLanguage, 'text' => $fromText, 'category_id' => $categoryId));
        $toPhrase = $db->getUnique('phrase', array('language_id' => $toLanguage, 'text' => $toText, 'category_id' => $categoryId));

        if (!$fromPhrase || !$toPhrase) {
            throw new Exception("Failed to insert phrases");
        }

        $db->insert('translation', array(
            'from_phrase_id' => $fromPhrase['id'],
            'to_phrase_id' => $toPhrase['id']
        ));
    }

    fclose($file);
    $file = null;
} catch (Exception $ex) {
    echo "Unexpected exception: " . $ex->getMessage() . "\n";
    if ($file) {
        fclose($file);
    }
}

function sanitize($text) {
    return trim(str_replace('’', "'", $text));
}

function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}