# Changelog

[Changes since last release](https://github.com/ethanhann/redisearch-php/compare/0.7.0...0.8.0)

* Add [search result sorting](searching.md#sorting-results).
* Remove NoScoreIdx and Optimize methods as they are deprecated and/or non-functional in RediSearch.
* Add [explain method](searching.md#explaining-a-query) for explaining a query.
* Add optional [query logging](searching.md#logging-queries).

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
