<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add team import metadata and update the logo and external API ID definitions.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'This migration requires MySQL or MariaDB.');

        $this->addSql('ALTER TABLE team
            ADD founded INT(11) DEFAULT NULL,
            ADD national TINYINT(1) DEFAULT NULL,
            MODIFY logo VARCHAR(100) DEFAULT NULL,
            ADD team_api_id INT(10) UNSIGNED NOT NULL,
            ADD country_id INT(11) NOT NULL,
            ADD status TINYINT(1) DEFAULT 1,
            ADD __lastUpdate DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_team_api_id ON team (team_api_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'This migration requires MySQL or MariaDB.');

        $this->addSql('DROP INDEX uniq_team_api_id ON team');
        $this->addSql("UPDATE team SET logo = '' WHERE logo IS NULL");
        $this->addSql('ALTER TABLE team
            DROP founded,
            DROP national,
            MODIFY logo VARCHAR(150) NOT NULL,
            DROP team_api_id,
            DROP country_id,
            DROP status,
            DROP __lastUpdate');
    }
}
