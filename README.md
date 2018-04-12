**Travis**

[![Build Status](https://travis-ci.org/ethanhann/redisearch-php.svg?branch=master)](https://travis-ci.org/ethanhann/redisearch-php)

**Packagist**

[![Latest Stable Version](https://poser.pugx.org/ethanhann/redisearch-php/v/stable)](https://packagist.org/packages/ethanhann/redisearch-php)
[![Total Downloads](https://poser.pugx.org/ethanhann/redisearch-php/downloads)](https://packagist.org/packages/ethanhann/redisearch-php)
[![Latest Unstable Version](https://poser.pugx.org/ethanhann/redisearch-php/v/unstable)](https://packagist.org/packages/ethanhann/redisearch-php)
[![License](https://poser.pugx.org/ethanhann/redisearch-php/license)](https://packagist.org/packages/ethanhann/redisearch-php)

**What is this?**

RediSearch-PHP is a PHP client library for the [RediSearch](http://redisearch.io/) module which adds Full-Text search to Redis.

See the [documentation](http://www.ethanhann.com/redisearch-php/) for more information.

**Contributing**

Contributions are welcome. Before submitting a PR for review, please run **./vendor/bin/robo build** to ensure your 
contribution conforms to the project's code style, and that all tests in the test suite pass.

Do not run tests on a prod system (of course), or any system that has a Redis instance with data you care about - 
Redis is flushed between tests.

**Laravel Support**

[Laravel-RediSearch](https://github.com/ethanhann/Laravel-RediSearch) - Exposes RediSearch-PHP to Laravel as a Scout driver.
