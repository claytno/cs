<?php

namespace App\Entity;

use App\Repository\MapVetoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MapVetoRepository::class)]
class MapVeto
{
    public const TYPE_BAN = 'ban';
    public const TYPE_PICK = 'pick';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameMatch::class, inversedBy: 'mapVetos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameMatch $match = null;

    #[ORM\ManyToOne(targetEntity: Lobby::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lobby $lobby = null;

    #[ORM\Column(length: 50)]
    private ?string $mapName = null;

    #[ORM\Column(length: 10)]
    private string $type = self::TYPE_BAN;

    #[ORM\Column]
    private int $round = 1;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters e Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatch(): ?GameMatch
    {
        return $this->match;
    }

    public function setMatch(?GameMatch $match): static
    {
        $this->match = $match;
        return $this;
    }

    public function getLobby(): ?Lobby
    {
        return $this->lobby;
    }

    public function setLobby(?Lobby $lobby): static
    {
        $this->lobby = $lobby;
        return $this;
    }

    public function getMapName(): ?string
    {
        return $this->mapName;
    }

    public function setMapName(string $mapName): static
    {
        $this->mapName = $mapName;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getRound(): int
    {
        return $this->round;
    }

    public function setRound(int $round): static
    {
        $this->round = $round;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
