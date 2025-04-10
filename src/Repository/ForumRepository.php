<?php

namespace App\Repository;

use App\Entity\Forum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forum::class);
    }

    public function addForum(Forum $forum, bool $flush = true): void
    {
        $this->getEntityManager()->persist($forum);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function updateForum(Forum $forum, bool $flush = true): void
    {
        $this->getEntityManager()->persist($forum);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function deleteForumById(int $forumId): int
    {
        return $this->createQueryBuilder('f')
            ->delete()
            ->where('f.forum_id = :id')
            ->setParameter('id', $forumId)
            ->getQuery()
            ->execute();
    } 

    public function removeForum(Forum $forum, bool $flush = true): void
    {
        $this->getEntityManager()->remove($forum);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
    public function findAccessibleForums(?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.is_private = false');
    
        if ($userId !== null) {
            $qb->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.is_private', true),
                    $qb->expr()->like('f.list_members', ':userPattern')
                )
            )->setParameter('userPattern', '%,' . $userId . ',%');
        }
    
        return $qb->getQuery()->getResult();
    }

    public function findForForumIndex(?int $userId = null): array
    {
        return $this->createQueryBuilder('f')
            ->select('f.forum_id', 'f.name', 'f.description', 'f.post_count', 'f.nbr_members')
            ->addSelect('CASE WHEN f.is_private = false THEN true ELSE ' . 
                ($userId !== null ? 
                    'CASE WHEN f.list_members LIKE :userPattern THEN true ELSE false END' : 
                    'false') . 
                ' END AS is_accessible')
            ->setParameter('userPattern', '%,' . $userId . ',%')
            ->orderBy('f.post_count', 'DESC')
            ->getQuery()
            ->getResult();
    }
}