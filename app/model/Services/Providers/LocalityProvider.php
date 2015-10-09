<?php

namespace App\Model\Services\Providers;

use App\Model\Domain\Entities\Locality;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class LocalityProvider extends Object
{
    /** @var EntityManager  */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param Locality $locality
     * @return Locality
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function setupLocalityEntity(Locality $locality)
    {
        /* In order to NOT auto increment locality ID counter in DB by
           INSERTs that actually wont happen (e.g. safePersist()) and
           because Doctrine2 does NOT support locking of entire tables,
           we have to use native SQL(MySQL) query.
        */
        $this->em->getConnection()->executeQuery(
            'INSERT INTO locality (name)
             SELECT :name FROM locality
             WHERE NOT EXISTS(SELECT name FROM locality WHERE name = :name)
             LIMIT 1'
            , ['name' => $locality->getName()]);

        $result = $this->em->createQuery(
            'SELECT l AS locality FROM '.Locality::class.' l
             WHERE l.name = :name'
        )->setParameter('name', $locality->getName())
         ->getSingleResult()['locality'];

        return $result;
    }
}