<?php

namespace Hakam\MultiTenancyBundle\Tests\Functional;

use aPajo\MultiTenancyBundle\APajoMultiTenancyBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class ApajoMultiTenancyBundleTestingKernel extends Kernel
{
    private array $config;

    public function __construct(array $config = [])
    {
        parent::__construct('test', true);
        $this->config = $config;
    }

    public function registerBundles(): array
    {
        return [
            new DoctrineBundle(),
            new DoctrineMigrationsBundle(),
            new APajoMultiTenancyBundle()
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->register('annotation_reader', AnnotationReader::class);
            $container->loadFromExtension('hakam_multi_tenancy', $this->multiTenancyConfig);
        });
    }
}
