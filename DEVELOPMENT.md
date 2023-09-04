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

## Testing

Before running any of the tests, please install the project dependencies. Choose which version of TYPO3 you would like to test against.

```bash
# If you use PHP 7.3 or 7.4 (supported by Kitodo)
composer update --with=typo3/cms-core:^9.5
composer update --with=typo3/cms-core:^10.4

# If you use PHP 8
composer install-via-docker -- -t 9.5
composer install-via-docker -- -t 10.4
```

### Quick Start

```bash
# Run all tests
composer test

# Run specific kind of tests
composer test:unit
composer test:unit:local  # Run using locally installed PHP
composer test:func

# Run tests in watch mode
composer test:unit:watch
composer test:func:watch
```

### Run Tests Manually

Unit tests may be run either via a locally installed Composer / PHP setup, or within a Docker container.

```bash
# Run locally
vendor/bin/phpunit -c Build/Test/UnitTests.xml

# Run in Docker
Build/Test/runTests.sh
Build/Test/runTests.sh -w  # Watch mode
```

Functional tests may only be run in Docker as they require more infrastructure to be set up.

```bash
Build/Test/runTests.sh -s functional
Build/Test/runTests.sh -s functional -w  # Watch mode
```

To learn about available options (e.g., to select the PHP version), check the usage info:

```bash
Build/Test/runTests.sh -h
```

You may also interact with the Docker containers directly:

```bash
cd Build/Test/
vim .env  # Edit configuration
docker-compose run unit
docker-compose run functional
docker-compose down
```

### Fixtures

- Datasets may be created, for example, by exporting records from [MySQL Workbench](https://www.mysql.com/de/products/workbench/).
- When writing datasets, please use `uid`s that are easy to search (`grep`) for, and that ideally prevent merge conflicts.
  Some test cases use random nine-digit numbers (`rand(100000000, 999999999)`).

### File Structure

- `Build/Test/`: Test-related setup files (e.g. configuration for PHPUnit and testing container)
- `Tests/`: Test cases. In unit tests, namespacing follows the structure of `Classes/`.
- `Tests/Fixtures`: Datasets to use in functional tests.

### External Links

- [TYPO3 Testing Framework](https://github.com/TYPO3/testing-framework)
- [TYPO3 Explained: Extension testing](https://docs.typo3.org/m/typo3/reference-coreapi/9.5/en-us/Testing/ExtensionTesting.html)
- [typo3/cms-styleguide](https://github.com/TYPO3/styleguide)

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
