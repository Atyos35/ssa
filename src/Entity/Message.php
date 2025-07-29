<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    description: "Message interne SSA.",
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']],
    operations: [
        new GetCollection(
            description: "Liste des messages."
        ),
        new Get(
            description: "Détail d’un message."
        ),
        new Post(
            description: "Créer un nouveau message."
        ),
    ]
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
    private ?int $id = null;

    /**
     * Titre du message
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['message:read', 'agent:read:item'])]
    private string $title;

    /**
     * Corps du message
     */
    #[ORM\Column(type: 'string', length: 1000)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 1000)]
    #[Groups(['message:read', 'agent:read:item'])]
    private string $body;

    /**
     * Destinataire du message (agent)
     */
    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read', 'agent:read:item'])]
    #[MaxDepth(1)]
    private ?Agent $recipient = null;

    /**
     * Auteur du message (agent)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read', 'agent:read:item'])]
    #[MaxDepth(1)]
    private ?Agent $by = null;

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