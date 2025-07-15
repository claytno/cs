<?php

namespace App\Entity;

use App\Repository\LobbyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LobbyRepository::class)]
class Lobby
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'lobbies')]
    private ?User $relation = null;

    #[ORM\Column(nullable: true)]
    private ?bool $matched = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, LobbyPlayer>
     */
    #[ORM\OneToMany(targetEntity: LobbyPlayer::class, mappedBy: 'lobby', cascade: ['persist', 'remove'])]
    private Collection $players;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRelation(): ?User
    {
        return $this->relation;
    }

    public function setRelation(?User $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    public function isMatched(): ?bool
    {
        return $this->matched;
    }

    public function setMatched(?bool $matched): static
    {
        $this->matched = $matched;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, LobbyPlayer>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(LobbyPlayer $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
            $player->setLobby($this);
        }
        return $this;
    }

    public function removePlayer(LobbyPlayer $player): static
    {
        if ($this->players->removeElement($player)) {
            if ($player->getLobby() === $this) {
                $player->setLobby(null);
            }
        }
        return $this;
    }

    public function getPlayerCount(): int
    {
        return $this->players->count();
    }

    public function isFull(): bool
    {
        return $this->getPlayerCount() >= 5;
    }

    public function getLeader(): ?User
    {
        foreach ($this->players as $player) {
            if ($player->isLeader()) {
                return $player->getUser();
            }
        }
        return null;
    }

    public function hasPlayer(User $user): bool
    {
        foreach ($this->players as $player) {
            if ($player->getUser() === $user) {
                return true;
            }
        }
        return false;
    }
}
