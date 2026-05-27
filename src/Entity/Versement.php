<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Enum\ModePaiement;
use App\Repository\VersementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: VersementRepository::class)]
#[ApiResource(
    operations: [
        new Post(security: "is_granted('ROLE_USER')")
    ],
    normalizationContext: ['groups' => ['versement:read']],
    denormalizationContext: ['groups' => ['versement:write']]
)]
class Versement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['versement:read', 'paiement:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['versement:read', 'versement:write', 'paiement:read'])]
    private ?string $montant = null;

    #[ORM\Column(enumType: ModePaiement::class)]
    #[Groups(['versement:read', 'versement:write', 'paiement:read'])]
    private ModePaiement $mode = ModePaiement::ESPECES;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['versement:read', 'versement:write', 'paiement:read'])]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['versement:read', 'paiement:read'])]
    private ?\DateTimeImmutable $datePaiement = null;

    #[ORM\ManyToOne(inversedBy: 'versements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['versement:read', 'versement:write'])]
    private ?Paiement $paiement = null;

    #[ORM\Column]
    #[Groups(['versement:read'])]
    private bool $estValide = false;

    public function __construct()
    {
        $this->datePaiement = new \DateTimeImmutable();
    }

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

    public function getMode(): ?ModePaiement
    {
        return $this->mode;
    }

    public function setMode(ModePaiement $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeImmutable
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(\DateTimeImmutable $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        $this->paiement = $paiement;

        return $this;
    }

    public function isEstValide(): ?bool
    {
        return $this->estValide;
    }

    public function setEstValide(bool $estValide): static
    {
        $this->estValide = $estValide;

        return $this;
    }
}
