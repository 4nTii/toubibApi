<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260615151409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD social_number VARCHAR(50) DEFAULT NULL, ADD main_doctor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9A7425CB5 FOREIGN KEY (main_doctor_id) REFERENCES doctors (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E988DB7D88 ON users (social_number)');
        $this->addSql('CREATE INDEX IDX_1483A5E9A7425CB5 ON users (main_doctor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9A7425CB5');
        $this->addSql('DROP INDEX UNIQ_1483A5E988DB7D88 ON users');
        $this->addSql('DROP INDEX IDX_1483A5E9A7425CB5 ON users');
        $this->addSql('ALTER TABLE users DROP social_number, DROP main_doctor_id');
    }
}
