<?php
$characterTiming = 1.2;
$spaceTiming = 0.5;

$characterStart = array();
$characterStop = array();

$characterCount = 28;
$characters = array("a"," ","b"," ","c"," ","d"," ","e"," ","f"," ","g"," ","h"," ","i"," ","j"," ","k"," ","l"," ","m"," ","n"," ","o"," ","p"," ","q"," ","r"," ","s"," ","t"," ","u"," ","v"," ","x"," ","y"," ","z"," ","å"," ","ä"," ","ö", "");

$currentTime = 0;
for ($i = 0; $i < $characterCount; $i++) {
    $characterStart[] = $currentTime;
    $currentTime += $characterTiming;
    $characterStop[] = $currentTime;
    $characterStart[] = $currentTime;
    $currentTime += $spaceTiming;
    $characterStop[] = $currentTime;
}

echo json_encode(array(
    'characters' => $characters,
    'character_start_times_seconds' => $characterStart,
    'character_end_times_seconds' => $characterStop
)) . "\n";
