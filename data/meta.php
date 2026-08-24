<?php
header("Content-Type: application/json");

require_once 'SvenskaDatabase.class.php';
require_once 'utilities.inc.php';
require_once 'version.inc.php';

use com\elmakers\svenska\SvenskaDatabase;

try {
    $db = new SvenskaDatabase();
    $fromLanguage = getParameter('from');
    $toLanguage = getParameter('to');

    $fromPhrases = $db->getLanguagePhrases($fromLanguage);
    $toPhrases = $db->getLanguagePhrases($toLanguage);

    // May need to optimize this in the future?
    $translations = $db->getTranslations();

    $phrases = array();

    foreach ($fromPhrases as $fromPhrase) {
        if (isset($translations[$fromPhrase['id']])) {
            foreach ($translations[$fromPhrase['id']] as $candidate) {
                if (isset($toPhrases[$candidate['to_phrase_id']])) {
                    $fromPhrase['translation'] = $toPhrases[$candidate['to_phrase_id']];
                    $phrases[$fromPhrase['id']] = $fromPhrase;
                    break;
                }
            }
        }
    }

    $categories = $db->getCategories();
    $fromCategories = $db->getCategoryNames($fromLanguage);
    $toCategories = $db->getCategoryNames($toLanguage);

    foreach ($categories as &$category) {
        $category['from_name'] = $fromCategories[$category['id']]['name'] ?? null;
        $category['to_name'] = $toCategories[$category['id']]['name'] ?? null;
        $category['phrases'] = [];
        $category['children'] = [];
    }

    foreach ($phrases as $phrase) {
        $categories[$phrase['category_id']]['phrases'][] = $phrase['id'];
    }

    $allCategories = $categories;
    $categories = array();
    foreach ($allCategories as &$category) {
        if ($category['parent_category_id']) {
            $allCategories[$category['parent_category_id']]['children'][] = $category;
        } else {
            $categories[$category['id']] = &$category;
        }
    }

    echo json_encode(array(
        'success' => true,
        'version' => VERSION,
        'phrases' => $phrases,
        'categories' => $categories
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}
