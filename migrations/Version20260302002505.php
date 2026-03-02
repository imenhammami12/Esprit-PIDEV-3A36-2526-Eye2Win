<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Full schema migration: creates all tables and FKs.
 * Platform-aware: PostgreSQL (Render) and MySQL (local).
 */
final class Version20260302002505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all application tables (PostgreSQL and MySQL compatible)';
    }

    public function up(Schema $schema): void
    {
        $isPostgres = $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform;

        if ($isPostgres) {
            $this->upPostgres();
        } else {
            $this->upMysql();
        }
    }

    public function down(Schema $schema): void
    {
        $isPostgres = $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform;

        if ($isPostgres) {
            $this->downPostgres();
        } else {
            $this->downMysql();
        }
    }

    private function upMysql(): void
    {
        $this->addSql('CREATE TABLE agent (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL, image VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, game_id INT NOT NULL, INDEX IDX_268B9C9DE48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(50) NOT NULL, entity_type VARCHAR(100) NOT NULL, entity_id INT DEFAULT NULL, details LONGTEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_F6E1C0F5A76ED395 (user_id), INDEX idx_audit_created_at (created_at), INDEX idx_audit_entity_type (entity_type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE channel (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, game VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, is_active TINYINT NOT NULL, image_url VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, created_by VARCHAR(100) NOT NULL, approved_by VARCHAR(100) DEFAULT NULL, approved_at DATETIME DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE channel_invite (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) NOT NULL, created_by_email VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, mode VARCHAR(255) NOT NULL, max_uses INT DEFAULT NULL, uses INT DEFAULT NULL, is_active TINYINT NOT NULL, channel_id INT NOT NULL, UNIQUE INDEX UNIQ_39812AA95F37A13B (token), INDEX IDX_39812AA972F5A1AA (channel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE channel_join_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, requested_at DATETIME NOT NULL, decided_at DATETIME DEFAULT NULL, decided_by_email VARCHAR(255) DEFAULT NULL, reason LONGTEXT DEFAULT NULL, channel_id INT NOT NULL, requester_id INT DEFAULT NULL, INDEX IDX_A5422E5E72F5A1AA (channel_id), INDEX IDX_A5422E5EED442CF4 (requester_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE channel_member (id INT AUTO_INCREMENT NOT NULL, joined_at DATETIME NOT NULL, channel_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_8E87C00672F5A1AA (channel_id), INDEX IDX_8E87C006A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE coach_application (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, certifications LONGTEXT NOT NULL, experience LONGTEXT NOT NULL, submitted_at DATETIME NOT NULL, reviewed_at DATETIME DEFAULT NULL, review_comment LONGTEXT DEFAULT NULL, documents VARCHAR(255) DEFAULT NULL, cv_file VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_80818310A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE coin_purchase (id INT AUTO_INCREMENT NOT NULL, coins_amount INT NOT NULL, price_paid NUMERIC(10, 2) NOT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_ABE21FE0A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE complaint (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(200) NOT NULL, description LONGTEXT NOT NULL, category VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, resolved_at DATETIME DEFAULT NULL, admin_response LONGTEXT DEFAULT NULL, resolution_notes LONGTEXT DEFAULT NULL, attachment_path VARCHAR(255) DEFAULT NULL, sentiment_label VARCHAR(20) DEFAULT NULL, sentiment_score DOUBLE PRECISION DEFAULT NULL, sentiment_source VARCHAR(20) DEFAULT NULL, sentiment_priority_suggestion VARCHAR(20) DEFAULT NULL, submitted_by_id INT NOT NULL, assigned_to_id INT DEFAULT NULL, INDEX IDX_5F2732B579F7D87D (submitted_by_id), INDEX IDX_5F2732B5F4BD7827 (assigned_to_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL, icon VARCHAR(255) DEFAULT NULL, color VARCHAR(7) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_232B318C5E237E06 (name), UNIQUE INDEX UNIQ_232B318C989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE guide_video (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, video_url VARCHAR(255) NOT NULL, thumbnail VARCHAR(255) DEFAULT NULL, map VARCHAR(50) NOT NULL, likes INT NOT NULL, views INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, approved_at DATETIME DEFAULT NULL, uploaded_by_id INT NOT NULL, game_id INT NOT NULL, agent_id INT DEFAULT NULL, INDEX IDX_38DA1164A2B28FE8 (uploaded_by_id), INDEX IDX_38DA1164E48FD905 (game_id), INDEX IDX_38DA11643414710B (agent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE guide_video_likes (guide_video_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_6E86BC4A1C40BE2E (guide_video_id), INDEX IDX_6E86BC4AA76ED395 (user_id), PRIMARY KEY (guide_video_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE live_access (id INT AUTO_INCREMENT NOT NULL, coins_spent INT NOT NULL, purchased_at DATETIME NOT NULL, user_id INT NOT NULL, live_stream_id INT NOT NULL, INDEX IDX_653F9D80A76ED395 (user_id), INDEX IDX_653F9D806AFA264E (live_stream_id), UNIQUE INDEX unique_user_live (user_id, live_stream_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE live_stream (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, coin_price INT NOT NULL, status VARCHAR(50) NOT NULL, stream_key VARCHAR(191) NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, ended_at DATETIME DEFAULT NULL, coach_id INT NOT NULL, UNIQUE INDEX UNIQ_93BF08C820F533D7 (stream_key), INDEX IDX_93BF08C83C105691 (coach_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE matches (id INT AUTO_INCREMENT NOT NULL, equipe1 VARCHAR(255) NOT NULL, equipe2 VARCHAR(255) NOT NULL, score INT NOT NULL, date_match DATE NOT NULL, prix VARCHAR(255) NOT NULL, play_mode VARCHAR(20) DEFAULT \'En Ligne\' NOT NULL, localisation VARCHAR(255) DEFAULT NULL, tournoi_id INT NOT NULL, INDEX IDX_62615BAF607770A (tournoi_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, sent_at DATETIME NOT NULL, edited_at DATETIME NOT NULL, is_deleted TINYINT NOT NULL, sender_name VARCHAR(100) NOT NULL, sender_email VARCHAR(255) NOT NULL, channel_id INT NOT NULL, INDEX IDX_B6BD307F72F5A1AA (channel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE message_attachment (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, size INT NOT NULL, url VARCHAR(255) DEFAULT NULL, public_id VARCHAR(255) DEFAULT NULL, cloud_resource_type VARCHAR(20) DEFAULT NULL, message_id INT NOT NULL, INDEX IDX_B68FF524537A1329 (message_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, link VARCHAR(255) DEFAULT NULL, `read` TINYINT NOT NULL, user_id INT NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE password_reset_tokens (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, channel VARCHAR(20) NOT NULL, used TINYINT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_3967A2165F37A13B (token), INDEX IDX_3967A216A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE planning (IDplanning INT AUTO_INCREMENT NOT NULL, image VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, time TIME NOT NULL, localisation VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, need_partner TINYINT NOT NULL, level VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY (IDplanning)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE player_stat (id INT AUTO_INCREMENT NOT NULL, score INT NOT NULL, accuracy DOUBLE PRECISION NOT NULL, actions_count INT NOT NULL, videomatch_id INT NOT NULL, INDEX IDX_82A2AF12BB1BA406 (videomatch_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, rating INT NOT NULL, created_at DATETIME NOT NULL, sentiment VARCHAR(20) DEFAULT NULL, user_id INT NOT NULL, ID_planning INT NOT NULL, INDEX IDX_794381C6A76ED395 (user_id), INDEX IDX_794381C64D83DF64 (ID_planning), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, max_members INT NOT NULL, is_active TINYINT NOT NULL, owner_id INT NOT NULL, INDEX IDX_C4E0A61F7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team_membership (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, invited_at DATETIME DEFAULT NULL, joined_at DATETIME DEFAULT NULL, team_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B826A040296CD8AE (team_id), INDEX IDX_B826A040A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE tournoi (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) NOT NULL, type_tournoi VARCHAR(255) NOT NULL, prix DOUBLE PRECISION DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE tournoi_user (tournoi_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D0703ACDF607770A (tournoi_id), INDEX IDX_D0703ACDA76ED395 (user_id), PRIMARY KEY (tournoi_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE training_session (idtraining INT AUTO_INCREMENT NOT NULL, status VARCHAR(50) NOT NULL, joined_at DATETIME NOT NULL, ID_planning INT NOT NULL, IDcurrent_user INT NOT NULL, INDEX IDX_D7A45DA4D83DF64 (ID_planning), INDEX IDX_D7A45DA7A95F30B (IDcurrent_user), PRIMARY KEY (idtraining)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(50) NOT NULL, roles_json LONGTEXT NOT NULL, password VARCHAR(255) NOT NULL, account_status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, last_login DATETIME NOT NULL, full_name VARCHAR(100) DEFAULT NULL, bio VARCHAR(255) DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, coin_balance INT DEFAULT 0 NOT NULL, totp_secret VARCHAR(255) DEFAULT NULL, is_totp_enabled TINYINT DEFAULT 0 NOT NULL, backup_codes_json LONGTEXT DEFAULT NULL, totp_enabled_at DATETIME DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, telegram_chat_id VARCHAR(100) DEFAULT NULL, face_descriptor LONGTEXT DEFAULT NULL, face_image VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE valorant_equipe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, side VARCHAR(30) DEFAULT NULL, score INT DEFAULT NULL, match_id INT NOT NULL, INDEX IDX_48EE30F22ABEACD6 (match_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE valorant_joueur (id INT AUTO_INCREMENT NOT NULL, tracker_player_id VARCHAR(120) DEFAULT NULL, riot_name VARCHAR(120) NOT NULL, riot_tag VARCHAR(20) DEFAULT NULL, agent VARCHAR(50) DEFAULT NULL, match_id INT NOT NULL, equipe_id INT DEFAULT NULL, INDEX IDX_91D623222ABEACD6 (match_id), INDEX IDX_91D623226D861B89 (equipe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE valorant_match (id INT AUTO_INCREMENT NOT NULL, tracker_match_id VARCHAR(120) NOT NULL, map_name VARCHAR(120) DEFAULT NULL, mode VARCHAR(120) DEFAULT NULL, played_at DATETIME DEFAULT NULL, duration_seconds INT DEFAULT NULL, score_team_a INT DEFAULT NULL, score_team_b INT DEFAULT NULL, status VARCHAR(20) NOT NULL, raw_data JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, archived_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, INDEX IDX_DB44CF517E3C61F9 (owner_id), UNIQUE INDEX uniq_valorant_match_owner (owner_id, tracker_match_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE valorant_statistique (id INT AUTO_INCREMENT NOT NULL, kills INT NOT NULL, deaths INT NOT NULL, assists INT NOT NULL, headshots INT DEFAULT NULL, damage INT DEFAULT NULL, weapons JSON DEFAULT NULL, timings JSON DEFAULT NULL, extra JSON DEFAULT NULL, joueur_id INT NOT NULL, UNIQUE INDEX UNIQ_7ECF1A73A9E2D76C (joueur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE video (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, game_type VARCHAR(100) DEFAULT NULL, file_path VARCHAR(255) NOT NULL, public_id VARCHAR(255) DEFAULT NULL, duration DOUBLE PRECISION DEFAULT NULL, resolution VARCHAR(50) DEFAULT NULL, fps DOUBLE PRECISION DEFAULT NULL, uploaded_at DATETIME NOT NULL, status VARCHAR(255) NOT NULL, visibility VARCHAR(10) NOT NULL, type VARCHAR(20) NOT NULL, match_external_id VARCHAR(120) DEFAULT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, kill_info LONGTEXT DEFAULT NULL, likes_count INT NOT NULL, metadata_json LONGTEXT DEFAULT NULL, highlight_id INT DEFAULT NULL, uploaded_by_id INT NOT NULL, INDEX IDX_7CC7DA2CF216DCD4 (highlight_id), INDEX IDX_7CC7DA2CA2B28FE8 (uploaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9DE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE channel_invite ADD CONSTRAINT FK_39812AA972F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE channel_join_request ADD CONSTRAINT FK_A5422E5E72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE channel_join_request ADD CONSTRAINT FK_A5422E5EED442CF4 FOREIGN KEY (requester_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE channel_member ADD CONSTRAINT FK_8E87C00672F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE channel_member ADD CONSTRAINT FK_8E87C006A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE coach_application ADD CONSTRAINT FK_80818310A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE coin_purchase ADD CONSTRAINT FK_ABE21FE0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE complaint ADD CONSTRAINT FK_5F2732B579F7D87D FOREIGN KEY (submitted_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE complaint ADD CONSTRAINT FK_5F2732B5F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE guide_video ADD CONSTRAINT FK_38DA1164A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE guide_video ADD CONSTRAINT FK_38DA1164E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE guide_video ADD CONSTRAINT FK_38DA11643414710B FOREIGN KEY (agent_id) REFERENCES agent (id)');
        $this->addSql('ALTER TABLE guide_video_likes ADD CONSTRAINT FK_6E86BC4A1C40BE2E FOREIGN KEY (guide_video_id) REFERENCES guide_video (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guide_video_likes ADD CONSTRAINT FK_6E86BC4AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE live_access ADD CONSTRAINT FK_653F9D80A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE live_access ADD CONSTRAINT FK_653F9D806AFA264E FOREIGN KEY (live_stream_id) REFERENCES live_stream (id)');
        $this->addSql('ALTER TABLE live_stream ADD CONSTRAINT FK_93BF08C83C105691 FOREIGN KEY (coach_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BAF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id)');
        $this->addSql('ALTER TABLE message_attachment ADD CONSTRAINT FK_B68FF524537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE player_stat ADD CONSTRAINT FK_82A2AF12BB1BA406 FOREIGN KEY (videomatch_id) REFERENCES video (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C64D83DF64 FOREIGN KEY (ID_planning) REFERENCES planning (IDplanning)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE team_membership ADD CONSTRAINT FK_B826A040296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE team_membership ADD CONSTRAINT FK_B826A040A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE tournoi_user ADD CONSTRAINT FK_D0703ACDF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournoi_user ADD CONSTRAINT FK_D0703ACDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training_session ADD CONSTRAINT FK_D7A45DA4D83DF64 FOREIGN KEY (ID_planning) REFERENCES planning (IDplanning)');
        $this->addSql('ALTER TABLE training_session ADD CONSTRAINT FK_D7A45DA7A95F30B FOREIGN KEY (IDcurrent_user) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE valorant_equipe ADD CONSTRAINT FK_48EE30F22ABEACD6 FOREIGN KEY (match_id) REFERENCES valorant_match (id)');
        $this->addSql('ALTER TABLE valorant_joueur ADD CONSTRAINT FK_91D623222ABEACD6 FOREIGN KEY (match_id) REFERENCES valorant_match (id)');
        $this->addSql('ALTER TABLE valorant_joueur ADD CONSTRAINT FK_91D623226D861B89 FOREIGN KEY (equipe_id) REFERENCES valorant_equipe (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE valorant_match ADD CONSTRAINT FK_DB44CF517E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE valorant_statistique ADD CONSTRAINT FK_7ECF1A73A9E2D76C FOREIGN KEY (joueur_id) REFERENCES valorant_joueur (id)');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CF216DCD4 FOREIGN KEY (highlight_id) REFERENCES video (id)');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
    }

    private function downMysql(): void
    {
        $this->addSql('ALTER TABLE agent DROP FOREIGN KEY FK_268B9C9DE48FD905');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5A76ED395');
        $this->addSql('ALTER TABLE channel_invite DROP FOREIGN KEY FK_39812AA972F5A1AA');
        $this->addSql('ALTER TABLE channel_join_request DROP FOREIGN KEY FK_A5422E5E72F5A1AA');
        $this->addSql('ALTER TABLE channel_join_request DROP FOREIGN KEY FK_A5422E5EED442CF4');
        $this->addSql('ALTER TABLE channel_member DROP FOREIGN KEY FK_8E87C00672F5A1AA');
        $this->addSql('ALTER TABLE channel_member DROP FOREIGN KEY FK_8E87C006A76ED395');
        $this->addSql('ALTER TABLE coach_application DROP FOREIGN KEY FK_80818310A76ED395');
        $this->addSql('ALTER TABLE coin_purchase DROP FOREIGN KEY FK_ABE21FE0A76ED395');
        $this->addSql('ALTER TABLE complaint DROP FOREIGN KEY FK_5F2732B579F7D87D');
        $this->addSql('ALTER TABLE complaint DROP FOREIGN KEY FK_5F2732B5F4BD7827');
        $this->addSql('ALTER TABLE guide_video DROP FOREIGN KEY FK_38DA1164A2B28FE8');
        $this->addSql('ALTER TABLE guide_video DROP FOREIGN KEY FK_38DA1164E48FD905');
        $this->addSql('ALTER TABLE guide_video DROP FOREIGN KEY FK_38DA11643414710B');
        $this->addSql('ALTER TABLE guide_video_likes DROP FOREIGN KEY FK_6E86BC4A1C40BE2E');
        $this->addSql('ALTER TABLE guide_video_likes DROP FOREIGN KEY FK_6E86BC4AA76ED395');
        $this->addSql('ALTER TABLE live_access DROP FOREIGN KEY FK_653F9D80A76ED395');
        $this->addSql('ALTER TABLE live_access DROP FOREIGN KEY FK_653F9D806AFA264E');
        $this->addSql('ALTER TABLE live_stream DROP FOREIGN KEY FK_93BF08C83C105691');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BAF607770A');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F72F5A1AA');
        $this->addSql('ALTER TABLE message_attachment DROP FOREIGN KEY FK_B68FF524537A1329');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_3967A216A76ED395');
        $this->addSql('ALTER TABLE player_stat DROP FOREIGN KEY FK_82A2AF12BB1BA406');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C64D83DF64');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F7E3C61F9');
        $this->addSql('ALTER TABLE team_membership DROP FOREIGN KEY FK_B826A040296CD8AE');
        $this->addSql('ALTER TABLE team_membership DROP FOREIGN KEY FK_B826A040A76ED395');
        $this->addSql('ALTER TABLE tournoi_user DROP FOREIGN KEY FK_D0703ACDF607770A');
        $this->addSql('ALTER TABLE tournoi_user DROP FOREIGN KEY FK_D0703ACDA76ED395');
        $this->addSql('ALTER TABLE training_session DROP FOREIGN KEY FK_D7A45DA4D83DF64');
        $this->addSql('ALTER TABLE training_session DROP FOREIGN KEY FK_D7A45DA7A95F30B');
        $this->addSql('ALTER TABLE valorant_equipe DROP FOREIGN KEY FK_48EE30F22ABEACD6');
        $this->addSql('ALTER TABLE valorant_joueur DROP FOREIGN KEY FK_91D623222ABEACD6');
        $this->addSql('ALTER TABLE valorant_joueur DROP FOREIGN KEY FK_91D623226D861B89');
        $this->addSql('ALTER TABLE valorant_match DROP FOREIGN KEY FK_DB44CF517E3C61F9');
        $this->addSql('ALTER TABLE valorant_statistique DROP FOREIGN KEY FK_7ECF1A73A9E2D76C');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2CF216DCD4');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2CA2B28FE8');
        $this->addSql('DROP TABLE agent');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE channel');
        $this->addSql('DROP TABLE channel_invite');
        $this->addSql('DROP TABLE channel_join_request');
        $this->addSql('DROP TABLE channel_member');
        $this->addSql('DROP TABLE coach_application');
        $this->addSql('DROP TABLE coin_purchase');
        $this->addSql('DROP TABLE complaint');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE guide_video');
        $this->addSql('DROP TABLE guide_video_likes');
        $this->addSql('DROP TABLE live_access');
        $this->addSql('DROP TABLE live_stream');
        $this->addSql('DROP TABLE matches');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE message_attachment');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('DROP TABLE planning');
        $this->addSql('DROP TABLE player_stat');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_membership');
        $this->addSql('DROP TABLE tournoi');
        $this->addSql('DROP TABLE tournoi_user');
        $this->addSql('DROP TABLE training_session');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE valorant_equipe');
        $this->addSql('DROP TABLE valorant_joueur');
        $this->addSql('DROP TABLE valorant_match');
        $this->addSql('DROP TABLE valorant_statistique');
        $this->addSql('DROP TABLE video');
        $this->addSql('DROP TABLE messenger_messages');
    }

    private function upPostgres(): void
    {
        $conn = $this->connection;

        $conn->executeStatement('CREATE TABLE agent (id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL, image VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) NOT NULL, game_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_268B9C9DE48FD905 ON agent (game_id)');

        $conn->executeStatement('CREATE TABLE audit_log (id SERIAL PRIMARY KEY, action VARCHAR(50) NOT NULL, entity_type VARCHAR(100) NOT NULL, entity_id INT DEFAULT NULL, details TEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at TIMESTAMP(0) NOT NULL, user_id INT DEFAULT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_F6E1C0F5A76ED395 ON audit_log (user_id)');
        $conn->executeStatement('CREATE INDEX idx_audit_created_at ON audit_log (created_at)');
        $conn->executeStatement('CREATE INDEX idx_audit_entity_type ON audit_log (entity_type)');

        $conn->executeStatement('CREATE TABLE channel (id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, game VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, is_active SMALLINT NOT NULL, image_url VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) NOT NULL, created_by VARCHAR(100) NOT NULL, approved_by VARCHAR(100) DEFAULT NULL, approved_at TIMESTAMP(0) DEFAULT NULL, rejection_reason TEXT DEFAULT NULL)');

        $conn->executeStatement('CREATE TABLE channel_invite (id SERIAL PRIMARY KEY, token VARCHAR(64) NOT NULL, created_by_email VARCHAR(255) NOT NULL, expires_at TIMESTAMP(0) DEFAULT NULL, mode VARCHAR(255) NOT NULL, max_uses INT DEFAULT NULL, uses INT DEFAULT NULL, is_active SMALLINT NOT NULL, channel_id INT NOT NULL)');
        $conn->executeStatement('CREATE UNIQUE INDEX UNIQ_39812AA95F37A13B ON channel_invite (token)');
        $conn->executeStatement('CREATE INDEX IDX_39812AA972F5A1AA ON channel_invite (channel_id)');

        $conn->executeStatement('CREATE TABLE channel_join_request (id SERIAL PRIMARY KEY, status VARCHAR(255) NOT NULL, requested_at TIMESTAMP(0) NOT NULL, decided_at TIMESTAMP(0) DEFAULT NULL, decided_by_email VARCHAR(255) DEFAULT NULL, reason TEXT DEFAULT NULL, channel_id INT NOT NULL, requester_id INT DEFAULT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_A5422E5E72F5A1AA ON channel_join_request (channel_id)');
        $conn->executeStatement('CREATE INDEX IDX_A5422E5EED442CF4 ON channel_join_request (requester_id)');

        $conn->executeStatement('CREATE TABLE channel_member (id SERIAL PRIMARY KEY, joined_at TIMESTAMP(0) NOT NULL, channel_id INT NOT NULL, user_id INT DEFAULT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_8E87C00672F5A1AA ON channel_member (channel_id)');
        $conn->executeStatement('CREATE INDEX IDX_8E87C006A76ED395 ON channel_member (user_id)');

        $conn->executeStatement('CREATE TABLE coach_application (id SERIAL PRIMARY KEY, status VARCHAR(20) NOT NULL, certifications TEXT NOT NULL, experience TEXT NOT NULL, submitted_at TIMESTAMP(0) NOT NULL, reviewed_at TIMESTAMP(0) DEFAULT NULL, review_comment TEXT DEFAULT NULL, documents VARCHAR(255) DEFAULT NULL, cv_file VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_80818310A76ED395 ON coach_application (user_id)');

        $conn->executeStatement('CREATE TABLE coin_purchase (id SERIAL PRIMARY KEY, coins_amount INT NOT NULL, price_paid NUMERIC(10, 2) NOT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) NOT NULL, completed_at TIMESTAMP(0) DEFAULT NULL, user_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_ABE21FE0A76ED395 ON coin_purchase (user_id)');

        $conn->executeStatement('CREATE TABLE complaint (id SERIAL PRIMARY KEY, subject VARCHAR(200) NOT NULL, description TEXT NOT NULL, category VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) NOT NULL, updated_at TIMESTAMP(0) DEFAULT NULL, resolved_at TIMESTAMP(0) DEFAULT NULL, admin_response TEXT DEFAULT NULL, resolution_notes TEXT DEFAULT NULL, attachment_path VARCHAR(255) DEFAULT NULL, sentiment_label VARCHAR(20) DEFAULT NULL, sentiment_score DOUBLE PRECISION DEFAULT NULL, sentiment_source VARCHAR(20) DEFAULT NULL, sentiment_priority_suggestion VARCHAR(20) DEFAULT NULL, submitted_by_id INT NOT NULL, assigned_to_id INT DEFAULT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_5F2732B579F7D87D ON complaint (submitted_by_id)');
        $conn->executeStatement('CREATE INDEX IDX_5F2732B5F4BD7827 ON complaint (assigned_to_id)');

        $conn->executeStatement('CREATE TABLE game (id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL, icon VARCHAR(255) DEFAULT NULL, color VARCHAR(7) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) NOT NULL)');
        $conn->executeStatement('CREATE UNIQUE INDEX UNIQ_232B318C5E237E06 ON game (name)');
        $conn->executeStatement('CREATE UNIQUE INDEX UNIQ_232B318C989D9B62 ON game (slug)');

        $conn->executeStatement('CREATE TABLE guide_video (id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, video_url VARCHAR(255) NOT NULL, thumbnail VARCHAR(255) DEFAULT NULL, map VARCHAR(50) NOT NULL, likes INT NOT NULL, views INT NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) NOT NULL, approved_at TIMESTAMP(0) DEFAULT NULL, uploaded_by_id INT NOT NULL, game_id INT NOT NULL, agent_id INT DEFAULT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_38DA1164A2B28FE8 ON guide_video (uploaded_by_id)');
        $conn->executeStatement('CREATE INDEX IDX_38DA1164E48FD905 ON guide_video (game_id)');
        $conn->executeStatement('CREATE INDEX IDX_38DA11643414710B ON guide_video (agent_id)');

        $conn->executeStatement('CREATE TABLE guide_video_likes (guide_video_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (guide_video_id, user_id))');
        $conn->executeStatement('CREATE INDEX IDX_6E86BC4A1C40BE2E ON guide_video_likes (guide_video_id)');
        $conn->executeStatement('CREATE INDEX IDX_6E86BC4AA76ED395 ON guide_video_likes (user_id)');

        $conn->executeStatement('CREATE TABLE live_access (id SERIAL PRIMARY KEY, coins_spent INT NOT NULL, purchased_at TIMESTAMP(0) NOT NULL, user_id INT NOT NULL, live_stream_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_653F9D80A76ED395 ON live_access (user_id)');
        $conn->executeStatement('CREATE INDEX IDX_653F9D806AFA264E ON live_access (live_stream_id)');
        $conn->executeStatement('CREATE UNIQUE INDEX unique_user_live ON live_access (user_id, live_stream_id)');

        $conn->executeStatement('CREATE TABLE live_stream (id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, coin_price INT NOT NULL, status VARCHAR(50) NOT NULL, stream_key VARCHAR(191) NOT NULL, created_at TIMESTAMP(0) NOT NULL, started_at TIMESTAMP(0) DEFAULT NULL, ended_at TIMESTAMP(0) DEFAULT NULL, coach_id INT NOT NULL)');
        $conn->executeStatement('CREATE UNIQUE INDEX UNIQ_93BF08C820F533D7 ON live_stream (stream_key)');
        $conn->executeStatement('CREATE INDEX IDX_93BF08C83C105691 ON live_stream (coach_id)');

        $conn->executeStatement('CREATE TABLE matches (id SERIAL PRIMARY KEY, equipe1 VARCHAR(255) NOT NULL, equipe2 VARCHAR(255) NOT NULL, score INT NOT NULL, date_match DATE NOT NULL, prix VARCHAR(255) NOT NULL, play_mode VARCHAR(20) DEFAULT \'En Ligne\' NOT NULL, localisation VARCHAR(255) DEFAULT NULL, tournoi_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_62615BAF607770A ON matches (tournoi_id)');

        $conn->executeStatement('CREATE TABLE message (id SERIAL PRIMARY KEY, content TEXT NOT NULL, sent_at TIMESTAMP(0) NOT NULL, edited_at TIMESTAMP(0) NOT NULL, is_deleted SMALLINT NOT NULL, sender_name VARCHAR(100) NOT NULL, sender_email VARCHAR(255) NOT NULL, channel_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_B6BD307F72F5A1AA ON message (channel_id)');

        $conn->executeStatement('CREATE TABLE message_attachment (id SERIAL PRIMARY KEY, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, size INT NOT NULL, url VARCHAR(255) DEFAULT NULL, public_id VARCHAR(255) DEFAULT NULL, cloud_resource_type VARCHAR(20) DEFAULT NULL, message_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_B68FF524537A1329 ON message_attachment (message_id)');

        $conn->executeStatement('CREATE TABLE notification (id SERIAL PRIMARY KEY, type VARCHAR(50) NOT NULL, message TEXT NOT NULL, is_read SMALLINT NOT NULL, created_at TIMESTAMP(0) NOT NULL, link VARCHAR(255) DEFAULT NULL, "read" SMALLINT NOT NULL, user_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)');

        $conn->executeStatement('CREATE TABLE password_reset_tokens (id SERIAL PRIMARY KEY, token VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) NOT NULL, expires_at TIMESTAMP(0) NOT NULL, channel VARCHAR(20) NOT NULL, used SMALLINT NOT NULL, user_id INT NOT NULL)');
        $conn->executeStatement('CREATE UNIQUE INDEX UNIQ_3967A2165F37A13B ON password_reset_tokens (token)');
        $conn->executeStatement('CREATE INDEX IDX_3967A216A76ED395 ON password_reset_tokens (user_id)');

        $conn->executeStatement('CREATE TABLE planning ("IDplanning" SERIAL PRIMARY KEY, image VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, time TIME NOT NULL, localisation VARCHAR(255) NOT NULL, description TEXT NOT NULL, need_partner SMALLINT NOT NULL, level VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL)');

        $conn->executeStatement('CREATE TABLE player_stat (id SERIAL PRIMARY KEY, score INT NOT NULL, accuracy DOUBLE PRECISION NOT NULL, actions_count INT NOT NULL, videomatch_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_82A2AF12BB1BA406 ON player_stat (videomatch_id)');

        $conn->executeStatement('CREATE TABLE review (id SERIAL PRIMARY KEY, content TEXT NOT NULL, rating INT NOT NULL, created_at TIMESTAMP(0) NOT NULL, sentiment VARCHAR(20) DEFAULT NULL, user_id INT NOT NULL, "ID_planning" INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_794381C6A76ED395 ON review (user_id)');
        $conn->executeStatement('CREATE INDEX IDX_794381C64D83DF64 ON review ("ID_planning")');

        $conn->executeStatement('CREATE TABLE team (id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) NOT NULL, max_members INT NOT NULL, is_active SMALLINT NOT NULL, owner_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_C4E0A61F7E3C61F9 ON team (owner_id)');

        $conn->executeStatement('CREATE TABLE team_membership (id SERIAL PRIMARY KEY, role VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, invited_at TIMESTAMP(0) DEFAULT NULL, joined_at TIMESTAMP(0) DEFAULT NULL, team_id INT NOT NULL, user_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_B826A040296CD8AE ON team_membership (team_id)');
        $conn->executeStatement('CREATE INDEX IDX_B826A040A76ED395 ON team_membership (user_id)');

        $conn->executeStatement('CREATE TABLE tournoi (id SERIAL PRIMARY KEY, nom VARCHAR(255) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, description TEXT DEFAULT NULL, image VARCHAR(255) NOT NULL, type_tournoi VARCHAR(255) NOT NULL, prix DOUBLE PRECISION DEFAULT 0 NOT NULL)');

        $conn->executeStatement('CREATE TABLE tournoi_user (tournoi_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (tournoi_id, user_id))');
        $conn->executeStatement('CREATE INDEX IDX_D0703ACDF607770A ON tournoi_user (tournoi_id)');
        $conn->executeStatement('CREATE INDEX IDX_D0703ACDA76ED395 ON tournoi_user (user_id)');

        $conn->executeStatement('CREATE TABLE training_session (idtraining SERIAL PRIMARY KEY, status VARCHAR(50) NOT NULL, joined_at TIMESTAMP(0) NOT NULL, "ID_planning" INT NOT NULL, "IDcurrent_user" INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_D7A45DA4D83DF64 ON training_session ("ID_planning")');
        $conn->executeStatement('CREATE INDEX IDX_D7A45DA7A95F30B ON training_session ("IDcurrent_user")');
        
        $conn->executeStatement('CREATE TABLE "user" (id SERIAL PRIMARY KEY, email VARCHAR(180) NOT NULL, username VARCHAR(50) NOT NULL, roles_json TEXT NOT NULL, password VARCHAR(255) NOT NULL, account_status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) NOT NULL, last_login TIMESTAMP(0) NOT NULL, full_name VARCHAR(100) DEFAULT NULL, bio VARCHAR(255) DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, coin_balance INT DEFAULT 0 NOT NULL, totp_secret VARCHAR(255) DEFAULT NULL, is_totp_enabled BOOLEAN DEFAULT FALSE NOT NULL, backup_codes_json TEXT DEFAULT NULL, totp_enabled_at TIMESTAMP(0) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, telegram_chat_id VARCHAR(100) DEFAULT NULL, face_descriptor TEXT DEFAULT NULL, face_image VARCHAR(255) DEFAULT NULL)');
        $conn->executeStatement('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $conn->executeStatement('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');

        $conn->executeStatement('CREATE TABLE valorant_equipe (id SERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, side VARCHAR(30) DEFAULT NULL, score INT DEFAULT NULL, match_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_48EE30F22ABEACD6 ON valorant_equipe (match_id)');

        $conn->executeStatement('CREATE TABLE valorant_joueur (id SERIAL PRIMARY KEY, tracker_player_id VARCHAR(120) DEFAULT NULL, riot_name VARCHAR(120) NOT NULL, riot_tag VARCHAR(20) DEFAULT NULL, agent VARCHAR(50) DEFAULT NULL, match_id INT NOT NULL, equipe_id INT DEFAULT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_91D623222ABEACD6 ON valorant_joueur (match_id)');
        $conn->executeStatement('CREATE INDEX IDX_91D623226D861B89 ON valorant_joueur (equipe_id)');

        $conn->executeStatement('CREATE TABLE valorant_match (id SERIAL PRIMARY KEY, tracker_match_id VARCHAR(120) NOT NULL, map_name VARCHAR(120) DEFAULT NULL, mode VARCHAR(120) DEFAULT NULL, played_at TIMESTAMP(0) DEFAULT NULL, duration_seconds INT DEFAULT NULL, score_team_a INT DEFAULT NULL, score_team_b INT DEFAULT NULL, status VARCHAR(20) NOT NULL, raw_data JSONB DEFAULT NULL, created_at TIMESTAMP(0) NOT NULL, updated_at TIMESTAMP(0) NOT NULL, archived_at TIMESTAMP(0) DEFAULT NULL, owner_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_DB44CF517E3C61F9 ON valorant_match (owner_id)');
        $conn->executeStatement('CREATE UNIQUE INDEX uniq_valorant_match_owner ON valorant_match (owner_id, tracker_match_id)');

        $conn->executeStatement('CREATE TABLE valorant_statistique (id SERIAL PRIMARY KEY, kills INT NOT NULL, deaths INT NOT NULL, assists INT NOT NULL, headshots INT DEFAULT NULL, damage INT DEFAULT NULL, weapons JSONB DEFAULT NULL, timings JSONB DEFAULT NULL, extra JSONB DEFAULT NULL, joueur_id INT NOT NULL)');
        $conn->executeStatement('CREATE UNIQUE INDEX UNIQ_7ECF1A73A9E2D76C ON valorant_statistique (joueur_id)');

        $conn->executeStatement('CREATE TABLE video (id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, game_type VARCHAR(100) DEFAULT NULL, file_path VARCHAR(255) NOT NULL, public_id VARCHAR(255) DEFAULT NULL, duration DOUBLE PRECISION DEFAULT NULL, resolution VARCHAR(50) DEFAULT NULL, fps DOUBLE PRECISION DEFAULT NULL, uploaded_at TIMESTAMP(0) NOT NULL, status VARCHAR(255) NOT NULL, visibility VARCHAR(10) NOT NULL, type VARCHAR(20) NOT NULL, match_external_id VARCHAR(120) DEFAULT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, kill_info TEXT DEFAULT NULL, likes_count INT NOT NULL, metadata_json TEXT DEFAULT NULL, highlight_id INT DEFAULT NULL, uploaded_by_id INT NOT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_7CC7DA2CF216DCD4 ON video (highlight_id)');
        $conn->executeStatement('CREATE INDEX IDX_7CC7DA2CA2B28FE8 ON video (uploaded_by_id)');

        $conn->executeStatement('CREATE TABLE messenger_messages (id BIGSERIAL PRIMARY KEY, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) NOT NULL, available_at TIMESTAMP(0) NOT NULL, delivered_at TIMESTAMP(0) DEFAULT NULL)');
        $conn->executeStatement('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name, available_at, delivered_at, id)');

        $conn->executeStatement('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9DE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $conn->executeStatement('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE channel_invite ADD CONSTRAINT FK_39812AA972F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE channel_join_request ADD CONSTRAINT FK_A5422E5E72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE channel_join_request ADD CONSTRAINT FK_A5422E5EED442CF4 FOREIGN KEY (requester_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE channel_member ADD CONSTRAINT FK_8E87C00672F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE channel_member ADD CONSTRAINT FK_8E87C006A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE coach_application ADD CONSTRAINT FK_80818310A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE coin_purchase ADD CONSTRAINT FK_ABE21FE0A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE complaint ADD CONSTRAINT FK_5F2732B579F7D87D FOREIGN KEY (submitted_by_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE complaint ADD CONSTRAINT FK_5F2732B5F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE guide_video ADD CONSTRAINT FK_38DA1164A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE guide_video ADD CONSTRAINT FK_38DA1164E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $conn->executeStatement('ALTER TABLE guide_video ADD CONSTRAINT FK_38DA11643414710B FOREIGN KEY (agent_id) REFERENCES agent (id)');
        $conn->executeStatement('ALTER TABLE guide_video_likes ADD CONSTRAINT FK_6E86BC4A1C40BE2E FOREIGN KEY (guide_video_id) REFERENCES guide_video (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE guide_video_likes ADD CONSTRAINT FK_6E86BC4AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE live_access ADD CONSTRAINT FK_653F9D80A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE live_access ADD CONSTRAINT FK_653F9D806AFA264E FOREIGN KEY (live_stream_id) REFERENCES live_stream (id)');
        $conn->executeStatement('ALTER TABLE live_stream ADD CONSTRAINT FK_93BF08C83C105691 FOREIGN KEY (coach_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE matches ADD CONSTRAINT FK_62615BAF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $conn->executeStatement('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id)');
        $conn->executeStatement('ALTER TABLE message_attachment ADD CONSTRAINT FK_B68FF524537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE player_stat ADD CONSTRAINT FK_82A2AF12BB1BA406 FOREIGN KEY (videomatch_id) REFERENCES video (id)');
        $conn->executeStatement('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE review ADD CONSTRAINT FK_794381C64D83DF64 FOREIGN KEY ("ID_planning") REFERENCES planning ("IDplanning")');
        $conn->executeStatement('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE team_membership ADD CONSTRAINT FK_B826A040296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $conn->executeStatement('ALTER TABLE team_membership ADD CONSTRAINT FK_B826A040A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE tournoi_user ADD CONSTRAINT FK_D0703ACDF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE tournoi_user ADD CONSTRAINT FK_D0703ACDA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE training_session ADD CONSTRAINT FK_D7A45DA4D83DF64 FOREIGN KEY ("ID_planning") REFERENCES planning ("IDplanning")');
        $conn->executeStatement('ALTER TABLE training_session ADD CONSTRAINT FK_D7A45DA7A95F30B FOREIGN KEY ("IDcurrent_user") REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE valorant_equipe ADD CONSTRAINT FK_48EE30F22ABEACD6 FOREIGN KEY (match_id) REFERENCES valorant_match (id)');
        $conn->executeStatement('ALTER TABLE valorant_joueur ADD CONSTRAINT FK_91D623222ABEACD6 FOREIGN KEY (match_id) REFERENCES valorant_match (id)');
        $conn->executeStatement('ALTER TABLE valorant_joueur ADD CONSTRAINT FK_91D623226D861B89 FOREIGN KEY (equipe_id) REFERENCES valorant_equipe (id) ON DELETE SET NULL');
        $conn->executeStatement('ALTER TABLE valorant_match ADD CONSTRAINT FK_DB44CF517E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id)');
        $conn->executeStatement('ALTER TABLE valorant_statistique ADD CONSTRAINT FK_7ECF1A73A9E2D76C FOREIGN KEY (joueur_id) REFERENCES valorant_joueur (id)');
        $conn->executeStatement('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CF216DCD4 FOREIGN KEY (highlight_id) REFERENCES video (id)');
        $conn->executeStatement('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES "user" (id)');
    }

    private function downPostgres(): void
    {
        $tablesWithFk = [
            'agent' => ['FK_268B9C9DE48FD905'], 'audit_log' => ['FK_F6E1C0F5A76ED395'], 'channel_invite' => ['FK_39812AA972F5A1AA'],
            'channel_join_request' => ['FK_A5422E5E72F5A1AA', 'FK_A5422E5EED442CF4'], 'channel_member' => ['FK_8E87C00672F5A1AA', 'FK_8E87C006A76ED395'],
            'coach_application' => ['FK_80818310A76ED395'], 'coin_purchase' => ['FK_ABE21FE0A76ED395'], 'complaint' => ['FK_5F2732B579F7D87D', 'FK_5F2732B5F4BD7827'],
            'guide_video' => ['FK_38DA1164A2B28FE8', 'FK_38DA1164E48FD905', 'FK_38DA11643414710B'], 'guide_video_likes' => ['FK_6E86BC4A1C40BE2E', 'FK_6E86BC4AA76ED395'],
            'live_access' => ['FK_653F9D80A76ED395', 'FK_653F9D806AFA264E'], 'live_stream' => ['FK_93BF08C83C105691'], 'matches' => ['FK_62615BAF607770A'],
            'message' => ['FK_B6BD307F72F5A1AA'], 'message_attachment' => ['FK_B68FF524537A1329'], 'notification' => ['FK_BF5476CAA76ED395'],
            'password_reset_tokens' => ['FK_3967A216A76ED395'], 'player_stat' => ['FK_82A2AF12BB1BA406'], 'review' => ['FK_794381C6A76ED395', 'FK_794381C64D83DF64'],
            'team' => ['FK_C4E0A61F7E3C61F9'], 'team_membership' => ['FK_B826A040296CD8AE', 'FK_B826A040A76ED395'],
            'tournoi_user' => ['FK_D0703ACDF607770A', 'FK_D0703ACDA76ED395'], 'training_session' => ['FK_D7A45DA4D83DF64', 'FK_D7A45DA7A95F30B'],
            'valorant_equipe' => ['FK_48EE30F22ABEACD6'], 'valorant_joueur' => ['FK_91D623222ABEACD6', 'FK_91D623226D861B89'],
            'valorant_match' => ['FK_DB44CF517E3C61F9'], 'valorant_statistique' => ['FK_7ECF1A73A9E2D76C'],
            'video' => ['FK_7CC7DA2CF216DCD4', 'FK_7CC7DA2CA2B28FE8'],
        ];
        foreach ($tablesWithFk as $table => $fks) {
            $quotedTable = $table === 'user' ? '"user"' : $table;
            foreach ($fks as $fk) {
                $this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' DROP CONSTRAINT IF EXISTS ' . $fk);
            }
        }
        $allTables = ['agent', 'audit_log', 'channel', 'channel_invite', 'channel_join_request', 'channel_member', 'coach_application', 'coin_purchase', 'complaint', 'game', 'guide_video', 'guide_video_likes', 'live_access', 'live_stream', 'matches', 'message', 'message_attachment', 'notification', 'password_reset_tokens', 'planning', 'player_stat', 'review', 'team', 'team_membership', 'tournoi', 'tournoi_user', 'training_session', 'valorant_equipe', 'valorant_joueur', 'valorant_match', 'valorant_statistique', 'video', 'user', 'messenger_messages'];
        foreach (array_reverse($allTables) as $table) {
            $quoted = $table === 'user' ? '"user"' : $table;
            $this->connection->executeStatement('DROP TABLE IF EXISTS ' . $quoted . ' CASCADE');
        }
    }
}
