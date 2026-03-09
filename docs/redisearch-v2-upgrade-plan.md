# RediSearch v2.x Upgrade Plan

## Background

RediSearch v2.0 (shipped as part of Redis Stack) fundamentally changed the document storage model.
Documents are no longer stored in RediSearch's internal format — they are now standard Redis Hashes,
and RediSearch indexes them automatically. Several commands were removed or renamed as a result.

This library currently targets RediSearch v1.x and uses removed commands (`FT.ADD`, `FT.DROP`,
`FT.DEL`), which means it does not work at all with modern Redis Stack (RediSearch v2.x / v2.10+).

---

## Breaking Changes to Fix

### 1. `FT.ADD` → `HSET`

**Affected:** `Index::add()`, `Index::replace()`, `Index::addMany()`, `Index::replaceMany()`

`FT.ADD` was removed in v2.0. Documents must now be stored as Redis Hashes with the standard
`HSET` command. The index is maintained automatically.

**Migration strategy:**
- Remove the `FT.ADD` code path from `_add()`.
- All document writes use `HSET <key> field1 val1 field2 val2 ...`.
- The document key is the ID (optionally prefixed with `PREFIX` values from `FT.CREATE`).
- `add()` must explicitly verify the index exists (`FT.INFO`) and that the key does not already
  exist (`EXISTS`) to preserve existing exception semantics (`UnknownIndexNameException`,
  `DocumentAlreadyInIndexException`).
- `replace()`, `addHash()`, `replaceHash()` become simple upserts (HSET is always an upsert).

### 2. `FT.DROP` → `FT.DROPINDEX`

**Affected:** `Index::drop()`

`FT.DROP` was replaced by `FT.DROPINDEX`. Importantly, `FT.DROPINDEX` by default **keeps** the
underlying hash documents (the old `FT.DROP` deleted them). To delete documents too, pass `DD`.

**Migration strategy:**
- Change `rawCommand('FT.DROP', ...)` → `rawCommand('FT.DROPINDEX', ...)`.
- Add an optional `bool $deleteDocuments = false` parameter. When `true`, append `DD`.

### 3. `FT.DEL` → `DEL`

**Affected:** `Index::delete()`

`FT.DEL` was removed. Since documents are now hashes, deleting a document means deleting its
hash key with the standard Redis `DEL` command. This automatically removes it from the index.

**Migration strategy:**
- Reconstruct the full Redis key from the document ID and any configured prefixes.
- Call `rawCommand('DEL', [$key])`.
- The `$deleteDocument` parameter becomes a no-op (kept for API compatibility only).

---

## Score and Language Backward Compatibility

In v1.x, `FT.ADD` accepted a per-document score and a `LANGUAGE` option. In v2.x these are gone
from the write path but can be replicated:

- **SCORE_FIELD**: Add `SCORE_FIELD __score` to `FT.CREATE`. RediSearch reads the `__score` hash
  field as the document's base score. The existing `getHashDefinition()` already stores `__score`,
  so this gives backward-compatible `WITHSCORES` behavior.
- **LANGUAGE_FIELD**: Add `LANGUAGE_FIELD __language` to `FT.CREATE`. RediSearch reads `__language`
  for per-document stemmer selection. The existing `getHashDefinition()` already stores `__language`.
- **Client-side language validation**: `add()` must validate the language before calling HSET,
  since HSET does not validate it. Add `Language::isSupported(string $lang): bool` and throw
  `UnsupportedRediSearchLanguageException` for unknown languages.

**Document.php cleanup:** Remove the `REPLACE` flag from `getHashDefinition()`. It was added for
`FT.ADD`'s REPLACE option but it incorrectly pollutes the HSET key-value pairs (treating `REPLACE`
as a field name).

---

## New Features

### 4. VectorField (RediSearch v2.2+)

Add `src/Fields/VectorField.php` implementing the `VECTOR` field type for KNN (k-nearest
neighbor) similarity search.

**Schema syntax:**
```
FT.CREATE myIdx SCHEMA vec VECTOR FLAT 6 TYPE FLOAT32 DIM 128 DISTANCE_METRIC COSINE
```

**VectorField constructor parameters:**
| Parameter | Type | Default | Description |
|---|---|---|---|
| `$name` | string | — | Field name |
| `$algorithm` | string | `FLAT` | `FLAT` (brute-force) or `HNSW` |
| `$type` | string | `FLOAT32` | `FLOAT32` or `FLOAT64` |
| `$dim` | int | `128` | Number of vector dimensions |
| `$distanceMetric` | string | `COSINE` | `L2`, `IP`, or `COSINE` |
| `$extraAttributes` | array | `[]` | Additional algorithm-specific options |

Add `Index::addVectorField(...)` convenience method and declare it in `IndexInterface`.

### 5. Dialect Support (RediSearch v2.4+)

Add `Builder::dialect(int $version): BuilderInterface` and `DIALECT $version` to the
`FT.SEARCH` arguments. Add to `BuilderInterface` and delegate from `Index`.

Dialect versions:
- `1` — classic (default for backward compat)
- `2` — enables `%fuzzy%` matching and other v2.4 query syntax
- `3` — additional operators

### 6. Language.php: Missing Languages

Add constants and include in the supported list for languages added in newer RediSearch versions:
`BASQUE`, `CATALAN`, `CHINESE`, `GREEK`, `INDONESIAN`, `IRISH`, `LITHUANIAN`, `NEPALI`.

---

## Files Changed

| File | Change |
|---|---|
| `src/Index.php` | `drop()`, `delete()`, `_add()`, `add()`, `replace()`, `addHash()`, `replaceHash()`, `addMany()`, `create()`, new `addVectorField()`, `dialect()` |
| `src/Document/Document.php` | Remove `REPLACE` from `getHashDefinition()` |
| `src/IndexInterface.php` | Add `addVectorField()`, update `drop()` signature |
| `src/Language.php` | Add missing languages + `isSupported()` / `getSupported()` |
| `src/Query/Builder.php` | Add `$dialect` property and `dialect()` method; include in `makeSearchCommandArguments()` |
| `src/Query/BuilderInterface.php` | Add `dialect(int $version): BuilderInterface` |
| `src/Fields/VectorField.php` | **New file** — `VECTOR` field type |

---

## Test Impact

| Test | Impact |
|---|---|
| `testShouldDropIndex` | ✅ `FT.DROPINDEX` returns OK same as `FT.DROP` |
| `testShouldDeleteDocumentById` | ✅ `DEL` removes hash → no longer searchable |
| `testShouldPhysicallyDeleteDocumentById` | ✅ same as above; `$deleteDocument` param is no-op |
| `testAddDocumentAlreadyInIndex` | ✅ `EXISTS` check before HSET preserves exception |
| `testAddDocumentToUndefinedIndex` | ✅ `FT.INFO` check preserves `UnknownIndexNameException` |
| `testAddDocumentWithUnsupportedLanguage` | ✅ client-side `Language::isSupported()` validation |
| `testAddDocumentWithZeroScore` | ✅ `SCORE_FIELD __score` in `FT.CREATE` → WITHSCORES returns stored score |
| `testAddDocumentWithNonDefaultScore` | ✅ same |
| `testReplaceDocumentFromHash` | ✅ HSET upserts; `addHash()` returns `true` (not raw HSET count) |
| `testShouldCreateIndexWithNoFrequencies` | ✅ `SCORE_FIELD`/`LANGUAGE_FIELD` go before SCHEMA, not in index_options |
| All batch tests | ✅ `addMany()` pipelines HSET without per-document existence checks |
