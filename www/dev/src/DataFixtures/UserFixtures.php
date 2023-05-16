<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Security\ApiKeyAuthenticator;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends BaseFixtures
{
    /**
     * @var ApiKeyAuthenticator
     */
    private $authenticator;

    public function __construct(ApiKeyAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }


    function loadData(ObjectManager $manager)
    {

        $this->createMany(User::class, 1, function (User $user) use ($manager) {

            $user->setPassword('T608PM1gnT');
            $user->setUsername($this->faker->name);
            $user->setRoles(['ROLE_USER']);
            $user->setToken($this->authenticator->getToken());
        });

    }
}
