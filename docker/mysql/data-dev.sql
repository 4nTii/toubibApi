-- ============================================================
-- Toubib -- Test data fixtures (France)
-- Usage: docker compose exec -T db mysql -u toubib_user -ptoubib_password toubib < fixtures.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE app_config;
TRUNCATE TABLE users_password_reset_token;
TRUNCATE TABLE reviews;
TRUNCATE TABLE prescriptions;
TRUNCATE TABLE messages;
TRUNCATE TABLE notifications;
TRUNCATE TABLE appointments;
TRUNCATE TABLE unavailability_slots;
TRUNCATE TABLE doctor_business_site;
TRUNCATE TABLE doctors;
TRUNCATE TABLE patients;
TRUNCATE TABLE users;
TRUNCATE TABLE business_sites;
TRUNCATE TABLE specialties;
TRUNCATE TABLE regions;
TRUNCATE TABLE logging_attempt;

SET FOREIGN_KEY_CHECKS = 1;

-- ── AppConfig ────────────────────────────────────────────────
INSERT INTO app_config (app_name, version, logo, maintenance, update_at) VALUES
('Toubib', '1.0', '/logo.png', 0, NOW());

-- ── Regions ──────────────────────────────────────────────────
INSERT INTO regions (id, name, country) VALUES
(1, 'Île-de-France', 'France'),
(2, 'Provence-Alpes-Côte d\'Azur', 'France'),
(3, 'Auvergne-Rhône-Alpes', 'France'),
(4, 'Occitanie', 'France'),
(5, 'Nouvelle-Aquitaine', 'France');

-- ── Specialités ──────────────────────────────────────────────
INSERT INTO specialties (id, name, description) VALUES
(1, 'Médecine générale',  'Soins primaires, prévention et suivi médical global'),
(2, 'Cardiologie',        'Maladies du cœur et du système cardiovasculaire'),
(3, 'Pédiatrie',          'Médecine des nourrissons, enfants et adolescents'),
(4, 'Dermatologie',       'Maladies de la peau, des phanères et des muqueuses'),
(5, 'Ophtalmologie',      'Maladies des yeux et troubles de la vision');

-- ── Business Sites ───────────────────────────────────────────
INSERT INTO business_sites (id, name, address, ville, phone, email, location_google_map, region_id) VALUES
(1, 'Cabinet Médical du Marais',     '14 Rue de Bretagne',          'Paris',        '+33144781200', 'cabinet.marais@gmail.com',     'https://maps.google.com/?q=48.8598,2.3601',  1),
(2, 'Clinique Saint-Charles',        '3 Avenue du Prado',           'Marseille',    '+33491220000', 'contact@clinique-stcharles.fr','https://maps.google.com/?q=43.2965,5.3877',  2),
(3, 'Cabinet Pédiatrique Lyon 6',    '22 Cours Vitton',             'Lyon',         '+33472741800', 'pediatrie.lyon6@gmail.com',    'https://maps.google.com/?q=45.7640,4.8357',  3),
(4, 'Centre Dermatologique Toulouse','8 Place du Capitole',         'Toulouse',     '+33561230000', 'dermato.toulouse@gmail.com',   'https://maps.google.com/?q=43.6047,1.4442',  4);

-- ── Users ────────────────────────────────────────────────────
-- Mot de passe : Admin@1234 / Doctor@1234 / Patient@1234
INSERT INTO users (id, first_name, last_name, gender, email, phone, password, role, is_active, is_email_verified, is_phone_verified, user_token, date_inscription) VALUES
-- Admin
(1,  'Admin',    'Toubib',      'male',   'admin@toubib.fr',          '+33600000000', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_ADMIN',   1, 1, 1, 'tok_admin_001', NOW()),
-- Médecins
(2,  'Pierre',   'Dupont',      'male',   'dr.dupont@toubib.fr',      '+33611000001', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_DOCTOR',  1, 1, 1, 'tok_doc_001',   NOW()),
(3,  'Sophie',   'Martin',      'female', 'dr.martin@toubib.fr',      '+33611000002', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_DOCTOR',  1, 1, 1, 'tok_doc_002',   NOW()),
(4,  'Thomas',   'Bernard',     'male',   'dr.bernard@toubib.fr',     '+33611000003', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_DOCTOR',  1, 1, 1, 'tok_doc_003',   NOW()),
(5,  'Claire',   'Leroy',       'female', 'dr.leroy@toubib.fr',       '+33611000004', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_DOCTOR',  1, 1, 1, 'tok_doc_004',   NOW()),
-- Patients
(6,  'Lucas',    'Moreau',      'male',   'lucas.moreau@gmail.com',   '+33622000001', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_PATIENT', 1, 1, 1, 'tok_pat_001',   NOW()),
(7,  'Emma',     'Petit',       'female', 'emma.petit@gmail.com',     '+33622000002', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_PATIENT', 1, 1, 1, 'tok_pat_002',   NOW()),
(8,  'Hugo',     'Simon',       'male',   'hugo.simon@gmail.com',     '+33622000003', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_PATIENT', 1, 1, 1, 'tok_pat_003',   NOW()),
(9,  'Camille',  'Laurent',     'female', 'camille.laurent@gmail.com','+33622000004', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_PATIENT', 1, 1, 1, 'tok_pat_004',   NOW()),
(10, 'Antoine',  'Girard',      'male',   'antoine.girard@gmail.com', '+33622000005', '$2y$13$hGxGMkk3MdqXVEEFi2mYGOUDVNOC9RmTiuMiN9YWKN1Qu1OJaAMjW', 'ROLE_PATIENT', 1, 1, 1, 'tok_pat_005',   NOW());

-- ── Doctors ──────────────────────────────────────────────────
INSERT INTO doctors (id, user_id, speciality_id, license_number, activity_started, biography, is_active, accept_new_patients, teleconsultation_enabled, verified) VALUES
(1, 2, 1, 'RPPS-DUPONT-001',  '2008-09-01', 'Médecin généraliste installé à Paris depuis 2008. Ancien interne des hôpitaux de Paris.',             1, 1, 1, 1),
(2, 3, 2, 'RPPS-MARTIN-001',  '2011-03-01', 'Cardiologue à Marseille, spécialisée dans l\'insuffisance cardiaque et les arythmies.',               1, 1, 1, 1),
(3, 4, 3, 'RPPS-BERNARD-001', '2014-11-01', 'Pédiatre à Lyon, suivi de 0 à 18 ans. Passionné par la médecine du sport chez l\'enfant.',            1, 1, 0, 1),
(4, 5, 4, 'RPPS-LEROY-001',   '2016-06-01', 'Dermatologue à Toulouse, spécialisée en dermatologie esthétique et traitement de l\'acné.',           1, 1, 1, 1);

-- ── Patients ─────────────────────────────────────────────────
INSERT INTO patients (id, user_id, medical_history) VALUES
(1, 6,  'Aucun antécédent notable. Vaccins à jour.'),
(2, 7,  'Allergie aux pénicillines. Asthme léger depuis l\'enfance.'),
(3, 8,  'Diabète de type 2 diagnostiqué en 2022. Suivi régulier.'),
(4, 9,  'Migraines chroniques. Traitement préventif en cours.'),
(5, 10, 'Hypertension artérielle traitée depuis 2020.');

-- ── Doctor Business Sites ────────────────────────────────────
INSERT INTO doctor_business_site (id, doctor_id, business_site_id, is_owner, is_primary, consultation_duration, consultation_fee) VALUES
(1, 1, 1, 1, 1, 20, 2500),
(2, 2, 2, 1, 1, 30, 5000),
(3, 3, 3, 1, 1, 30, 3500),
(4, 4, 4, 1, 1, 30, 4500),
(5, 1, 3, 0, 0, 20, 2500);

-- ── Appointments ─────────────────────────────────────────────
INSERT INTO appointments (id, patient_id, doctor_id, business_site_id, start_time, end_time, status, notes) VALUES
(1, 1, 1, 1, '2026-05-15 09:00:00', '2026-05-15 09:20:00', 'scheduled',  'Consultation annuelle de suivi'),
(2, 2, 2, 2, '2026-05-15 10:30:00', '2026-05-15 11:00:00', 'scheduled',  'Contrôle post-hospitalisation'),
(3, 3, 1, 1, '2026-05-14 14:00:00', '2026-05-14 14:20:00', 'completed',  'Renouvellement ordonnance diabète'),
(4, 4, 3, 3, '2026-05-13 11:00:00', '2026-05-13 11:30:00', 'completed',  'Visite de contrôle pédiatrique'),
(5, 5, 2, 2, '2026-05-10 09:30:00', '2026-05-10 10:00:00', 'cancelled',  'Patient empêché, annulation la veille'),
(6, 1, 4, 4, '2026-05-20 16:00:00', '2026-05-20 16:30:00', 'scheduled',  'Première consultation dermatologie');

-- ── Reviews ──────────────────────────────────────────────────
INSERT INTO reviews (id, patient_id, doctor_id, appointment_id, rating, comment, created_at) VALUES
(1, 3, 1, 3, 5, 'Docteur très à l\'écoute, explications claires. Je recommande.',        NOW()),
(2, 4, 3, 4, 4, 'Excellent pédiatre, mes enfants adorent venir en consultation.',         NOW()),
(3, 1, 1, NULL, 5, 'Dr. Dupont suit ma famille depuis des années, toujours disponible.', NOW());

-- ── Prescriptions ────────────────────────────────────────────
INSERT INTO prescriptions (id, doctor_id, patient_id, appointment_id, medications, notes, created_at) VALUES
(1, 1, 3, 3, 'Metformine 1000mg — 1 comprimé matin et soir au repas\nGlucophage LP 500mg — 1 comprimé le soir', 'Contrôle glycémique dans 3 mois. Régime équilibré indispensable.', NOW()),
(2, 3, 4, 4, 'Doliprane 500mg — 1 sachet si fièvre > 38.5°C\nSmecta — 1 sachet 3x/jour pendant 5 jours',      'Boire suffisamment. Revoir si persistance des symptômes au-delà de 48h.', NOW());

-- ── Messages ─────────────────────────────────────────────────
INSERT INTO messages (id, sender_id, receiver_id, appointment_id, content, sent_at, is_read) VALUES
(1, 6, 2, 1, 'Bonjour Docteur Dupont, je confirme mon rendez-vous de jeudi matin.',          NOW(), 1),
(2, 2, 6, 1, 'Bonjour Lucas, je vous attends jeudi à 9h. Pensez à apporter votre carnet de santé.', NOW(), 0),
(3, 7, 3, 2, 'Docteur Martin, faut-il être à jeun pour l\'échocardiographie ?',              NOW(), 0);

-- ── Notifications ────────────────────────────────────────────
INSERT INTO notifications (id, user_id, title, message, is_read, created_at) VALUES
(1, 6,  'Rappel rendez-vous',    'Votre rendez-vous est jeudi à 9h avec le Dr. Dupont.',         0, NOW()),
(2, 7,  'Rappel rendez-vous',    'Votre rendez-vous est jeudi à 10h30 avec le Dr. Martin.',      0, NOW()),
(3, 8,  'Ordonnance disponible', 'Votre ordonnance du Dr. Dupont est prête à être téléchargée.',  1, NOW()),
(4, 2,  'Nouveau message',       'Vous avez un nouveau message de Lucas Moreau.',                0, NOW());

-- ── Unavailability Slots ─────────────────────────────────────
INSERT INTO unavailability_slots (id, doctor_id, business_site_id, start_time, end_time, reason) VALUES
(1, 1, 1, '2026-07-14 00:00:00', '2026-08-15 23:59:00', 'Congés estivaux'),
(2, 2, 2, '2026-06-05 08:00:00', '2026-06-05 18:00:00', 'Congrès de cardiologie à Paris');