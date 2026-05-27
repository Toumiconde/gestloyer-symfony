<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProprietaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProprietaireRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['proprietaire:read']],
    denormalizationContext: ['groups' => ['proprietaire:write']]
)]
class Proprietaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['proprietaire:read', 'bien:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['proprietaire:read', 'proprietaire:write', 'bien:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['proprietaire:read', 'proprietaire:write', 'bien:read'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['proprietaire:read', 'proprietaire:write'])]
    private ?string $telephone = null;

    #[ORM\OneToOne(inversedBy: 'proprietaire', targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['proprietaire:read', 'proprietaire:write'])]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'proprietaire', targetEntity: Bien::class)]
    #[Groups(['proprietaire:read'])]
    private Collection $biens;

    #[ORM\OneToMany(mappedBy: 'proprietaire', targetEntity: Reversement::class)]
    private Collection $reversements;

    public function __construct()
    {
        $this->biens = new ArrayCollection();
        $this->reversements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Bien>
     */
    public function getBiens(): Collection
    {
        return $this->biens;
    }

    public function addBien(Bien $bien): static
    {
        if (!$this->biens->contains($bien)) {
            $this->biens->add($bien);
            $bien->setProprietaire($this);
        }

        return $this;
    }

    public function removeBien(Bien $bien): static
    {
        if ($this->biens->removeElement($bien)) {
            // set the owning side to null (unless already changed)
            if ($bien->getProprietaire() === $this) {
                $bien->setProprietaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reversement>
     */
    public function getReversements(): Collection
    {
        return $this->reversements;
    }

    public function addReversement(Reversement $reversement): static
    {
        if (!$this->reversements->contains($reversement)) {
            $this->reversements->add($reversement);
            $reversement->setProprietaire($this);
        }

        return $this;
    }

    public function removeReversement(Reversement $reversement): static
    {
        if ($this->reversements->removeElement($reversement)) {
            // set the owning side to null (unless already changed)
            if ($reversement->getProprietaire() === $this) {
                $reversement->setProprietaire(null);
            }
        }

        return $this;
    }
}
