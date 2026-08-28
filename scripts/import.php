<?php

use com\elmakers\svenska\SvenskaDatabase;

require_once '../data/config.inc.php';
require_once '../data/SvenskaDatabase.class.php';

function update($db, $phrases, $fromLanguageId, $toLanguageId, $test) {
    $fromLanguageRecord = $db->getLanguage($fromLanguageId);
    if (!$fromLanguageRecord) {
        throw new Exception("Unknown language: $fromLanguageId");
    }
    $fromLanguage = $fromLanguageRecord['name'];
    $toLanguageRecord = $db->getLanguage($toLanguageId);
    if (!$toLanguageRecord) {
        throw new Exception("Unknown language: $toLanguageId");
    }
    $toLanguage = $toLanguageRecord['name'];

    $fromPhrases = $db->getLanguagePhrases($fromLanguageId);
    $fromPhrases = $db->index($fromPhrases, 'text');
    $toPhrases = $db->getLanguagePhrases($toLanguageId);
    $toPhrases = $db->index($toPhrases, 'text');

    $categories = $db->getCategories();
    $fromCategoryNameLookup = $db->getCategoryNames($fromLanguageId);
    $fromCategoryNames = array_change_key_case($db->index($fromCategoryNameLookup, 'name'));
    $toCategoryNameLookup = $db->getCategoryNames($toLanguageId);
    $toCategoryNames = array_change_key_case($db->index($toCategoryNameLookup, 'name'));

    $fromCategoryIndex = "Category $fromLanguage";
    $toCategoryIndex = "Category $toLanguage";

    foreach ($phrases as $row) {
        if (!isset($row[$fromLanguage]) || !isset($row[$toLanguage]) || !isset($row[$fromCategoryIndex]) || !isset($row[$toCategoryIndex])) {
            echo json_encode($row) . "\n";
            echo "$fromLanguage, $toLanguage, $fromCategoryIndex, $toCategoryIndex\n";

            throw new Exception("Failed to find required column");
        }

        $fromText = $row[$fromLanguage];
        $toText = $row[$toLanguage];
        $fromCategory = $row[$fromCategoryIndex];
        $fromCategoryKey = strtolower($fromCategory);
        $toCategory = $row[$toCategoryIndex];
        $toCategoryKey = $toCategory ? strtolower($toCategory) : $toCategory;

        if (isset($fromPhrases[$fromText]) && isset($toPhrases[$toText])) {
            if (!$test) {
                echo "Skipped: $fromText\n";
            }
            continue;
        }

        if (!$fromCategory) {
            throw new Exception("Missing category for $fromText");
        }

        if (isset($fromCategoryNames[$fromCategoryKey])) {
            $categoryId = $fromCategoryNames[$fromCategoryKey]['category_id'];
            if ($toCategoryKey && isset($toCategoryNameLookup[$categoryId]) && $toCategoryNameLookup[$categoryId]['name'] != $toCategory) {
                throw new Exception("Category mismatch for $fromCategory ($categoryId) and $toCategory | {$toCategoryNameLookup[$categoryId]['name']}");
            }
            if ($toCategoryKey && !isset($toCategoryNames[$toCategoryKey])) {
                echo "Adding new translation for category: $fromCategory => $toCategory\n";
                if (!$test) {
                    $db->insert('category_name', array('category_id' => $categoryId, 'language_id' => $toLanguageId, 'name' => $toCategory));
                }
                $toCategoryNames[$toCategoryKey] = array('category_id' => $categoryId);
            }
        } else if (isset($toCategoryNames[$toCategoryKey])) {
            $categoryId = $toCategoryNames[$toCategoryKey]['category_id'];
            if (isset($fromCategoryNameLookup[$categoryId])) {
                throw new Exception("Category mismatch for ($categoryId) creating new record for $fromCategoryKey => $toCategory | {$fromCategoryNameLookup[$categoryId]['name']}");
            }
            echo "Adding new translation for category: $fromCategory => $toCategory\n";
            if (!$test) {
                $db->insert('category_name', array('category_id' => $categoryId, 'language_id' => $fromLanguageId, 'name' => $fromCategory));
            }
            $fromCategoryNames[$fromCategoryKey] = array('category_id' => $categoryId);
        } else {
            if (!$toCategory) {
                throw new Exception("Missing translation for new category $fromCategory");
            }
            echo "Adding new category: $fromCategory\n";
            $categoryId = guidv4();
            if (!$test) {
                $db->insert('category', array('id' => $categoryId));
                $db->insert('category_name', array('category_id' => $categoryId, 'language_id' => $fromLanguageId, 'name' => $fromCategory));
                $db->insert('category_name', array('category_id' => $categoryId, 'language_id' => $toLanguageId, 'name' => $toCategory));
            }
            $fromCategoryNames[$fromCategoryKey] = array('category_id' => $categoryId);
            $toCategoryNames[$toCategoryKey] = array('category_id' => $categoryId);
            $categories[$categoryId] = array('id' => $categoryId);
        }

        echo "Adding: $fromText\n";

        $fromPhrase = $fromPhrases[$fromText] ?? null;
        $toPhrase = $toPhrases[$toText] ?? null;

        if (!$fromPhrase) {
            if (!$test) {
                $db->insert('phrase', array('language_id' => $fromLanguageId, 'text' => $fromText, 'category_id' => $categoryId));
                $fromPhrase = $db->getUnique('phrase', array('language_id' => $fromLanguageId, 'text' => $fromText, 'category_id' => $categoryId));
            } else {
                echo "  Adding: $fromText\n";
                $fromPhrase = array('id' => guidv4(), 'language_id' => $fromLanguageId, 'text' => $fromText, 'category_id' => $categoryId);
            }
            $fromPhrases[$fromText] = $fromPhrase;
        }
        if (!$toPhrase) {
            echo "  Adding: $toText\n";
            if (!$test) {
                $db->insert('phrase', array('language_id' => $toLanguageId, 'text' => $toText, 'category_id' => $categoryId));
                $toPhrase = $db->getUnique('phrase', array('language_id' => $toLanguageId, 'text' => $toText, 'category_id' => $categoryId));
            } else {
                $toPhrase = array('id' => guidv4(), 'language_id' => $toLanguageId, 'text' => $toText, 'category_id' => $categoryId);
            }
            $toPhrases[$toText] = $toPhrase;
        }

        if (!$fromPhrase || !$toPhrase) {
            throw new Exception("Failed to insert phrases");
        }

        if (!$test) {
            $db->insert('translation', array(
                'from_phrase_id' => $fromPhrase['id'],
                'to_phrase_id' => $toPhrase['id']
            ));
        }
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
    $languageTranslations = $db->getLanguageTranslations();
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

    foreach ($languageTranslations as $languageTranslation) {
        $fromLanguage =  $languageTranslation['from_language_id'];
        $toLanguage = $languageTranslation['to_language_id'];
        echo "Updating $fromLanguage => $toLanguage\n";
        update($db, $phrases, $fromLanguage, $toLanguage, $testMode);
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