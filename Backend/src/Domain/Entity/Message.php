<?php

namespace App\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use App\Domain\Entity\User;
use App\Domain\Entity\Agent;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post()
    ],
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']]
)]
#[ORM\Entity]
class Message
{
    /**
     * Identifiant unique du message
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['message:read'])]
    private ?int $id = null;

    /**
     * Titre du message
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['message:read', 'message:write'])]
    private string $title = '';

    /**
     * Corps du message
     */
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 1000)]
    #[Groups(['message:read', 'message:write'])]
    private string $body = '';

    /**
     * Destinataire du message (Agent)
     */
    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read', 'message:write'])]
    private ?Agent $recipient = null;

    /**
     * Expéditeur du message (User)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['message:read', 'message:write'])]
    private ?User $by = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['message:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getBy(): ?User
    {
        return $this->by;
    }

    public function setBy(?User $by): self
    {
        $this->by = $by;
        return $this;
    }

    public function getRecipient(): ?Agent
    {
        return $this->recipient;
    }

    public function setRecipient(?Agent $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }
} 
