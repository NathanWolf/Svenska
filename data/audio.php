<?php
header("Content-Type: application/json");

require_once 'SvenskaDatabase.class.php';
require_once 'TextToSpeech.class.php';
require_once 'utilities.inc.php';
require_once 'version.inc.php';

use com\elmakers\svenska\TextToSpeech;
use com\elmakers\svenska\SvenskaDatabase;

try {
    $phraseId = getParameter('phrase');

    $db = new SvenskaDatabase();
    $api = new TextToSpeech($db);
    $audio = $api->getPhraseAudio($phraseId);

    echo json_encode(array(
        'success' => true,
        'version' => VERSION,
        'phrase' => $phraseId,
        'audio' => $audio
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}
