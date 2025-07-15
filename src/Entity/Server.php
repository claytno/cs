<?php

namespace App\Entity;

use App\Repository\ServerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
class Server
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'O nome do servidor é obrigatório')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'O nome deve ter pelo menos {{ limit }} caracteres', maxMessage: 'O nome não pode ter mais de {{ limit }} caracteres')]
    private ?string $name = null;

    #[ORM\Column(length: 45)]
    #[Assert\NotBlank(message: 'O IP do servidor é obrigatório')]
    #[Assert\Ip(message: 'Por favor, insira um endereço IP válido')]
    private ?string $ip = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'A porta do servidor é obrigatória')]
    #[Assert\Range(min: 1, max: 65535, notInRangeMessage: 'A porta deve estar entre {{ min }} e {{ max }}')]
    private ?int $port = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'A senha RCON é obrigatória')]
    private ?string $rcon_pass = null;

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

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(int $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function getRconPass(): ?string
    {
        return $this->rcon_pass;
    }

    public function setRconPass(string $rcon_pass): static
    {
        $this->rcon_pass = $rcon_pass;

        return $this;
    }

    /**
     * Retorna o endereço completo do servidor (IP:Porta)
     */
    public function getFullAddress(): string
    {
        return $this->ip . ':' . $this->port;
    }

    /**
     * Verifica se o servidor está configurado completamente
     */
    public function isConfigured(): bool
    {
        return !empty($this->name) && 
               !empty($this->ip) && 
               !empty($this->port) && 
               !empty($this->rcon_pass);
    }

    /**
     * Representação em string do servidor
     */
    public function __toString(): string
    {
        return $this->name . ' (' . $this->getFullAddress() . ')';
    }
}
