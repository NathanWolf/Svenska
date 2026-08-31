<?php

require_once('data/version.inc.php');

?>
<html lang="en-us">
    <head>
        <title>På Svenska, Tack!</title>
        <meta name="viewport" content="width=device-width,initial-scale=0.5,maximum-scale=0.5,user-scalable=no"/>
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
                let svenka = new Svenska();
                svenka.register();
                svenka.load();
                _svenska = svenka;
            };
            window.onerror = function(msg, url, line, col, error) { _svenska.onInternalError(msg, url, line, col, error); };
        </script>
    </head>
<body>
    <div id="main">
        <div id="modes" style="display: none">
            <div id="mode_listen">

            </div>
            <div id="mode_flashcards_from">

            </div>
            <div id="mode_flashcards_to">

            </div>
        </div>
        <div id="categories" style="display: none">

        </div>
        <div id="lesson" style="display: none">
            <div class="toolbar">
                <div class="controls" id="controls_top" style="visibility: hidden">
                    <div class="left_group">
                        <span id="button_back" class="icon-button">X</span>
                    </div>
                    <div class="center_group">
                    </div>
                    <div class="right_group">

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
            <div class="toolbar">
                <div class="controls" id="controls_bottom" style="visibility: hidden">
                    <div class="left_group">
                        <span id="button_repeat" class="icon-button">&#x27F2;&#xFE0E;</span>
                        <span id="button_wait" class="icon-button icon-button-small">&#x2026;&#xFE0E;</span>
                    </div>
                    <div class="center_group">
                        <span id="button_previous" class="icon-button icon-button-small">&#x25C0;&#xFE0E;</span>
                        <span id="button_paused" class="icon-button">&#x23F8;&#xFE0E;</span>
                        <span id="button_next" class="icon-button icon-button-small">&#x25B6;&#xFE0E;</span>
                    </div>
                    <div class="right_group">
                        <span id="button_shuffle" class="icon-button">&#x2928;&#xFE0E;</span>
                    </div>
                </div>
            </div>
        </div>
        <div id="loading">
            <div class="spinner"></div>
        </div>
    </div>
</body>
</html>

