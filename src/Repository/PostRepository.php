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

    public function findByForumAndSearch(?string $forumId, ?string $searchTerm, ?int $userId, string $sortBy = 'date'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.forumId', 'f')
            ->leftJoin('p.idUser', 'u');

        if ($forumId) {
            $qb->andWhere('f.forum_id = :forumId')
               ->setParameter('forumId', (int)$forumId);
        }

        if ($searchTerm) {
            $qb->andWhere('p.announcementTitle LIKE :searchTerm OR p.announcementContent LIKE :searchTerm OR p.surveyQuestion LIKE :searchTerm OR p.surveyTags LIKE :searchTerm OR p.announcementTags LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Add sorting
        switch ($sortBy) {
            case 'votes':
                $qb->orderBy('p.votes', 'DESC');
                break;
            case 'signals':
                $qb->orderBy('p.nbrSignal', 'DESC');
                break;
            default: // date
                $qb->orderBy('p.dateCreation', 'DESC');
        }

        return $qb->getQuery()->getResult();
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