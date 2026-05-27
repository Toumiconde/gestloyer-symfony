<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Enum\StatutPaiement;
use App\Repository\PaiementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_contrat_mois', fields: ['contrat', 'mois'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('VIEW', object)"),
        new Patch(
            uriTemplate: '/paiements/{id}/valider',
            security: "is_granted('ROLE_COMPTABLE')",
            denormalizationContext: ['groups' => ['paiement:valider']]
        )
    ],
    normalizationContext: ['groups' => ['paiement:read']]
)]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['paiement:read', 'versement:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['paiement:read'])]
    private ?Contrat $contrat = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['paiement:read'])]
    private ?\DateTimeImmutable $mois = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['paiement:read'])]
    private ?string $montantDu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['paiement:read'])]
    private string $montantVerse = '0.00';

    #[ORM\Column(enumType: StatutPaiement::class)]
    #[Groups(['paiement:read'])]
    private StatutPaiement $statut = StatutPaiement::EN_ATTENTE;

    #[ORM\OneToMany(mappedBy: 'paiement', targetEntity: Versement::class, cascade: ['persist'])]
    #[Groups(['paiement:read'])]
    private Collection $versements;

    #[ORM\OneToOne(mappedBy: 'paiement', targetEntity: Quittance::class, cascade: ['persist', 'remove'])]
    #[Groups(['paiement:read'])]
    private ?Quittance $quittance = null;

    public function __construct()
    {
        $this->versements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
    }

    public function setContrat(?Contrat $contrat): static
    {
        $this->contrat = $contrat;

        return $this;
    }

    public function getMois(): ?\DateTimeImmutable
    {
        return $this->mois;
    }

    public function setMois(\DateTimeImmutable $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    public function getMontantDu(): ?string
    {
        return $this->montantDu;
    }

    public function setMontantDu(string $montantDu): static
    {
        $this->montantDu = $montantDu;

        return $this;
    }

    public function getMontantVerse(): string
    {
        return $this->montantVerse;
    }

    public function setMontantVerse(string $montantVerse): static
    {
        $this->montantVerse = $montantVerse;

        return $this;
    }

    public function getStatut(): ?StatutPaiement
    {
        return $this->statut;
    }

    public function setStatut(StatutPaiement $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getSolde(): string
    {
        return bcsub($this->montantDu ?? '0.00', $this->montantVerse, 2);
    }

    public function isComplet(): bool
    {
        return bccomp($this->getSolde(), '0', 2) <= 0;
    }

    /**
     * @return Collection<int, Versement>
     */
    public function getVersements(): Collection
    {
        return $this->versements;
    }

    public function addVersement(Versement $versement): static
    {
        if (!$this->versements->contains($versement)) {
            $this->versements->add($versement);
            $versement->setPaiement($this);
            // Auto update montantVerse
            $this->montantVerse = bcadd($this->montantVerse, $versement->getMontant(), 2);
        }

        return $this;
    }

    public function removeVersement(Versement $versement): static
    {
        if ($this->versements->removeElement($versement)) {
            // set the owning side to null (unless already changed)
            if ($versement->getPaiement() === $this) {
                $versement->setPaiement(null);
            }
            // Auto update montantVerse
            $this->montantVerse = bcsub($this->montantVerse, $versement->getMontant(), 2);
        }

        return $this;
    }

    public function getQuittance(): ?Quittance
    {
        return $this->quittance;
    }

    public function setQuittance(Quittance $quittance): static
    {
        // set the owning side of the relation if necessary
        if ($quittance->getPaiement() !== $this) {
            $quittance->setPaiement($this);
        }

        $this->quittance = $quittance;

        return $this;
    }
}
