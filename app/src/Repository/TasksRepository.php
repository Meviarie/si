<?php
/**
 * Tasks Repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;

/**
 * Class TasksRepository.
 *
 */
class TasksRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 10;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * Tag repository.
     *
     * @var null|\Repository\TagRepository $tagRepository
     */
    protected $tagRepository = null;

    /**
     * TagRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->tagRepository = new TagRepository($db);
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
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT a.id_tasks) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    // ...
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
        $queryBuilder->where('a.id_tasks = :id_tasks')
            ->setParameter(':id_tasks', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        if ($result) {
            $result['tags'] = $this->findLinkedTags($result['id_tasks']);
        }

        return $result;
    }

    /**
     * Save record.
     *
     * @param array $task Task
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($task, $id)
    {
        $this->db->beginTransaction();

        try {
            // $currentDateTime = new \DateTime();
            //$bookmark['modified_at'] = $currentDateTime->format('Y-m-d H:i:s');
            $tagsIds = isset($task['tags']) ? array_column($task['tags'], 'id_tasks') : [];
            //unset($task['tags']);

            if (isset($task['id_tasks']) && ctype_digit((string) $task['id_tasks'])) {
                // update record
                $bookmarkId = $task['id_tasks'];
                unset($task['id_tasks']);
                $this->removeLinkedTags($taskId);
                $this->addLinkedTags($taskId, $tagsIds);
                $this->db->update('tasks', $task, ['id_tasks' => $taskId]);
            } else {
                // add new record

                dump($task);

                $tags = $task['tags'];

                unset($task['tags']);
                $this->db->insert('tasks', $tasks);
                $noteId = $this->db->lastInsertId();
                $this->addLinkedTags($tags);
                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder->select('t.idtags')
                    ->from('tags', 't')
                    ->where('t.name = :name')
                    ->setParameter(':name', $tags, \PDO::PARAM_STR);
                $result = $queryBuilder->execute()->fetch();
                $idtags = $result['idtags'];
                //dump($idtags);

                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder->update('tasks','a')
                    ->set('a.login_data_id_login_data', '?')
                    ->set('a.tags_idtags', '?')
                    ->where('a.id_tasks = ?')
                    ->setParameter(0, $id, \PDO::PARAM_INT)
                    ->setParameter(1, $idtags, \PDO::PARAM_INT)
                    ->setParameter(2, $taskId, \PDO::PARAM_INT)
                ;
                $queryBuilder->execute();
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove record.
     *
     * @param array $task Task
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return boolean Result
     */
    public function delete($task)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedTags($task['id_tasks']);
            $this->db->delete('tasks', ['id_tasks' => $task['id_tasks']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Finds linked tags Ids.
     *
     * @param int $taskId Task Id
     *
     * @return array Result
     */
    protected function findLinkedTagsIds($tagsIds)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('at.tags_idtags')
            ->from('tasks', 'at')
            ->where('at.tags_idtags = :idtags')
            ->setParameter(':idtags', $tagsIds, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'idtags') : [];
    }

    /**
     * Remove linked tags.
     *
     * @param int $taskId Task Id
     *
     * @return boolean Result
     */
    protected function removeLinkedTags($taskId)
    {
        return $this->db->delete('tasks', ['id_tasks' => $taskId]);
    }

    /**
     * Add linked tags.
     *
     * @param int $taskId Task Id
     * @param array $tagsIds Tags Ids
     */
    protected function addLinkedTags($tag)
    {
        if (!is_array($tag)) {
            $tag = [$tag];
        }

        foreach ($tag as $tags) {
            $this->db->insert(
                'tags',
                [
                    'name' => $tags,
                ]
            );
        }
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select(
            'a.id_tasks',
            'a.content',
            'a.deadline',
            'a.done_or_not',
            'a.tags_idtags',
            'a.login_data_id_login_data	'
        )->from('tasks', 'a');
    }

    /**
     * Find linked tags.
     *
     * @param int $taskId task Id
     *
     * @return array Result
     */
    public function findLinkedTags($taskId)
    {
        $tagsIds = $this->findLinkedTagsIds($taskId);

        return is_array($tagsIds)
            ? $this->tagRepository->findById($tagsIds)
            : [];
    }
}