<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Warsaw');
require __DIR__ . '/harness.php';
foreach (glob(__DIR__ . '/test_*.php') as $file) {
    require $file;
}
exit(run_tests($argv[1] ?? null));
