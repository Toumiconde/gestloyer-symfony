<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Enum\StatutBien;
use App\Enum\TypeBien;
use App\Repository\BienRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BienRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('VIEW', object)"),
        new Post(security: "is_granted('ROLE_GESTIONNAIRE')"),
        new Put(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)")
    ],
    normalizationContext: ['groups' => ['bien:read']],
    denormalizationContext: ['groups' => ['bien:write']],
    paginationItemsPerPage: 20
)]
class Bien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['bien:read', 'contrat:read', 'incident:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['bien:read', 'bien:write', 'contrat:read'])]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['bien:read', 'bien:write'])]
    private ?string $adresse = null;

    #[ORM\Column(enumType: TypeBien::class)]
    #[Groups(['bien:read', 'bien:write'])]
    private TypeBien $type = TypeBien::APPARTEMENT;

    #[ORM\Column(enumType: StatutBien::class)]
    #[Groups(['bien:read', 'bien:write'])]
    private StatutBien $statut = StatutBien::DISPONIBLE;

    #[ORM\ManyToOne(inversedBy: 'biens')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['bien:read', 'bien:write'])]
    private ?Proprietaire $proprietaire = null;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: Contrat::class)]
    #[Groups(['bien:read'])]
    private Collection $contrats;

    #[ORM\OneToMany(mappedBy: 'bien', targetEntity: Incident::class)]
    private Collection $incidents;

    public function __construct()
    {
        $this->contrats = new ArrayCollection();
        $this->incidents = new ArrayCollection();
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getType(): ?TypeBien
    {
        return $this->type;
    }

    public function setType(TypeBien $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatut(): ?StatutBien
    {
        return $this->statut;
    }

    public function setStatut(StatutBien $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getProprietaire(): ?Proprietaire
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?Proprietaire $proprietaire): static
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    /**
     * @return Collection<int, Contrat>
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(Contrat $contrat): static
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats->add($contrat);
            $contrat->setBien($this);
        }

        return $this;
    }

    public function removeContrat(Contrat $contrat): static
    {
        if ($this->contrats->removeElement($contrat)) {
            // set the owning side to null (unless already changed)
            if ($contrat->getBien() === $this) {
                $contrat->setBien(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Incident>
     */
    public function getIncidents(): Collection
    {
        return $this->incidents;
    }

    public function addIncident(Incident $incident): static
    {
        if (!$this->incidents->contains($incident)) {
            $this->incidents->add($incident);
            $incident->setBien($this);
        }

        return $this;
    }

    public function removeIncident(Incident $incident): static
    {
        if ($this->incidents->removeElement($incident)) {
            // set the owning side to null (unless already changed)
            if ($incident->getBien() === $this) {
                $incident->setBien(null);
            }
        }

        return $this;
    }
}
