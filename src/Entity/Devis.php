<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DevisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DevisRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['devis:read']],
    denormalizationContext: ['groups' => ['devis:write']]
)]
class Devis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['devis:read', 'incident:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['devis:read', 'devis:write', 'incident:read'])]
    private ?string $montant = null;

    #[ORM\Column(length: 255)]
    #[Groups(['devis:read', 'devis:write', 'incident:read'])]
    private ?string $prestataire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['devis:read', 'devis:write', 'incident:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['devis:read', 'devis:write', 'incident:read'])]
    private string $statut = 'SOUMIS'; // SOUMIS, APPROUVE, REJETE

    #[ORM\ManyToOne(inversedBy: 'devis')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['devis:read', 'devis:write'])]
    private ?Incident $incident = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['devis:read'])]
    private ?string $documentPath = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getPrestataire(): ?string
    {
        return $this->prestataire;
    }

    public function setPrestataire(string $prestataire): static
    {
        $this->prestataire = $prestataire;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getIncident(): ?Incident
    {
        return $this->incident;
    }

    public function setIncident(?Incident $incident): static
    {
        $this->incident = $incident;

        return $this;
    }

    public function getDocumentPath(): ?string
    {
        return $this->documentPath;
    }

    public function setDocumentPath(?string $documentPath): static
    {
        $this->documentPath = $documentPath;

        return $this;
    }
}
