<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Enum\UserStatus;
use App\Enum\UserRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 👇 ICI : le rôle est un ENUM UserRole, PAS une string
    #[ORM\Column(enumType: UserRole::class, nullable: true)]
    private ?UserRole $role = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(enumType: UserStatus::class, nullable: true)]
    private ?UserStatus $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $hireDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EmployeeSkill::class)]
    private Collection $skills;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: JobOffer::class)]
    private Collection $createdOffers;

    #[ORM\OneToMany(mappedBy: 'internalApplicant', targetEntity: CandidateProfile::class)]
    private Collection $candidateProfiles;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->createdOffers = new ArrayCollection();
        $this->candidateProfiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getStatus(): ?UserStatus
    {
        return $this->status;
    }

    public function setStatus(?UserStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function getHireDate(): ?\DateTimeInterface
    {
        return $this->hireDate;
    }

    public function setHireDate(?\DateTimeInterface $hireDate): static
    {
        $this->hireDate = $hireDate;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;
        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    // 🚩🚩🚩 Très important : le type est bien ?UserRole
    public function getRole(): ?UserRole
    {
        return $this->role;
    }

    public function setRole(?UserRole $role): self
    {
        $this->role = $role;
        return $this;
    }

    // ✅ Rôles pour Symfony Security
    public function getRoles(): array
    {
        $roles = [];

        if ($this->role !== null) {
            // enum → texte (ADMIN, HR_MANAGER, ...)
            $roles[] = 'ROLE_' . $this->role->value;
        }

        // tout le monde a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }


    // qque modif
    public function getRoleLabel(): string
    {
        return $this->role?->value ?? '';
    }

    public function getStatusLabel(): string
    {
        return $this->status?->value ?? '';
    }


    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, EmployeeSkill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(EmployeeSkill $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
            $skill->setUser($this);
        }

        return $this;
    }

    public function removeSkill(EmployeeSkill $skill): static
    {
        if ($this->skills->removeElement($skill)) {
            if ($skill->getUser() === $this) {
                $skill->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobOffer>
     */
    public function getCreatedOffers(): Collection
    {
        return $this->createdOffers;
    }

    public function addCreatedOffer(JobOffer $jobOffer): static
    {
        if (!$this->createdOffers->contains($jobOffer)) {
            $this->createdOffers->add($jobOffer);
            $jobOffer->setCreator($this);
        }

        return $this;
    }

    public function removeCreatedOffer(JobOffer $jobOffer): static
    {
        if ($this->createdOffers->removeElement($jobOffer)) {
            if ($jobOffer->getCreator() === $this) {
                $jobOffer->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CandidateProfile>
     */
    public function getCandidateProfiles(): Collection
    {
        return $this->candidateProfiles;
    }

    public function addCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if (!$this->candidateProfiles->contains($candidateProfile)) {
            $this->candidateProfiles->add($candidateProfile);
            $candidateProfile->setInternalApplicant($this);
        }

        return $this;
    }

    public function removeCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if ($this->candidateProfiles->removeElement($candidateProfile)) {
            if ($candidateProfile->getInternalApplicant() === $this) {
                $candidateProfile->setInternalApplicant(null);
            }
        }

        return $this;
    }
}
