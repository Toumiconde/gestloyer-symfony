<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611184351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agence_config (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse LONGTEXT DEFAULT NULL, siret VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, iban VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, is_seen TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bien ADD nb_chambres INT DEFAULT NULL, ADD douche_interne TINYINT DEFAULT NULL, ADD douche_externe TINYINT DEFAULT NULL, ADD has_terrasse TINYINT DEFAULT NULL, ADD emplacement VARCHAR(255) DEFAULT NULL');
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
        $this->addSql('ALTER TABLE user ADD nom VARCHAR(255) DEFAULT NULL, ADD prenom VARCHAR(255) DEFAULT NULL, ADD telephone VARCHAR(20) DEFAULT NULL, ADD photo_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE versement ADD CONSTRAINT FK_716E93672A4C4478 FOREIGN KEY (paiement_id) REFERENCES paiement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('DROP TABLE agence_config');
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE bien DROP FOREIGN KEY FK_45EDC38676C50E4A');
        $this->addSql('ALTER TABLE bien DROP nb_chambres, DROP douche_interne, DROP douche_externe, DROP has_terrasse, DROP emplacement');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993BD95B80F');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993D8A38199');
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52B59E53FB9');
        $this->addSql('ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11ABD95B80F');
        $this->addSql('ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11AEC439BC');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E1823061F');
        $this->addSql('ALTER TABLE proprietaire DROP FOREIGN KEY FK_69E399D6A76ED395');
        $this->addSql('ALTER TABLE quittance DROP FOREIGN KEY FK_D57587DD2A4C4478');
        $this->addSql('ALTER TABLE reversement DROP FOREIGN KEY FK_6D60122376C50E4A');
        $this->addSql('ALTER TABLE `user` DROP nom, DROP prenom, DROP telephone, DROP photo_filename');
        $this->addSql('ALTER TABLE versement DROP FOREIGN KEY FK_716E93672A4C4478');
    }
}
