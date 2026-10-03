<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

/**
 * Static safety audit of the two GitHub Actions workflows.
 *
 *   ITEM 1  piie-deploy.yml must have no active trigger.
 *   ITEM 8  no bare `php` / `composer` invocation in a COMMAND position in the
 *           deployment workflow, because bare `php` on the production host
 *           resolves to 8.2.27 while composer.lock is resolved for 8.3.
 *   ITEM 9  YAML validity, and no secret literal committed to a workflow.
 *
 * WHY ITEM 8 IS NOT APPLIED TO CI
 * ------------------------------
 * On ubuntu-22.04 with shivammathur/setup-php, exactly one PHP is installed and
 * placed on PATH, so a bare `php` in CI is correct and is what that action is
 * documented to do. Applying the production rule to CI would be a false positive,
 * and a probe that cries wolf gets ignored.
 *
 * WHY "COMMAND POSITION"
 * ---------------------
 * A line only counts if, after stripping any comment marker, it BEGINS with the
 * command (optionally after `sudo` or an env assignment). Otherwise prose such as
 * "a bare `php` resolves through PATH to..." - which is exactly what this file
 * should say - would be reported as a violation.
 */

$failures = 0;

function check(string $label, bool $ok): void
{
    global $failures;
    if (! $ok) {
        $failures++;
    }
    echo '  '.($ok ? 'PASS' : 'FAIL').'  '.$label."\n";
}

foreach (['piie-ci.yml', 'piie-deploy.yml'] as $file) {
    $path = __DIR__.'/../.github/workflows/'.$file;
    $src = file_get_contents($path);

    echo "\n=== $file ===\n";

    try {
        $doc = Yaml::parse($src);
        check('YAML parses', true);
        echo '        jobs: '.implode(', ', array_keys($doc['jobs']))."\n";
    } catch (Throwable $e) {
        check('YAML parses', false);
        echo '        '.substr($e->getMessage(), 0, 200)."\n";
        continue;
    }

    $hasTrigger = isset($doc['on']) || isset($doc[true]);
    if ($file === 'piie-deploy.yml') {
        check('ITEM 1  deployment workflow has NO active trigger', ! $hasTrigger);
    } else {
        check('CI workflow has an active trigger', $hasTrigger);
    }

    // ---- ITEM 8 ------------------------------------------------------------
    // Matches `php` / `composer` in a COMMAND POSITION only. Two shapes count:
    //
    //   1. the statement begins with it        -> `php artisan migrate`
    //   2. it follows a shell operator         -> `set -e; php artisan migrate`
    //                                               `cd /x && composer install`
    //                                               `$(php -v)`
    //
    // Requiring an operator boundary (not a path character) is what keeps
    // `/usr/local/php83/bin/php` and prose such as "a bare `php` resolves..."
    // from being reported.
    //
    // The backtick is deliberately NOT treated as an operator. It is a
    // command-substitution character in shell, but in a heavily-commented workflow it is
    // overwhelmingly inline-code markup in prose - and treating it as an operator
    // made this check fail on the very sentence that explains the rule. The $( form
    // is the one that actually appears in these files.
    $bare = [];
    if ($file === 'piie-deploy.yml') {
        foreach (explode("\n", $src) as $i => $line) {
            $body = ltrim(ltrim($line), '# ');
            $n = $i + 1;

            $php = preg_match('#(^|[;|&(]|&&|\|\|)\s*(sudo\s+)?(env\s+[A-Za-z_]+=\S+\s+)*php\s+\S#', $body);
            $com = preg_match('#(^|[;|&(]|&&|\|\|)\s*(sudo\s+)?composer\s+\S#', $body);

            if ($php || $com) {
                $bare[] = $n.': '.trim($line);
            }
        }
        check('ITEM 8  deploy: no bare php/composer in a command position', $bare === []);
    } else {
        echo "  SKIP  ITEM 8  not applicable to CI (setup-php pins a single PHP)\n";
    }
    foreach ($bare as $b) {
        echo '        '.$b."\n";
    }

    // ---- ITEM 9: no committed secret --------------------------------------
    $leaks = [];
    if (str_contains($src, 'GOCSPX-')) {
        $leaks[] = 'Google client secret literal';
    }
    if (str_contains($src, 'PRIVATE KEY-----')) {
        $leaks[] = 'private key block';
    }
    if (preg_match('/AKIA[0-9A-Z]{16}/', $src)) {
        $leaks[] = 'AWS access key id';
    }
    if (preg_match('~/home/[a-z]+/\.ssh/[a-z0-9_]+~i', $src)) {
        $leaks[] = 'private key path';
    }
    if (preg_match('~C:\\\\+Users\\\\+~', $src)) {
        $leaks[] = 'local Windows path';
    }
    if (str_contains($src, '198.251.89.82')) {
        $leaks[] = 'production IP address';
    }
    check('ITEM 9  no secret literal committed to the workflow', $leaks === []);
    foreach ($leaks as $l) {
        echo '        '.$l."\n";
    }

    // ---- secret reference discipline ---------------------------------------
    if ($file === 'piie-deploy.yml') {
        check('ITEM 9  deploy: key material only via ${{ secrets.* }}',
            str_contains($src, 'secrets.PIIE_SSH_KEY'));
    } else {
        check('ITEM 9  CI references no deployment secret', ! str_contains($src, 'PIIE_SSH'));
    }
}

echo "\n".($failures === 0 ? "ALL CHECKS PASSED\n" : $failures." CHECK(S) FAILED\n");
exit($failures === 0 ? 0 : 1);