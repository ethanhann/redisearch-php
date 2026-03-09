# RediSearch v2+ Upgrade Plan — Supplemental Implementation Review (Evidence-Based)

This supplement evaluates the implementation of the v2 plan documented in:

- `https://github.com/ethanhann/redisearch-php/blob/dev/docs/redisearch-v2-upgrade-plan.md`

and maps remaining gaps against RediSearch v2+ API coverage.

---

## Methodology

I compared:

1. Plan requirements in `dev/docs/redisearch-v2-upgrade-plan.md`.
2. `dev` branch implementation files (raw snapshots).
3. Current branch (`work`) implementation coverage in `src/`.

The intent is to separate:

- **What the plan asked for**
- **What was actually implemented on `dev`**
- **What still remains for broader v2+ API parity**

---

## A. Plan implementation scorecard

### 1) `FT.ADD` migration to hash writes (`HSET`)

**Plan asked:** remove `FT.ADD` write path; use hash writes with compatibility checks.

**Implemented on `dev`:**
- `_add()` writes via `HSET` and consumes `Document::getHashDefinition()`.
- `add()` performs:
  - `FT.INFO` existence check,
  - language validation via `Language::isSupported()`,
  - `EXISTS` duplicate check,
  - then writes with HSET.
- `replace()` / `addHash()` / `replaceHash()` use upsert semantics.

**Assessment:** ✅ Implemented and aligned with v2 hash model.

**Residual caveat:** key reconstruction uses concatenated multi-prefix behavior (`implode(':', $prefixes) . ':' . $id`), which can be ambiguous when `PREFIX` contains alternatives.

---

### 2) `FT.DROP` migration to `FT.DROPINDEX`

**Plan asked:** switch to `FT.DROPINDEX` and optionally `DD`.

**Implemented on `dev`:**
- `drop(bool $deleteDocuments = false)` emits `FT.DROPINDEX` and appends `DD` when requested.

**Assessment:** ✅ Correct.

---

### 3) `FT.DEL` migration to `DEL`

**Plan asked:** delete underlying document key with `DEL`; keep old parameter for compatibility.

**Implemented on `dev`:**
- `delete($id, $deleteDocument = false)` now resolves key and uses `DEL`.
- compatibility parameter retained.

**Assessment:** ✅ Correct for v2 hash-backed docs.

---

### 4) Backward compatibility for score/language

**Plan asked:** use `SCORE_FIELD __score` and `LANGUAGE_FIELD __language`; remove `REPLACE` from hash definition; add client-side language validation.

**Implemented on `dev`:**
- `create()` includes `SCORE_FIELD __score` and `LANGUAGE_FIELD __language` before `SCHEMA`.
- `Document::getHashDefinition()` includes `__score` and optional `__language`.
- `REPLACE` removed from hash definition path.
- `Language` expanded + `isSupported()`/`getSupported()` added.
- `add()` validates unsupported languages.

**Assessment:** ✅ Implemented as designed.

---

### 5) Vector field support

**Plan asked:** add `VectorField`, convenience API, and interface exposure.

**Implemented on `dev`:**
- Added `src/Fields/VectorField.php`.
- Added `Index::addVectorField(...)`.
- Added method to `IndexInterface`.

**Assessment:** ✅ Implemented.

**Caveat:** validation is permissive (algorithm/type/distance/required attrs not strongly enforced).

---

### 6) Query dialect support

**Plan asked:** `dialect(int $version)` in builder + interface + `Index` delegation + `FT.SEARCH DIALECT` args.

**Implemented on `dev`:**
- `dialect()` added in query builder and interface.
- `Index::dialect()` delegates.
- `DIALECT` appended in search args.

**Assessment:** ✅ Implemented.

**Caveat:** no guardrails on accepted versions.

---

### 7) Additional language constants

**Plan asked:** add missing language constants in newer RediSearch.

**Implemented on `dev`:**
- Added BASQUE, CATALAN, CHINESE, GREEK, INDONESIAN, IRISH, LITHUANIAN, NEPALI, and support list helpers.

**Assessment:** ✅ Implemented.

---

## B. What this means in practice

The `dev` implementation appears to satisfy the plan’s core objective: **unblocking compatibility with RediSearch v2+ by removing dependency on removed v1 write/delete/drop commands and adapting document semantics to hashes.**

Estimated completeness against the *plan document itself*: **~90%+**.

The remaining issues are mainly edge-case correctness and API hardening (prefix disambiguation, stricter validation).

---

## C. Remaining RediSearch v2+ API-surface gaps (beyond the plan)

The plan intentionally targeted critical compatibility. The broader RediSearch v2+ API still has major unwrapped areas.

## 1) Index creation/lifecycle breadth

Missing or limited first-class support for:

- `FT.CREATE ON JSON|HASH`
- `FILTER`
- `MAXTEXTFIELDS`
- `TEMPORARY`
- `SKIPINITIALSCAN`
- richer score/language default knobs and schema-time options
- `FT.ALTER`
- `FT._LIST`

## 2) JSON-native indexing/document workflows

Current write-path assumptions are hash-centric. A full v2+ surface typically needs:

- JSON index modeling (`ON JSON`)
- JSON path field abstractions
- JSON document writes/updates aligned with index behavior

## 3) Search option coverage (`FT.SEARCH`)

Useful modern gaps include:

- `PARAMS` (critical for parameterized vector/hybrid queries)
- `INORDER`
- `WITHCOUNT`
- `EXPLAINSCORE`
- richer `RETURN` alias ergonomics

## 4) Aggregate advanced execution

Core aggregate pipeline exists, but cursor-oriented execution remains missing:

- `WITHCURSOR`
- `FT.CURSOR READ`
- `FT.CURSOR DEL`

## 5) Admin/analysis/synonym/spell command families

Not exposed as first-class helpers:

- `FT.SYNUPDATE`, `FT.SYNDUMP`
- `FT.SPELLCHECK`
- `FT.DICTADD`, `FT.DICTDEL`, `FT.DICTDUMP`
- `FT.PROFILE`, `FT.EXPLAINCLI`

---

## D. Prioritized next steps

1. **Correct prefix-to-key strategy** for add/delete/existence checks when multiple prefixes are configured.
2. **Harden validation** in `VectorField` and `dialect()` to fail fast in PHP.
3. **Add `FT.SEARCH PARAMS` support** to enable robust vector/hybrid query workflows.
4. **Expand `FT.CREATE` option modeling** (`ON`, `FILTER`, `SKIPINITIALSCAN`, etc.).
5. **Add aggregate cursor support** for large-result workflows.
6. **Phase in missing command families** (`FT.ALTER`, synonyms, spellcheck, dict, profile).

---

## Bottom line

- **Plan execution quality:** strong and mostly complete for intended v2 migration scope.
- **Library-to-RediSearch-v2+ parity:** still partial; substantial API-surface expansion remains for full feature parity.