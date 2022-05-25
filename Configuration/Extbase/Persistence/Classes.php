<?php
declare(strict_types = 1);

return [
    \Kitodo\Dlf\Domain\Model\ActionLog::class => [
        'tableName' => 'tx_dlf_actionlog',
    ],
    \Kitodo\Dlf\Domain\Model\Basket::class => [
        'tableName' => 'tx_dlf_basket',
    ],
    \Kitodo\Dlf\Domain\Model\Collection::class => [
        'tableName' => 'tx_dlf_collections',
    ],
    \Kitodo\Dlf\Domain\Model\Document::class => [
        'tableName' => 'tx_dlf_documents',
    ],
    \Kitodo\Dlf\Domain\Model\Format::class => [
        'tableName' => 'tx_dlf_formats',
    ],
    \Kitodo\Dlf\Domain\Model\Library::class => [
        'tableName' => 'tx_dlf_libraries',
    ],
    \Kitodo\Dlf\Domain\Model\Mail::class => [
        'tableName' => 'tx_dlf_mail',
    ],
    \Kitodo\Dlf\Domain\Model\Metadata::class => [
        'tableName' => 'tx_dlf_metadata',
    ],
    \Kitodo\Dlf\Domain\Model\MetadataFormat::class => [
        'tableName' => 'tx_dlf_metadataformat',
    ],
    \Kitodo\Dlf\Domain\Model\MetadataSubentry::class => [
        'tableName' => 'tx_dlf_metadatasubentries',
    ],
    \Kitodo\Dlf\Domain\Model\Printer::class => [
        'tableName' => 'tx_dlf_printer',
    ],
    \Kitodo\Dlf\Domain\Model\SolrCore::class => [
        'tableName' => 'tx_dlf_solrcores',
    ],
    \Kitodo\Dlf\Domain\Model\Structure::class => [
        'tableName' => 'tx_dlf_structures',
    ],
    \Kitodo\Dlf\Domain\Model\Token::class => [
        'tableName' => 'tx_dlf_tokens',
    ]
];
