<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Je force l\'unicité des collections par utilisateur (user_id + name) et je nettoie les doublons existants (compatible MySQL).';
    }

    public function up(Schema $schema): void
    {
        // Je supprime les doublons en conservant l'entrée avec l'id le plus faible
        $this->addSql(
            'DELETE FROM `collection`
             WHERE id NOT IN (
                 SELECT id FROM (
                     SELECT MIN(id) AS id
                     FROM `collection`
                     GROUP BY user_id, name
                 ) t
             )'
        );

        // J'ajoute la contrainte d'unicité
        $this->addSql(
            'ALTER TABLE `collection`
             ADD CONSTRAINT uniq_collection_user_name UNIQUE (user_id, name)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE `collection`
             DROP INDEX uniq_collection_user_name'
        );
    }
}
