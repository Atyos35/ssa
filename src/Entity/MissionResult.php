<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity]
class MissionResult
{
    /**
     * Identifiant unique du résultat
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Statut du résultat de la mission
     */
    #[ORM\Column(enumType: MissionStatus::class)]
    #[Assert\NotNull]
    private MissionStatus $status;

    /**
     * Résumé du résultat
     */
    #[ORM\Column(type: 'string', length: 500)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    private string $summary;

    /**
     * Mission associée à ce résultat
     */
    #[ORM\OneToOne(inversedBy: 'missionResult', targetEntity: Mission::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Mission $mission = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function setMission(?Mission $mission): self
    {
        $this->mission = $mission;
        return $this;
    }
} 