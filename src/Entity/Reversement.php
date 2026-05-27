<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\StatutReversement;
use App\Repository\ReversementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ReversementRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_PROPRIETAIRE')"),
        new Get(security: "is_granted('VIEW', object)")
    ],
    normalizationContext: ['groups' => ['reversement:read']]
)]
class Reversement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reversement:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reversements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['reversement:read'])]
    private ?Proprietaire $proprietaire = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['reversement:read'])]
    private ?\DateTimeImmutable $mois = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['reversement:read'])]
    private ?string $montantBrut = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['reversement:read'])]
    private ?string $commission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['reversement:read'])]
    private ?string $fraisMaintenance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['reversement:read'])]
    private ?string $montantNet = null;

    #[ORM\Column(enumType: StatutReversement::class)]
    #[Groups(['reversement:read'])]
    private StatutReversement $statut = StatutReversement::CALCULE;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['reversement:read'])]
    private ?string $pdfPath = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMois(): ?\DateTimeImmutable
    {
        return $this->mois;
    }

    public function setMois(\DateTimeImmutable $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    public function getMontantBrut(): ?string
    {
        return $this->montantBrut;
    }

    public function setMontantBrut(string $montantBrut): static
    {
        $this->montantBrut = $montantBrut;

        return $this;
    }

    public function getCommission(): ?string
    {
        return $this->commission;
    }

    public function setCommission(string $commission): static
    {
        $this->commission = $commission;

        return $this;
    }

    public function getFraisMaintenance(): ?string
    {
        return $this->fraisMaintenance;
    }

    public function setFraisMaintenance(string $fraisMaintenance): static
    {
        $this->fraisMaintenance = $fraisMaintenance;

        return $this;
    }

    public function getMontantNet(): ?string
    {
        return $this->montantNet;
    }

    public function setMontantNet(string $montantNet): static
    {
        $this->montantNet = $montantNet;

        return $this;
    }

    public function getStatut(): ?StatutReversement
    {
        return $this->statut;
    }

    public function setStatut(StatutReversement $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;

        return $this;
    }
}
