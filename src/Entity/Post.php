<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\PostRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    public function __construct()
    {
        $this->postId = 0; // Initialize postId
        $this->votes = 0;
        $this->dateCreation = new \DateTime();
        $this->dateModification = new \DateTime();
        $this->cheminFichier = '';
        $this->status = 'active';
        $this->surveyQuestion = '';
        $this->surveyTags = '';
        $this->surveyUserList = '';
        $this->announcementTitle = '';
        $this->announcementContent = '';
        $this->announcementTags = '';
        $this->commentContent = '';
        $this->nbrSignal = 0;
        $this->upVoteList = '';
        $this->downVoteList = '';
        $this->signalList = '';
        $this->choixs = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->type = 'announcement';
    }
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "post_id", type: "integer")]
    private int $postId;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "posts")]
    #[ORM\JoinColumn(name: "id_user", referencedColumnName: "id_user", onDelete: "CASCADE")]
    private User $idUser;

    #[ORM\ManyToOne(targetEntity: Forum::class, inversedBy: "posts")]
    #[ORM\JoinColumn(name: "forum_id", referencedColumnName: "forum_id", onDelete: "CASCADE")]
    private Forum $forumId;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $votes = 0;

    #[ORM\Column(name: "date_creation", type: "datetime", nullable: true, columnDefinition: "TIMESTAMP DEFAULT CURRENT_TIMESTAMP")]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: "date_modification", type: "datetime", nullable: true, columnDefinition: "TIMESTAMP DEFAULT CURRENT_TIMESTAMP")]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(name: "chemin_fichier", type: "text", nullable: true)]
    private ?string $cheminFichier = null;

    #[ORM\Column(type: "string", columnDefinition: "ENUM('survey', 'announcement', 'comment')")]
    private string $type;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $status = 'active';

    #[ORM\Column(name: "survey_question", type: "text", nullable: true)]
    #[Assert\Length(
        max: 200,
        maxMessage: "La question ne doit pas dépasser {{ limit }} caractères"
    )]
    private ?string $surveyQuestion = null;

    #[ORM\Column(name: "survey_tags", type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: "Les tags ne doivent pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9,\s]+$/",
        message: "Les tags ne doivent contenir que des lettres, chiffres et virgules"
    )]
    private ?string $surveyTags = null;

    #[ORM\Column(name: "survey_user_list", type: "text", nullable: true)]
    private ?string $surveyUserList = null;

    #[ORM\Column(name: "announcement_title", type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: "Le titre ne doit pas dépasser {{ limit }} caractères"
    )]
    private ?string $announcementTitle = null;

    #[ORM\Column(name: "announcement_content", type: "text", nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: "Le contenu ne doit pas dépasser {{ limit }} caractères"
    )]
    private ?string $announcementContent = null;

    #[ORM\Column(name: "announcement_tags", type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: "Les tags ne doivent pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9,\s]+$/",
        message: "Les tags ne doivent contenir que des lettres, chiffres et virgules"
    )]
    private ?string $announcementTags;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "posts")]
    #[ORM\JoinColumn(name: "parent_id", referencedColumnName: "post_id", nullable: true, onDelete: "CASCADE")]
    private ?Post $parentId = null;

    #[ORM\Column(name: "comment_content", type: "text", nullable: true)]
    #[Assert\Length(
        max: 200,
        maxMessage: "Le commentaire ne doit pas dépasser {{ limit }} caractères"
    )]
    private ?string $commentContent = null;

    #[ORM\Column(name: "nbr_signal", type: "integer", options: ["default" => 0])]
    private int $nbrSignal = 0;

    #[ORM\Column(name: "UpVoteList", type: "text", nullable: true)]
    private ?string $upVoteList = null;

    #[ORM\Column(name: "downVoteList", type: "text", nullable: true)]
    private ?string $downVoteList = null;

    #[ORM\Column(name: "SingalList", type: "text", nullable: true)]
    private ?string $signalList = null;

    #[ORM\OneToMany(mappedBy: "post", targetEntity: Choix::class)]
    private Collection $choixs;

    #[ORM\OneToMany(mappedBy: "parentId", targetEntity: Post::class)]
    private Collection $posts;

    // Getters and Setters

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function setPostId(int $postId): self
    {
        $this->postId = $postId;
        return $this;
    }

    public function getIdUser(): User
    {
        return $this->idUser;
    }

    public function setIdUser(User $idUser): self
    {
        $this->idUser = $idUser;
        return $this;
    }

    public function getForumId(): Forum
    {
        return $this->forumId;
    }

    public function setForumId(Forum $forumId): self
    {
        $this->forumId = $forumId;
        return $this;
    }

    public function getVotes(): int
    {
        return $this->votes;
    }

    public function setVotes(int $votes): self
    {
        $this->votes = $votes;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function getCheminFichier(): ?string
    {
        return $this->cheminFichier;
    }

    public function setCheminFichier(?string $cheminFichier): self
    {
        $this->cheminFichier = $cheminFichier;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getSurveyQuestion(): ?string
    {
        return $this->surveyQuestion;
    }

    public function setSurveyQuestion(?string $surveyQuestion): self
    {
        $this->surveyQuestion = $surveyQuestion;
        return $this;
    }

    public function getSurveyTags(): ?string
    {
        return $this->surveyTags;
    }

    public function setSurveyTags(?string $surveyTags): self
    {
        $this->surveyTags = $surveyTags;
        return $this;
    }

    public function getSurveyUserList(): ?string
    {
        return $this->surveyUserList;
    }

    public function setSurveyUserList(?string $surveyUserList): self
    {
        $this->surveyUserList = $surveyUserList;
        return $this;
    }

    public function getAnnouncementTitle(): ?string
    {
        return $this->announcementTitle;
    }

    public function setAnnouncementTitle(?string $announcementTitle): self
    {
        $this->announcementTitle = $announcementTitle;
        return $this;
    }

    public function getAnnouncementContent(): ?string
    {
        return $this->announcementContent;
    }

    public function setAnnouncementContent(?string $announcementContent): self
    {
        $this->announcementContent = $announcementContent;
        return $this;
    }

    public function getAnnouncementTags(): ?string
    {
        return $this->announcementTags;
    }

    public function setAnnouncementTags(?string $announcementTags): self
    {
        $this->announcementTags = $announcementTags;
        return $this;
    }

    public function getParentId(): ?self
    {
        return $this->parentId;
    }

    public function setParentId(?self $parentId): self
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getCommentContent(): ?string
    {
        return $this->commentContent;
    }

    public function setCommentContent(?string $commentContent): self
    {
        $this->commentContent = $commentContent;
        return $this;
    }

    public function getNbrSignal(): int
    {
        return $this->nbrSignal;
    }

    public function setNbrSignal(int $nbrSignal): self
    {
        $this->nbrSignal = $nbrSignal;
        return $this;
    }

    public function getUpVoteList(): ?string
    {
        return $this->upVoteList;
    }

    public function setUpVoteList(?string $upVoteList): self
    {
        $this->upVoteList = $upVoteList;
        return $this;
    }

    public function getDownVoteList(): ?string
    {
        return $this->downVoteList;
    }

    public function setDownVoteList(?string $downVoteList): self
    {
        $this->downVoteList = $downVoteList;
        return $this;
    }

    public function getSignalList(): ?string
    {
        return $this->signalList;
    }

    public function setSignalList(?string $signalList): self
    {
        $this->signalList = $signalList;
        return $this;
    }

    public function getChoixs(): Collection
    {
        return $this->choixs;
    }

    public function addChoix(Choix $choix): self
    {
        if (!$this->choixs->contains($choix)) {
            $this->choixs[] = $choix;
            $choix->setPost($this);
        }
        return $this;
    }

    public function removeChoix(Choix $choix): self
    {
        if ($this->choixs->removeElement($choix)) {
            // set the owning side to null (unless already changed)
            if ($choix->getPost() === $this) {
                $choix->setPost(null);
            }
        }
        return $this;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(self $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setParentId($this);
        }
        return $this;
    }

    public function removePost(self $post): self
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getParentId() === $this) {
                $post->setParentId(null);
            }
        }
        return $this;
    }
}