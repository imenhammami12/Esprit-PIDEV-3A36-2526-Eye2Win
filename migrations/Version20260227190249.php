<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227190249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message_attachment (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, size INT NOT NULL, url VARCHAR(255) DEFAULT NULL, public_id VARCHAR(255) DEFAULT NULL, cloud_resource_type VARCHAR(20) DEFAULT NULL, message_id INT NOT NULL, INDEX IDX_B68FF524537A1329 (message_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE message_attachment ADD CONSTRAINT FK_B68FF524537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_complaint_list_sort ON complaint');
        $this->addSql('DROP INDEX idx_complaint_priority ON complaint');
        $this->addSql('DROP INDEX idx_complaint_resolved_at ON complaint');
        $this->addSql('DROP INDEX idx_complaint_created_at ON complaint');
        $this->addSql('DROP INDEX idx_complaint_status ON complaint');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_attachment DROP FOREIGN KEY FK_B68FF524537A1329');
        $this->addSql('DROP TABLE message_attachment');
        $this->addSql('CREATE INDEX idx_complaint_list_sort ON complaint (status, priority, created_at)');
        $this->addSql('CREATE INDEX idx_complaint_priority ON complaint (priority)');
        $this->addSql('CREATE INDEX idx_complaint_resolved_at ON complaint (resolved_at)');
        $this->addSql('CREATE INDEX idx_complaint_created_at ON complaint (created_at)');
        $this->addSql('CREATE INDEX idx_complaint_status ON complaint (status)');
    }
}
