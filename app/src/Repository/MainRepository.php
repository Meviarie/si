<?php
/**
 * Main Repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class MainRepository.
 *
 */
class MainRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }
    /**
     * Test.
     *
     */
     public function Date()
    {

        $date = new \DateTime();
        $dataczas = $date->format('Y-m-d H:i');
        $day = $date->format('D');

        return $dataczas;

    }

     public function Day()
    {
        $date = new \DateTime();
        $day = $date->format('w');
	    $query = $this->queryAll();
        $query->where('id_data = :id_data')
            ->setParameter(':id_data', $day, \PDO::PARAM_INT);
        $result = $query->execute()->fetch();

        return $result;
    }

     protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select(
            'd.id_data',
            'd.data'
        )->from('data', 'd');
    }
}
