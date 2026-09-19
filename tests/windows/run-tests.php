<?php
/**
 * Usage:
 *   php run-tests.php                       # run every test case
 *   php run-tests.php --filter=standalone    # run test cases whose name contains "standalone"
 *   php run-tests.php --filter standalone    # same, space-separated form
 */
require_once __DIR__ . '/lib/bootstrap.php';

// A previous run stopped abnormally can leave crash_handler.exe holding a handle on that run's
// workspace folder, blocking this run's cleanup. Best-effort, silenced.
exec('taskkill /f /im crash_handler.exe 2>nul');

$options = getopt('', ['filter:']);
$filter = $options['filter'] ?? null;

exit((new TestRunner(__DIR__ . '/tests'))->run($filter));
