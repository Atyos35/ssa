<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

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
    #[Groups(['agent:read:collection', 'agent:read:item', 'agent:write'])]
    private int $yearsOfExperience;

    /**
     * Statut de l'agent
     */
    #[ORM\Column(enumType: AgentStatus::class)]
    #[Assert\NotNull]
    #[Groups(['agent:read:collection', 'agent:read:item', 'agent:write'])]
    private AgentStatus $status;

    /**
     * Date d'enrôlement de l'agent
     */
    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    #[Groups(['agent:read:collection', 'agent:read:item', 'agent:write'])]
    private \DateTimeImmutable $enrolementDate;

    /**
     * Messages reçus par l'agent (destinataire)
     */
    #[ORM\OneToMany(mappedBy: 'recipient', targetEntity: Message::class, cascade: ['remove'])]
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
    #[Groups(['agent:read:collection', 'agent:read:item', 'agent:write'])]
    #[MaxDepth(1)]
    private ?Country $infiltratedCountry = null;

    /**
     * Mentor de l'agent (autre agent)
     */
    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['agent:read:item', 'agent:write'])]
    #[MaxDepth(1)]
    private ?Agent $mentor = null;

    public function __construct()
    {
        parent::__construct();
        $this->messages = new ArrayCollection();
        $this->missions = new ArrayCollection();
    }

    public function getFirstName(): string
    {
        return parent::getFirstName();
    }

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

    public function getMentor(): ?self
    {
        return $this->mentor;
    }

    public function setMentor(?self $mentor): self
    {
        $this->mentor = $mentor;
        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setRecipient($this);
        }
        return $this;
    }

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
