<?php

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        earlyReturn: true,
    )
    ->withPhpSets()
    ->withSkip([
        // The codebase does not use strict_types; adding it everywhere is a separate decision.
        SafeDeclareStrictTypesRector::class,
    ]);
