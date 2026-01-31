<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131122341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, reporter_id INT NOT NULL, reported_user_id INT NOT NULL, reason LONGTEXT NOT NULL, status VARCHAR(10) NOT NULL, admin_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', treated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C42F7784E1CFE6F5 (reporter_id), INDEX IDX_C42F7784E7566E (reported_user_id), UNIQUE INDEX uniq_report_reporter_reported (reporter_id, reported_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784E1CFE6F5 FOREIGN KEY (reporter_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784E7566EE7566E FOREIGN KEY (reported_user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784E1CFE6F5');
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784E7566EE7566E');
        $this->addSql('DROP TABLE report');
    }
}
