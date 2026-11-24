<?php

declare(strict_types=1);

// Fails when line coverage in a PHPUnit Clover report is below a minimum.
// Usage: php scripts/check-coverage.php <clover.xml> <minimum percent>

if ($argc !== 3 || !is_numeric($argv[2])) {
    fwrite(STDERR, "Usage: php scripts/check-coverage.php <clover.xml> <minimum percent>\n");
    exit(2);
}

if (!is_file($argv[1])) {
    fwrite(STDERR, "Coverage report {$argv[1]} is missing. Run PHPUnit with a coverage driver (pcov or Xdebug).\n");
    exit(2);
}

$report = simplexml_load_file($argv[1]);
if ($report === false || !isset($report->project->metrics)) {
    fwrite(STDERR, "Cannot read coverage metrics from {$argv[1]}\n");
    exit(2);
}

$metrics = $report->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$minimum = (float) $argv[2];
$percent = $statements > 0 ? 100 * $covered / $statements : 0.0;

printf("Line coverage: %.2f%% (%d/%d), minimum %.2f%%\n", $percent, $covered, $statements, $minimum);
if ($percent < $minimum) {
    fwrite(STDERR, "Line coverage is below the minimum.\n");
    exit(1);
}
