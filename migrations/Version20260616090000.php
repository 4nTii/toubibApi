<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table user_cards';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_cards (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            card_holder VARCHAR(100) NOT NULL,
            card_number VARCHAR(19) NOT NULL,
            expire_date VARCHAR(7) NOT NULL,
            card_cvv VARCHAR(4) NOT NULL,
            INDEX IDX_USER_CARDS_USER (user_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE user_cards ADD CONSTRAINT FK_USER_CARDS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_cards DROP FOREIGN KEY FK_USER_CARDS_USER');
        $this->addSql('DROP TABLE user_cards');
    }
}
