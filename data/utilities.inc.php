<?php

function getParameter($name) {
    if (!isset($_REQUEST[$name])) {
        throw new Exception("Missing $name parameter");
    }
    return $_REQUEST[$name];
}
