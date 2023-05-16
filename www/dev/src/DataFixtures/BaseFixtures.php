<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Factory;
use Faker\Generator;

/**
 * Class BaseFixtures
 * @package App\DataFixtures
 */
abstract class BaseFixtures extends Fixture
{
    /** @var Generator */
    protected $faker;
    /** @var ObjectManager */
    protected $manager;

    public function load(ObjectManager $manager)
    {
        $this->faker = Factory::create('ru_RU');
        $this->manager = $manager;
        
        $this->loadData($manager);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return mixed
     */
    abstract function loadData(ObjectManager $manager);

    /**
     * @param string $className
     * @param callable $factory
     * @return mixed
     */
    protected function create(string $className, callable $factory)
    {
        $entity = new $className();
        $factory($entity);

        $this->manager->persist($entity);
        
        return $entity;
    }

    /**
     * @param string $className
     * @param int $count
     * @param callable $factory
     */
    protected function createMany(string $className, int $count, callable $factory)
    {
        for ($i = 0; $i < $count; $i++) {
            $entity = $this->create($className, $factory);

            $this->addReference($className . "|$i", $entity);
        }
    }

    private $referencesIndex = [];

    /**
     * @param $className
     * @return object
     * @throws Exception
     */
    protected function getRandomReference($className)
    {
        if (! isset($this->referencesIndex[$className])) {
            $this->referencesIndex[$className] = [];
            
            foreach ($this->referenceRepository->getReferences() as $key => $reference) {
                if (strpos($key, $className . '|') === 0) {
                    $this->referencesIndex[$className][] = $key;
                }
            }
        }

        if (empty($this->referencesIndex[$className])) {
            throw new Exception('Не найдены ссылки на класс: ' . $className);
        }
        
        return $this->getReference($this->faker->randomElement($this->referencesIndex[$className]));
    }


}
