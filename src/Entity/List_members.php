<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Forum;

#[ORM\Entity]
class List_members
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $list_id;

        #[ORM\ManyToOne(targetEntity: Forum::class, inversedBy: "list_memberss")]
    #[ORM\JoinColumn(name: 'forum_id', referencedColumnName: 'forum_id', onDelete: 'CASCADE')]
    private Forum $forum_id;

    #[ORM\Column(type: "integer")]
    private int $id_user;

    public function getList_id(): int
    {
        return $this->list_id;
    }

    public function setList_id(int $value): self
    {
        $this->list_id = $value;
        return $this;
    }

    public function getForum_id(): int
    {
        return $this->forum_id;
    }

    public function setForum_id(int $value): self
    {
        $this->forum_id = $value;
        return $this;
    }

    public function getId_user(): int
    {
        return $this->id_user;
    }

    public function setId_user(int $value): self
    {
        $this->id_user = $value;
        return $this;
    }
}
