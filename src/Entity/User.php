<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Ya existe una cuenta con ese email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $steamId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $steamName = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $steamAvatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $steamProfileUrl = null;

    /**
     * @var Collection<int, Lobby>
     */
    #[ORM\OneToMany(targetEntity: Lobby::class, mappedBy: 'relation')]
    private Collection $lobbies;

    public function __construct()
    {
        $this->lobbies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getSteamId(): ?string
    {
        return $this->steamId;
    }

    public function setSteamId(?string $steamId): static
    {
        $this->steamId = $steamId;

        return $this;
    }

    public function getSteamName(): ?string
    {
        return $this->steamName;
    }

    public function setSteamName(?string $steamName): static
    {
        $this->steamName = $steamName;

        return $this;
    }

    public function getSteamAvatar(): ?string
    {
        return $this->steamAvatar;
    }

    public function setSteamAvatar(?string $steamAvatar): static
    {
        $this->steamAvatar = $steamAvatar;

        return $this;
    }

    public function getSteamProfileUrl(): ?string
    {
        return $this->steamProfileUrl;
    }

    public function setSteamProfileUrl(?string $steamProfileUrl): static
    {
        $this->steamProfileUrl = $steamProfileUrl;

        return $this;
    }

    public function isSteamUser(): bool
    {
        return !empty($this->steamId);
    }

    public function getDisplayName(): string
    {
        if ($this->isSteamUser() && $this->steamName) {
            return $this->steamName;
        }
        
        return $this->email ?? 'Unknown User';
    }

    /**
     * @return Collection<int, Lobby>
     */
    public function getLobbies(): Collection
    {
        return $this->lobbies;
    }

    public function addLobby(Lobby $lobby): static
    {
        if (!$this->lobbies->contains($lobby)) {
            $this->lobbies->add($lobby);
            $lobby->setRelation($this);
        }

        return $this;
    }

    public function removeLobby(Lobby $lobby): static
    {
        if ($this->lobbies->removeElement($lobby)) {
            // set the owning side to null (unless already changed)
            if ($lobby->getRelation() === $this) {
                $lobby->setRelation(null);
            }
        }

        return $this;
    }
}
