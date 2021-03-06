<?php

// @see https://github.com/FriendsOfPHP/PHP-CS-Fixer

$finder = PhpCsFixer\Finder::create()
    ->in(array(__DIR__ . '/src', __DIR__ . '/tests'))
;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => array('syntax' => 'long'),
        'concat_space' => array('spacing' => 'one'),
        'ordered_imports' => true,
        'no_trailing_whitespace' => true,
        'no_useless_return' => true,
        'no_useless_else' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
    ))
    ->setFinder($finder)
;
