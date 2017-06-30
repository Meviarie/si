<?php
/**
 * Admin Repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;

/**
 * Class AdminRepository.
 *
 */
class AdminRepository
{
	/**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 20;

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
	 public function findAll()
    {
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT u.id_login_data) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }
    /**
     * ShowUsers.
     *
     */
    public function ShowUsers()
    {
	$queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();

    }

    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select(
            'u.id_login_data',
            'u.login',
            'u.password',
            'u.user_roles_id_user_roles'
        )->from('login_data', 'u');
    }

    /**
     * Find user by id.
     *
     * @param  $id Id
     * @return mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('id_login_data', 'login')
            ->from('login_data')
            ->where('id_login_data = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return $result;
    }


    /**
     * Change user's password.
     *
     * @param $data Data
     * @param $app App
     */
    public function changeUsersPassword($data, $app)
    {
        $password = $app['security.encoder.bcrypt']->encodePassword($data['password'], '');
        $this->db->update('login_data', ['password'=>$password], ['id_login_data'=>$data['id']]);
    }

    /**
     * Change user's role.
     *
     * @param $data Data
     * @param $app App
     */
    public function changeUsersRoles($data)
    {
        dump($data);
        //$user = $this->getUserByLogin($login);
        $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder->update('login_data','u')
                    ->set('u.user_roles_id_user_roles', '?')
                    ->where('u.id_login_data = ?')
                    ->setParameter(0, $data['name'], \PDO::PARAM_INT)
                    ->setParameter(1, $data['id'], \PDO::PARAM_INT)
                ;
        $queryBuilder->execute();
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.id_login_data', 'u.login', 'u.password')
                ->from('login_data', 'u')
                ->where('u.login = :login')
                ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Remove record.
     *
     * @param array $tag Tag
     *
     * @return boolean Result
     */
    public function delete($id)
    {
        $this->db->beginTransaction();

        try {
            $this->db->delete('login_data', ['id_login_data' => $id]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

}
