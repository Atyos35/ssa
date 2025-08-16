<?php

namespace App\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    description: "Pays infiltré.",
    operations: [
        new GetCollection(
            description: "Liste des pays.",
            normalizationContext: ['groups' => ['country:list']]
        ),
        new Get(
            description: "Détail d'un pays (niveau de danger, agents, missions, chef de cellule, etc.).",
            normalizationContext: ['groups' => ['country:detail']]
        ),
        new Post(
            description: "Créer un nouveau pays.",
            denormalizationContext: ['groups' => ['country:write']]
        ),
        new Patch(
            description: "Modifier un pays.",
            denormalizationContext: ['groups' => ['country:write']]
        ),
    ]
)]
#[ORM\Entity]
class Country
{
    /**
     * Identifiant unique du pays
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['country:list', 'country:detail'])]
    private ?int $id = null;

    /**
     * Nom du pays
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['country:list', 'country:detail', 'agent:read:item', 'mission:read:item', 'country:write'])]
    private string $name;

    /**
     * Niveau de danger du pays
     */
    #[ORM\Column(enumType: DangerLevel::class, nullable: true)]
    #[Groups(['country:list', 'country:detail', 'agent:read:item', 'mission:read:item', 'country:write'])]
    private ?DangerLevel $danger = null;

    /**
     * Nombre d'agents dans le pays
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['country:list', 'country:detail', 'country:write'])]
    private ?int $numberOfAgents = null;

    /**
     * Chef de cellule du pays (agent)
     */
    #[ORM\OneToOne(targetEntity: User::class)]
    #[Groups(['country:list', 'country:detail', 'country:write'])]
    #[MaxDepth(1)]
    private ?Agent $cellLeader = null;

    /**
     * Missions se déroulant dans ce pays
     */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Mission::class)]
    #[Groups(['country:detail'])]
    #[MaxDepth(1)]
    private Collection $missions;

    /**
     * Agents infiltrés dans ce pays
     */
    #[ORM\OneToMany(mappedBy: 'infiltratedCountry', targetEntity: Agent::class)]
    #[Groups(['country:detail'])]
    #[MaxDepth(1)]
    private Collection $agents;

    public function __construct()
    {
        $this->missions = new ArrayCollection();
        $this->agents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDanger(): ?DangerLevel
    {
        return $this->danger;
    }

    public function setDanger(?DangerLevel $danger): self
    {
        $this->danger = $danger;
        return $this;
    }

    public function getNumberOfAgents(): ?int
    {
        return $this->numberOfAgents;
    }

    public function setNumberOfAgents(?int $number): self
    {
        $this->numberOfAgents = $number;
        return $this;
    }

    public function getCellLeader(): ?Agent
    {
        return $this->cellLeader;
    }

    public function setCellLeader(?Agent $cellLeader): self
    {
        $this->cellLeader = $cellLeader;
        return $this;
    }

    /**
     * @return Collection<int, Mission>
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    /**
     * @return Collection<int, Agent>
     */
    public function getAgents(): Collection
    {
        return $this->agents;
    }
} 
