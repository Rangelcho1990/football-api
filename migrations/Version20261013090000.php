<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create league, team, game and user tables for the football API.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'This migration requires MySQL or MariaDB.');

        $this->addSql('CREATE TABLE league (name VARCHAR(50) NOT NULL, league_name_slugged VARCHAR(50) NOT NULL, logo VARCHAR(150) NOT NULL, league_api_id INT NOT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (username VARCHAR(50) NOT NULL, password VARCHAR(500) NOT NULL, isActive TINYINT NOT NULL, point INT NOT NULL, email VARCHAR(100) NOT NULL, avatar INT NOT NULL, created_at DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX username (username), UNIQUE INDEX email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE game (game_time DATETIME NOT NULL, score VARCHAR(50) DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, league INT NOT NULL, home_team INT NOT NULL, away_team INT NOT NULL, UNIQUE INDEX gameIndex (home_team, away_team, game_time), INDEX IDX_232B318C3EB4C318 (league), INDEX IDX_232B318CE5C617D0 (home_team), INDEX IDX_232B318C558F2381 (away_team), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team (name VARCHAR(50) NOT NULL, logo VARCHAR(150) NOT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C3EB4C318 FOREIGN KEY (league) REFERENCES league (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CE5C617D0 FOREIGN KEY (home_team) REFERENCES team (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C558F2381 FOREIGN KEY (away_team) REFERENCES team (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'This migration requires MySQL or MariaDB.');

        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C3EB4C318');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CE5C617D0');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C558F2381');
        $this->addSql('DROP TABLE league');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE team');
    }
}
