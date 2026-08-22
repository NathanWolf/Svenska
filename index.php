<?php

require_once('data/version.inc.php');

?>
<html lang="en-us">
    <head>
        <title>På Svenska, Tack!</title>
        <meta name="viewport" content="width=device-width,initial-scale=0.5,maximum-scale=0.5,user-scalable=no"/>
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
    <div class="main">
        <div id="lesson" style="display: none">
            <div id="phrase">

            </div>
        </div>
        <div id="start" style="display: none">
            Click to Start
        </div>
        <div id="loading">
            Loading
        </div>
    </div>
</body>
</html>

