<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix user.is_totp_enabled: convert SMALLINT to BOOLEAN on PostgreSQL (Render).
 * No-op on MySQL. Run after Version20260302002505 on already-deployed DBs.
 */
final class Version20260302120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PostgreSQL: alter user.is_totp_enabled from SMALLINT to BOOLEAN';
    }

    public function up(Schema $schema): void
    {
        if (!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return;
        }
        // 1. Drop default first (required before type change on PostgreSQL)
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_totp_enabled DROP DEFAULT');
        // 2. Change type with explicit USING cast
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_totp_enabled TYPE BOOLEAN USING (is_totp_enabled::int != 0)');
        // 3. Restore default as boolean
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_totp_enabled SET DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        if (!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return;
        }
        // 1. Drop boolean default first
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_totp_enabled DROP DEFAULT');
        // 2. Revert to SMALLINT
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_totp_enabled TYPE SMALLINT USING (CASE WHEN is_totp_enabled THEN 1 ELSE 0 END)');
        // 3. Restore smallint default
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_totp_enabled SET DEFAULT 0');
    }
}