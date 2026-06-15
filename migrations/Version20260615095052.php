<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260615095052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // 1. Mettre à jour les FK avant de supprimer la table patients
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY `FK_6A41727A6B899279`');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A6B899279 FOREIGN KEY (patient_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE prescriptions DROP FOREIGN KEY `FK_E41E1AC36B899279`');
        $this->addSql('ALTER TABLE prescriptions ADD CONSTRAINT FK_E41E1AC36B899279 FOREIGN KEY (patient_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY `FK_6970EB0F6B899279`');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F6B899279 FOREIGN KEY (patient_id) REFERENCES users (id)');
        // 2. Supprimer la table patients (plus aucune FK ne la référence)
        $this->addSql('ALTER TABLE patients DROP FOREIGN KEY `FK_2CCC2E2CA76ED395`');
        $this->addSql('DROP TABLE patients');
        // 3. Créer la nouvelle table patients_history
        $this->addSql('CREATE TABLE patients_history (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, user_id INT NOT NULL, doctor_id INT NOT NULL, INDEX IDX_B2B824A76ED395 (user_id), INDEX IDX_B2B82487F4FB17 (doctor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE patients_history ADD CONSTRAINT FK_B2B824A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patients_history ADD CONSTRAINT FK_B2B82487F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE patients (id INT AUTO_INCREMENT NOT NULL, medical_history LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, user_id INT NOT NULL, UNIQUE INDEX UNIQ_2CCC2E2CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT `FK_2CCC2E2CA76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE patients_history DROP FOREIGN KEY FK_B2B824A76ED395');
        $this->addSql('ALTER TABLE patients_history DROP FOREIGN KEY FK_B2B82487F4FB17');
        $this->addSql('DROP TABLE patients_history');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A6B899279');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT `FK_6A41727A6B899279` FOREIGN KEY (patient_id) REFERENCES patients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE prescriptions DROP FOREIGN KEY FK_E41E1AC36B899279');
        $this->addSql('ALTER TABLE prescriptions ADD CONSTRAINT `FK_E41E1AC36B899279` FOREIGN KEY (patient_id) REFERENCES patients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F6B899279');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT `FK_6970EB0F6B899279` FOREIGN KEY (patient_id) REFERENCES patients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
