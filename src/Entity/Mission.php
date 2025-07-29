<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;

#[ApiResource(
    description: "Mission secrète.",
    operations: [
        new GetCollection(
            description: "Liste des missions et leurs résultats."
        ),
        new Get(
            description: "Détail d’une mission (agents, pays, résultat, etc.)."
        ),
        new Post(
            description: "Créer une nouvelle mission. Les agents doivent être infiltrés dans le pays pour pouvoir participer."
        ),
        new Patch(
            description: "Clôturer une mission et remplir le résultat."
        ),
    ]
)]
#[ORM\Entity]
class Mission
{
    /**
     * Identifiant unique de la mission
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Nom de la mission
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Groups(['mission:read:collection', 'mission:read:item', 'mission:write'])]
    private string $name;

    /**
     * Niveau de danger de la mission
     */
    #[ORM\Column(enumType: DangerLevel::class)]
    #[Assert\NotNull]
    #[Groups(['mission:read:item', 'mission:write'])]
    private DangerLevel $danger;

    /**
     * Statut de la mission
     */
    #[ORM\Column(enumType: MissionStatus::class)]
    #[Assert\NotNull]
    #[Groups(['mission:read:item', 'mission:write'])]
    private MissionStatus $status;

    /**
     * Description de la mission
     */
    #[ORM\Column(type: 'string', length: 500)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    #[Groups(['mission:read:item', 'mission:write'])]
    private string $description;

    /**
     * Objectifs de la mission
     */
    #[ORM\Column(type: 'string', length: 500)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    #[Groups(['mission:read:item', 'mission:write'])]
    private string $objectives;

    /**
     * Date de début de la mission
     */
    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    #[Groups(['mission:read:item', 'mission:write'])]
    private \DateTimeImmutable $startDate;

    /**
     * Date de fin de la mission
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['mission:read:item', 'mission:write'])]
    private ?\DateTimeImmutable $endDate = null;

    /**
     * Agents participant à la mission
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'missions')]
    #[Groups(['mission:read:item'])]
    #[MaxDepth(1)]
    private Collection $agents;

    /**
     * Résultat final de la mission
     */
    #[ORM\OneToOne(mappedBy: 'mission', targetEntity: MissionResult::class, cascade: ['persist', 'remove'])]
    #[Groups(['mission:read:item'])]
    #[MaxDepth(1)]
    private ?MissionResult $missionResult = null;

    /**
     * Pays où se déroule la mission
     */
    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'missions')]
    #[Assert\NotNull]
    #[Groups(['mission:read:item', 'mission:write'])]
    #[MaxDepth(1)]
    private ?Country $country = null;

    /**
     * Agent actuellement en mission
     */
    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'missions')]
    private ?Agent $currentAgent = null;

    public function __construct()
    {
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

    public function getDanger(): DangerLevel
    {
        return $this->danger;
    }

    public function setDanger(DangerLevel $danger): self
    {
        $this->danger = $danger;
        return $this;
    }

    public function getStatus(): MissionStatus
    {
        return $this->status;
    }

    public function setStatus(MissionStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getObjectives(): string
    {
        return $this->objectives;
    }

    public function setObjectives(string $objectives): self
    {
        $this->objectives = $objectives;
        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAgents(): Collection
    {
        return $this->agents;
    }

    public function addAgent(Agent $agent): self
    {
        if ($agent->getInfiltratedCountry() !== $this->getCountry()) {
            throw new \DomainException("L'agent ne peut pas participer à cette mission car il n'est pas infiltré dans le pays de la mission.");
        }
        if (!$this->agents->contains($agent)) {
            $this->agents[] = $agent;
        }
        return $this;
    }

    public function removeAgent(Agent $agent): self
    {
        if ($this->agents->contains($agent)) {
            $this->agents->removeElement($agent);
        }
        return $this;
    }

    public function getMissionResult(): ?MissionResult
    {
        return $this->missionResult;
    }

    public function setMissionResult(?MissionResult $missionResult): self
    {
        $this->missionResult = $missionResult;
        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;
        return $this;
    }
} 