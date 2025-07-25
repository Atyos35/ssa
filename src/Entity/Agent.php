<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource]
#[ORM\Entity]
class Agent extends User
{
    /**
     * Nom de code de l'agent
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    private string $codeName;

    /**
     * Années d'expérience de l'agent
     */
    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $yearsOfExperience;

    /**
     * Statut de l'agent
     */
    #[ORM\Column(enumType: AgentStatus::class)]
    #[Assert\NotNull]
    private AgentStatus $status;

    /**
     * Date d'enrôlement de l'agent
     */
    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    private \DateTimeImmutable $enrolementDate;

    /**
     * Mission en cours de l'agent
     */
    #[ORM\ManyToOne(targetEntity: Mission::class, inversedBy: 'agents')]
    private ?Mission $currentMission = null;

    /**
     * Mentor de l'agent (autre agent)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?Agent $mentor = null;

    /**
     * Messages envoyés par l'agent (composition)
     */
    #[ORM\OneToMany(mappedBy: 'by', targetEntity: Message::class, cascade: ['remove'])]
    private Collection $messages;

    /**
     * Missions auxquelles participe l'agent
     */
    #[ORM\ManyToMany(targetEntity: Mission::class, mappedBy: 'agents')]
    private Collection $missions;

    /**
     * Pays infiltré par l'agent
     */
    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'agents')]
    private ?Country $infiltratedCountry = null;

    public function __construct()
    {
        parent::__construct();
        $this->messages = new ArrayCollection();
        $this->missions = new ArrayCollection();
    }

    #[ApiResource(readable: false, operations: ['get_collection'])]
    public function getFirstName(): string
    {
        return parent::getFirstName();
    }

    #[ApiResource(readable: false, operations: ['get_collection'])]
    public function getLastName(): string
    {
        return parent::getLastName();
    }

    public function getCodeName(): string
    {
        return $this->codeName;
    }

    public function setCodeName(string $codeName): self
    {
        $this->codeName = $codeName;
        return $this;
    }

    public function getYearsOfExperience(): int
    {
        return $this->yearsOfExperience;
    }

    public function setYearsOfExperience(int $years): self
    {
        $this->yearsOfExperience = $years;
        return $this;
    }

    public function getStatus(): AgentStatus
    {
        return $this->status;
    }

    public function setStatus(AgentStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getEnrolementDate(): \DateTimeImmutable
    {
        return $this->enrolementDate;
    }

    public function setEnrolementDate(\DateTimeImmutable $date): self
    {
        $this->enrolementDate = $date;
        return $this;
    }

    public function getCurrentMission(): ?Mission
    {
        return $this->currentMission;
    }

    public function setCurrentMission(?Mission $mission): self
    {
        $this->currentMission = $mission;
        return $this;
    }

    public function getMentor(): ?self
    {
        return $this->mentor;
    }

    public function setMentor(?self $mentor): self
    {
        $this->mentor = $mentor;
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @return Collection<int, Mission>
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function getInfiltratedCountry(): ?Country
    {
        return $this->infiltratedCountry;
    }

    public function setInfiltratedCountry(?Country $country): self
    {
        $this->infiltratedCountry = $country;
        return $this;
    }
} 