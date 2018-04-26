# Changelog

## 1.1.0

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/1.0.1...1.1.0)

* Support [aggregations](aggregating.md).

## 1.0.1

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/1.0.0...1.0.1)

* Support NOINDEX fields.

## 1.0.0

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.11.0...1.0.0)

* Support complete RediSearch API, now including RETURN, SUMMARIZE, HIGHLIGHT, EXPANDER, and PAYLOAD in search queries.

## 0.11.0

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.10.1...0.11.0)

* Add [hash indexing](indexing.md#indexing-from-a-hash).

## 0.10.1

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.10.0...0.10.1)

* Polished docs, and added a section on the Laravel RediSearch package.
* Made internal changes to how numeric and geo search queries are generated. 

## 0.10.0

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.9.0...0.10.0)

* Remove RedisClient class and add adapter functionality.
* There are now adapters for Predis, PhpRedis, and the Cheprasov client. They all extend [AbstractRedisClient](https://github.com/ethanhann/redisearch-php/blob/master/src/Redis/AbstractRedisClient.php) which implements [RedisClientInterface](https://github.com/ethanhann/redisearch-php/blob/master/src/Redis/RedisClientInterface.php). An additional adapter can be created by extending AbstractRedisClient or by implementing RedisClientInterface if needed for some reason. 
* Handle RediSearch module error when index is created on a Redis database other than 0.
* Return boolean true instead of "OK" when using PredisAdapter.
* A new index now requires that a redis client is passed into its constructor - removed the magic behavior where a default RedisClient instance was auto initialized.

## 0.9.0

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.8.0...0.9.0)

* An exception is now thrown when the RediSearch module is not loaded in Redis.
* Allow a [language to be specified](searching.md#setting-a-language) when searching.

## 0.8.0

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.7.0...0.8.0)

* Add [search result sorting](searching.md#sorting-results).
* Remove NoScoreIdx and Optimize methods as they are deprecated and/or non-functional in RediSearch.
* Add [explain method](searching.md#explaining-a-query) for explaining a query.
* Add optional [query logging](searching.md#logging-queries).
* Add [suggestions](suggesting.md).

## 0.7.0

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.6.0...0.7.0)

* Many bug fixes and code quality improvements.

## 0.6.0

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.5.0...0.6.0)

* Add [batch indexing](indexing.md#batch-indexing).

## 0.5.0

* Rename vendor namespace from **Eeh** to **Ehann**
* **AbstractIndex** was renamed to **Index** and is no longer abstract.
* Custom document ID is now properly set when adding to an index.
