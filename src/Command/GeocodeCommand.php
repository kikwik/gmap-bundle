<?php

namespace Kikwik\GmapBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Geocoder\Provider\Provider;
use Kikwik\GmapBundle\Geocodable\GeocodableEntityInterface;
use Kikwik\GmapBundle\Geocodable\GeocodeStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeocodeCommand extends Command
{
    protected static $defaultName = 'kikwik:gmap:geocode';
    protected static $defaultDescription = 'Geocode entities that implements GeocodableEntityInterface';
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var Provider
     */
    private $googleMapsGeocoder;

    public function __construct(EntityManagerInterface $entityManager, Provider $googleMapsGeocoder)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->googleMapsGeocoder = $googleMapsGeocoder;
    }

    protected function configure()
    {
        $this
            ->addOption('limit',null, InputOption::VALUE_OPTIONAL,'Limit the number of request (per entity type)',5)
            ->addOption('failed',null,InputOption::VALUE_NONE,'Try to geocode the failed ones')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        // find all the entity class that implements GeocodableEntityInterface
        $geocodableEntityClasses = [];
        foreach($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata)
        {
            $class = $metadata->getName();
            $refClass = new \ReflectionClass($class);
            if($refClass->implementsInterface(GeocodableEntityInterface::class))
            {
                $geocodableEntityClasses[] = $class;
            }
        }

        foreach($geocodableEntityClasses as $class)
        {
            // find all objects (of $class class) that need geocode
            $io->note($class);

            $quesryBuilderMethod = $input->getOption('failed')
                ? 'createFailedGeocodeQueryBuilder'
                : 'createNeedGeocodeQueryBuilder';
            $objects = $this->entityManager->getRepository($class)->$quesryBuilderMethod('a')
                ->setMaxResults($input->getOption('limit'))
                ->getQuery()
                ->getResult();

            foreach($objects as $object)
            {
                /** @var GeocodableEntityInterface $object */
                $io->writeln('geocoding: '.$object->createGeocodeQueryString());

                $object->doGeocode($this->googleMapsGeocoder);
                $this->entityManager->persist($object);
                $this->entityManager->flush();

                switch($object->getGeocodeStatus())
                {
                    case GeocodeStatus::OK:
                        $status = '<info>'.$object->getGeocodeStatus().'</info>';
                        break;
                    case GeocodeStatus::ZERO_RESULTS:
                        $status = '<comment>'.$object->getGeocodeStatus().'</comment>';
                        break;
                    default:
                        $status = '<error>'.$object->getGeocodeStatus().'</error>';
                        break;
                }

                if($object->isGeocoded())
                {
                    $io->writeln('           '.$status.' - <info>'.$object->getLatitude().'</info> <info>'.$object->getLongitude().'</info> '.$object->getFormattedAddress());
                }
                else
                {
                    $io->writeln('           '.$status);
                }
            }
        }
        return Command::SUCCESS;
    }
}