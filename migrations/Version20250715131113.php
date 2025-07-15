<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250715131113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE lobby (id INT AUTO_INCREMENT NOT NULL, relation_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, matched TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_CCE455F73256915B (relation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lobby_player (id INT AUTO_INCREMENT NOT NULL, lobby_id INT NOT NULL, user_id INT NOT NULL, is_leader TINYINT(1) NOT NULL, joined_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7D7F3054B6612FD9 (lobby_id), INDEX IDX_7D7F3054A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE map_veto (id INT AUTO_INCREMENT NOT NULL, match_id INT NOT NULL, lobby_id INT NOT NULL, map_name VARCHAR(50) NOT NULL, type VARCHAR(10) NOT NULL, round INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_247A4E392ABEACD6 (match_id), INDEX IDX_247A4E39B6612FD9 (lobby_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `match` (id INT AUTO_INCREMENT NOT NULL, lobby_a_id INT NOT NULL, lobby_b_id INT NOT NULL, stage VARCHAR(20) NOT NULL, lobby_aaccepted TINYINT(1) DEFAULT NULL, lobby_baccepted TINYINT(1) DEFAULT NULL, selected_map VARCHAR(50) DEFAULT NULL, server_info VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', started_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', finished_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7A5BC505A998B0A0 (lobby_a_id), INDEX IDX_7A5BC505BB2D1F4E (lobby_b_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE server (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, ip VARCHAR(45) NOT NULL, port INT NOT NULL, rcon_pass VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT '(DC2Type:json)', password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, steam_id VARCHAR(255) DEFAULT NULL, steam_name VARCHAR(255) DEFAULT NULL, steam_avatar VARCHAR(500) DEFAULT NULL, steam_profile_url VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lobby ADD CONSTRAINT FK_CCE455F73256915B FOREIGN KEY (relation_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lobby_player ADD CONSTRAINT FK_7D7F3054B6612FD9 FOREIGN KEY (lobby_id) REFERENCES lobby (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lobby_player ADD CONSTRAINT FK_7D7F3054A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE map_veto ADD CONSTRAINT FK_247A4E392ABEACD6 FOREIGN KEY (match_id) REFERENCES `match` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE map_veto ADD CONSTRAINT FK_247A4E39B6612FD9 FOREIGN KEY (lobby_id) REFERENCES lobby (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `match` ADD CONSTRAINT FK_7A5BC505A998B0A0 FOREIGN KEY (lobby_a_id) REFERENCES lobby (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `match` ADD CONSTRAINT FK_7A5BC505BB2D1F4E FOREIGN KEY (lobby_b_id) REFERENCES lobby (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE lobby DROP FOREIGN KEY FK_CCE455F73256915B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lobby_player DROP FOREIGN KEY FK_7D7F3054B6612FD9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lobby_player DROP FOREIGN KEY FK_7D7F3054A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE map_veto DROP FOREIGN KEY FK_247A4E392ABEACD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE map_veto DROP FOREIGN KEY FK_247A4E39B6612FD9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `match` DROP FOREIGN KEY FK_7A5BC505A998B0A0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `match` DROP FOREIGN KEY FK_7A5BC505BB2D1F4E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lobby
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lobby_player
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE map_veto
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `match`
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reset_password_request
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE server
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `user`
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
