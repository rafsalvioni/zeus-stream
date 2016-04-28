<?php

require '../vendor/autoload.php';

set_error_handler(function($errno, $errstr, $errfile, $errline)
{
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});
