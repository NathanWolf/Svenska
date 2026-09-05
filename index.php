<?php

require_once('data/version.inc.php');

?>
<!DOCTYPE html>
<html lang="en-us">
    <head>
        <meta charset="utf-8">
        <title>På Svenska, Tack!</title>
        <meta name="viewport" content="width=device-width,initial-scale=0.5,maximum-scale=0.5,user-scalable=no"/>
        <meta name="theme-color" content="#06070b">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="stylesheet" href="css/main.css?v=<?=VERSION?>">
        <script type="text/javascript" src="js/svenska.js?v=<?=VERSION?>"></script>

        <script type="text/javascript">
            const _version = <?=VERSION?>;
            var _svenska = null;
            window.onload = function() {
                _svenska = new Svenska();
                _svenska.register();
                _svenska.load();
            };
            window.onerror = function(msg, url, line, col, error) { _svenska.onInternalError(msg, url, line, col, error); };
        </script>
    </head>
<body>
    <div id="main">
        <div id="modes" style="display: none">
            <h1 id="app_title">På Svenska, Tack!</h1>
            <div id="mode_list">
                <div id="mode_listen" class="mode">

                </div>
                <div id="mode_flashcards_from" class="mode">

                </div>
                <div id="mode_flashcards_to" class="mode">

                </div>
            </div>
        </div>
        <div id="categories" style="display: none">
            <div class="toolbar toolbar_top">
                <div class="header">
                    <span id="button_category_back" class="icon-button">&#x2190;&#xFE0E;</span>
                    <span id="category_title" class="screen_title"></span>
                </div>
            </div>
            <div id="category_list">

            </div>
        </div>
        <div id="lesson" style="display: none">
            <div class="toolbar toolbar_top">
                <div class="controls" id="controls_top">
                    <div class="left_group">
                        <span id="button_back" class="icon-button">&#x2715;&#xFE0E;</span>
                    </div>
                    <div class="center_group">
                        <span id="progress" class="screen_title"></span>
                    </div>
                    <div class="right_group">
                        <span id="button_settings" class="icon-button">&#x2699;&#xFE0E;</span>
                    </div>
                </div>
            </div>
            <div id="translation">
                <div id="translation_to">
                    <div id="phrase">

                    </div>
                </div>
                <div id="translation_from">

                </div>
            </div>
            <div class="toolbar toolbar_bottom">
                <div class="controls" id="controls_bottom">
                    <div class="left_group">
                        <span id="button_repeat" class="icon-button">&#x27F2;&#xFE0E;</span>
                        <span id="button_wait" class="icon-button icon-button-small">&#9201;&#xFE0E;</span>
                    </div>
                    <div class="center_group">
                        <span id="button_previous" class="icon-button icon-button-small">&#9198;&#xFE0E;</span>
                        <span id="button_play" class="icon-button" style="display: none">&#x25B6;&#xFE0E;</span>
                        <span id="button_pause" class="icon-button">&#x23F8;&#xFE0E;</span>
                        <span id="button_next" class="icon-button icon-button-small">&#9197;&#xFE0E;</span>
                    </div>
                    <div class="right_group">
                        <span id="button_shuffle" class="icon-button">&#x2928;&#xFE0E;</span>
                    </div>
                </div>
            </div>
        </div>
        <div id="settings" style="display: none">
            <div class="toolbar toolbar_top">
                <div class="header">
                    <span id="button_settings_back" class="icon-button">&#x2190;&#xFE0E;</span>
                    <span id="settings_title" class="screen_title"></span>
                </div>
            </div>
            <div id="settings_list">
                <div class="setting_field">
                    <select id="language_selector">

                    </select>
                </div>
                <div class="setting_field">
                    <select id="voice_selector">

                    </select>
                </div>
            </div>
        </div>
        <div id="loading">
            <div class="spinner"></div>
        </div>
    </div>
</body>
</html>
