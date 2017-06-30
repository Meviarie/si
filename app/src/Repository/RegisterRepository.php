<?php
/**
 * Register repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;


/**
 * Class RegisterRepository.
 *
 * @package Repository
 */
class RegisterRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 1;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;


    /**
     * RegisterRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }


    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('l.id_login_data = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('l.login', 'l.password')
            ->from('login_data', 'l');
    }

    public function save($register, $app)
    {
        $this->db->beginTransaction();
        try {

            if (isset($register['id']) && ctype_digit((string) $register['id']) && $register['password']!==$register['repeat_password']) {
                // update record

                $register['password'] = $app['security.encoder.bcrypt']->encodePassword($register['password'], '');

                $this->db->update('login_data', $register);


            } else {
                // add new record
                unset($register['repeat_password']);
                //dump($register);
                $register['password'] = $app['security.encoder.bcrypt']->encodePassword($register['password'], '');
                $register['user_roles_id_user_roles'] = '2';
                $this->db->insert('login_data', $register);



            }
            $this->db->commit();

        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }

    }
}