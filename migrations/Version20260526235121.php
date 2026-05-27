<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260526235121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bien (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse LONGTEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, proprietaire_id INT NOT NULL, INDEX IDX_45EDC38676C50E4A (proprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contrat (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(180) NOT NULL, date_debut DATE NOT NULL, date_fin DATE DEFAULT NULL, loyer_mensuel NUMERIC(15, 2) NOT NULL, caution NUMERIC(15, 2) NOT NULL, statut VARCHAR(255) NOT NULL, bien_id INT NOT NULL, locataire_id INT NOT NULL, UNIQUE INDEX UNIQ_60349993F55AE19E (numero), INDEX IDX_60349993BD95B80F (bien_id), INDEX IDX_60349993D8A38199 (locataire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE devis (id INT AUTO_INCREMENT NOT NULL, montant NUMERIC(15, 2) NOT NULL, prestataire VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(255) NOT NULL, document_path VARCHAR(255) DEFAULT NULL, incident_id INT NOT NULL, INDEX IDX_8B27C52B59E53FB9 (incident_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE incident (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, priorite VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, date_declaration DATETIME NOT NULL, bien_id INT NOT NULL, declarant_id INT NOT NULL, INDEX IDX_3D03A11ABD95B80F (bien_id), INDEX IDX_3D03A11AEC439BC (declarant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, mois DATE NOT NULL, montant_du NUMERIC(15, 2) NOT NULL, montant_verse NUMERIC(15, 2) NOT NULL, statut VARCHAR(255) NOT NULL, contrat_id INT NOT NULL, INDEX IDX_B1DC7A1E1823061F (contrat_id), UNIQUE INDEX unique_contrat_mois (contrat_id, mois), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE proprietaire (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, telephone VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_69E399D6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quittance (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(180) NOT NULL, date_generation DATETIME NOT NULL, pdf_path VARCHAR(255) DEFAULT NULL, paiement_id INT NOT NULL, UNIQUE INDEX UNIQ_D57587DDF55AE19E (numero), UNIQUE INDEX UNIQ_D57587DD2A4C4478 (paiement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reversement (id INT AUTO_INCREMENT NOT NULL, mois DATE NOT NULL, montant_brut NUMERIC(15, 2) NOT NULL, commission NUMERIC(15, 2) NOT NULL, frais_maintenance NUMERIC(15, 2) NOT NULL, montant_net NUMERIC(15, 2) NOT NULL, statut VARCHAR(255) NOT NULL, pdf_path VARCHAR(255) DEFAULT NULL, proprietaire_id INT NOT NULL, INDEX IDX_6D60122376C50E4A (proprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE versement (id INT AUTO_INCREMENT NOT NULL, montant NUMERIC(15, 2) NOT NULL, mode VARCHAR(255) NOT NULL, reference VARCHAR(255) DEFAULT NULL, date_paiement DATETIME NOT NULL, est_valide TINYINT NOT NULL, paiement_id INT NOT NULL, INDEX IDX_716E93672A4C4478 (paiement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
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
        $this->addSql('DROP TABLE bien');
        $this->addSql('DROP TABLE contrat');
        $this->addSql('DROP TABLE devis');
        $this->addSql('DROP TABLE incident');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE proprietaire');
        $this->addSql('DROP TABLE quittance');
        $this->addSql('DROP TABLE reversement');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE versement');
    }
}
