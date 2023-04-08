<?php

namespace Kikwik\GmapBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Yaml;

class KikwikGmapExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        // bazinga_geocoder configuration
        $bazingaGeocoderConfig = Yaml::parseFile(__DIR__.'/../Resources/config/bazinga_geocoder.yaml');
        $container->prependExtensionConfig('bazinga_geocoder', $bazingaGeocoderConfig);

        // stof_doctrine_extensions configuration
        $stofDoctrineExtensionConfig = Yaml::parseFile(__DIR__.'/../Resources/config/stof_doctrine_extensions.yaml');
        $container->prependExtensionConfig('stof_doctrine_extensions', $stofDoctrineExtensionConfig);
    }


    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $twigExtension = $container->getDefinition('kikwik_gmap.twig.gmap_extension');
        $twigExtension->setArgument('$gmapApiKeyJs', $config['gmapApiKeyJs']);
    }

}