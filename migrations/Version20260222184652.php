<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222184652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE channel_invite (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) NOT NULL, created_by_email VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, mode VARCHAR(255) NOT NULL, max_uses INT DEFAULT NULL, uses INT DEFAULT NULL, is_active TINYINT NOT NULL, channel_id INT DEFAULT NULL, INDEX IDX_39812AA972F5A1AA (channel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE channel_join_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, requested_at DATETIME NOT NULL, decided_at DATETIME DEFAULT NULL, decided_by_email VARCHAR(255) DEFAULT NULL, reason LONGTEXT DEFAULT NULL, channel_id INT DEFAULT NULL, requester_id INT DEFAULT NULL, INDEX IDX_A5422E5E72F5A1AA (channel_id), INDEX IDX_A5422E5EED442CF4 (requester_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE channel_member (id INT AUTO_INCREMENT NOT NULL, joined_at DATETIME NOT NULL, channel_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_8E87C00672F5A1AA (channel_id), INDEX IDX_8E87C006A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE channel_invite ADD CONSTRAINT FK_39812AA972F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id)');
        $this->addSql('ALTER TABLE channel_join_request ADD CONSTRAINT FK_A5422E5E72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id)');
        $this->addSql('ALTER TABLE channel_join_request ADD CONSTRAINT FK_A5422E5EED442CF4 FOREIGN KEY (requester_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE channel_member ADD CONSTRAINT FK_8E87C00672F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id)');
        $this->addSql('ALTER TABLE channel_member ADD CONSTRAINT FK_8E87C006A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel_invite DROP FOREIGN KEY FK_39812AA972F5A1AA');
        $this->addSql('ALTER TABLE channel_join_request DROP FOREIGN KEY FK_A5422E5E72F5A1AA');
        $this->addSql('ALTER TABLE channel_join_request DROP FOREIGN KEY FK_A5422E5EED442CF4');
        $this->addSql('ALTER TABLE channel_member DROP FOREIGN KEY FK_8E87C00672F5A1AA');
        $this->addSql('ALTER TABLE channel_member DROP FOREIGN KEY FK_8E87C006A76ED395');
        $this->addSql('DROP TABLE channel_invite');
        $this->addSql('DROP TABLE channel_join_request');
        $this->addSql('DROP TABLE channel_member');
    }
}
