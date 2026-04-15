<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414185829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id)');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727AAB8EAD0B FOREIGN KEY (business_site_id) REFERENCES business_sites (id)');
        $this->addSql('ALTER TABLE business_sites ADD CONSTRAINT FK_C2E14DDC98260155 FOREIGN KEY (region_id) REFERENCES regions (id)');
        $this->addSql('ALTER TABLE doctors ADD CONSTRAINT FK_B67687BEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE doctors ADD CONSTRAINT FK_B67687BE3B5A08D7 FOREIGN KEY (speciality_id) REFERENCES specialties (id)');
        $this->addSql('ALTER TABLE doctors ADD CONSTRAINT FK_B67687BEAB8EAD0B FOREIGN KEY (business_site_id) REFERENCES business_sites (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B67687BEEC7E7152 ON doctors (license_number)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointments (id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT FK_2CCC2E2CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE prescriptions ADD CONSTRAINT FK_E41E1AC387F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE prescriptions ADD CONSTRAINT FK_E41E1AC36B899279 FOREIGN KEY (patient_id) REFERENCES patients (id)');
        $this->addSql('ALTER TABLE prescriptions ADD CONSTRAINT FK_E41E1AC3E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointments (id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0FE5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointments (id)');
        $this->addSql('ALTER TABLE unavailability_slots ADD CONSTRAINT FK_7FD233D987F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE unavailability_slots ADD CONSTRAINT FK_7FD233D9AB8EAD0B FOREIGN KEY (business_site_id) REFERENCES business_sites (id)');
        $this->addSql('ALTER TABLE users_password_reset_token ADD CONSTRAINT FK_7BC3403EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A6B899279');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A87F4FB17');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727AAB8EAD0B');
        $this->addSql('ALTER TABLE business_sites DROP FOREIGN KEY FK_C2E14DDC98260155');
        $this->addSql('ALTER TABLE doctors DROP FOREIGN KEY FK_B67687BEA76ED395');
        $this->addSql('ALTER TABLE doctors DROP FOREIGN KEY FK_B67687BE3B5A08D7');
        $this->addSql('ALTER TABLE doctors DROP FOREIGN KEY FK_B67687BEAB8EAD0B');
        $this->addSql('DROP INDEX UNIQ_B67687BEEC7E7152 ON doctors');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96F624B39D');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96CD53EDB6');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96E5B533F9');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE patients DROP FOREIGN KEY FK_2CCC2E2CA76ED395');
        $this->addSql('ALTER TABLE prescriptions DROP FOREIGN KEY FK_E41E1AC387F4FB17');
        $this->addSql('ALTER TABLE prescriptions DROP FOREIGN KEY FK_E41E1AC36B899279');
        $this->addSql('ALTER TABLE prescriptions DROP FOREIGN KEY FK_E41E1AC3E5B533F9');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F6B899279');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F87F4FB17');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0FE5B533F9');
        $this->addSql('ALTER TABLE unavailability_slots DROP FOREIGN KEY FK_7FD233D987F4FB17');
        $this->addSql('ALTER TABLE unavailability_slots DROP FOREIGN KEY FK_7FD233D9AB8EAD0B');
        $this->addSql('ALTER TABLE users_password_reset_token DROP FOREIGN KEY FK_7BC3403EA76ED395');
    }
}
