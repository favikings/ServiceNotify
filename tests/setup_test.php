<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Env.php';

$failures = [];
$temporaryFile = tempnam(sys_get_temp_dir(), 'service-notify-env-');

if ($temporaryFile === false) {
    fwrite(STDERR, "FAIL: Unable to create a temporary test file.\n");
    exit(1);
}

$fixture = <<<'ENV'
# Comment
SERVICE_NOTIFY_TEST_PLAIN=value
SERVICE_NOTIFY_TEST_QUOTED="value with spaces"
SERVICE_NOTIFY_TEST_SINGLE='literal value'
SERVICE_NOTIFY_TEST_EMPTY=
ENV;

try {
    if (file_put_contents($temporaryFile, $fixture) === false) {
        throw new RuntimeException('Unable to write the temporary test file.');
    }

    Env::load($temporaryFile);

    $expectations = [
        'SERVICE_NOTIFY_TEST_PLAIN' => 'value',
        'SERVICE_NOTIFY_TEST_QUOTED' => 'value with spaces',
        'SERVICE_NOTIFY_TEST_SINGLE' => 'literal value',
        'SERVICE_NOTIFY_TEST_EMPTY' => '',
    ];

    foreach ($expectations as $name => $expected) {
        $actual = Env::get($name);

        if ($actual !== $expected) {
            $failures[] = sprintf(
                '%s expected %s, received %s.',
                $name,
                var_export($expected, true),
                var_export($actual, true)
            );
        }
    }

    $missingWasRejected = false;

    try {
        Env::require('SERVICE_NOTIFY_TEST_MISSING');
    } catch (RuntimeException) {
        $missingWasRejected = true;
    }

    if (!$missingWasRejected) {
        $failures[] = 'Env::require() did not reject a missing variable.';
    }
} finally {
    unlink($temporaryFile);
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: Environment loading behaves as expected.\n");

