<?php
/**
 * Bookmark repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;

/**
 * Class BookmarkRepository.
 *
 * @package Repository
 */
class BookmarkRepository
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
            ->select('COUNT(DISTINCT b.id_bookmarks) AS total_results')
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
        $queryBuilder->where('b.id_bookmarks = :id_bookmarks')
            ->setParameter(':id_bookmarks', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();
	//dump($result);

        if ($result) {
            $result['tags_idtags'] = $this->findLinkedTags($result['tags_idtags']);
        }

        return $result;
    }

    /**
     * Save record.
     *
     * @param array $bookmark Bookmark
     *
     * @throws \Doctrine\DBAL\DBALException
     */  
    public function save($bookmark,  $id)
    {
        $this->db->beginTransaction();

        try {
            // $currentDateTime = new \DateTime();
            //$bookmark['modified_at'] = $currentDateTime->format('Y-m-d H:i:s');
            $tagsIds = isset($bookmark['tags']) ? array_column($bookmark['tags'], 'id_bookmarks') : [];
           //unset($bookmark['tags']);

            if (isset($bookmark['id_bookmarks']) && ctype_digit((string) $bookmark['id_bookmarks'])) {
                // update record
                $bookmarkId = $bookmark['id_bookmarks'];
                unset($bookmark['id_bookmarks']);
                $this->removeLinkedTags($bookmarkId);
                $this->addLinkedTags($bookmarkId, $tagsIds);
                $this->db->update('bookmarks', $bookmark, ['id_bookmarks' => $bookmarkId]);
            } else {
                  // add new record
                
                $tag = $bookmark['tags'];
                $tags = $tag['0'];
                //dump($tags);
                unset($bookmark['tags']);
                $this->db->insert('bookmarks', $bookmark);
                $bookmarkId = $this->db->lastInsertId();
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
                $queryBuilder->update('bookmarks','b')
                    ->set('b.login_data_id_login_data', '?')
                    ->set('b.tags_idtags', '?')
                    ->where('b.id_bookmarks = ?')
                    ->setParameter(0, $id, \PDO::PARAM_INT)
                    ->setParameter(1, $idtags, \PDO::PARAM_INT)
                    ->setParameter(2, $bookmarkId, \PDO::PARAM_INT)
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
     * @param array $bookmark Bookmark
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return boolean Result
     */
    public function delete($bookmark)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedTags($bookmark['id_bookmarks']);
            $this->db->delete('bookmarks', ['id_bookmarks' => $bookmark['id_bookmarks']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Finds linked tags Ids.
     *
     * @param int $bookmarkId Bookmark Id
     *
     * @return array Result
     */
    protected function findLinkedTagsIds($tagsIds)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('bt.tags_idtags')
            ->from('bookmarks', 'bt')
            ->where('bt.tags_idtags = :idtags')
            ->setParameter(':idtags', $tagsIds, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'idtags') : [];
    }


    /**
     * Remove linked tags.
     *
     * @param int $bookmarkId Bookmark Id
     *
     * @return boolean Result
     */
    protected function removeLinkedTags($bookmarkId)
    {
        return $this->db->delete('bookmarks', ['id_bookmarks' => $bookmarkId]);
    }

    /**
     * Add linked tags.
     *
     * @param int $bookmarkId Bookmark Id
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
            'b.id_bookmarks',
            'b.bookmark',
            'b.url',
            'b.tags_idtags',
            'b.login_data_id_login_data	'
        )->from('bookmarks', 'b');
    }

    /**
     * Find linked tags.
     *
     * @param int $bookmarkId Bookmark Id
     *
     * @return array Result
     */
    public function findLinkedTags($bookmarkId)
    {
        $tagsIds = $this->findLinkedTagsIds($bookmarkId);

        return is_array($tagsIds)
            ? $this->tagRepository->findById($tagsIds)
            : [];
    }
}