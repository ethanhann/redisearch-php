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
        $expected = 3;

        $result = $this->subject->setMinPrefix($expected);

        $this->assertTrue($result);
        $this->assertEquals($expected, $this->subject->getMinPrefix());
    }

    public function testShouldSetMaxExpansions()
    {
        $expected = 300;

        $result = $this->subject->setMaxExpansions($expected);

        $this->assertTrue($result);
        $this->assertEquals($expected, $this->subject->getMaxExpansions());
    }

    public function testShouldSetTimeout()
    {
        $expected = 100;

        $result = $this->subject->setTimeoutInMilliseconds($expected);

        $this->assertTrue($result);
        $this->assertEquals($expected, $this->subject->getTimeoutInMilliseconds());
    }

    public function testIsOnTimeoutPolicyReturn()
    {
        $this->subject->setOnTimeoutPolicyToReturn();

        $result = $this->subject->isOnTimeoutPolicyReturn();

        $this->assertTrue($result);
    }

    public function testIsOnTimeoutPolicyFail()
    {
        $this->subject->setOnTimeoutPolicyToFail();

        $result = $this->subject->isOnTimeoutPolicyFail();

        $this->assertTrue($result);
    }

    public function testShouldSetMinPhoneticTermLength()
    {
        $expected = 5;

        $result = $this->subject->setMinPhoneticTermLength($expected);

        $this->assertTrue($result);
        $this->assertEquals($expected, $this->subject->getMinPhoneticTermLength());
    }
}
