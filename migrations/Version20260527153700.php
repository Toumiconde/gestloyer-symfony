<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527153700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add session/route/method/url fields to activity_log for full audit trail';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_log ADD session_id VARCHAR(128) DEFAULT NULL, ADD route VARCHAR(255) DEFAULT NULL, ADD method VARCHAR(16) DEFAULT NULL, ADD url LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_B90A1D56FE45D9F2 ON activity_log (session_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_B90A1D56FE45D9F2 ON activity_log');
        $this->addSql('ALTER TABLE activity_log DROP session_id, DROP route, DROP method, DROP url');
    }
}

