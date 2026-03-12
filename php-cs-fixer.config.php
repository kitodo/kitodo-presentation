<?php
$finder = PhpCsFixer\Finder::create()
->in([
    __DIR__ . '/Classes',
    __DIR__ . '/Configuration',
    __DIR__ . '/Tests',
]);

return (new PhpCsFixer\Config())
->setRules([
    '@PSR12' => true,
])
->setFinder($finder);
