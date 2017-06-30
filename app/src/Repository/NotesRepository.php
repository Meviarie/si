<?php
/**
 * Notes Repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;

/**
 * Class NotesRepository.
 *
 */
class NotesRepository
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
            ->select('COUNT(DISTINCT n.id_notes) AS total_results')
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
        $queryBuilder->where('n.id_notes = :id_notes')
            ->setParameter(':id_notes', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        if ($result) {
            $result['tags'] = $this->findLinkedTags($result['id_notes']);
        }

        return $result;
    }

    /**
     * Save record.
     *
     * @param array $note note
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($note, $id)
    {
        $this->db->beginTransaction();

        try {
            // $currentDateTime = new \DateTime();
            //$bookmark['modified_at'] = $currentDateTime->format('Y-m-d H:i:s');
            $tagsIds = isset($note['tags']) ? array_column($note['tags'], 'id_notes') : [];
            //unset($note['tags']);

            if (isset($note['id_notes']) && ctype_digit((string) $note['id_notes'])) {
                // update record
                $noteId = $note['id_notes'];
                unset($note['id_notes']);
                $this->removeLinkedTags($noteId);
                $this->addLinkedTags($noteId, $tagsIds);
                $this->db->update('notes', $note, ['id_notes' => $noteId]);
            } else {
                // add new record
                $tag = $note['tags'];
                $tags = $tag['0'];
                //dump($tags);
                unset($note['tags']);
                $this->db->insert('notes', $note);
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
                $queryBuilder->update('notes','n')
                    ->set('n.login_data_id_login_data', '?')
                    ->set('n.tags_idtags', '?')
                    ->where('n.id_notes = ?')
                    ->setParameter(0, $id, \PDO::PARAM_INT)
                    ->setParameter(1, $idtags, \PDO::PARAM_INT)
                    ->setParameter(2, $noteId, \PDO::PARAM_INT)
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
     * @param array $note note
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return boolean Result
     */
    public function delete($note)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedTags($note['id_notes']);
            $this->db->delete('notes', ['id_notes' => $note['id_notes']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Finds linked tags Ids.
     *
     * @param int $noteId note Id
     *
     * @return array Result
     */
    protected function findLinkedTagsIds($tagsIds)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('nt.tags_idtags')
            ->from('notes', 'nt')
            ->where('nt.tags_idtags = :idtags')
            ->setParameter(':idtags', $tagsIds, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'idtags') : [];
    }

    /**
     * Remove linked tags.
     *
     * @param int $noteId note Id
     *
     * @return boolean Result
     */
    protected function removeLinkedTags($noteId)
    {
        return $this->db->delete('notes', ['id_notes' => $noteId]);
    }

    /**
     * Add linked tags.
     *
     * @param int $noteId note Id
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
            'n.id_notes',
            'n.content',
            'n.tags_idtags',
            'n.login_data_id_login_data	'
        )->from('notes', 'n');
    }

    /**
     * Find linked tags.
     *
     * @param int $noteId Note Id
     *
     * @return array Result
     */
    public function findLinkedTags($noteId)
    {
        $tagsIds = $this->findLinkedTagsIds($noteId);

        return is_array($tagsIds)
            ? $this->tagRepository->findById($tagsIds)
            : [];
    }
}