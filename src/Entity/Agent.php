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
use App\Application\AgentDataPersister;

#[ApiResource(
    description: "Agent secret de la SSA.",
    operations: [
        new GetCollection(
            description: "Liste des agents (noms/prénoms non exposés)."
        ),
        new Get(
            description: "Détail d’un agent (missions, messages, mentor, pays, etc.)."
        ),
        new Post(
            description: "Créer un nouvel agent. Un agent doit être infiltré dans un pays pour participer à une mission."
        ),
        new Patch(
            processor: AgentDataPersister::class,
            description: "Modifier un agent. Lors du passage au statut 'Killed in Action', tous les agents sont notifiés par message."
        ),
    ]
)]
#[ORM\Entity]
class Agent extends User
{
    /**
     * Nom de code de l'agent
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    #[Groups(['agent:read:collection', 'agent:read:item', 'agent:write'])]
    private string $codeName;

    /**
     * Années d'expérience de l'agent
     */
    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['agent:read:item', 'agent:write'])]
    private int $yearsOfExperience;

    /**
     * Statut de l'agent
     */
    #[ORM\Column(enumType: AgentStatus::class)]
    #[Assert\NotNull]
    #[Groups(['agent:read:item', 'agent:write'])]
    private AgentStatus $status;

    /**
     * Date d'enrôlement de l'agent
     */
    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    #[Groups(['agent:read:item', 'agent:write'])]
    private \DateTimeImmutable $enrolementDate;

    /**
     * Mission en cours de l'agent
     */
    #[ORM\ManyToOne(targetEntity: Mission::class, inversedBy: 'agents')]
    #[Groups(['agent:read:item', 'agent:write'])]
    #[MaxDepth(1)]
    private ?Mission $currentMission = null;

    /**
     * Messages reçus par l'agent (destinataire)
     */
    #[ORM\OneToMany(mappedBy: 'recipient', targetEntity: Message::class, cascade: ['remove'])]
    private Collection $messages;

    /**
     * Missions auxquelles participe l'agent
     */
    #[ORM\OneToMany(mappedBy: 'currentAgent', targetEntity: Mission::class)]
    private Collection $missions;

    /**
     * Pays infiltré par l'agent
     */
    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'agents')]
    #[Groups(['agent:read:item', 'agent:write', 'mission:write'])]
    #[MaxDepth(1)]
    private ?Country $infiltratedCountry = null;

    /**
     * Mentor de l'agent (autre agent)
     */
    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[Groups(['agent:read:item', 'agent:write'])]
    #[MaxDepth(1)]
    private ?Agent $mentor = null;

    public function __construct()
    {
        parent::__construct();
        $this->messages = new ArrayCollection();
        $this->missions = new ArrayCollection();
    }

    #[Groups(['agent:read:item'])]
    public function getFirstName(): string
    {
        return parent::getFirstName();
    }

    #[Groups(['agent:read:item'])]
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