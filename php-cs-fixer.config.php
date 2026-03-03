<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('.github')
    ->exclude('BUILD')
    ->exclude('Configuration')
    ->exclude('Resources')
    ->exclude('Tests')
    ->exclude('Classes/Api')
    ->exclude('Documentation')
    ->notPath(['php-cs-fixer.config.php']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
