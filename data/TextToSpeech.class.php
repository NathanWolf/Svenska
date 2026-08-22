<?php

namespace com\elmakers\svenska;

use Exception;

require_once 'SvenskaDatabase.class.php';
require_once 'config.inc.php';

class TextToSpeech {
    private SvenskaDatabase $db;
    private string $apiKey;
    private string $voiceId;
    private float $speed;
    private string $cacheDir;

    public function __construct(SvenskaDatabase $db) {
        $this->db = $db;
        $this->apiKey = _CONFIG['elevenlabs']['api-key'];
        $this->voiceId = _CONFIG['elevenlabs']['voice-id'];
        $this->speed = 0.75;
        $this->cacheDir = __DIR__ . '/audio';
    }

    public function getPhraseAudio(string $phraseId): array {
        $phrase = $this->db->getPhrase($phraseId);
        if (!$phrase) {
            throw new Exception("Phrase not found: $phraseId");
        }

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        $audio = $this->db->getAudio($phraseId, $this->voiceId, $this->speed);
        if (!$audio) {
            $responseBody = $this->generate($phrase['text']);
            $decoded = json_decode($responseBody, true);
            if ($decoded === null || !isset($decoded['audio_base64'])) {
                throw new Exception("Unexpected response shape: {$responseBody}");
            }

            $audioBinary = base64_decode($decoded['audio_base64']);
            $alignment = $decoded['alignment'] ?? null;
            $normalizedAlignment = $decoded['normalized_alignment'] ?? null;
            if ($alignment) $alignment = json_encode($alignment);
            if ($normalizedAlignment) $normalizedAlignment = json_encode($normalizedAlignment);
            $this->db->insertAudio($phraseId, $this->voiceId, $this->speed, $alignment, $normalizedAlignment);
            $audio = $this->db->getAudio($phraseId, $this->voiceId, $this->speed);
            if (!$audio) {
                throw new Exception("Failed to insert audio record");
            }
            $audio['audio'] = $audioBinary;
            $filePath = rtrim($this->cacheDir, '/') . '/' . $audio['id'] . '.mp3';
            file_put_contents($filePath, $audioBinary);
        } else {
            $filePath = rtrim($this->cacheDir, '/') . '/' . $audio['id'] . '.mp3';
            if (!file_exists($filePath)) {
                throw new Exception("File not found: " . $filePath);
            }
            $audio['audio'] = file_get_contents($filePath);
        }
        return $audio;
    }

    private function generate(string $text): string {
        $url = "https://api.elevenlabs.io/v1/text-to-speech/{$this->voiceId}/with-timestamps";

        $payload = json_encode([
            'text' => $text,
            'model_id' => 'eleven_multilingual_v2',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
                'speed' => 0.75
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'xi-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: audio/mpeg',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($audioData === false) {
            throw new Exception("cURL error: {$curlError}");
        }

        if ($httpCode !== 200) {
            // On errors ElevenLabs returns JSON, not audio -- surface it directly.
            throw new Exception("ElevenLabs API error (HTTP {$httpCode}): {$audioData}");
        }

        return $audioData;
    }
}
