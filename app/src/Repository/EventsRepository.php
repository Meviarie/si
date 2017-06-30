<?php
/**
 * Events Repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;

/**
 * Class EventsRepository.
 *
 */
class EventsRepository
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
            ->select('COUNT(DISTINCT e.id_events) AS total_results')
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
        $queryBuilder->where('e.id_events = :id_events')
            ->setParameter(':id_events', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        if ($result) {
            $result['tags'] = $this->findLinkedTags($result['id_events']);
        }

        return $result;
    }

    /**
     * Save record.
     *
     * @param array $event Event
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($event, $id)
    {
        $this->db->beginTransaction();

        try {
            // $currentDateTime = new \DateTime();
            //$bookmark['modified_at'] = $currentDateTime->format('Y-m-d H:i:s');
            $tagsIds = isset($event['tags']) ? array_column($event['tags'], 'id_events') : [];
           // unset($event['tags']);

            if (isset($event['id_events']) && ctype_digit((string) $event['id_events'])) {
                // update record
                $eventId = $event['id_events'];
                unset($event['id_events']);
                $this->removeLinkedTags($eventId);
                $this->addLinkedTags($eventId, $tagsIds);
                $this->db->update('events', $event, ['id_events' => $eventId]);
            } else {
                // add new record

                $tag = $event['tags'];
                $tags = $event['0'];
                //dump($tags);
                unset($event['tags']);
                $this->db->insert('events', $event);
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
                $queryBuilder->update('events','e')
                    ->set('e.login_data_id_login_data', '?')
                    ->set('e.tags_idtags', '?')
                    ->where('e.id_events = ?')
                    ->setParameter(0, $id, \PDO::PARAM_INT)
                    ->setParameter(1, $idtags, \PDO::PARAM_INT)
                    ->setParameter(2, $eventId, \PDO::PARAM_INT)
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
     * @param array $event Event
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return boolean Result
     */
    public function delete($event)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedTags($event['id_events']);
            $this->db->delete('events', ['id_events' => $event['id_events']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Finds linked tags Ids.
     *
     * @param int $eventId Event Id
     *
     * @return array Result
     */
    protected function findLinkedTagsIds($tagsIds)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('et.tags_idtags')
            ->from('events', 'et')
            ->where('et.tags_idtags = :idtags')
            ->setParameter(':idtags', $tagsIds, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'idtags') : [];
    }

    /**
     * Remove linked tags.
     *
     * @param int $eventId Event Id
     *
     * @return boolean Result
     */
    protected function removeLinkedTags($eventId)
    {
        return $this->db->delete('events', ['id_events' => $eventId]);
    }

    /**
     * Add linked tags.
     *
     * @param int $eventId Event Id
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
            'e.id_events',
            'e.event_name',
            'e.date',
            'e.time',
            'e.tags_idtags',
            'e.login_data_id_login_data	'
        )->from('events', 'e');
    }

    /**
     * Find linked tags.
     *
     * @return array Result
     */
    public function findLinkedTags($eventId)
    {
        $tagsIds = $this->findLinkedTagsIds($eventId);

        return is_array($tagsIds)
            ? $this->tagRepository->findById($tagsIds)
            : [];
    }
}