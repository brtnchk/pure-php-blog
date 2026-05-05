<?php

declare(strict_types=1);

/**
 * Single source of truth for PHP code style across the project.
 *
 * Rules are layered:
 *   - @PER-CS2.0      — modern successor to PSR-12 (the team baseline)
 *   - @PHP82Migration — promotes 8.2 syntax (readonly, never, etc.)
 *   - project-specific overrides — listed last, win on conflict
 *
 * Run locally:   composer fix          (rewrites files)
 *                composer fix:check    (dry-run; CI uses this)
 */

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/public',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRules([
        '@PER-CS2.0'      => true,
        '@PER-CS2.0:risky' => true,
        '@PHP82Migration' => true,

        // ---- arrays / strings ----
        'array_syntax'                 => ['syntax' => 'short'],
        'single_quote'                 => true,
        'trailing_comma_in_multiline'  => ['elements' => ['arrays', 'arguments', 'parameters', 'match']],

        // ---- imports ----
        'no_unused_imports'    => true,
        'ordered_imports'      => ['sort_algorithm' => 'alpha', 'imports_order' => ['class', 'function', 'const']],
        'global_namespace_import' => [
            'import_classes'   => true,
            'import_constants' => false,
            'import_functions' => false,
        ],

        // ---- declare strict types on every file ----
        'declare_strict_types' => true,
        'blank_line_after_opening_tag' => true,

        // ---- class layout ----
        'class_attributes_separation' => [
            'elements' => ['method' => 'one', 'property' => 'one', 'const' => 'one', 'trait_import' => 'none'],
        ],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'visibility_required' => ['elements' => ['property', 'method', 'const']],

        // ---- functions / arguments ----
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'no_unused_imports'     => true,

        // ---- whitespace ----
        'no_extra_blank_lines'        => ['tokens' => ['extra', 'throw', 'use', 'use_trait']],
        'single_blank_line_at_eof'    => true,
        'no_trailing_whitespace'      => true,
        'no_whitespace_in_blank_line' => true,

        // ---- control flow ----
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],

        // ---- nullable / types ----
        'nullable_type_declaration_for_default_null_value' => true,

        // ---- phpdoc ----
        'no_empty_phpdoc'             => true,
        'phpdoc_align'                => ['align' => 'left'],
        'phpdoc_indent'               => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_separation'           => true,
        'phpdoc_trim'                 => true,
    ]);