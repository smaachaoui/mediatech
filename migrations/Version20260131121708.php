<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131121708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blocked_user (id INT AUTO_INCREMENT NOT NULL, blocker_id INT NOT NULL, blocked_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_718E1137548D5975 (blocker_id), INDEX IDX_718E113721FF5136 (blocked_id), UNIQUE INDEX uniq_blocked_user_pair (blocker_id, blocked_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE blocked_user ADD CONSTRAINT FK_718E1137548D5975 FOREIGN KEY (blocker_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE blocked_user ADD CONSTRAINT FK_718E113721FF5136 FOREIGN KEY (blocked_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blocked_user DROP FOREIGN KEY FK_718E1137548D5975');
        $this->addSql('ALTER TABLE blocked_user DROP FOREIGN KEY FK_718E113721FF5136');
        $this->addSql('DROP TABLE blocked_user');
    }
}
