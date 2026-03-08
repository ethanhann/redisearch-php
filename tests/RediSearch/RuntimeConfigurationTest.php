<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\RuntimeConfiguration;
use Ehann\Tests\RediSearchTestCase;

class RuntimeConfigurationTest extends RediSearchTestCase
{
    private RuntimeConfiguration $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = (new RuntimeConfiguration($this->redisClient, 'foo'));
    }

    public function tearDown(): void
    {
        $this->redisClient->flushAll();
    }

    public function testShouldSetMinPrefix(): void
    {
        // Arrange
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }
        $expected = 3;

        // Act
        $result = $this->subject->setMinPrefix($expected);

        // Assert
        $this->assertTrue($result);
        $this->assertSame($expected, $this->subject->getMinPrefix());
    }

    public function testShouldSetMaxExpansions(): void
    {
        // Arrange
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }
        $expected = 300;

        // Act
        $result = $this->subject->setMaxExpansions($expected);

        // Assert
        $this->assertTrue($result);
        $this->assertSame($expected, $this->subject->getMaxExpansions());
    }

    public function testShouldSetTimeout(): void
    {
        // Arrange
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }
        $expected = 100;

        // Act
        $result = $this->subject->setTimeoutInMilliseconds($expected);

        // Assert
        $this->assertTrue($result);
        $this->assertSame($expected, $this->subject->getTimeoutInMilliseconds());
    }

    public function testIsOnTimeoutPolicyReturn(): void
    {
        // Arrange
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }
        $this->subject->setOnTimeoutPolicyToReturn();

        // Act
        $result = $this->subject->isOnTimeoutPolicyReturn();

        // Assert
        $this->assertTrue($result);
    }

    public function testIsOnTimeoutPolicyFail(): void
    {
        // Arrange
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }
        $this->subject->setOnTimeoutPolicyToFail();

        // Act
        $result = $this->subject->isOnTimeoutPolicyFail();

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldSetMinPhoneticTermLength(): void
    {
        // Arrange
        if ($this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is configured to use PhpRedis.');
        }
        $expected = 5;

        // Act
        $result = $this->subject->setMinPhoneticTermLength($expected);

        // Assert
        $this->assertTrue($result);
        $this->assertSame($expected, $this->subject->getMinPhoneticTermLength());
    }
}
