<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Post;

#[ORM\Entity]
class Choix
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_choix;

        #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "choixs")]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'post_id', onDelete: 'CASCADE')]
    private Post $post_id;

    #[ORM\Column(type: "text")]
    private string $choix;

    #[ORM\Column(type: "float")]
    private float $pourcentage;

    #[ORM\Column(type: "integer")]
    private int $choice_votes_count;

    public function getId_choix(): int
    {
        return $this->id_choix;
    }

    public function setId_choix(int $value): self
    {
        $this->id_choix = $value;
        return $this;
    }

    public function getPost_id(): int
    {
        return $this->post_id;
    }

    public function setPost_id(int $value): self
    {
        $this->post_id = $value;
        return $this;
    }

    public function getChoix(): string
    {
        return $this->choix;
    }

    public function setChoix(string $value): self
    {
        $this->choix = $value;
        return $this;
    }

    public function getPourcentage(): float
    {
        return $this->pourcentage;
    }

    public function setPourcentage(float $value): self
    {
        $this->pourcentage = $value;
        return $this;
    }

    public function getChoice_votes_count(): int
    {
        return $this->choice_votes_count;
    }

    public function setChoice_votes_count(int $value): self
    {
        $this->choice_votes_count = $value;
        return $this;
    }
}
