<?php

namespace App\Entity;

use App\Repository\GameMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameMatchRepository::class)]
#[ORM\Table(name: '`match`')]
class GameMatch
{
    public const STAGE_WAITING = 'waiting';
    public const STAGE_VETO = 'veto';
    public const STAGE_CONNECT = 'connect';
    public const STAGE_LIVE = 'live';
    public const STAGE_FINISHED = 'finished';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Lobby::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lobby $lobbyA = null;

    #[ORM\ManyToOne(targetEntity: Lobby::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lobby $lobbyB = null;

    #[ORM\Column(length: 20)]
    private string $stage = self::STAGE_WAITING;

    #[ORM\Column(nullable: true)]
    private ?bool $lobbyAAccepted = null;

    #[ORM\Column(nullable: true)]
    private ?bool $lobbyBAccepted = null;

    #[ORM\Column(nullable: true, length: 50)]
    private ?string $selectedMap = null;

    #[ORM\Column(nullable: true, length: 255)]
    private ?string $serverInfo = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    /**
     * @var Collection<int, MapVeto>
     */
    #[ORM\OneToMany(targetEntity: MapVeto::class, mappedBy: 'match', cascade: ['persist', 'remove'])]
    private Collection $mapVetos;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->mapVetos = new ArrayCollection();
    }

    // Getters e Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLobbyA(): ?Lobby
    {
        return $this->lobbyA;
    }

    public function setLobbyA(?Lobby $lobbyA): static
    {
        $this->lobbyA = $lobbyA;
        return $this;
    }

    public function getLobbyB(): ?Lobby
    {
        return $this->lobbyB;
    }

    public function setLobbyB(?Lobby $lobbyB): static
    {
        $this->lobbyB = $lobbyB;
        return $this;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function setStage(string $stage): static
    {
        $this->stage = $stage;
        return $this;
    }

    public function isLobbyAAccepted(): ?bool
    {
        return $this->lobbyAAccepted;
    }

    public function setLobbyAAccepted(?bool $lobbyAAccepted): static
    {
        $this->lobbyAAccepted = $lobbyAAccepted;
        return $this;
    }

    public function isLobbyBAccepted(): ?bool
    {
        return $this->lobbyBAccepted;
    }

    public function setLobbyBAccepted(?bool $lobbyBAccepted): static
    {
        $this->lobbyBAccepted = $lobbyBAccepted;
        return $this;
    }

    public function getSelectedMap(): ?string
    {
        return $this->selectedMap;
    }

    public function setSelectedMap(?string $selectedMap): static
    {
        $this->selectedMap = $selectedMap;
        return $this;
    }

    public function getServerInfo(): ?string
    {
        return $this->serverInfo;
    }

    public function setServerInfo(?string $serverInfo): static
    {
        $this->serverInfo = $serverInfo;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    /**
     * @return Collection<int, MapVeto>
     */
    public function getMapVetos(): Collection
    {
        return $this->mapVetos;
    }

    public function addMapVeto(MapVeto $mapVeto): static
    {
        if (!$this->mapVetos->contains($mapVeto)) {
            $this->mapVetos->add($mapVeto);
            $mapVeto->setMatch($this);
        }
        return $this;
    }

    public function removeMapVeto(MapVeto $mapVeto): static
    {
        if ($this->mapVetos->removeElement($mapVeto)) {
            if ($mapVeto->getMatch() === $this) {
                $mapVeto->setMatch(null);
            }
        }
        return $this;
    }

    // Métodos de negócio

    public function bothAccepted(): bool
    {
        return $this->lobbyAAccepted === true && $this->lobbyBAccepted === true;
    }

    public function canStartVeto(): bool
    {
        return $this->stage === self::STAGE_WAITING && $this->bothAccepted();
    }

    public function canConnect(): bool
    {
        return $this->stage === self::STAGE_CONNECT && $this->selectedMap !== null;
    }

    public function getUserLobby(User $user): ?Lobby
    {
        if ($this->lobbyA && $this->lobbyA->hasPlayer($user)) {
            return $this->lobbyA;
        }
        if ($this->lobbyB && $this->lobbyB->hasPlayer($user)) {
            return $this->lobbyB;
        }
        return null;
    }

    public function isUserCaptain(User $user): bool
    {
        $userLobby = $this->getUserLobby($user);
        if (!$userLobby) {
            return false;
        }
        return $userLobby->getLeader() === $user;
    }

    public function getOpponentLobby(Lobby $lobby): ?Lobby
    {
        if ($this->lobbyA === $lobby) {
            return $this->lobbyB;
        }
        if ($this->lobbyB === $lobby) {
            return $this->lobbyA;
        }
        return null;
    }
}
