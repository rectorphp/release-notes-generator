<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withImportNames(true)
    ->withPaths([__DIR__ . '/bin', __DIR__ . '/src', __DIR__ . '/tests'])
    ->withRootFiles()
    ->withPhpSets()
    ->withPreparedSets(instanceOf: true, naming: true, deadCode: true, codeQuality: true, codingStyle: true, typeDeclarations: true, strictBooleans: true);
