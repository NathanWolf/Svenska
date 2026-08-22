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
    if (!isset($headers['from']) || !isset($headers['to']) || !isset($headers['category'])) {
        throw new Exception("Failed to find required column in file $filename");
    }
    $fromIndex = $headers['from'];
    $toIndex = $headers['to'];
    $categoryIndex = $headers['category'];
    $phrases = array();
    while (($row = fgetcsv($file, null, ',', '"', '\\')) !== FALSE) {
        $fromText = sanitize($row[$fromIndex]);
        $toText = sanitize($row[$toIndex]);
        $categoryId = $row[$categoryIndex];

        if (isset($fromPhrases[$fromText]) || isset($toPhrases[$toText])) {
            echo "SKIPPED: $fromText\n";
            continue;
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
    return str_replace('’', "'", $text);
}
