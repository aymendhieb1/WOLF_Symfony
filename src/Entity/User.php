<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_user = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $mail = null;

    #[ORM\Column(length: 255)]
    private ?int $num_tel = null;

    #[ORM\Column(length: 255)]
    private ?string $mdp = null;

    #[ORM\Column(type: "integer")]
    private ?int $status = null;

    #[ORM\Column(length: 255)]
    private ?string $photo_profil = null;
    #[ORM\Column(length: 255)]
    private ?int $role = null;
    public function getRole(): ?int
    {
        return $this->role;
    }

    public function setRole(int $role): static
    {
        $this->role = $role;
        return $this;
    }


    public function getId(): ?int
    {
        return $this->id_user;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function getNumTel(): ?int
    {
        return $this->num_tel;
    }

    public function setNumTel(int $num_tel): static
    {
        $this->num_tel = $num_tel;

        return $this;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): static
    {
        $this->mdp = $mdp;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photo_profil;
    }

    public function setPhotoProfil(string $photo_profil): static
    {
        $this->photo_profil = $photo_profil;

        return $this;
    }
    public function getUserIdentifier(): string
    {
        return (string) $this->mail;
    }

    /**
     * Retourne les rôles de l'utilisateur sous forme de tableau.
     */
    public function getRoles(): array
    {
        if ($this->role === 0) {
            return ['ROLE_ADMIN'];
        }

        return ['ROLE_CLIENT'];
    }



    /**
     * Retourne le mot de passe haché de l'utilisateur.
     */
    public function getPassword(): string
    {
        return $this->mdp;
    }

    /**
     * Si vous stockez des données sensibles temporairement, vous pouvez les effacer ici.
     */
    public function eraseCredentials(): void
    {
        // Par exemple, si vous aviez un champ "plainPassword", vous pourriez le vider ici.
    }
    public function getUsername(): string
    {
        // Retourne l'identifiant unique de l'utilisateur (ici, on utilise l'email)
        return $this->mail;
    }

    public function getSalt(): ?string
    {
        // Avec bcrypt ou sodium, le sel est géré automatiquement
        return null;
    }
}
