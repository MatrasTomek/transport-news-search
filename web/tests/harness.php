<?php
declare(strict_types=1);

$GLOBALS['__tests'] = [];

function test(string $name, callable $fn): void
{
    $GLOBALS['__tests'][] = [$name, $fn];
}

function assert_same(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new AssertionError(($msg !== '' ? "$msg: " : '') . 'oczekiwano ' . var_export($expected, true) . ', otrzymano ' . var_export($actual, true));
    }
}

function assert_true(mixed $cond, string $msg = 'warunek fałszywy'): void
{
    if ($cond !== true) {
        throw new AssertionError($msg);
    }
}

function assert_throws(string $class, callable $fn): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $class) {
            return;
        }
        throw new AssertionError("oczekiwano $class, otrzymano " . get_class($e) . ': ' . $e->getMessage());
    }
    throw new AssertionError("oczekiwano wyjątku $class");
}

function run_tests(?string $filter): int
{
    $failed = 0;
    $count = 0;
    foreach ($GLOBALS['__tests'] as [$name, $fn]) {
        if ($filter !== null && !str_contains($name, $filter)) {
            continue;
        }
        $count++;
        try {
            $fn();
            echo "PASS $name\n";
        } catch (Throwable $e) {
            $failed++;
            echo "FAIL $name\n  " . $e->getMessage() . "\n";
        }
    }
    echo "\n$count testów, $failed błędów\n";
    return $failed > 0 ? 1 : 0;
}
