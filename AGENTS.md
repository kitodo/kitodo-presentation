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

The binding rules are the [Kitodo Coding Guidelines](https://github.com/kitodo/coding-guidelines) (German) and, through them, the [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html). Points that matter most when editing this repository:

- **One task per branch and pull request.** A branch fixes exactly one bug or implements one feature, is created from `main`, and must be kept up to date with `main` until merged. Purely cosmetic changes (formatting, reordering) or non-functional additions (docs, comments) to otherwise untouched code go in a separate branch, never mixed into a functional change. Bug fixes that also affect a supported release branch (`7.x`, see `SECURITY.md`) need a second PR there.
- **Tests are mandatory.** Every changed or new behaviour must be covered by unit or functional tests that run in the existing GitHub workflow, and a bug fix should add a regression test. Review by release management starts only after PHPStan, php-cs-fixer and the test workflows pass.
- **Code and documentation are in English.** Issues and mailing-list discussion may be in German.
- **PHP style.** Follow TYPO3 CGL and PSR-12. One class per file, named after the class, in the `\Kitodo\Dlf` namespace matching the directory. Import foreign classes with `use` at the top and reference them unqualified. Declare parameter and return types everywhere. The guidelines require `declare(strict_types=1);` in every PHP file; only a few existing files have it yet, so add it to new files but do not sweep it into unrelated ones.
- **PHPDoc.** Document every class, public method and class property; non-trivial private methods too. Use `@property[-read|-write]` on classes, `@param` then `@return` on methods, `@var` on properties, in that order.
- **Plugins and modules** extend `AbstractController`. All URL parameters live in the `tx_dlf` namespace so plugins can read each other's input. New frontend plugins or backend modules normally belong in a separate extension depending on `kitodo/presentation`; only base functionality goes into `dlf`.
- **Errors are logged.** Exceptions are handled or passed up to where they can be, and always logged at the appropriate level; the same goes for warnings and notices. Use getters and setters rather than public properties; always brace loops and conditionals.
- **Commit messages.** Subjects are prefixed `[BUGFIX]`, `[FEATURE]`, `[MAINTENANCE]`, `[SECURITY]` or `[DOCUMENTATION]` and describe the change briefly but meaningfully. No changelog inside source files. Commits are signed off (`git commit -s`). Work materially shaped by an AI tool is declared with a trailer in the form used by the [Koha Coding Guidelines](https://wiki.koha-community.org/wiki/Coding_Guidelines), since the Kitodo guidelines have no rule for this yet, and the human author stays responsible for the change:

  ```
  Assisted-by: <ModelName> <Version> (<Vendor>)
  Signed-off-by: Jane Doe <jane@example.org>
  ```

  Do not use `Co-Authored-By` for AI tools. Routine autocompletion or linting needs no trailer. When a model orchestrates sub-agents, declare only the orchestrating model.
- Every PHP file starts with the Kitodo GPL-3.0-or-later license header (copy from any existing class).
- `.editorconfig`: 4 spaces for PHP, 2 for JS/TS/YAML/TypoScript, tabs for JSON/XLF/SQL. UTF-8, Unix line endings, final newline.
- Localized strings live in `Resources/Private/Language/locallang*.xlf`; new DB columns go in `ext_tables.sql` plus the matching `Configuration/TCA/` file.
- Versioning follows SemVer. Features target the current or next stable release; about three months before a functional release, release management declares a feature freeze after which only fixes and maintenance are accepted. Announce larger development plans to release management early.
