<?php
use Symfony\CS\Config\Config;
use Symfony\CS\Finder\DefaultFinder;
use Symfony\CS\FixerInterface;

$finder = DefaultFinder::create()->in(['src', 'tests']);

return Config::create()
             ->level(FixerInterface::SYMFONY_LEVEL)
             ->fixers([
                 'align_double_arrow',
                 'align_equals',

                 'ereg_to_preg',
                 'multiline_spaces_before_semicolon',
                 'newline_after_open_tag',
                 'no_blank_lines_before_namespace',
                 'ordered_use',
                 'php4_constructor',
                 'phpdoc_order',
                 'short_array_syntax',
                 'short_echo_tag',
                 'strict',
                 'strict_param',
             ])
             ->setUsingCache(true)
             ->finder($finder);
