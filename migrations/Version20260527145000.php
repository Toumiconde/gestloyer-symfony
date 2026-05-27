<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527145000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity_log table for admin history tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, actor_email VARCHAR(180) DEFAULT NULL, actor_role VARCHAR(64) DEFAULT NULL, target_email VARCHAR(180) DEFAULT NULL, action VARCHAR(80) NOT NULL, details LONGTEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B90A1D56771FE7CA (action), INDEX IDX_B90A1D56A76ED395 (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity_log');
    }
}

