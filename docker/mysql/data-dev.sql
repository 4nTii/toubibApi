-- ============================================================
-- Toubib -- Test data fixtures (France)
-- Usage: make db-fixtures
-- ============================================================

ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- FK checks disabled for the entire file to allow forward references
-- (users.main_doctor_id → doctors, doctors.user_id → users)
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
TRUNCATE TABLE patients_history;
TRUNCATE TABLE users;
TRUNCATE TABLE business_sites;
TRUNCATE TABLE specialties;
TRUNCATE TABLE regions;
TRUNCATE TABLE logging_attempt;

-- ── AppConfig ────────────────────────────────────────────────
INSERT INTO app_config (app_name, version, logo, maintenance, update_at) VALUES
('Toubib', '1.0', '/logo.png', 0, NOW());

-- ── Regions ──────────────────────────────────────────────────
INSERT INTO regions (id, name, country) VALUES
(1, 'Île-de-France',              'France'),
(2, 'Provence-Alpes-Côte d\'Azur','France'),
(3, 'Auvergne-Rhône-Alpes',       'France'),
(4, 'Occitanie',                  'France'),
(5, 'Nouvelle-Aquitaine',         'France');

-- ── Specialités ──────────────────────────────────────────────
INSERT INTO specialties (id, name, description) VALUES
(1, 'Médecine générale', 'Soins primaires, prévention et suivi médical global'),
(2, 'Cardiologie',       'Maladies du cœur et du système cardiovasculaire'),
(3, 'Pédiatrie',         'Médecine des nourrissons, enfants et adolescents'),
(4, 'Dermatologie',      'Maladies de la peau, des phanères et des muqueuses'),
(5, 'Ophtalmologie',     'Maladies des yeux et troubles de la vision');

-- ── Business Sites ───────────────────────────────────────────
INSERT INTO business_sites (id, name, address, ville, phone, email, location_google_map, region_id) VALUES
(1, 'Cabinet Médical du Marais',      '14 Rue de Bretagne',    'Paris',     '+33144781200', 'cabinet.marais@gmail.com',      'https://maps.google.com/?q=48.8598,2.3601', 1),
(2, 'Clinique Saint-Charles',         '3 Avenue du Prado',     'Marseille', '+33491220000', 'contact@clinique-stcharles.fr', 'https://maps.google.com/?q=43.2965,5.3877', 2),
(3, 'Cabinet Pédiatrique Lyon 6',     '22 Cours Vitton',       'Lyon',      '+33472741800', 'pediatrie.lyon6@gmail.com',     'https://maps.google.com/?q=45.7640,4.8357', 3),
(4, 'Centre Dermatologique Toulouse', '8 Place du Capitole',   'Toulouse',  '+33561230000', 'dermato.toulouse@gmail.com',    'https://maps.google.com/?q=43.6047,1.4442', 4);

-- ── Users ────────────────────────────────────────────────────
-- Mot de passe : azerty123 (tous les comptes)
-- social_number : 15 chiffres bruts (format affiché : 1 92 03 75 014 023 48)
-- main_doctor_id : référence doctors.id (certains NULL)
INSERT INTO users (id, first_name, last_name, gender, email, biography, phone, password, role, is_active, is_email_verified, is_phone_verified, user_token, force_password_change, date_inscription, birth_day, address, social_number, main_doctor_id) VALUES
-- ── Admin ──
(1,  'Admin',    'Toubib',    'male',   'admin@toubib.fr',
     'Fondateur de la plateforme. PWD: azerty123',
     '+33600000000', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_ADMIN',  1, 1, 1, 'tok_admin_001', 0, NOW(), NULL,           NULL,                              NULL,             NULL),
-- ── Médecins ──
(2,  'Pierre',   'Dupont',    'male',   'dr.dupont@toubib.fr',
     'Médecin généraliste parisien, 17 ans d\'expérience. PWD: azerty123',
     '+33611000001', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_DOCTOR', 1, 1, 1, 'tok_doc_001',   0, NOW(), '1973-04-12',   '14 Rue de Bretagne, 75003 Paris', '173047501402213', NULL),
(3,  'Sophie',   'Martin',    'female', 'dr.martin@toubib.fr',
     'Cardiologue à Marseille, spécialisée insuffisance cardiaque. PWD: azerty123',
     '+33611000002', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_DOCTOR', 1, 1, 1, 'tok_doc_002',   0, NOW(), '1978-09-23',   '3 Avenue du Prado, 13006 Marseille', NULL,             NULL),
(4,  'Thomas',   'Bernard',   'male',   'dr.bernard@toubib.fr',
     'Pédiatre à Lyon, suivi 0-18 ans. PWD: azerty123',
     '+33611000003', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_DOCTOR', 1, 1, 1, 'tok_doc_003',   0, NOW(), '1981-03-07',   NULL,                              '181036900101874', NULL),
(5,  'Claire',   'Leroy',     'female', 'dr.leroy@toubib.fr',
     'Dermatologue à Toulouse. PWD: azerty123',
     '+33611000004', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_DOCTOR', 1, 1, 1, 'tok_doc_004',   0, NOW(), '1983-11-30',   NULL,                              NULL,             NULL),
-- ── Patients ──
(6,  'Lucas',    'Moreau',    'male',   'lucas.moreau@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000001', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_001', 0, NOW(), '1992-03-15', '12 Rue de la Paix, 75001 Paris',        '192037501402348', 1),
(7,  'Emma',     'Petit',     'female', 'emma.petit@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000002', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_002', 0, NOW(), '1987-07-22', '45 Boulevard Baille, 13005 Marseille',  '287072130201866', 2),
(8,  'Hugo',     'Simon',     'male',   'hugo.simon@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000003', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_003', 0, NOW(), '1995-11-08', '8 Rue Victor Hugo, 69003 Lyon',         NULL,             1),
(9,  'Camille',  'Laurent',   'female', 'camille.laurent@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000004', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_004', 0, NOW(), '1990-05-30', NULL,                                    '290053300105041', 3),
(10, 'Antoine',  'Girard',    'male',   'antoine.girard@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000005', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_005', 0, NOW(), '1985-09-12', NULL,                                    NULL,             NULL),
(11, 'Marie',    'Dubois',    'female', 'marie.dubois@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000011', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_011', 0, NOW(), '1978-02-14', '23 Avenue des Fleurs, 75015 Paris',     '278021375001142', 1),
(12, 'Nicolas',  'Fontaine',  'male',   'nicolas.fontaine@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000012', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_012', 0, NOW(), '1991-06-25', NULL,                                    NULL,             1),
(13, 'Isabelle', 'Mornet',    'female', 'isabelle.mornet@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000013', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_013', 0, NOW(), '2001-01-03', '17 Rue Nationale, 31000 Toulouse',      '201011300104722', 4),
(14, 'Kevin',    'Lambert',   'male',   'kevin.lambert@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000014', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_014', 0, NOW(), '1983-04-17', '5 Rue du Faubourg, 67000 Strasbourg',   '183043100104188', 2),
(15, 'Pauline',  'Rousseau',  'female', 'pauline.rousseau@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000015', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_015', 0, NOW(), '1996-08-09', NULL,                                    NULL,             NULL),
(16, 'Julien',   'Blanc',     'male',   'julien.blanc@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000016', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_016', 0, NOW(), '1979-12-23', '34 Rue de la République, 69001 Lyon',   '179123800103355', 3),
(17, 'Nathalie', 'Garnier',   'female', 'nathalie.garnier@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000017', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_017', 0, NOW(), '1968-10-05', '12 Allée des Roses, 33000 Bordeaux',    '268105900101244', 1),
(18, 'Florian',  'Chapuis',   'male',   'florian.chapuis@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000018', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_018', 0, NOW(), '2000-05-30', NULL,                                    NULL,             NULL),
(19, 'Céline',   'Arnaud',    'female', 'celine.arnaud@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000019', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_019', 0, NOW(), '1993-03-20', '89 Boulevard des Capucines, 75002 Paris','293033100104477', 1),
(20, 'Maxime',   'Guerin',    'male',   'maxime.guerin@gmail.com',
     'Patient de Toubib. PWD: azerty123',
     '+33622000020', '$2y$13$usWpycVj3S7jkyRq6h74YOSxj1W/8QWWssiWPPOjYlGreJ1uEn3ma',
     'ROLE_USER', 1, 1, 1, 'tok_pat_020', 0, NOW(), '1988-07-14', NULL,                                    NULL,             4);

-- ── Doctors ──────────────────────────────────────────────────
INSERT INTO doctors (id, user_id, speciality_id, license_number, activity_started, biography, profile_picture, is_active, accept_new_patients, teleconsultation_enabled, verified) VALUES
(1, 2, 1, 'RPPS-DUPONT-001',  '2008-09-01', 'Médecin généraliste installé à Paris depuis 2008. Ancien interne des hôpitaux de Paris.',   'avatars/doctors/35ba471e4fd7eb1d8688ffd77c74b3ec.jpg', 1, 1, 1, 1),
(2, 3, 2, 'RPPS-MARTIN-001',  '2011-03-01', 'Cardiologue à Marseille, spécialisée dans l\'insuffisance cardiaque et les arythmies.',     'avatars/doctors/38ccaa2deea2075e3c02f9755f38d0e9.jpg', 1, 1, 1, 1),
(3, 4, 3, 'RPPS-BERNARD-001', '2014-11-01', 'Pédiatre à Lyon, suivi de 0 à 18 ans. Passionné par la médecine du sport chez l\'enfant.', 'avatars/doctors/38ccaa2deea2075e3c02f9755f38d0e6.jpg', 1, 1, 0, 1),
(4, 5, 4, 'RPPS-LEROY-001',   '2016-06-01', 'Dermatologue à Toulouse, spécialisée en dermatologie esthétique et traitement de l\'acné.','',                                                     1, 1, 1, 1);

-- ── Patients History ─────────────────────────────────────────
INSERT INTO patients_history (id, user_id, doctor_id, date, notes) VALUES
(1,  6,  1, '2025-11-20 09:00:00', 'Vaccination grippe saisonnière effectuée. Rappel tétanos prévu en 2027.'),
(2,  6,  1, '2026-03-10 09:20:00', 'Aucun antécédent notable. Vaccins à jour. Patient en bonne santé générale.'),
(3,  7,  2, '2026-03-15 10:30:00', 'Allergie aux pénicillines signalée. Asthme léger depuis l\'enfance, traitement de fond au besoin.'),
(4,  8,  1, '2026-01-15 10:00:00', 'Première visite. Diabète de type 2 diagnostic initial. Bilan sanguin prescrit.'),
(5,  8,  1, '2026-04-02 14:20:00', 'Diabète de type 2 confirmé en 2022. Suivi glycémique régulier. HbA1c stable à 7,1%.'),
(6,  9,  3, '2026-04-10 11:30:00', 'Migraines chroniques. Traitement préventif en cours, réévaluation dans 3 mois.'),
(7,  10, 2, '2026-04-20 09:30:00', 'Hypertension artérielle traitée depuis 2020. Tension bien contrôlée sous traitement.'),
(8,  11, 1, '2026-04-15 10:20:00', 'Antécédent fracture poignet gauche (2019). Ostéoporose à surveiller. Calcium + Vit D prescrits.'),
(9,  12, 1, '2026-05-01 14:30:00', 'Hypercholestérolémie familiale. Statines depuis 2023, bilan lipidique stable.'),
(10, 14, 2, '2026-03-20 09:00:00', 'Fibrillation auriculaire paroxystique. Anticoagulants prescrits. Holter prévu en juin.'),
(11, 16, 3, '2026-04-30 11:15:00', 'Retard de croissance. Courbe en amélioration. Prochain bilan osseux en septembre.'),
(12, 17, 1, '2026-05-10 08:50:00', 'Ménopause débutante. Traitement hormonal substitutif envisagé. Bilan gynécologique à faire.'),
(13, 19, 1, '2026-06-01 09:20:00', 'Rhinite allergique saisonnière. Désensibilisation en cours depuis 2025.'),
(14, 11, 2, '2026-05-20 11:00:00', 'Consultation cardiaque secondaire suite à palpitations. ECG normal. Surveillance maintenue.');

-- ── Doctor Business Sites ────────────────────────────────────
INSERT INTO doctor_business_site (id, doctor_id, business_site_id, is_owner, is_primary, consultation_duration, consultation_fee, working_schedule, share_calendar) VALUES
(1, 1, 1, 1, 1, 20, 2500, '{"monday":{"end":"18:00","start":"08:30","enabled":true},"tuesday":{"end":"18:00","start":"08:00","enabled":true},"wednesday":{"end":"12:00","start":"08:00","enabled":true},"thursday":{"end":"18:00","start":"08:00","enabled":true},"friday":{"end":"17:00","start":"08:00","enabled":true},"saturday":{"end":"12:00","start":"09:00","enabled":false},"sunday":{"end":"00:00","start":"00:00","enabled":false}}', 0),
(2, 2, 2, 1, 1, 30, 5000, '{"monday":{"end":"18:00","start":"08:30","enabled":true},"tuesday":{"end":"18:00","start":"08:00","enabled":true},"wednesday":{"end":"12:00","start":"08:00","enabled":true},"thursday":{"end":"18:00","start":"08:00","enabled":true},"friday":{"end":"17:00","start":"08:00","enabled":true},"saturday":{"end":"12:00","start":"09:00","enabled":false},"sunday":{"end":"00:00","start":"00:00","enabled":false}}', 0),
(3, 3, 3, 1, 1, 30, 3500, '{"monday":{"end":"18:00","start":"08:30","enabled":true},"tuesday":{"end":"18:00","start":"08:00","enabled":true},"wednesday":{"end":"12:00","start":"08:00","enabled":true},"thursday":{"end":"18:00","start":"08:00","enabled":true},"friday":{"end":"17:00","start":"08:00","enabled":true},"saturday":{"end":"12:00","start":"09:00","enabled":false},"sunday":{"end":"00:00","start":"00:00","enabled":false}}', 0),
(4, 4, 4, 1, 1, 30, 4500, '{"monday":{"end":"18:00","start":"08:30","enabled":true},"tuesday":{"end":"18:00","start":"08:00","enabled":true},"wednesday":{"end":"12:00","start":"08:00","enabled":true},"thursday":{"end":"18:00","start":"08:00","enabled":true},"friday":{"end":"17:00","start":"08:00","enabled":true},"saturday":{"end":"12:00","start":"09:00","enabled":false},"sunday":{"end":"00:00","start":"00:00","enabled":false}}', 0),
(5, 1, 3, 0, 0, 20, 2500, '{"monday":{"end":"18:00","start":"08:30","enabled":true},"tuesday":{"end":"18:00","start":"08:00","enabled":true},"wednesday":{"end":"12:00","start":"08:00","enabled":true},"thursday":{"end":"18:00","start":"08:00","enabled":true},"friday":{"end":"17:00","start":"08:00","enabled":true},"saturday":{"end":"12:00","start":"09:00","enabled":false},"sunday":{"end":"00:00","start":"00:00","enabled":false}}', 0);

-- ── Appointments ─────────────────────────────────────────────
INSERT INTO appointments (id, patient_id, doctor_id, business_site_id, start_time, end_time, status, notes) VALUES
-- Passés
(1,  8,  1, 1, '2026-03-10 09:00:00', '2026-03-10 09:20:00', 'completed', 'Contrôle glycémique trimestriel'),
(2,  9,  3, 3, '2026-04-13 11:00:00', '2026-04-13 11:30:00', 'completed', 'Visite de contrôle pédiatrique'),
(3,  8,  1, 1, '2026-05-14 14:00:00', '2026-05-14 14:20:00', 'completed', 'Renouvellement ordonnance diabète'),
(4,  10, 2, 2, '2026-05-10 09:30:00', '2026-05-10 10:00:00', 'canceled',  'Patient empêché, annulation la veille'),
(5,  15, 1, 1, '2026-04-05 10:00:00', '2026-04-05 10:20:00', 'completed', 'Consultation grippe saisonnière'),
(6,  16, 3, 3, '2026-05-28 11:30:00', '2026-05-28 12:00:00', 'completed', 'Visite pédiatrique'),
(7,  18, 2, 2, '2026-04-15 10:00:00', '2026-04-15 10:30:00', 'completed', 'Bilan cardiovasculaire'),
(8,  20, 4, 4, '2026-05-05 15:00:00', '2026-05-05 15:30:00', 'canceled',  'Annulation patient indisponible'),
(9,  11, 2, 2, '2026-05-20 11:00:00', '2026-05-20 11:30:00', 'completed', 'Palpitations — ECG normal'),
(10, 7,  2, 2, '2026-05-15 10:30:00', '2026-05-15 11:00:00', 'completed', 'Contrôle post-hospitalisation'),
-- Futurs / en cours
(11, 6,  1, 1, '2026-06-15 09:00:00', '2026-06-15 09:20:00', 'confirmed', 'Consultation annuelle de suivi'),
(12, 6,  4, 4, '2026-06-20 16:00:00', '2026-06-20 16:30:00', 'scheduled', 'Première consultation dermatologie'),
(13, 11, 1, 1, '2026-06-23 10:00:00', '2026-06-23 10:20:00', 'scheduled', 'Bilan de santé annuel'),
(14, 12, 1, 1, '2026-06-25 14:00:00', '2026-06-25 14:20:00', 'confirmed', 'Suivi tension artérielle'),
(15, 13, 4, 4, '2026-07-02 11:00:00', '2026-07-02 11:30:00', 'scheduled', 'Consultation acné'),
(16, 14, 2, 2, '2026-06-18 09:00:00', '2026-06-18 09:30:00', 'confirmed', 'Échocardiographie de contrôle'),
(17, 17, 1, 1, '2026-07-10 08:30:00', '2026-07-10 08:50:00', 'scheduled', 'Consultation ménopause'),
(18, 19, 1, 1, '2026-06-16 09:00:00', '2026-06-16 09:20:00', 'confirmed', 'Suivi allergie saisonnière'),
(19, 6,  1, 1, '2026-06-30 09:00:00', '2026-06-30 09:20:00', 'scheduled', 'Suivi traitement chronique'),
(20, 14, 1, 1, '2026-07-15 14:00:00', '2026-07-15 14:20:00', 'scheduled', 'Consultation généraliste de routine');

-- ── Reviews ──────────────────────────────────────────────────
INSERT INTO reviews (id, patient_id, doctor_id, appointment_id, rating, comment, created_at) VALUES
(1, 8,  1, 3,    5, 'Docteur très à l\'écoute, explications claires. Je recommande.',                    NOW()),
(2, 9,  3, 2,    4, 'Excellent pédiatre, mes enfants adorent venir en consultation.',                    NOW()),
(3, 6,  1, NULL, 5, 'Dr. Dupont suit ma famille depuis des années, toujours disponible.',               NOW()),
(4, 11, 1, NULL, 4, 'Médecin très compétent et rassurant. Temps d\'attente un peu long.',               NOW()),
(5, 14, 2, 16,   5, 'Dr. Martin est excellente, très précise dans ses explications cardiaques.',        NOW()),
(6, 16, 3, 6,    3, 'Consultation correcte mais manque de chaleur humaine pour les enfants.',           NOW()),
(7, 17, 1, NULL, 5, 'Suivi impeccable depuis 10 ans, je ne changerais pas de médecin.',                NOW());

-- ── Prescriptions ────────────────────────────────────────────
INSERT INTO prescriptions (id, doctor_id, patient_id, appointment_id, medications, notes, created_at) VALUES
(1, 1, 8,  3,    'Metformine 1000mg — 1 comprimé matin et soir au repas\nGlucophage LP 500mg — 1 comprimé le soir',   'Contrôle glycémique dans 3 mois. Régime équilibré indispensable.',       NOW()),
(2, 3, 9,  2,    'Doliprane 500mg — 1 sachet si fièvre > 38,5°C\nSmecta — 1 sachet 3×/jour pendant 5 jours',          'Boire suffisamment. Revoir si persistance des symptômes au-delà de 48h.',NOW()),
(3, 1, 12, NULL, 'Atorvastatine 20mg — 1 comprimé le soir\nCrestor 10mg si tolérance insuffisante',                   'Bilan lipidique dans 6 semaines. Éviter le pamplemousse.',               NOW()),
(4, 2, 14, 16,   'Xarelto 20mg — 1 comprimé/jour au dîner\nBisoprolol 5mg — 1 comprimé le matin',                    'Surveillance INR mensuelle. Pas d\'AINS sans avis médical.',             NOW()),
(5, 1, 17, NULL, 'Climaston 1mg — 1 comprimé/jour\nCalcium 500mg + Vit D3 — 1 comprimé le matin',                    'Réévaluation THB dans 3 mois. Mammographie annuelle.',                   NOW()),
(6, 1, 11, NULL, 'Calcium Sandoz Forte 500mg — 1 comprimé/jour\nVitamine D3 1000 UI — 1 ampoule/mois',               'Activité physique recommandée. Contrôle densitométrie osseux annuel.',   NOW());

-- ── Messages ─────────────────────────────────────────────────
INSERT INTO messages (id, sender_id, receiver_id, appointment_id, content, sent_at, is_read) VALUES
(1, 6,  2,  11,  'Bonjour Docteur Dupont, je confirme mon rendez-vous de lundi matin.',                         NOW(), 1),
(2, 2,  6,  11,  'Bonjour Lucas, je vous attends lundi à 9h. Pensez à apporter votre carnet de santé.',        NOW(), 0),
(3, 7,  3,  10,  'Docteur Martin, faut-il être à jeun pour l\'échocardiographie ?',                             NOW(), 1),
(4, 11, 2,  13,  'Bonjour Dr Dupont, est-ce que je dois apporter mes résultats de prise de sang ?',            NOW(), 1),
(5, 2,  11, 13,  'Bonjour Marie, oui merci d\'apporter tous vos bilans récents.',                              NOW(), 0),
(6, 14, 3,  16,  'Docteur Martin, j\'ai des douleurs thoraciques depuis hier soir, est-ce urgent ?',           NOW(), 1),
(7, 3,  14, 16,  'Si les douleurs persistent allez aux urgences. Sinon votre RDV est confirmé pour demain.',   NOW(), 0);

-- ── Notifications ────────────────────────────────────────────
INSERT INTO notifications (id, user_id, title, message, is_read, created_at) VALUES
(1,  6,  'Rappel rendez-vous',     'Votre rendez-vous est lundi à 9h avec le Dr. Dupont.',                          0, NOW()),
(2,  7,  'Rappel rendez-vous',     'Votre rendez-vous du 15 mai avec le Dr. Martin est terminé.',                   1, NOW()),
(3,  8,  'Ordonnance disponible',  'Votre ordonnance du Dr. Dupont est prête à être téléchargée.',                   1, NOW()),
(4,  2,  'Nouveau message',        'Vous avez un nouveau message de Lucas Moreau.',                                  0, NOW()),
(5,  11, 'Rappel rendez-vous',     'Votre rendez-vous est le 23 juin à 10h avec le Dr. Dupont.',                    0, NOW()),
(6,  14, 'Rappel rendez-vous',     'Votre rendez-vous cardiologie est le 18 juin à 9h avec le Dr. Martin.',         0, NOW()),
(7,  19, 'Rendez-vous confirmé',   'Votre rendez-vous du 16 juin avec le Dr. Dupont est confirmé.',                 1, NOW()),
(8,  2,  'Nouveau message',        'Marie Dubois vous a envoyé un message concernant son prochain RDV.',             0, NOW()),
(9,  17, 'Résultats disponibles',  'Vos résultats de bilan hormonal sont disponibles dans votre espace.',            0, NOW()),
(10, 6,  'Rappel traitement',      'Pensez à renouveler votre ordonnance avant votre prochain rendez-vous.',         1, NOW());

-- ── Unavailability Slots ─────────────────────────────────────
INSERT INTO unavailability_slots (id, doctor_id, business_site_id, start_time, end_time, reason) VALUES
(1, 1, 1, '2026-07-14 00:00:00', '2026-08-15 23:59:00', 'Congés estivaux'),
(2, 2, 2, '2026-06-05 08:00:00', '2026-06-05 18:00:00', 'Congrès de cardiologie à Paris'),
(3, 3, 3, '2026-06-20 00:00:00', '2026-06-27 23:59:00', 'Formation médicale continue à Bordeaux');

SET FOREIGN_KEY_CHECKS = 1;
