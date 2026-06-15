<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration consolidée — schéma complet en un seul fichier.
 * Remplace les 5 migrations précédentes.
 */
final class Version20260615200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma complet consolidé : toutes les tables dans leur état final.';
    }

    public function up(Schema $schema): void
    {
        // ── Tables sans dépendances ──────────────────────────────────────────

        $this->addSql('CREATE TABLE app_config (id INT AUTO_INCREMENT NOT NULL, app_name VARCHAR(150) NOT NULL, version VARCHAR(20) NOT NULL, logo VARCHAR(255) NOT NULL, maintenance TINYINT NOT NULL, update_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('CREATE TABLE regions (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, country VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('CREATE TABLE specialties (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('CREATE TABLE logging_attempt (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(45) NOT NULL, user_agent LONGTEXT DEFAULT NULL, attempted_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // ── users (sans FK vers doctors — ajoutée après) ────────────────────

        $this->addSql('CREATE TABLE users (
            id INT AUTO_INCREMENT NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            email VARCHAR(180) NOT NULL,
            address VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            photo VARCHAR(255) DEFAULT NULL,
            biography LONGTEXT DEFAULT NULL,
            birth_day DATE DEFAULT NULL,
            date_inscription DATETIME NOT NULL,
            last_login DATETIME DEFAULT NULL,
            role VARCHAR(20) NOT NULL,
            is_active TINYINT NOT NULL,
            is_phone_verified TINYINT NOT NULL,
            is_email_verified TINYINT NOT NULL,
            force_password_change TINYINT(1) NOT NULL DEFAULT 0,
            user_token VARCHAR(255) NOT NULL,
            social_number VARCHAR(50) DEFAULT NULL,
            main_doctor_id INT DEFAULT NULL,
            UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
            UNIQUE INDEX UNIQ_1483A5E9444F97DD (phone),
            UNIQUE INDEX UNIQ_1483A5E988DB7D88 (social_number),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // ── business_sites (→ regions) ───────────────────────────────────────

        $this->addSql('CREATE TABLE business_sites (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(150) NOT NULL,
            address VARCHAR(255) NOT NULL,
            ville VARCHAR(255) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            location_google_map VARCHAR(255) DEFAULT NULL,
            region_id INT NOT NULL,
            INDEX IDX_C2E14DDC98260155 (region_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE business_sites ADD CONSTRAINT FK_C2E14DDC98260155 FOREIGN KEY (region_id) REFERENCES regions (id)');

        // ── doctors (→ users, → specialties) ────────────────────────────────

        $this->addSql('CREATE TABLE doctors (
            id INT AUTO_INCREMENT NOT NULL,
            is_active TINYINT NOT NULL,
            license_number VARCHAR(100) NOT NULL,
            activity_started DATE NOT NULL,
            biography LONGTEXT DEFAULT NULL,
            profile_picture VARCHAR(255) DEFAULT NULL,
            accept_new_patients TINYINT NOT NULL,
            teleconsultation_enabled TINYINT NOT NULL,
            verified TINYINT NOT NULL,
            user_id INT NOT NULL,
            speciality_id INT NOT NULL,
            UNIQUE INDEX UNIQ_B67687BEEC7E7152 (license_number),
            UNIQUE INDEX UNIQ_B67687BEA76ED395 (user_id),
            INDEX IDX_B67687BE3B5A08D7 (speciality_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE doctors ADD CONSTRAINT FK_B67687BEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE doctors ADD CONSTRAINT FK_B67687BE3B5A08D7 FOREIGN KEY (speciality_id) REFERENCES specialties (id)');

        // ── FK users.main_doctor_id → doctors (maintenant que doctors existe) ─

        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9A7425CB5 FOREIGN KEY (main_doctor_id) REFERENCES doctors (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E9A7425CB5 ON users (main_doctor_id)');

        // ── users_password_reset_token (→ users) ─────────────────────────────

        $this->addSql('CREATE TABLE users_password_reset_token (
            id INT AUTO_INCREMENT NOT NULL,
            token VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            user_id INT NOT NULL,
            INDEX IDX_7BC3403EA76ED395 (user_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE users_password_reset_token ADD CONSTRAINT FK_7BC3403EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        // ── doctor_business_site (→ doctors, → business_sites) ──────────────

        $this->addSql('CREATE TABLE doctor_business_site (
            id INT AUTO_INCREMENT NOT NULL,
            is_owner TINYINT NOT NULL,
            is_primary TINYINT NOT NULL,
            consultation_duration INT DEFAULT 30 NOT NULL,
            consultation_fee INT DEFAULT NULL,
            working_schedule JSON DEFAULT NULL,
            share_calendar TINYINT DEFAULT 0 NOT NULL,
            doctor_id INT NOT NULL,
            business_site_id INT NOT NULL,
            INDEX IDX_29CC33587F4FB17 (doctor_id),
            INDEX IDX_29CC335AB8EAD0B (business_site_id),
            UNIQUE INDEX unique_doctor_site (doctor_id, business_site_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE doctor_business_site ADD CONSTRAINT FK_29CC33587F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE doctor_business_site ADD CONSTRAINT FK_29CC335AB8EAD0B FOREIGN KEY (business_site_id) REFERENCES business_sites (id) ON DELETE CASCADE');

        // ── patients_history (→ users, → doctors) ────────────────────────────

        $this->addSql('CREATE TABLE patients_history (
            id INT AUTO_INCREMENT NOT NULL,
            date DATETIME NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            user_id INT NOT NULL,
            doctor_id INT NOT NULL,
            INDEX IDX_B2B824A76ED395 (user_id),
            INDEX IDX_B2B82487F4FB17 (doctor_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE patients_history ADD CONSTRAINT FK_B2B824A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patients_history ADD CONSTRAINT FK_B2B82487F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE');

        // ── appointments (→ users, → doctors, → business_sites) ─────────────

        $this->addSql('CREATE TABLE appointments (
            id INT AUTO_INCREMENT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            status VARCHAR(50) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            business_site_id INT NOT NULL,
            INDEX IDX_6A41727A6B899279 (patient_id),
            INDEX IDX_6A41727A87F4FB17 (doctor_id),
            INDEX IDX_6A41727AAB8EAD0B (business_site_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A6B899279 FOREIGN KEY (patient_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727AAB8EAD0B FOREIGN KEY (business_site_id) REFERENCES business_sites (id)');

        // ── prescriptions (→ doctors, → users, → appointments) ───────────────

        $this->addSql('CREATE TABLE prescriptions (
            id INT AUTO_INCREMENT NOT NULL,
            medications LONGTEXT NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            doctor_id INT NOT NULL,
            patient_id INT NOT NULL,
            appointment_id INT DEFAULT NULL,
            INDEX IDX_E41E1AC387F4FB17 (doctor_id),
            INDEX IDX_E41E1AC36B899279 (patient_id),
            INDEX IDX_E41E1AC3E5B533F9 (appointment_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE prescriptions ADD CONSTRAINT FK_E41E1AC387F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE prescriptions ADD CONSTRAINT FK_E41E1AC36B899279 FOREIGN KEY (patient_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE prescriptions ADD CONSTRAINT FK_E41E1AC3E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointments (id)');

        // ── reviews (→ users, → doctors, → appointments) ─────────────────────

        $this->addSql('CREATE TABLE reviews (
            id INT AUTO_INCREMENT NOT NULL,
            rating SMALLINT NOT NULL,
            comment LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            appointment_id INT DEFAULT NULL,
            INDEX IDX_6970EB0F6B899279 (patient_id),
            INDEX IDX_6970EB0F87F4FB17 (doctor_id),
            INDEX IDX_6970EB0FE5B533F9 (appointment_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F6B899279 FOREIGN KEY (patient_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0FE5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointments (id)');

        // ── messages (→ users, → appointments) ───────────────────────────────

        $this->addSql('CREATE TABLE messages (
            id INT AUTO_INCREMENT NOT NULL,
            content LONGTEXT NOT NULL,
            sent_at DATETIME NOT NULL,
            is_read TINYINT NOT NULL,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            appointment_id INT DEFAULT NULL,
            INDEX IDX_DB021E96F624B39D (sender_id),
            INDEX IDX_DB021E96CD53EDB6 (receiver_id),
            INDEX IDX_DB021E96E5B533F9 (appointment_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointments (id)');

        // ── notifications (→ users) ───────────────────────────────────────────

        $this->addSql('CREATE TABLE notifications (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT DEFAULT NULL,
            is_read TINYINT NOT NULL,
            created_at DATETIME NOT NULL,
            user_id INT NOT NULL,
            INDEX IDX_6000B0D3A76ED395 (user_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        // ── unavailability_slots (→ doctors, → business_sites) ───────────────

        $this->addSql('CREATE TABLE unavailability_slots (
            id INT AUTO_INCREMENT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            doctor_id INT NOT NULL,
            business_site_id INT NOT NULL,
            INDEX IDX_7FD233D987F4FB17 (doctor_id),
            INDEX IDX_7FD233D9AB8EAD0B (business_site_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE unavailability_slots ADD CONSTRAINT FK_7FD233D987F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id)');
        $this->addSql('ALTER TABLE unavailability_slots ADD CONSTRAINT FK_7FD233D9AB8EAD0B FOREIGN KEY (business_site_id) REFERENCES business_sites (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('DROP TABLE IF EXISTS unavailability_slots');
        $this->addSql('DROP TABLE IF EXISTS notifications');
        $this->addSql('DROP TABLE IF EXISTS messages');
        $this->addSql('DROP TABLE IF EXISTS reviews');
        $this->addSql('DROP TABLE IF EXISTS prescriptions');
        $this->addSql('DROP TABLE IF EXISTS appointments');
        $this->addSql('DROP TABLE IF EXISTS patients_history');
        $this->addSql('DROP TABLE IF EXISTS doctor_business_site');
        $this->addSql('DROP TABLE IF EXISTS users_password_reset_token');
        $this->addSql('DROP TABLE IF EXISTS doctors');
        $this->addSql('DROP TABLE IF EXISTS users');
        $this->addSql('DROP TABLE IF EXISTS business_sites');
        $this->addSql('DROP TABLE IF EXISTS specialties');
        $this->addSql('DROP TABLE IF EXISTS regions');
        $this->addSql('DROP TABLE IF EXISTS logging_attempt');
        $this->addSql('DROP TABLE IF EXISTS app_config');
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
