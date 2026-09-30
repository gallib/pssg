<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->append([
        __DIR__ . '/bin/pssg',
    ])
    ->name('*.php');

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,

        'array_syntax' => [
            'syntax' => 'short',
        ],

        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],

        'blank_line_after_opening_tag' => true,

        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try'],
        ],

        'braces_position' => [
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],

        'concat_space' => [
            'spacing' => 'one',
        ],

        'declare_strict_types' => true,

        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],

        'no_superfluous_phpdoc_tags' => false,

        'no_unused_imports' => true,

        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],

        'single_quote' => true,

        'trailing_comma_in_multiline' => [
            'elements' => ['arrays'],
        ],
    ])
    ->setFinder($finder);
