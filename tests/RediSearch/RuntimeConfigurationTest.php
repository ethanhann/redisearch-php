<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\RuntimeConfiguration;
use Ehann\Tests\RediSearchTestCase;

class RuntimeConfigurationTest extends RediSearchTestCase
{
    /** @var RuntimeConfiguration */
    private $subject;

    public function setUp()
    {
        $this->subject = (new RuntimeConfiguration($this->redisClient, 'foo'));
    }

    public function tearDown()
    {
        $this->redisClient->flushAll();
    }

    public function testShouldSetMinPrefix()
    {
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }

        $expected = 3;

        $result = $this->subject->setMinPrefix($expected);

        $this->assertTrue($result);
        $this->assertEquals($expected, $this->subject->getMinPrefix());
    }

    public function testShouldSetMaxExpansions()
    {
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }

        $expected = 300;

        $result = $this->subject->setMaxExpansions($expected);

        $this->assertTrue($result);
        $this->assertEquals($expected, $this->subject->getMaxExpansions());
    }

    public function testShouldSetTimeout()
    {
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }

        $expected = 100;

        $result = $this->subject->setTimeoutInMilliseconds($expected);

        $this->assertTrue($result);
        $this->assertEquals($expected, $this->subject->getTimeoutInMilliseconds());
    }

    public function testIsOnTimeoutPolicyReturn()
    {
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }

        $this->subject->setOnTimeoutPolicyToReturn();

        $result = $this->subject->isOnTimeoutPolicyReturn();

        $this->assertTrue($result);
    }

    public function testIsOnTimeoutPolicyFail()
    {
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }

        $this->subject->setOnTimeoutPolicyToFail();

        $result = $this->subject->isOnTimeoutPolicyFail();

        $this->assertTrue($result);
    }

    public function testShouldSetMinPhoneticTermLength()
    {
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }

        $expected = 5;

        $result = $this->subject->setMinPhoneticTermLength($expected);

        $this->assertTrue($result);
        $this->assertEquals($expected, $this->subject->getMinPhoneticTermLength());
    }
}
