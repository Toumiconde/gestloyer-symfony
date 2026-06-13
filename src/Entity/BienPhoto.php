<?php

namespace App\Entity;

use App\Repository\BienPhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BienPhotoRepository::class)]
class BienPhoto
{
    public const CATEGORIES = [
        'facade'   => 'Façade / Extérieur',
        'salon'    => 'Salon / Séjour',
        'chambre'  => 'Chambre',
        'cuisine'  => 'Cuisine',
        'douche'   => 'Salle de bain / Douche',
        'wc'       => 'WC',
        'terrasse' => 'Terrasse / Balcon',
        'couloir'  => 'Couloir / Entrée',
        'autre'    => 'Autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Bien $bien = null;

    #[ORM\Column(length: 255)]
    private string $filename = '';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $categorie = 'autre';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getBien(): ?Bien { return $this->bien; }
    public function setBien(?Bien $bien): static { $this->bien = $bien; return $this; }

    public function getFilename(): string { return $this->filename; }
    public function setFilename(string $filename): static { $this->filename = $filename; return $this; }

    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(?string $categorie): static { $this->categorie = $categorie; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getCategorieLabel(): string
    {
        return self::CATEGORIES[$this->categorie ?? 'autre'] ?? 'Autre';
    }
}
