[![Build Status](https://travis-ci.org/ethanhann/redisearch-php.svg?branch=master)](https://travis-ci.org/ethanhann/redisearch-php)

# Quick Start

RediSearch-PHP is a PHP client library for the [RediSearch](http://redisearch.io/) module which adds Full-Text search to Redis.

See the [documentation](http://www.ethanhann.com/redisearch-php/) for more information.

# Contributing

Contributions are welcome. Before submitting a PR for review, please run **./vendor/bin/robo build** to ensure your 
contribution conforms to the project's code style, and that all tests in the test suite pass.

Do not run tests on a prod system (of course), or any system that has a Redis instance with data you care about - 
Redis is flushed between tests.
