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

           DUAL is "dummy" table - there is no need to reference any table
           (more info in MySQL SELECT documentation)
        */
        $this->em->getConnection()->executeQuery(
            'INSERT INTO locality (name, user)
             SELECT :name, :user FROM DUAL
             WHERE NOT EXISTS(
                   SELECT l.name, l.user
                   FROM locality l
                   WHERE l.user = :user AND l.name = :name)
             LIMIT 1'
            , ['user' => $locality->getUser()->getId(), 'name' => $locality->getName()]);

        $result = $this->em->createQuery(
            'SELECT l FROM '.Locality::class.' l
             WHERE l.user = :user AND l.name = :name'
        )->setParameters([
            'user' => $locality->getUser(),
            'name' => $locality->getName()
        ])->getSingleResult();

        return $result;
    }
}