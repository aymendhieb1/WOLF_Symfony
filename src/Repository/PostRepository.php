<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Survey;
use App\Entity\Announcement;
use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function save(Post $post, bool $flush = true): void
    {
        $this->getEntityManager()->persist($post);
        
        if ($flush) {
            $this->getEntityManager()->flush();
            $this->incrementPostCount($post->getForumId());
        }
    }

    public function remove(Post $post, bool $flush = true): void
    {
        $forumId = $post->getForumId();
        $this->getEntityManager()->remove($post);
        
        if ($flush) {
            $this->getEntityManager()->flush();
            $this->decrementPostCount($forumId);
        }
    }

    public function findAllPosts(): array
    {
        return $this->createQueryBuilder('p')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    private function incrementPostCount(int $forumId): void
    {
        $forum = $this->getEntityManager()->getRepository(Forum::class)->find($forumId);
        if ($forum) {
            $forum->setPostCount($forum->getPostCount() + 1);
            $this->getEntityManager()->flush();
        }
    }

    private function decrementPostCount(int $forumId): void
    {
        $forum = $this->getEntityManager()->getRepository(Forum::class)->find($forumId);
        if ($forum) {
            $forum->setPostCount($forum->getPostCount() - 1);
            $this->getEntityManager()->flush();
        }
    }

    public function findByForumAndSearch(?int $forumId, ?string $search, ?int $userId)
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.forumId', 'f') // Note: using 'forumId' as the association name
            ->where('f.is_private = false');
    
        if ($userId !== null) {
            $qb->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.is_private', true),
                    $qb->expr()->like('f.list_members', ':userPattern')
                )
            )->setParameter('userPattern', '%,' . $userId . ',%');
        }
    
        if ($forumId) {
            $qb->andWhere('p.forumId = :forumId') // Using forumId directly
               ->setParameter('forumId', $forumId);
        }
    
        if ($search) {
            $qb->andWhere('p.title LIKE :search OR p.content LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
    
        return $qb->orderBy('p.dateCreation', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function findWithFilters(?int $forumId = null, ?string $search = null, ?string $type = null, ?int $userId = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.forum', 'f')
            ->leftJoin('f.members', 'm')
            ->addOrderBy('p.dateCreation', 'DESC');

        if ($forumId) {
            $qb->andWhere('p.forum = :forumId')
               ->setParameter('forumId', $forumId);
        }

        if ($search) {
            $qb->andWhere('p.title LIKE :search OR p.content LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        // If user is not admin, only show posts from forums they have access to
        if ($userId) {
            $qb->andWhere('f.isPrivate = false OR m.id = :userId')
               ->setParameter('userId', $userId);
        }

        return $qb->getQuery()->getResult();
    }
}