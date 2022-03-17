<?php

return [
    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                // When DataMapFactory checks the cache, it shouldn't try to access the database
                'extbase_datamapfactory_datamap' => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\NullBackend::class,
                ],
            ],
        ],
        'encryptionKey' => 'TYPO3 wants an encryption key here, but it should not be needed.',
    ],
];
