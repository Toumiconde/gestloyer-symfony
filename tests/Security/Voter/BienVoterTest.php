<?php

namespace App\Tests\Security\Voter;

use App\Entity\Bien;
use App\Entity\Contrat;
use App\Entity\Proprietaire;
use App\Entity\User;
use App\Enum\RoleUtilisateur;
use App\Security\Voter\BienVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BienVoterTest extends TestCase
{
    private BienVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new BienVoter();
    }

    private function createTokenWithUser(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        return $token;
    }

    public function testAdminCanViewAnything(): void
    {
        $admin = new User();
        $admin->setRole(RoleUtilisateur::ADMIN);
        
        $bien = new Bien();
        $token = $this->createTokenWithUser($admin);

        // the 'vote' method is protected, we must use a ReflectionClass or just test the public vote method (but Voter class abstracts it).
        // For testing Voter correctly in PHPUnit without integration test:
        $class = new \ReflectionClass(BienVoter::class);
        $method = $class->getMethod('voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->voter, [BienVoter::VIEW, $bien, $token]);
        $this->assertTrue($result);
    }

    public function testLocataireCannotViewRandomBien(): void
    {
        $locataire = new User();
        $locataire->setRole(RoleUtilisateur::LOCATAIRE);
        
        $bien = new Bien();
        $token = $this->createTokenWithUser($locataire);

        $class = new \ReflectionClass(BienVoter::class);
        $method = $class->getMethod('voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->voter, [BienVoter::VIEW, $bien, $token]);
        $this->assertFalse($result); // Locataire should not see a random property
    }

    public function testLocataireCanViewHisBien(): void
    {
        $locataire = new User();
        $locataire->setRole(RoleUtilisateur::LOCATAIRE);
        
        $bien = new Bien();
        $contrat = new Contrat();
        $contrat->setLocataire($locataire);
        $bien->addContrat($contrat);

        $token = $this->createTokenWithUser($locataire);

        $class = new \ReflectionClass(BienVoter::class);
        $method = $class->getMethod('voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->voter, [BienVoter::VIEW, $bien, $token]);
        $this->assertTrue($result); // Locataire CAN see the property if he rents it
    }
}
