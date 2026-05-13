<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414185611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial squashed schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_config (id INT AUTO_INCREMENT NOT NULL, app_name VARCHAR(150) NOT NULL, version VARCHAR(20) NOT NULL, logo VARCHAR(255) NOT NULL, maintenance TINYINT NOT NULL, update_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE appointments (id INT AUTO_INCREMENT NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, status VARCHAR(50) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, business_site_id INT NOT NULL, INDEX IDX_6A41727A6B899279 (patient_id), INDEX IDX_6A41727A87F4FB17 (doctor_id), INDEX IDX_6A41727AAB8EAD0B (business_site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE business_sites (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, address VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, location_google_map VARCHAR(255) DEFAULT NULL, region_id INT NOT NULL, INDEX IDX_C2E14DDC98260155 (region_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE doctor_business_site (id INT AUTO_INCREMENT NOT NULL, is_owner TINYINT NOT NULL, is_primary TINYINT NOT NULL, consultation_duration INT DEFAULT 30 NOT NULL, consultation_fee INT DEFAULT NULL, working_schedule JSON DEFAULT NULL, doctor_id INT NOT NULL, business_site_id INT NOT NULL, INDEX IDX_29CC33587F4FB17 (doctor_id), INDEX IDX_29CC335AB8EAD0B (business_site_id), UNIQUE INDEX unique_doctor_site (doctor_id, business_site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE doctors (id INT AUTO_INCREMENT NOT NULL, is_active TINYINT NOT NULL, license_number VARCHAR(100) NOT NULL, activity_started DATE NOT NULL, biography LONGTEXT DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, accept_new_patients TINYINT NOT NULL, teleconsultation_enabled TINYINT NOT NULL, verified TINYINT NOT NULL, user_id INT NOT NULL, speciality_id INT NOT NULL, UNIQUE INDEX UNIQ_B67687BEEC7E7152 (license_number), UNIQUE INDEX UNIQ_B67687BEA76ED395 (user_id), INDEX IDX_B67687BE3B5A08D7 (speciality_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE logging_attempt (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(45) NOT NULL, user_agent LONGTEXT DEFAULT NULL, attempted_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messages (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, sent_at DATETIME NOT NULL, is_read TINYINT NOT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, appointment_id INT DEFAULT NULL, INDEX IDX_DB021E96F624B39D (sender_id), INDEX IDX_DB021E96CD53EDB6 (receiver_id), INDEX IDX_DB021E96E5B533F9 (appointment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT DEFAULT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_6000B0D3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE patients (id INT AUTO_INCREMENT NOT NULL, medical_history LONGTEXT DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_2CCC2E2CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prescriptions (id INT AUTO_INCREMENT NOT NULL, medications LONGTEXT NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, doctor_id INT NOT NULL, patient_id INT NOT NULL, appointment_id INT DEFAULT NULL, INDEX IDX_E41E1AC387F4FB17 (doctor_id), INDEX IDX_E41E1AC36B899279 (patient_id), INDEX IDX_E41E1AC3E5B533F9 (appointment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE regions (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, country VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reviews (id INT AUTO_INCREMENT NOT NULL, rating SMALLINT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, appointment_id INT DEFAULT NULL, INDEX IDX_6970EB0F6B899279 (patient_id), INDEX IDX_6970EB0F87F4FB17 (doctor_id), INDEX IDX_6970EB0FE5B533F9 (appointment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE specialties (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE unavailability_slots (id INT AUTO_INCREMENT NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, reason VARCHAR(255) DEFAULT NULL, doctor_id INT NOT NULL, business_site_id INT NOT NULL, INDEX IDX_7FD233D987F4FB17 (doctor_id), INDEX IDX_7FD233D9AB8EAD0B (business_site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, gender VARCHAR(10) NOT NULL, email VARCHAR(180) NOT NULL, address VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, biography LONGTEXT DEFAULT NULL, birth_day DATE DEFAULT NULL, date_inscription DATETIME NOT NULL, last_login DATETIME DEFAULT NULL, role VARCHAR(20) NOT NULL, is_active TINYINT NOT NULL, is_phone_verified TINYINT NOT NULL, is_email_verified TINYINT NOT NULL, user_token VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9444F97DD (phone), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users_password_reset_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7BC3403EA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id)');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727AAB8EAD0B FOREIGN KEY (business_site_id) REFERENCES business_sites (id)');
        $this->addSql('ALTER TABLE business_sites ADD CONSTRAINT FK_C2E14DDC98260155 FOREIGN KEY (region_id) REFERENCES regions (id)');
        $this->addSql('ALTER TABLE doctor_business_site ADD CONSTRAINT FK_29CC33587F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE doctor_business_site ADD CONSTRAINT FK_29CC335AB8EAD0B FOREIGN KEY (business_site_id) REFERENCES business_sites (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE doctors ADD CONSTRAINT FK_B67687BEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE doctors ADD CONSTRAINT FK_B67687BE3B5A08D7 FOREIGN KEY (speciality_id) REFERENCES specialties (id)');
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
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A6B899279');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A87F4FB17');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727AAB8EAD0B');
        $this->addSql('ALTER TABLE business_sites DROP FOREIGN KEY FK_C2E14DDC98260155');
        $this->addSql('ALTER TABLE doctor_business_site DROP FOREIGN KEY FK_29CC33587F4FB17');
        $this->addSql('ALTER TABLE doctor_business_site DROP FOREIGN KEY FK_29CC335AB8EAD0B');
        $this->addSql('ALTER TABLE doctors DROP FOREIGN KEY FK_B67687BEA76ED395');
        $this->addSql('ALTER TABLE doctors DROP FOREIGN KEY FK_B67687BE3B5A08D7');
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
        $this->addSql('DROP TABLE app_config');
        $this->addSql('DROP TABLE appointments');
        $this->addSql('DROP TABLE business_sites');
        $this->addSql('DROP TABLE doctor_business_site');
        $this->addSql('DROP TABLE doctors');
        $this->addSql('DROP TABLE logging_attempt');
        $this->addSql('DROP TABLE messages');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE patients');
        $this->addSql('DROP TABLE prescriptions');
        $this->addSql('DROP TABLE regions');
        $this->addSql('DROP TABLE reviews');
        $this->addSql('DROP TABLE specialties');
        $this->addSql('DROP TABLE unavailability_slots');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_password_reset_token');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
