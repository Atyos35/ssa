<?php

namespace App\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['user:read']]),
        new Post(denormalizationContext: ['groups' => ['user:write']]),
        new Patch(denormalizationContext: ['groups' => ['user:write']]),
    ]
)]
#[ORM\Entity]
#[ORM\Table(name: '`user`')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'dtype', type: 'string')]
#[ORM\DiscriminatorMap(['user' => User::class, 'agent' => Agent::class])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Identifiant unique de l'utilisateur (UUID)
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['user:read', 'agent:read:collection', 'agent:read:item'])]
    private ?Uuid $id = null;

    /**
     * Prénom de l'utilisateur
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Groups(['user:read', 'user:write', 'agent:read:item', 'agent:write'])]
    private string $firstName;

    /**
     * Nom de famille de l'utilisateur
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Groups(['user:read', 'user:write', 'agent:read:item', 'agent:write'])]
    private string $lastName;

    /**
     * Rôles de l'utilisateur
     */
    #[ORM\Column(type: 'json')]
    #[Assert\NotNull]
    #[Groups(['user:read', 'user:write', 'agent:read:collection', 'agent:read:item'])]
    private array $roles = [];

    /**
     * Adresse email de l'utilisateur
     */
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write', 'agent:read:item'])]
    private string $email;

    /**
     * Mot de passe de l'utilisateur (hashé)
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 255)]
    #[Assert\Regex(
        pattern: '/^.{12,}$/',
        message: 'Le mot de passe doit contenir au minimum 12 caractères.'
    )]
    #[Assert\Regex(
        pattern: '/[a-z].*[a-z]/',
        message: 'Le mot de passe doit contenir au moins 2 minuscules.'
    )]
    #[Assert\Regex(
        pattern: '/[A-Z].*[A-Z]/',
        message: 'Le mot de passe doit contenir au moins 2 majuscules.'
    )]
    #[Assert\Regex(
        pattern: '/[0-9].*[0-9]/',
        message: 'Le mot de passe doit contenir au moins 2 chiffres.'
    )]
    #[Assert\Regex(
        pattern: '/[^a-zA-Z0-9].*[^a-zA-Z0-9]/',
        message: 'Le mot de passe doit contenir au moins 2 caractères spéciaux.'
    )]
    #[Groups(['user:write'])]
    private string $password;

    /**
     * Token de vérification d'email
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $emailVerificationToken = null;

    /**
     * Date d'expiration du token de vérification
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailVerificationExpiresAt = null;

    /**
     * Email vérifié ou non
     */
    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read'])]
    private bool $emailVerified = false;

    public function __construct()
    {
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données temporaires sensibles sur l'utilisateur, effacez-les ici
        // $this->plainPassword = null;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $token): self
    {
        $this->emailVerificationToken = $token;
        return $this;
    }

    public function getEmailVerificationExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationExpiresAt;
    }

    public function setEmailVerificationExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->emailVerificationExpiresAt = $expiresAt;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $verified): self
    {
        $this->emailVerified = $verified;
        return $this;
    }
} 
