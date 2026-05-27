<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527154800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_seen to activity_log for admin notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_log ADD is_seen TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('CREATE INDEX IDX_B90A1D56D8A2C5E5 ON activity_log (is_seen)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_B90A1D56D8A2C5E5 ON activity_log');
        $this->addSql('ALTER TABLE activity_log DROP is_seen');
    }
}

