<?php

namespace App\Repository;

use App\Entity\Paiement;
use App\Entity\Proprietaire;
use App\Enum\RoleUtilisateur;
use App\Enum\StatutPaiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    public function calculerTotalBrutByProprietaireAndMois(Proprietaire $proprietaire, \DateTimeImmutable $mois): ?string
    {
        return $this->createQueryBuilder('p')
            ->select('SUM(p.montantVerse)')
            ->join('p.contrat', 'c')
            ->join('c.bien', 'b')
            ->where('b.proprietaire = :proprietaire')
            ->andWhere('p.mois = :mois')
            ->andWhere('p.statut = :statutValide')
            ->setParameter('proprietaire', $proprietaire)
            ->setParameter('mois', $mois->format('Y-m-d'))
            ->setParameter('statutValide', \App\Enum\StatutPaiement::VALIDE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array{email: string, solde: string, contrats: int}>
     */
    public function findLocatairesEnRetardPourMois(\DateTimeImmutable $mois, int $limit = 500): array
    {
        // Note: Paiement.mois is a DATE (immutable). We normalize to first day.
        $moisNormalized = $mois->setDate((int) $mois->format('Y'), (int) $mois->format('m'), 1);

        $rows = $this->createQueryBuilder('p')
            ->select('u.email AS email')
            ->addSelect('SUM(p.montantDu - p.montantVerse) AS solde')
            ->addSelect('COUNT(DISTINCT c.id) AS contrats')
            ->join('p.contrat', 'c')
            ->join('c.locataire', 'u')
            ->where('p.mois = :mois')
            ->andWhere('p.statut IN (:statuts)')
            ->andWhere('u.isActive = true')
            ->andWhere('u.role = :roleLocataire')
            ->groupBy('u.email')
            ->having('SUM(p.montantDu - p.montantVerse) > 0')
            ->orderBy('solde', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('mois', $moisNormalized->format('Y-m-d'))
            ->setParameter('statuts', [StatutPaiement::EN_ATTENTE, StatutPaiement::PARTIEL])
            ->setParameter('roleLocataire', RoleUtilisateur::LOCATAIRE)
            ->getQuery()
            ->getArrayResult();

        // Ensure stable string casting for decimals
        return array_map(static function (array $r): array {
            return [
                'email' => (string) ($r['email'] ?? ''),
                'solde' => (string) ($r['solde'] ?? '0'),
                'contrats' => (int) ($r['contrats'] ?? 0),
            ];
        }, $rows);
    }
}
