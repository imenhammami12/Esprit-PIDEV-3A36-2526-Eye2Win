<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Alter tournoi.prix and valorant columns to DOUBLE PRECISION / JSON.
 * Platform-aware: MySQL uses CHANGE, PostgreSQL uses ALTER COLUMN.
 */
final class Version20260301233857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter tournoi.prix and valorant_match/valorant_statistique columns';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($platform === 'postgresql') {
            $this->addSql('ALTER TABLE tournoi ALTER COLUMN prix TYPE DOUBLE PRECISION USING prix::double precision');
            $this->addSql('ALTER TABLE tournoi ALTER COLUMN prix SET DEFAULT 0');
            $this->addSql('ALTER TABLE tournoi ALTER COLUMN prix SET NOT NULL');
            $this->addSql('ALTER TABLE valorant_match ALTER COLUMN raw_data TYPE JSONB USING CASE WHEN raw_data IS NULL OR raw_data = \'\' THEN NULL ELSE raw_data::jsonb END');
            $this->addSql('ALTER TABLE valorant_statistique ALTER COLUMN weapons TYPE JSONB USING CASE WHEN weapons IS NULL OR weapons = \'\' THEN NULL ELSE weapons::jsonb END');
            $this->addSql('ALTER TABLE valorant_statistique ALTER COLUMN timings TYPE JSONB USING CASE WHEN timings IS NULL OR timings = \'\' THEN NULL ELSE timings::jsonb END');
            $this->addSql('ALTER TABLE valorant_statistique ALTER COLUMN extra TYPE JSONB USING CASE WHEN extra IS NULL OR extra = \'\' THEN NULL ELSE extra::jsonb END');
        } else {
            $this->addSql('ALTER TABLE tournoi CHANGE prix prix DOUBLE PRECISION DEFAULT 0 NOT NULL');
            $this->addSql('ALTER TABLE valorant_match CHANGE raw_data raw_data JSON DEFAULT NULL');
            $this->addSql('ALTER TABLE valorant_statistique CHANGE weapons weapons JSON DEFAULT NULL, CHANGE timings timings JSON DEFAULT NULL, CHANGE extra extra JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($platform === 'postgresql') {
            $this->addSql('ALTER TABLE tournoi ALTER COLUMN prix TYPE DOUBLE PRECISION USING prix::double precision');
            $this->addSql('ALTER TABLE tournoi ALTER COLUMN prix SET DEFAULT 0');
            $this->addSql('ALTER TABLE tournoi ALTER COLUMN prix SET NOT NULL');
            $this->addSql('ALTER TABLE valorant_match ALTER COLUMN raw_data TYPE TEXT');
            $this->addSql('ALTER TABLE valorant_statistique ALTER COLUMN weapons TYPE TEXT');
            $this->addSql('ALTER TABLE valorant_statistique ALTER COLUMN timings TYPE TEXT');
            $this->addSql('ALTER TABLE valorant_statistique ALTER COLUMN extra TYPE TEXT');
        } else {
            $this->addSql('ALTER TABLE tournoi CHANGE prix prix DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
            $this->addSql('ALTER TABLE valorant_match CHANGE raw_data raw_data LONGTEXT DEFAULT NULL');
            $this->addSql('ALTER TABLE valorant_statistique CHANGE weapons weapons LONGTEXT DEFAULT NULL, CHANGE timings timings LONGTEXT DEFAULT NULL, CHANGE extra extra LONGTEXT DEFAULT NULL');
        }
    }
}
