<?php

namespace App\Model\Repositories;

use App\Model\Entities\Locality;
use Nette\Utils\Validators;
use Tracy\Debugger;

class LocalityRepository extends BaseRepository
{
    /**
     * @param int $id
     * @return Locality
     */
    public function findById($id)
    {
        Validators::assert($id, 'numericint');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('localityID = ?', $id)
                                   ->fetch();

        if ($result == FALSE)
            throw new \Exceptions\Runtime\LocalityNotFoundException;

        return $this->createEntity($result);
    }

    /**
     * @param string $localityName
     * @return Locality
     */
    public function findByName($localityName)
    {
        Validators::assert($localityName, 'unicode');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('name = ?', $localityName)
                                   ->fetch();

        if ($result == FALSE)
            throw new \Exceptions\Runtime\LocalityNotFoundException;

        return $this->createEntity($result);

    }

    /**
     * @param string $localityName
     * @param int $userID
     * @return array
     */
    public function findSimilarByName($localityName, $userID, $limit)
    {
        Validators::assert($localityName, 'unicode|null');
        Validators::assert($userID, 'numericint');
        Validators::assert($limit, 'numericint');

        $results = $this->connection->select('l.localityID, l.name')
                        ->from($this->getTable())->as('l')
                        ->innerJoin('locality_user lu
                                     ON (lu.localityID = l.localityID)')
                        ->where('l.name LIKE %~like~ COLLATE utf8_czech_ci', $localityName)
                        ->where('lu.userID = ?', $userID)
                        ->limit($limit)
                        ->fetchAll();

        return $this->createEntities($results);
    }

    /**
     * @param int $userID
     * @return int
     */
    public function getNumberOfUserLocalities($userID)
    {
        Validators::assert($userID, 'numericint');

        $result = $this->connection
                       ->select('COUNT(localityUserID) as count')
                       ->from('locality_user')
                       ->where('userID = ?', $userID)
                       ->orderBy('localityUserID')
                       ->fetch();

        return $result['count'];
    }

    /**
     * @return array
     */
    public function findAll()
    {
        return $this->createEntities($this->connection->select('*')
                                          ->from($this->getTable())
                                          ->fetchAll());
    }

    /**
     * @param int $userID
     * @return array
     */
    public function findAllUserLocalities($userID)
    {
        Validators::assert($userID, 'numericint');

        $results = $this->connection->select('l.localityID, l.name')
                        ->from($this->getTable())->as('l')
                        ->innerJoin('locality_user lu
                                     ON (lu.localityID = l.localityID)')
                        ->where('lu.userID = ?', $userID)
                        //->orderBy('l.name')
                        ->fetchAll();

        return $this->createEntities($results);
    }

    /**
     * @param int $localityID
     * @param int $userID
     */
    public function removeUserLocality($localityID, $userID)
    {
        Validators::assert($localityID, 'numericint');
        Validators::assert($userID, 'numericint');

        $this->connection->delete('locality_user')
                         ->where('localityID = ? AND userID = ?',
                                 $localityID, $userID)->execute();
    }

    /**
     * @param array $localitiesIDs
     * @param int $userID
     */
    public function removeLocalities(array $localitiesIDs, $userID)
    {
        Validators::assert($userID, 'numericint');

        $this->connection->delete('locality_user')
                         ->where('userID = ?', $userID)
                         ->where('localityID IN (?)', $localitiesIDs)
                         ->execute();
    }

    /**
     * @param Locality $locality
     * @param $userID
     */
    public function saveLocalityToUserList(Locality $locality, $userID)
    {
        Validators::assert($userID, 'numericint');

        $this->connection
             ->query('INSERT IGNORE INTO locality_user',
                 ['localityID' => $locality->localityID,
                  'userID' => $userID]
             );
    }

    /**
     * @param Locality $locality
     * @return Locality
     * @throws \DibiException
     */
    public function setupLocality(Locality $locality)
    {
        try {
            $this->connection
                 ->query('INSERT INTO [locality]', ['name' => $locality->name],
                         'ON DUPLICATE KEY UPDATE
                          localityID = LAST_INSERT_ID(localityID)');

            $id = $this->connection->getInsertId();
            if (!$locality->isDetached()) {
                $locality->detach();
            }

            $locality->makeAlive($this->entityFactory, $this->connection, $this->mapper);
            $locality->attach($id);

            return $locality;

        } catch (\DibiException $e) {

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

}