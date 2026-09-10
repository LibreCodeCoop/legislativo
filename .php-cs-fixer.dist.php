<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/lib')
    ->in(__DIR__ . '/tests')
    ->name('*.php');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP82Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => false,
            'import_constants' => false,
        ],
        'list_syntax' => ['syntax' => 'short'],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'allow_unused_params' => false,
        ],
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'php_unit_mock' => true,
        'php_unit_namespaced' => true,
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_summary' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arguments', 'arrays', 'match', 'parameters'],
        ],
        'yoda_style' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
