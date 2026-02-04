<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Je supprime les tables liées aux anciennes fonctionnalités sociales (amis, blocage, notifications, signalements, wishlist).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');

        $this->addSql('DROP TABLE IF EXISTS friendship');
        $this->addSql('DROP TABLE IF EXISTS blocked_user');
        $this->addSql('DROP TABLE IF EXISTS notification');
        $this->addSql('DROP TABLE IF EXISTS report');
        $this->addSql('DROP TABLE IF EXISTS wishlist');

        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');

        $this->addSql('CREATE TABLE blocked_user (id INT AUTO_INCREMENT NOT NULL, blocker_id INT NOT NULL, blocked_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7E55B9F88BDBA0D9 (blocker_id), INDEX IDX_7E55B9F8B57A55B0 (blocked_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE friendship (id INT AUTO_INCREMENT NOT NULL, requester_id INT NOT NULL, addressee_id INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_66E9F6E4B6FD4F9 (requester_id), INDEX IDX_66E9F6EECE6E (addressee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF5476CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, reporter_id INT NOT NULL, target_user_id INT DEFAULT NULL, target_comment_id INT DEFAULT NULL, reason VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C42F778B9BBEDC1A (reporter_id), INDEX IDX_C42F778B3D035E56 (target_user_id), INDEX IDX_C42F778B2B7F1F54 (target_comment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wishlist (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7A63D3C1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE blocked_user ADD CONSTRAINT FK_7E55B9F88BDBA0D9 FOREIGN KEY (blocker_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE blocked_user ADD CONSTRAINT FK_7E55B9F8B57A55B0 FOREIGN KEY (blocked_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT FK_66E9F6E4B6FD4F9 FOREIGN KEY (requester_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT FK_66E9F6EECE6E FOREIGN KEY (addressee_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778B9BBEDC1A FOREIGN KEY (reporter_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778B3D035E56 FOREIGN KEY (target_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778B2B7F1F54 FOREIGN KEY (target_comment_id) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE wishlist ADD CONSTRAINT FK_7A63D3C1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
