<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource]
#[ORM\Entity]
class Country
{
    /**
     * Identifiant unique du pays
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Nom du pays
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $name;

    /**
     * Niveau de danger du pays
     */
    #[ORM\Column(enumType: DangerLevel::class)]
    #[Assert\NotNull]
    private DangerLevel $danger;

    /**
     * Nombre d'agents dans le pays
     */
    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $numberOfAgents;

    /**
     * Chef de cellule du pays (agent)
     */
    #[ORM\OneToOne(targetEntity: User::class)]
    #[Assert\NotNull]
    private ?Agent $cellLeader = null;

    /**
     * Missions se déroulant dans ce pays
     */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Mission::class)]
    private Collection $missions;

    /**
     * Agents infiltrés dans ce pays
     */
    #[ORM\OneToMany(mappedBy: 'infiltratedCountry', targetEntity: Agent::class)]
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

    public function getDanger(): DangerLevel
    {
        return $this->danger;
    }

    public function setDanger(DangerLevel $danger): self
    {
        $this->danger = $danger;
        return $this;
    }

    public function getNumberOfAgents(): int
    {
        return $this->numberOfAgents;
    }

    public function setNumberOfAgents(int $number): self
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

    /**
     * Retourne le plus haut niveau de danger des missions actives dans ce pays
     */
    public function getMaxLevelDanger(): ?DangerLevel
    {
        $dangerLevels = [];
        foreach (
            $this->getMissions() as $mission) {
            if ($mission->getStatus() === MissionStatus::Success) {
                continue;
            }
            $dangerLevels[] = $mission->getDanger();
        }
        if (empty($dangerLevels)) {
            return null;
        }
        
        $order = [
            DangerLevel::Low => 1,
            DangerLevel::Medium => 2,
            DangerLevel::High => 3,
            DangerLevel::Critical => 4,
        ];
        usort($dangerLevels, function($a, $b) use ($order) {
            return $order[$b->value] <=> $order[$a->value];
        });
        return $dangerLevels[0];
    }
} 