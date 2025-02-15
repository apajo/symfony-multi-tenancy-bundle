<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/src',
  ])

  ->withPhpSets(php82: true)
  ->withRules([
    // AddVoidReturnTypeWhereNoReturnRector::class,
    TypedPropertyFromStrictConstructorRector::class
  ])
  ->withPreparedSets(
    deadCode: true,
    codeQuality: true,
    typeDeclarations: true,
  )->withSets([
    DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    // NetteSetList::ANNOTATIONS_TO_ATTRIBUTES,
    // SensiolabsSetList::FRAMEWORK_EXTRA_61,
  ]);
;
