<?php

namespace App\Tests\Service;

use App\Entity\Contrat;
use App\Entity\Paiement;
use App\Entity\Versement;
use App\Enum\ModePaiement;
use App\Enum\StatutPaiement;
use App\Service\PaiementService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class PaiementServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private PaiementService $paiementService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $this->paiementService = new PaiementService($this->entityManager, $this->messageBus);
    }

    public function testDeclarerVersementPartiel(): void
    {
        $paiement = new Paiement();
        $paiement->setMontantDu('1000.00');
        $paiement->setMontantVerse('0.00');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Versement::class));
            
        $this->entityManager->expects($this->once())
            ->method('flush');

        $versement = $this->paiementService->declarerVersement($paiement, '400.00', ModePaiement::ESPECES);

        $this->assertEquals('400.00', $versement->getMontant());
        $this->assertEquals(ModePaiement::ESPECES, $versement->getMode());
        $this->assertEquals(StatutPaiement::PARTIEL, $paiement->getStatut());
        $this->assertEquals('400.00', $paiement->getMontantVerse());
        $this->assertEquals('600.00', $paiement->getSolde());
        $this->assertFalse($paiement->isComplet());
    }

    public function testDeclarerVersementComplet(): void
    {
        $paiement = new Paiement();
        $paiement->setMontantDu('1000.00');
        $paiement->setMontantVerse('0.00');

        $this->paiementService->declarerVersement($paiement, '1000.00', ModePaiement::VIREMENT);

        $this->assertEquals(StatutPaiement::COMPLET, $paiement->getStatut());
        $this->assertEquals('1000.00', $paiement->getMontantVerse());
        $this->assertEquals('0.00', $paiement->getSolde());
        $this->assertTrue($paiement->isComplet());
    }

    public function testValiderPaiementDispatchMessage(): void
    {
        $paiement = $this->createMock(Paiement::class);
        $paiement->method('isComplet')->willReturn(true);
        $paiement->method('getId')->willReturn(42);

        $paiement->expects($this->once())
            ->method('setStatut')
            ->with(StatutPaiement::VALIDE);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass())); // Mocking envelope

        $this->paiementService->validerPaiement($paiement);
    }

    public function testValiderPaiementIncompletThrowsException(): void
    {
        $paiement = new Paiement();
        $paiement->setMontantDu('1000.00');
        $paiement->setMontantVerse('500.00');

        $this->expectException(\LogicException::class);
        
        $this->paiementService->validerPaiement($paiement);
    }
}
