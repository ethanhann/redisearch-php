<?php

namespace Ehann\Tests;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;

class TestTimeListener implements TestListener
{
    /**
     * @param Test $test
     * @param float $time
     */
    public function endTest(Test $test, $time)
    {
        $formattedNumber = round($time, 4);
        print "{$test->getName()} took $formattedNumber seconds." . PHP_EOL;
    }

    /**
     * An error occurred.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addError(Test $test, \Exception $e, $time)
    {
        // TODO: Implement addError() method.
    }

    /**
     * A warning occurred.
     *
     * @param Test $test
     * @param \PHPUnit\Framework\Warning $e
     * @param float $time
     */
    public function addWarning(Test $test, \PHPUnit\Framework\Warning $e, $time)
    {
        // TODO: Implement addWarning() method.
    }

    /**
     * A failure occurred.
     *
     * @param Test $test
     * @param \PHPUnit\Framework\AssertionFailedError $e
     * @param float $time
     */
    public function addFailure(Test $test, \PHPUnit\Framework\AssertionFailedError $e, $time)
    {
        // TODO: Implement addFailure() method.
    }

    /**
     * Incomplete test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addIncompleteTest(Test $test, \Exception $e, $time)
    {
        // TODO: Implement addIncompleteTest() method.
    }

    /**
     * Risky test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addRiskyTest(Test $test, \Exception $e, $time)
    {
        // TODO: Implement addRiskyTest() method.
    }

    /**
     * Skipped test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addSkippedTest(Test $test, \Exception $e, $time)
    {
        // TODO: Implement addSkippedTest() method.
    }

    /**
     * A test suite started.
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     */
    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite)
    {
        // TODO: Implement startTestSuite() method.
    }

    /**
     * A test suite ended.
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     */
    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite)
    {
        // TODO: Implement endTestSuite() method.
    }

    /**
     * A test started.
     *
     * @param Test $test
     */
    public function startTest(Test $test)
    {
        // TODO: Implement startTest() method.
    }
}
