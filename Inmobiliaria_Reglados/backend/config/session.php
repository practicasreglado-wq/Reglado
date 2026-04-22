<?php

ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
