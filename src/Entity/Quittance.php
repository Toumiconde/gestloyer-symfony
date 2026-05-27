<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Repository\QuittanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QuittanceRepository::class)]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('VIEW', object)")
    ],
    normalizationContext: ['groups' => ['quittance:read']]
)]
class Quittance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['quittance:read', 'paiement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['quittance:read', 'paiement:read'])]
    private ?string $numero = null;

    #[ORM\OneToOne(inversedBy: 'quittance', targetEntity: Paiement::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['quittance:read'])]
    private ?Paiement $paiement = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['quittance:read'])]
    private ?\DateTimeImmutable $dateGeneration = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['quittance:read'])]
    private ?string $pdfPath = null;

    public function __construct()
    {
        $this->dateGeneration = new \DateTimeImmutable();
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

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(Paiement $paiement): static
    {
        $this->paiement = $paiement;

        return $this;
    }

    public function getDateGeneration(): ?\DateTimeImmutable
    {
        return $this->dateGeneration;
    }

    public function setDateGeneration(\DateTimeImmutable $dateGeneration): static
    {
        $this->dateGeneration = $dateGeneration;

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
