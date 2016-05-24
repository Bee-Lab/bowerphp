<?php
// see https://github.com/FriendsOfPHP/PHP-CS-Fixer

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
;

return Symfony\CS\Config\Config::create()
    ->fixers(['concat_with_spaces', 'align_double_arrow', 'short_array_syntax', '-concat_without_spaces', 'ordered_use', 'unused_use', '-phpdoc_short_description', '-phpdoc_separation'])
    ->finder($finder)
;
