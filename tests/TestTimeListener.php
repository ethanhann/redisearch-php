<?php

namespace Ehann\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;

class TestTimeListener implements TestListener
{
    public function endTest(Test $test, float $time): void
    {
        $formattedNumber = round($time, 4);
        print "{$test->getName()} took $formattedNumber seconds." . PHP_EOL;
    }

    public function addError(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addError() method.
    }

    public function addWarning(Test $test, Warning $e, float $time): void
    {
        // TODO: Implement addWarning() method.
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        // TODO: Implement addFailure() method.
    }

    public function addIncompleteTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addIncompleteTest() method.
    }

    public function addRiskyTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addRiskyTest() method.
    }

    public function addSkippedTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addSkippedTest() method.
    }

    public function startTestSuite(TestSuite $suite): void
    {
        // TODO: Implement startTestSuite() method.
    }

    public function endTestSuite(TestSuite $suite): void
    {
        // TODO: Implement endTestSuite() method.
    }

    public function startTest(Test $test): void
    {
        // TODO: Implement startTest() method.
    }
}
