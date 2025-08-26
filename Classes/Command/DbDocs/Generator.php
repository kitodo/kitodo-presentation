<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Command\DbDocs;

use Doctrine\DBAL\Schema\Table;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use TYPO3\CMS\Core\Database\Schema\Parser\Lexer;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Aggregates information about database tables and generates an .rst reference page.
 *
 * @author Kajetan Dvoracek <kajetan.dvoracek@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Generator
{
    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var DataMapper
     */
    protected $dataMapper;

    /**
     * @var SqlReader
     */
    protected $sqlReader;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @param DataMapper $dataMapper
     */
    public function injectDataMapper(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @param SqlReader $sqlReader
     */
    public function injectSqlReader(SqlReader $sqlReader)
    {
        $this->sqlReader = $sqlReader;
    }

    /**
     * @param ConfigurationManager $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    public function __construct()
    {
        $this->languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create("default");
    }

    /**
     * Collect information about relevant tables from `ext_tables.sql` and the
     * Extbase classmap.
     */
    public function collectTables(): array
    {
        $sqlCode = $this->sqlReader->getTablesDefinitionString(true);
        $createTableStatements = $this->sqlReader->getCreateTableStatementArray($sqlCode);

        $tableToClassName = $this->getTableClassMap();

        $result = [];

        foreach ($createTableStatements as $statement) {
            $parser = new Parser(new Lexer());
            list($table) = $parser->parse($statement);

            $tableName = $table->getName();
            if (!str_starts_with($tableName, 'tx_dlf_')) {
                continue;
            }

            $className = $tableToClassName[$tableName] ?? null;

            $result[] = $this->getTableInfo($table, $className);
        }

        return $result;
    }

    /**
     * Get a map from database table names to their domain model class names.
     */
    public function getTableClassMap(): array
    {
        $dataMapFactory = GeneralUtility::makeInstance(DataMapFactory::class);

        // access classes configuration through reflection, which is otherwise not available?
        $reflectionProperty = new ReflectionProperty(DataMapFactory::class, 'classesConfiguration');
        $reflectionProperty->setAccessible(true);
        $classesConfiguration = $reflectionProperty->getValue($dataMapFactory);
        $reflectionProperty = new ReflectionProperty(ClassesConfiguration::class, 'configuration');
        $reflectionProperty->setAccessible(true);
        $configuration = $reflectionProperty->getValue($classesConfiguration);

        $result = [];
        foreach ($configuration as $className => $tableConf) {
            $tableName = $tableConf['tableName'];
            $result[$tableName] = $className;
        }

        return $result;
    }

    /**
     * Collect information about a single table.
     *
     * @param Table $table The table to be analyzed
     * @param string|null $className Fully qualified name of the domain model class
     */
    protected function getTableInfo(Table $table, ?string $className): object
    {
        $tableName = $table->getName();

        $isPrimary = [];
        if (!is_null($primaryKey = $table->getPrimaryKey())) {
            foreach ($primaryKey->getUnquotedColumns() as $primaryColumn) {
                $isPrimary[$primaryColumn] = true;
            }
        }

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columnName = $column->getName();

            $columns[$columnName] = (object) [
                'name' => $columnName,
                'type' => $column->getType(),
                'isPrimary' => isset($isPrimary[$columnName]),
                'sqlComment' => $column->getComment() ?? '',
                'fieldComment' => '',
                'feComment' => $this->languageService->sL($GLOBALS['TCA'][$tableName]['columns'][$columnName]['label'] ?? ''),
            ];
        }

        $result = (object) [
            'name' => $tableName,
            'columns' => $columns,
            'modelClass' => null,
            'sqlComment' => $table->getComment() ?? '',
            'classComment' => '',
            'feComment' => $this->languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title'] ?? ''),
        ];

        // Integrate doc-comments from model class and its fields
        if ($className !== null) {
            $reflection = new ReflectionClass($className);

            $dataMap = $this->dataMapper->getDataMap($className);

            foreach ($reflection->getProperties() as $property) {
                // If the TCA doesn't list the column, DataMap won't know about it.
                // In that case, try to guess the column name from the property name.

                $column = $dataMap->getColumnMap($property->getName());
                $columnName = $column === null
                    ? GeneralUtility::camelCaseToLowerCaseUnderscored($property->getName())
                    : $column->getColumnName();

                if (isset($result->columns[$columnName])) {
                    $result->columns[$columnName]->fieldComment = $this->parsePropertyDocComment($property->getDocComment());
                }
            }

            $result->modelClass = $className;
            $result->classComment = $this->parseDocComment($reflection->getDocComment());
        }

        return $result;
    }

    protected function parsePropertyDocComment($docComment)
    {
        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            // extract text from @var line
            if ($line !== '' && str_contains($line, '@var')) {
                $text = preg_replace('#\\s*/?[*/]*\\s?(.*)$#', '$1', $line) . "\n";
                $text = preg_replace('/@var [^ ]+ ?/', '', $text);
                return trim($text);
            }
        }
        return '';
    }

    protected function parseDocComment($docComment)
    {
        // TODO: Consider using phpDocumentor (though that splits the docblock into summary and description)

        // Adopted from DocCommentParser in TYPO3 v9
        // https://github.com/TYPO3/typo3/blob/57944c8c5add00f0e8a1a5e1d07f30a8f20a8201/typo3/sysext/extbase/Classes/Reflection/DocCommentParser.php
        $text = '';
        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            // Stop parsing at first tag
            if ($line !== '' && str_contains($line, '@')) {
                break;
            }

            // There may be a single non-signifying space after the doc-comment asterisk,
            // which is not included.
            $text .= preg_replace('#\\s*/?[*/]*\\s?(.*)$#', '$1', $line) . "\n";
        }
        $text = trim($text);

        return $text;
    }

    /**
     * Transform table structure into .rst page.
     */
    public function generatePage(array $tables)
    {
        $page = new RstSection();
        $page->setHeader('Database Tables');
        $page->addText(<<<RST
This is a reference of all database tables defined by Kitodo.Presentation.

.. tip:: This page is auto-generated. If you would like to edit it, please use doc-comments in the model class, COMMENT fields in ``ext_tables.sql`` if the table does not have one, or TCA labels. Then, you may re-generate the page by running ``vendor/bin/typo3 kitodo:dbdocs`` from the composer-based TYPO3 install directory (not the Kitodo.Presentation source directory).
RST);

        // Sort tables alphabetically
        usort($tables, function ($lhs, $rhs) {
            return $lhs->name <=> $rhs->name;
        });

        foreach ($tables as $tableInfo) {
            $section = $page->subsection();

            // Set header
            $header = $tableInfo->name;
            if (!empty($tableInfo->feComment)) {
                $header .= ': ' . $tableInfo->feComment;
            }
            $section->setHeader($header);

            // Set introductory text of subsection
            if ($tableInfo->modelClass) {
                $section->addText('Extbase domain model: ``' . $tableInfo->modelClass . '``');
            }
            $section->addText($tableInfo->classComment);
            $section->addText($tableInfo->sqlComment);

            // Generate main table
            $header = [[
                'field' => 'Field',
                'description' => 'Description',
            ]];

            $rows = array_map(function ($column) use ($page) {
                return [
                    'field' => (
                        $page->format($column->name, ['bold' => $column->isPrimary])
                        . "\u{00a0}\u{00a0}"
                        . $page->format($column->type->getName(), ['italic' => true])
                    ),

                    'description' => $page->paragraphs([
                        $page->format($column->feComment, ['italic' => true]),
                        $column->fieldComment,
                        $column->sqlComment,
                    ]),
                ];
            }, $tableInfo->columns);

            $section->addTable($rows, $header);
        }
        return $page;
    }
}
