<?php

return (new PhpCsFixer\Config())
    ->setFinder((new PhpCsFixer\Finder())->in(['src', 'tests']))
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        'trailing_comma_in_multiline' => false,
    ])
;
