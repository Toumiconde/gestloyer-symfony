<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527232807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_B90A1D56D8A2C5E5 ON activity_log');
        $this->addSql('DROP INDEX IDX_B90A1D56771FE7CA ON activity_log');
        $this->addSql('DROP INDEX IDX_B90A1D56FE45D9F2 ON activity_log');
        $this->addSql('DROP INDEX IDX_B90A1D56A76ED395 ON activity_log');
        $this->addSql('ALTER TABLE activity_log ADD payload JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE is_seen is_seen TINYINT NOT NULL');
        $this->addSql('ALTER TABLE bien ADD CONSTRAINT FK_45EDC38676C50E4A FOREIGN KEY (proprietaire_id) REFERENCES proprietaire (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_60349993BD95B80F FOREIGN KEY (bien_id) REFERENCES bien (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_60349993D8A38199 FOREIGN KEY (locataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE devis ADD CONSTRAINT FK_8B27C52B59E53FB9 FOREIGN KEY (incident_id) REFERENCES incident (id)');
        $this->addSql('ALTER TABLE incident ADD CONSTRAINT FK_3D03A11ABD95B80F FOREIGN KEY (bien_id) REFERENCES bien (id)');
        $this->addSql('ALTER TABLE incident ADD CONSTRAINT FK_3D03A11AEC439BC FOREIGN KEY (declarant_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E1823061F FOREIGN KEY (contrat_id) REFERENCES contrat (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE proprietaire ADD CONSTRAINT FK_69E399D6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE quittance ADD CONSTRAINT FK_D57587DD2A4C4478 FOREIGN KEY (paiement_id) REFERENCES paiement (id)');
        $this->addSql('ALTER TABLE reversement ADD CONSTRAINT FK_6D60122376C50E4A FOREIGN KEY (proprietaire_id) REFERENCES proprietaire (id)');
        $this->addSql('ALTER TABLE versement ADD CONSTRAINT FK_716E93672A4C4478 FOREIGN KEY (paiement_id) REFERENCES paiement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP payload, CHANGE is_seen is_seen TINYINT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_B90A1D56D8A2C5E5 ON activity_log (is_seen)');
        $this->addSql('CREATE INDEX IDX_B90A1D56771FE7CA ON activity_log (action)');
        $this->addSql('CREATE INDEX IDX_B90A1D56FE45D9F2 ON activity_log (session_id)');
        $this->addSql('CREATE INDEX IDX_B90A1D56A76ED395 ON activity_log (created_at)');
        $this->addSql('ALTER TABLE bien DROP FOREIGN KEY FK_45EDC38676C50E4A');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993BD95B80F');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993D8A38199');
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52B59E53FB9');
        $this->addSql('ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11ABD95B80F');
        $this->addSql('ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11AEC439BC');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E1823061F');
        $this->addSql('ALTER TABLE proprietaire DROP FOREIGN KEY FK_69E399D6A76ED395');
        $this->addSql('ALTER TABLE quittance DROP FOREIGN KEY FK_D57587DD2A4C4478');
        $this->addSql('ALTER TABLE reversement DROP FOREIGN KEY FK_6D60122376C50E4A');
        $this->addSql('ALTER TABLE versement DROP FOREIGN KEY FK_716E93672A4C4478');
    }
}
