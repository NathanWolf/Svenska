<?php

namespace com\elmakers\svenska;

use Exception;

require_once 'Database.class.php';

class SvenskaDatabase extends Database {

    public function createUser($email, $password, $firstName, $lastName, $address) {
        $existing = $this->lookupUser($email);
        if ($existing) {
            throw new Exception("User $email already exists");
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = array(
            'email' => $email,
            'password_hash' => $hash,
            'first_name' => $firstName,
            'last_name' => $lastName
        );
        $this->insert('user', $user);
        $user = $this->lookupUser($email);
        if (!$user) {
            throw new Exception("Failed to create new user account");
        }
        $token = $this->generateToken();
        $this->insert('user_token', array('user_id' => $user['id'], 'token' => $token, 'remote_address' => $address));
        $this->sanitize($user, $token);
        $user['properties'] = array();
        return $user;
    }

    public function getUsers() {
        return $this->getAll('user');
    }

    private function generateToken() {
        return bin2hex(random_bytes(16));
    }

    public function login($email, $password, $address) {
        $user = $this->lookupUser($email);
        if (!$user) {
            throw new Exception("Unknown user $email");
        }
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Incorrect password for user $email");
        }
        $token = $this->generateToken();
        $this->insert('user_token', array('user_id' => $user['id'], 'token' => $token, 'remote_address' => $address));
        $this->sanitize($user, $token);
        return $user;
    }

    public function sanitize(&$user, $token) {
        unset($user['password_hash']);
        $user['token'] = $token;
    }

    public function changePassword($userId, $token, $password) {
        $user = $this->validateLogin($userId, $token);
        $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $this->saveUser($user);
    }

    public function forceChangePassword($email, $password) {
        $user = $this->lookupUser($email);
        if (!$user) {
            throw new Exception("Invalid user $email");
        }
        $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $this->saveUser($user);
    }

    public function validateLogin($userId, $token) {
        $user = $this->getUser($userId);
        if (!$user) {
            throw new Exception("Invalid user: $userId");
        }
        $token = $this->queryOne('user_token', 'user_id=:user AND token=:token', array('user' => $userId, 'token' => $token));
        if (!$token){
            throw new Exception("Invalid logic for user: $userId");
        }
        $this->sanitize($user, $token['token']);
        return $user;
    }

    public function logout($userId, $token) {
        $this->validateLogin($userId, $token);
        $this->execute('DELETE FROM user_token WHERE user_id=:user AND token=:token', array('user' => $userId, 'token' => $token));
    }

    private function processUser(&$user) {
        if ($user) {
            if ($user['chat']) {
                $user['chat'] = json_decode($user['chat'], true);
            }
            if ($user['preferences']) {
                $user['preferences'] = json_decode($user['preferences'], true);
            } else {
                $user['preferences'] = array();
            }
        }
    }

    public function lookupUser($email) {
        $user = $this->get('user', $email, 'email');
        $this->processUser($user);
        return $user;
    }

    public function getUser($userId) {
        $user = $this->get('user', $userId);
        $this->processUser($user);
        return $user;
    }

    public function saveUser($user) {
        unset($user['properties']);
        if (isset($user['preferences'])) {
            $user['preferences'] = json_encode($user['preferences']);
        }
        $this->save('user', $user);
    }

    public function getPhrases() {
        $phrases = $this->getAll('phrase');
        return $this->index($phrases);
    }

    public function getPhrase($phraseId) {
        return $this->get('phrase', $phraseId);
    }

    public function getLanguage($languageId){
        $language = $this->get('language', $languageId);
        if (!$language) {
            $language = $this->queryOne('language', 'language_id=:id order by priority asc limit 1', array('id' => $languageId));
        }
        return $language;
    }

    public function getLanguagePhrases($languageId) {
        $phrases = $this->getMultiple('phrase', $languageId, 'language_id');
        return $this->index($phrases);
    }

    public function getCategories() {
        $categories = $this->getAll('category', 'priority');
        return $this->index($categories);
    }

    public function getCategoryNames($languageId) {
        $names = $this->getMultiple('category_name', $languageId, 'language_id');
        return $this->index($names, 'category_id');
    }

    public function getTranslations() {
        $translations = $this->getAll('translation');
        return $this->multiIndex($translations, 'from_phrase_id', 'to_phrase_id');
    }

    public function getLanguageTranslations() {
        return $this->getAll('language_translation');
    }

    public function getLanguages() {
        $languages = $this->getAll('language');
        return $this->index($languages);
    }

    public function getUIText($languageId) {
        $uiText = $this->getAll('ui_text');
        $uiTextNames = $this->getMultiple('ui_text_name', $languageId, 'language_id');
        $uiTextNames = $this->index($uiTextNames, 'ui_text_id');
        foreach ($uiText as &$text) {
            $text['name'] = $uiTextNames[$text['id']]['name'] ?? null;
        }
        return $this->index($uiText);
    }

    public function getAudio($phraseId, $voiceId, $speed) {
        $audio = $this->queryOne('audio', 'phrase_id=:phrase AND voice_id=:voice AND speed=:speed', array('phrase' => $phraseId, 'voice' => $voiceId, 'speed' => $speed));
        if ($audio) {
            if ($audio['alignment']) $audio['alignment'] = json_decode($audio['alignment'], true);
            if ($audio['normalized_alignment']) $audio['normalized_alignment'] = json_decode($audio['normalized_alignment'], true);
        }
        return $audio;
    }

    public function getSampleAudio() {
        $audio = $this->queryOne('audio', '1 limit 1');
        if ($audio) {
            if ($audio['alignment']) $audio['alignment'] = json_decode($audio['alignment'], true);
            if ($audio['normalized_alignment']) $audio['normalized_alignment'] = json_decode($audio['normalized_alignment'], true);
        }
        return $audio;
    }

    public function insertAudio($phraseId, $voiceId, $speed, $alignment, $normalizedAlignment) {
        return $this->insert('audio', array('phrase_id' => $phraseId, 'voice_id' => $voiceId, 'speed' => $speed, 'alignment' => $alignment, 'normalized_alignment' => $normalizedAlignment));
    }
}
