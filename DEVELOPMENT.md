# Development

## Kitodo.Presentation 4.0

- In Extbase, there is a default naming scheme to map model names to database
  table names. As we currently don't use these for historic reasons, the mapping
  needs to be reconfigured:

  - [ext_typoscript_setup.txt](ext_typoscript_setup.txt) is for compatibility
    with TYPO3 v9.
  - [Classes.php](Configuration/Extbase/Persistence/Classes.php) is for TYPO3
    v10 onwards.
  - `polyfillExtbaseClassesForTYPO3v9` (defined in [Helper.php](Classes/Common/Helper.php))
    is used for TYPO3 v9 compatibility with the expression language function
    `getDocumentType()` ([DocumentTypeFunctionProvider.php](Classes/ExpressionLanguage/DocumentTypeFunctionProvider.php)).

  To simplify this, we may consider to rename database tables according to the
  default naming scheme.


## Future Changes

### Pagination Widget will be removed in TYPO3 11

https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.0/Breaking-92529-AllFluidWidgetFunctionalityRemoved.html

The current solution does only work with TYPO3 9 and 10.

As of TYPO3 10 a new pagination API has been introduced. This could be used as replacement in a release supporting TYPO3 10 and 11.

https://docs.typo3.org/m/typo3/reference-coreapi/10.4/en-us/ApiOverview/Pagination/Index.html

## Documentation

Following TYPO3's practices, the main documentation of the extension is located in `Documentation` and written in [reStructuredText](https://en.wikipedia.org/wiki/ReStructuredText). The build system is [Sphinx](https://en.wikipedia.org/wiki/Sphinx_(documentation_generator)).

### Local Preview Server

To preview the rendered output and automatically rebuild documentation on changes, you may spawn a local server. This supports auto-refresh and is faster than the official preview build, but omits some features such as syntax highlighting.

This requires Python 2 to be installed.

```bash
# First start: Setup Sphinx in a virtualenv
composer docs:setup

# Spawn server
composer docs:serve
composer docs:serve -- -E  # Don't use a saved environment (useful when changing toctree)
composer docs:serve -- -p 8000  # Port may be specified
```

By default, the output is served to http://127.0.0.1:8000.

> The setup and serve commands are defined in [Build/Documentation/sphinx.sh](./Build/Documentation/sphinx.sh).

### Official Preview Build

The TYPO3 project [provides a Docker image to build documentation](https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/RenderingDocs/Quickstart.html). This requires both Docker and Docker Compose to be installed.

```bash
# Full build
composer docs:t3 makehtml

# Only run sphinx-build
composer docs:t3 just1sphinxbuild

# (Alternative) Run docker-compose manually
docker-compose -f ./Build/Documentation/docker-compose.t3docs.yml run --rm t3docs makehtml
```

The build output is available at [Documentation-GENERATED-temp/Result/project/0.0.0/Index.html](./Documentation-GENERATED-temp/Result/project/0.0.0/Index.html).

### Database Documentation

Generate the database reference table:

```bash
composer install
composer docs:db
```
