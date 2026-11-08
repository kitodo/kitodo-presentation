# AGENTS.md

Guidance for AI coding agents working in this repository.

## What this is

Kitodo.Presentation (`kitodo/presentation`, extension key `dlf`, namespace `Kitodo\Dlf\`) is a TYPO3 extension for presenting METS/MODS and IIIF digital library content. It targets TYPO3 12.4 and 13.4 and PHP 8.2–8.4, and needs Apache Solr with the `solr-ocrhighlighting` plugin for search. Release branches are `main` (next major) and `7.x`; CI runs PHPStan on both, the test suite on `main`.

## Commands

Dependencies are installed through Docker (the TYPO3 core-testing images). `-t` picks the TYPO3 version to install against:

```bash
composer install-via-docker -- -t 13.4     # or -t 12.4
```

Tests (PHPUnit 10, `typo3/testing-framework`). Unit tests run locally or in Docker; functional tests run only in Docker because they need MariaDB/MySQL, Solr and a PHP web server:

```bash
composer test                 # unit + functional (Docker)
composer test:unit            # Docker
composer test:unit:local      # vendor/bin/phpunit -c Build/Test/UnitTests.xml
composer test:func            # Docker
composer test:unit:watch      # phpunit-watcher
```

Single test file or filtered test (the trailing path is passed through to PHPUnit; `-e` passes extra PHPUnit options; `-p` picks PHP 8.2/8.3/8.4, `-d mysql` switches DBMS):

```bash
Build/Test/runTests.sh -s unit Tests/Unit/Format/AltoTest.php
Build/Test/runTests.sh -s functional -e "--filter canLoadDocument" Tests/Functional/Common/MetsDocumentTest.php
Build/Test/runTests.sh -h
```

Static analysis and code style (PSR-12 via php-cs-fixer, PHPStan level 7 on `Classes/` with per-TYPO3-version configs in `.github/`):

```bash
composer phpstan
composer php-cs-fixer:check
composer php-cs-fixer:fix
```

JavaScript (only the media player is built; everything else under `Resources/Public/JavaScript` is hand-written and committed). All npm commands run from `Build/`:

```bash
cd Build && npm install
npm run build        # webpack production → Resources/Public/JavaScript/DlfMediaPlayer/, Resources/Public/Css/
npm run watch
npm run test         # jest, tests live next to sources as *.test.js in Resources/Private/JavaScript
npm run typecheck    # tsc over JS with jsconfig.json
```

Documentation (reST, rendered with the TYPO3 render-guides Docker image): `composer docs:build`, then `composer docs:start` to serve on port 9000.

## Architecture

### Document model (`Classes/Common/`)

`AbstractDocument` is the in-memory representation of one digital object. It has two concrete subclasses chosen by `AbstractDocument::getInstance($location, $settings)`: `MetsDocument` (METS XML, parsed with SimpleXML/XPath) and `IiifManifest` (IIIF Presentation API v1–v3, via `ubl/php-iiif-prezi-reader`). Both expose the same API: logical structure (TOC), physical structure (pages), smLinks between the two, metadata per structure node, file locations/mime types per use group, and fulltext.

Lazily computed properties use a `magicGet*()`/`__get()` convention: reading `$doc->physicalStructure` calls `magicGetPhysicalStructure()` and caches the result. When adding state, update `__sleep()`/`__wakeup()` too, because parsed documents are serialized into the `tx_dlf_doc` TYPO3 cache (`DocumentCacheManager`, with a per-worker in-memory pool that tests reset via `resetPool()`).

Do not confuse `AbstractDocument` with the Extbase entity `Domain\Model\Document` (the `tx_dlf_documents` DB row). The entity carries the parsed object via `getCurrentDocument()`/`setCurrentDocument()`; `AbstractController::loadDocument()` wires them together from a request `id` (UID or URL) or `recordId`.

### Metadata and fulltext formats (`Classes/Format/`)

Which XML formats are understood is data-driven: `tx_dlf_formats` rows (seeded from `Configuration/Tenant/format.yaml` by the backend "New Tenant" module) map a format name (METS `MDTYPE`/`OTHERMDTYPE` or fulltext root tag) to a namespace and an optional PHP class. Metadata classes implement `MetadataInterface::extractMetadata()` (`Mods`, `TeiHeader`, `AudioVideoMD`); fulltext classes implement `FulltextInterface` (`Alto`, `Tei`). Most metadata extraction is XPath configured per tenant in `tx_dlf_metadata`/`tx_dlf_metadataformat`; the classes handle values that are hard to express in XPath (e.g. authors with ORCID/VIAF lookups via `Classes/Api/`). See `Documentation/Developers/Metadata.rst`.

File use groups (image, thumbnail, fulltext, download, audio, video, waveform, score, model) come from extension configuration and are read through `Configuration\UseGroupsConfiguration`.

### Indexing and search (`Classes/Common/Indexer.php`, `Classes/Common/Solr/`)

`Indexer::add()` writes one Solr document per logical unit plus one per physical page (with OCR fulltext in MiniOCR form for `solr-ocrhighlighting`). Solr field names are configured via `tx_dlf_metadata` and resolved with `Indexer::getIndexFieldName()`. `Solr` wraps a Solarium client per core (`tx_dlf_solrcores`), with results cached in the `tx_dlf_solr` cache. `SolrSearch` implements Extbase's `QueryResultInterface` so Solr hits can be paginated like a repository result; `SearchResult/` holds highlight/page/region value objects. Solr configsets ship in `Configuration/ApacheSolr/configsets`.

### Frontend plugins (`Classes/Controller/`, `Configuration/FlexForms/`, `Resources/Private/Templates/`)

Each plugin (PageView, Search, Navigation, TableOfContents, Metadata, Toolbox, ListView, Collection, Calendar, Basket, Feeds, OaiPmh, MediaPlayer, MultiView, Embedded3dViewer, Annotation, Validation, Statistics, PageGrid) is an Extbase controller registered in `ext_localconf.php`, configured by a FlexForm of the same name, and rendered by Fluid templates/partials of the same name. All extend `AbstractController`, which loads the document, sanitizes settings and request data (`getParametersSafely()` reads `tx_dlf[...]` params shared across plugins), and handles the multi-view case. The Toolbox plugin's tools are registered in `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools']`.

PSR-15 middlewares in `Classes/Middleware/` (registered in `Configuration/RequestMiddlewares.php`) serve the AJAX/eID-style endpoints: `PageViewProxy` (CORS proxy for images/IIIF, uHash-protected), `SearchInDocument`, `SearchSuggest`, `Embedded3dViewer`, `DOMDocumentValidation`.

### CLI and scheduler (`Classes/Command/`, `Classes/Task/`)

`kitodo:index`, `kitodo:reindex`, `kitodo:harvest` (OAI-PMH via `caseyamcl/phpoaipmh`), `kitodo:delete`, `kitodo:optimize`, `kitodo:suggestBuild`, `kitodo:databaseDocsGenerate` are registered in `Configuration/Services.yaml`. Each has a matching scheduler task + `AdditionalFieldProvider` pair wrapping the same `BaseCommand` logic.

### Other pieces

- `Classes/Hooks/DataHandler.php`: reindexes/deletes Solr documents when backend records change; `KitodoProductionHacks` is a `MetsDocument` hook that derives a record ID for Kitodo.Production exports lacking one.
- `Classes/Updates/`: TYPO3 upgrade wizards (settings migration, format rows, Solr schema).
- `Classes/Validation/`: pluggable XML validation stack (XSD, Schematron via Saxon) used by the Validation plugin.
- `Classes/Controller/Backend/NewTenantController.php`: backend module that seeds a storage page from `Configuration/Tenant/*.yaml`.

## Testing conventions

- Unit test namespaces mirror `Classes/` (`Tests/Unit/Format/AltoTest.php` ↔ `Classes/Format/Alto.php`). Use PHPUnit attributes (`#[Test]`), not annotations.
- Functional tests extend `Tests/Functional/FunctionalTestCase.php`, which loads the extension, reads Solr host from `Build/Test/test.env`, and offers `setUpData()` (CSV fixtures), `setUpSolr()` (creates a core and imports `*.solr.json`), `initializeRepository()` and `assertArrayMatches()`. Controller tests extend `AbstractControllerTestCase` for request/view scaffolding.
- Inside the functional containers, `http://web:8000` is the TYPO3 instance and `http://web:8001` serves the repository root, so fixtures reference METS files as `http://web:8001/Tests/Fixtures/...`. Remote documents are snapshotted under `Tests/Fixtures/Remote/<host>/<path>` to keep tests hermetic; see the README there before refreshing one.
- Use easily greppable, collision-free `uid`s in CSV fixtures (existing tests use random nine-digit numbers).

## Conventions

- Commit subjects are prefixed `[BUGFIX]`, `[FEATURE]`, `[MAINTENANCE]`, `[SECURITY]` or `[DOCUMENTATION]`.
- Every PHP file starts with the Kitodo GPL-3.0-or-later license header (copy from any existing class). PHPDoc is expected on public methods.
- `.editorconfig`: 4 spaces for PHP, 2 for JS/TS/YAML/TypoScript, tabs for JSON/XLF/SQL.
- Localized strings live in `Resources/Private/Language/locallang*.xlf`; new DB columns go in `ext_tables.sql` plus the matching `Configuration/TCA/` file.
