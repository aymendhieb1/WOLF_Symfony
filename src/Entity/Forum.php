<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ForumRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ForumRepository::class)]
class Forum
{
    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->date_creation = new \DateTime();
        $this->post_count = 0;
        $this->nbr_members = 0;
        $this->is_private = false;
        $this->list_members = '';
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "forum_id", type: "integer")]
    private ?int $forum_id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "created_by", referencedColumnName: "id_user", onDelete: "CASCADE")]
    private ?User $createdBy = null;

    #[Assert\NotBlank(message: "Le nom ne peut pas être vide")]
    #[Assert\Length(
        max: 20,
        maxMessage: "Le nom ne doit pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z ]+$/",
        message: "Le nom ne doit contenir que des lettres et des espaces"
    )]
    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "integer")]
    private int $post_count;

    #[ORM\Column(type: "integer")] 
    private int $nbr_members;

    #[Assert\NotBlank(message: "La description ne peut pas être vide")]
    #[Assert\Length(
        max: 100,
        maxMessage: "La description ne doit pas dépasser {{ limit }} caractères"
    )]
    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_creation;

    #[ORM\Column(type: "boolean")]
    private bool $is_private;

    #[Assert\NotBlank(message: "La liste des membres ne peut pas être vide")]
  
    #[ORM\Column(type: "text")]
    private ?string $list_members = null;

    #[ORM\OneToMany(mappedBy: "forum", targetEntity: Post::class)]
    private Collection $posts;

    // Getters and Setters
    public function getForumId(): ?int
    {
        return $this->forum_id;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPostCount(): int
    {
        return $this->post_count;
    }

    public function setPostCount(int $post_count): self
    {
        $this->post_count = $post_count;
        return $this;
    }

    public function getNbrMembers(): int
    {
        return $this->nbr_members;
    }

    public function setNbrMembers(int $nbr_members): self
    {
        $this->nbr_members = $nbr_members;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->is_private;
    }

    public function setIsPrivate(bool $is_private): self
    {
        $this->is_private = $is_private;
        return $this;
    }

    public function getListMembers(): string
    {
        return $this->list_members;
    }

    public function setListMembers(string $list_members): self
    {
        $this->list_members = $list_members;
        return $this;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setForum($this);
        }
        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getForum() === $this) {
                $post->setForum(null);
            }
        }
        return $this;
    }
}