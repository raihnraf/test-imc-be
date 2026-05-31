<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/migrations',
        __DIR__ . '/routes',
        __DIR__ . '/seeds',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_whitespace_in_blank_line' => true,
        'compact_nullable_type_declaration' => true,
        'declare_equal_normalize' => true,
        'lowercase_cast' => true,
        'no_short_bool_cast' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_leading_import_slash' => true,
        'echo_tag_syntax' => true,
        'combine_consecutive_unsets' => true,
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'blank_line_after_opening_tag' => true,
        'blank_line_between_import_groups' => true,
        'braces' => true,
        'class_definition' => true,
        'declare_strict_types' => true,
        'is_null' => true,
        'modernize_types_casting' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'phpdoc_order' => true,
        'semicolon_after_instruction' => true,
        'strict_comparison' => true,
        'ternary_operator_spaces' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder($finder);
