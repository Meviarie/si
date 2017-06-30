<?php
/**
 * Contacts Repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Utils\Paginator;

/**
 * Class ContactsRepository.
 *
 */
class ContactsRepository
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
            ->select('COUNT(DISTINCT c.id_contacts) AS total_results')
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
        $queryBuilder->where('c.id_contacts = :id_contacts')
            ->setParameter(':id_contacts', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        if ($result) {
            $result['tags'] = $this->findLinkedTags($result['id_contacts']);
        }

        return $result;
    }

    /**
     * Save record.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($contact, $id)
    {
        $this->db->beginTransaction();

        try {
            $tagsIds = isset($contact['tags']) ? array_column($contact['tags'], 'id_contacts') : [];
            //unset($contact['tags']);

            if (isset($contact['id_contacts']) && ctype_digit((string) $contact['id_contacts'])) {
                // update record
                $contactsId = $contact['id_contacts'];
                $tag = $contact['tags_idtags'];
                unset($contact['id_contacts']);
                unset($contact['tags_idtags']);
                $this->removeLinkedTags($contactsId);
                $this->addLinkedTags($tagsIds);
                $this->db->update('contacts', $contact, ['id_contacts' => $contactsId]);
            } else {
                // add new record
                $tag = implode($contact['tags']);
                //dump($tag);
                unset($contact['tags']);
                $this->db->insert('contacts', $contact);
                $contactsId = $this->db->lastInsertId();
                $this->addLinkedTags($tag);
                $idtags = $this->findLinkedTags($tag);

                //$idtag = implode($idtags);
		        dump($idtags);
                $queryBuilder = $this->db->createQueryBuilder();
                $queryBuilder->update('contacts','c')
                    ->set('c.login_data_id_login_data', '?')
                    ->set('c.tags_idtags', '?')
                    ->where('c.id_contacts = ?')
                    ->setParameter(0, $id, \PDO::PARAM_INT)
                    ->setParameter(1, $idtags, \PDO::PARAM_INT)
                    ->setParameter(2, $contactsId, \PDO::PARAM_INT)
                ;
                $queryBuilder->execute();       }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove record.
     *
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return boolean Result
     */
    public function delete($contact)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedTags($contact['id_contacts']);
            $this->db->delete('contacts', ['id_contacts' => $contact['id_contacts']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Finds linked tags Ids.
     *
     * @return array Result
     */
    protected function findLinkedTagsIds($tags)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('t.idtags')
            ->from('tags', 't')
            ->where('t.name = :name')
            ->setParameter(':name', $tags, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();
        return isset($result) ? array_column($result, 'idtags') : [];
    }

    /**
     * Remove linked tags.
     *
     *
     * @return boolean Result
     */
    protected function removeLinkedTags($contactsId)
    {
        return $this->db->delete('contacts', ['id_contacts' => $contactsId]);
    }

    /**
     * Add linked tags.
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
            'c.id_contacts',
            'c.name',
            'c.surname',
            'c.phone_number',
            'c.mail',
            'c.web_page'
        )->from('contacts', 'c');
    }

    /**
     * Find linked tags.
     *
     * @return array Result
     */
    public function findLinkedTags($tagsId)
    {
        $tagsIds = $this->findLinkedTagsIds($tagsId);

        return is_array($tagsIds)
            ? $this->tagRepository->findById($tagsIds)
            : [];
    }

}