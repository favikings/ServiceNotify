<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Env.php';
require_once dirname(__DIR__) . '/src/RequestInput.php';
require_once dirname(__DIR__) . '/src/Security.php';

Env::load(dirname(__DIR__) . '/.env');
Security::enforceHttps();
Security::sendHeaders();

header('Location: ' . rtrim(Env::require('APP_URL'), '/') . '/login.php', true, 302);
exit;
