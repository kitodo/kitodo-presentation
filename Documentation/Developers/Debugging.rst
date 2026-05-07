=========
Debugging
=========

Kitodo.Presentation allows developers to debug their code by calling the debug functions, which outputs in a human-readable format.

Database Queries
================

The SQL queries executed by the database connection can be logged by calling the ``activateDebugMode()`` function from the repositories. This will output the queries in the frontend, which can be useful for debugging and optimizing database interactions.

.. code-block:: php

    public function activateDebugMode(): void
    {
        $this->debug = true;
    }

.. code-block:: php

    protected function debugQuery(QueryInterface $query): void
    {
        if ($this->debug) {
            $typo3DbQueryParser = GeneralUtility::makeInstance(Typo3DbQueryParser::class);
            $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);
            DebuggerUtility::var_dump($queryBuilder->getSQL());
            DebuggerUtility::var_dump($queryBuilder->getParameters());
        }
    }

.. code-block:: php

    protected function debugQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($this->debug) {
            DebuggerUtility::var_dump($queryBuilder->getSQL());
            DebuggerUtility::var_dump($queryBuilder->getParameters());
        }
    }


Fluid Templates
===============

The Fluid templates can be debugged by calling the ``activateDebugMode()`` function in the controller, which outputs the variables in a human-readable format. This can be useful for debugging the data passed to the templates and the logic within the templates.

.. code-block:: php

    protected function activateDebugMode(): void
    {
        $this->view->assign('debugActive', true);
    }

