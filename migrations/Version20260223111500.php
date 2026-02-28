<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223111500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add clip/highlight fields and relations to video entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE video ADD type VARCHAR(20) NOT NULL DEFAULT 'UPLOAD', ADD match_external_id VARCHAR(120) DEFAULT NULL, ADD thumbnail_path VARCHAR(255) DEFAULT NULL, ADD kill_info LONGTEXT DEFAULT NULL, ADD likes_count INT NOT NULL DEFAULT 0, ADD metadata_json LONGTEXT DEFAULT NULL, ADD highlight_id INT DEFAULT NULL");
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C6F16A922 FOREIGN KEY (highlight_id) REFERENCES video (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7CC7DA2C6F16A922 ON video (highlight_id)');
        $this->addSql('CREATE INDEX IDX_7CC7DA2CEFA53BA5 ON video (type)');
        $this->addSql('CREATE INDEX IDX_7CC7DA2CAA69BA6E ON video (match_external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2C6F16A922');
        $this->addSql('DROP INDEX IDX_7CC7DA2C6F16A922 ON video');
        $this->addSql('DROP INDEX IDX_7CC7DA2CEFA53BA5 ON video');
        $this->addSql('DROP INDEX IDX_7CC7DA2CAA69BA6E ON video');
        $this->addSql('ALTER TABLE video DROP type, DROP match_external_id, DROP thumbnail_path, DROP kill_info, DROP likes_count, DROP metadata_json, DROP highlight_id');
    }
}
