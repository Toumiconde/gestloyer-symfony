<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\StatutContrat;
use App\Repository\ContratRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContratRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('VIEW', object)")
    ],
    normalizationContext: ['groups' => ['contrat:read']],
    denormalizationContext: ['groups' => ['contrat:write']]
)]
class Contrat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contrat:read', 'paiement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['contrat:read'])]
    private ?string $numero = null;

    #[ORM\ManyToOne(inversedBy: 'contrats')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contrat:read', 'contrat:write'])]
    private ?Bien $bien = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contrat:read', 'contrat:write'])]
    private ?User $locataire = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['contrat:read', 'contrat:write'])]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['contrat:read', 'contrat:write'])]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['contrat:read', 'contrat:write'])]
    private ?string $loyerMensuel = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['contrat:read', 'contrat:write'])]
    private ?string $caution = null;

    #[ORM\Column(enumType: StatutContrat::class)]
    #[Groups(['contrat:read'])]
    private StatutContrat $statut = StatutContrat::EN_ATTENTE;

    #[ORM\OneToMany(mappedBy: 'contrat', targetEntity: Paiement::class)]
    private Collection $paiements;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

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

    public function getLocataire(): ?User
    {
        return $this->locataire;
    }

    public function setLocataire(?User $locataire): static
    {
        $this->locataire = $locataire;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getLoyerMensuel(): ?string
    {
        return $this->loyerMensuel;
    }

    public function setLoyerMensuel(string $loyerMensuel): static
    {
        $this->loyerMensuel = $loyerMensuel;

        return $this;
    }

    public function getCaution(): ?string
    {
        return $this->caution;
    }

    public function setCaution(string $caution): static
    {
        $this->caution = $caution;

        return $this;
    }

    public function getStatut(): ?StatutContrat
    {
        return $this->statut;
    }

    public function setStatut(StatutContrat $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setContrat($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getContrat() === $this) {
                $paiement->setContrat(null);
            }
        }

        return $this;
    }
}
