<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Enum\PrioriteIncident;
use App\Enum\StatutIncident;
use App\Repository\IncidentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: IncidentRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('VIEW', object)"),
        new Post(security: "is_granted('ROLE_USER')")
    ],
    normalizationContext: ['groups' => ['incident:read']],
    denormalizationContext: ['groups' => ['incident:write']]
)]
class Incident
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['incident:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['incident:read', 'incident:write'])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['incident:read', 'incident:write'])]
    private ?string $description = null;

    #[ORM\Column(enumType: PrioriteIncident::class)]
    #[Groups(['incident:read', 'incident:write'])]
    private PrioriteIncident $priorite = PrioriteIncident::NORMAL;

    #[ORM\Column(enumType: StatutIncident::class)]
    #[Groups(['incident:read'])]
    private StatutIncident $statut = StatutIncident::DECLARE;

    #[ORM\ManyToOne(inversedBy: 'incidents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['incident:read', 'incident:write'])]
    private ?Bien $bien = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['incident:read'])]
    private ?User $declarant = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['incident:read'])]
    private ?\DateTimeImmutable $dateDeclaration = null;

    #[ORM\OneToMany(mappedBy: 'incident', targetEntity: Devis::class)]
    #[Groups(['incident:read'])]
    private Collection $devis;

    public function __construct()
    {
        $this->dateDeclaration = new \DateTimeImmutable();
        $this->devis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriorite(): ?PrioriteIncident
    {
        return $this->priorite;
    }

    public function setPriorite(PrioriteIncident $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getStatut(): ?StatutIncident
    {
        return $this->statut;
    }

    public function setStatut(StatutIncident $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getBien(): ?Bien
    {
        return $this->bien;
    }

    public function setBien(?Bien $bien): static
    {
        $this->bien = $bien;

        return $this;
    }

    public function getDeclarant(): ?User
    {
        return $this->declarant;
    }

    public function setDeclarant(?User $declarant): static
    {
        $this->declarant = $declarant;

        return $this;
    }

    public function getDateDeclaration(): ?\DateTimeImmutable
    {
        return $this->dateDeclaration;
    }

    public function setDateDeclaration(\DateTimeImmutable $dateDeclaration): static
    {
        $this->dateDeclaration = $dateDeclaration;

        return $this;
    }

    /**
     * @return Collection<int, Devis>
     */
    public function getDevis(): Collection
    {
        return $this->devis;
    }

    public function addDevi(Devis $devi): static
    {
        if (!$this->devis->contains($devi)) {
            $this->devis->add($devi);
            $devi->setIncident($this);
        }

        return $this;
    }

    public function removeDevi(Devis $devi): static
    {
        if ($this->devis->removeElement($devi)) {
            // set the owning side to null (unless already changed)
            if ($devi->getIncident() === $this) {
                $devi->setIncident(null);
            }
        }

        return $this;
    }
}
