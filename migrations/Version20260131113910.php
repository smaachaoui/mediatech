<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131113910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE collection_book (id INT AUTO_INCREMENT NOT NULL, collection_id INT NOT NULL, book_id INT NOT NULL, added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_81928FDF514956FD (collection_id), INDEX IDX_81928FDF16A2B381 (book_id), UNIQUE INDEX uniq_collection_book (collection_id, book_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collection_movie (id INT AUTO_INCREMENT NOT NULL, collection_id INT NOT NULL, movie_id INT NOT NULL, added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5AA64A3C514956FD (collection_id), INDEX IDX_5AA64A3C8F93B6FC (movie_id), UNIQUE INDEX uniq_collection_movie (collection_id, movie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE collection_book ADD CONSTRAINT FK_81928FDF514956FD FOREIGN KEY (collection_id) REFERENCES collection (id)');
        $this->addSql('ALTER TABLE collection_book ADD CONSTRAINT FK_81928FDF16A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE collection_movie ADD CONSTRAINT FK_5AA64A3C514956FD FOREIGN KEY (collection_id) REFERENCES collection (id)');
        $this->addSql('ALTER TABLE collection_movie ADD CONSTRAINT FK_5AA64A3C8F93B6FC FOREIGN KEY (movie_id) REFERENCES movie (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE collection_book DROP FOREIGN KEY FK_81928FDF514956FD');
        $this->addSql('ALTER TABLE collection_book DROP FOREIGN KEY FK_81928FDF16A2B381');
        $this->addSql('ALTER TABLE collection_movie DROP FOREIGN KEY FK_5AA64A3C514956FD');
        $this->addSql('ALTER TABLE collection_movie DROP FOREIGN KEY FK_5AA64A3C8F93B6FC');
        $this->addSql('DROP TABLE collection_book');
        $this->addSql('DROP TABLE collection_movie');
    }
}
