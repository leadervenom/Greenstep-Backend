-- sql/greenstep.sql
CREATE DATABASE IF NOT EXISTS greenstep_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenstep_api;

DROP TABLE IF EXISTS eco_photos;
DROP TABLE IF EXISTS goals;
DROP TABLE IF EXISTS friend_requests;
DROP TABLE IF EXISTS friendships;
DROP TABLE IF EXISTS challenge_members;
DROP TABLE IF EXISTS challenges;
DROP TABLE IF EXISTS tips;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS activity_types;
DROP TABLE IF EXISTS users;

-- 1. Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL DEFAULT '',
    role ENUM('member','admin') NOT NULL DEFAULT 'member',
    eco_points INT DEFAULT 0,
    gained_today INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Activity Types Table
CREATE TABLE activity_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    kg_co2_per_unit DECIMAL(10,4) NOT NULL,
    info VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 3. Activity Logs Table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    emissions_kg DECIMAL(10,2) NOT NULL,
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    logged_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_type_id) REFERENCES activity_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Tips Table
CREATE TABLE tips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    category VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- 5. Challenges Table
CREATE TABLE challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    group_progress_percent DECIMAL(5,2) DEFAULT 0.00,
    created_by INT NULL,
    is_active INT DEFAULT 1,
    is_completed INT DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6. Challenge Members (Junction Table)
CREATE TABLE challenge_members (
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (challenge_id, user_id),
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Friendships Table
CREATE TABLE friendships (
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    PRIMARY KEY (user_id, friend_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Friend Requests Table
CREATE TABLE friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    requested_at VARCHAR(50) NOT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Performance Goals Table
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    target_to_reduce_kg DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    emissions_reduced_so_far_kg DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Eco Photos Table
CREATE TABLE eco_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    achievement TEXT NOT NULL,
    uploaded_on DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Demo password for all seeded users: password
INSERT INTO users (id, name, email, password_hash, role, eco_points, gained_today, created_at) VALUES
(1,   'You (GreenRunner)', 'you@greenstep.app',             '$2y$10$uSixnFrjcOkwKP.thvYswezo23rlMSXNEWSYT5uL3b3RIGacXn50e', 'member', 1240, 80, '2026-05-01 09:00:00'),
(201, 'Sarah Connor',      'sarah.connor@greenstep.app',    '$2y$10$uSixnFrjcOkwKP.thvYswezo23rlMSXNEWSYT5uL3b3RIGacXn50e', 'member', 1420, 0,  '2026-04-12 10:15:00'),
(202, 'Alex Mercer',       'alex.mercer@greenstep.app',     '$2y$10$uSixnFrjcOkwKP.thvYswezo23rlMSXNEWSYT5uL3b3RIGacXn50e', 'member', 1100, 0,  '2026-04-18 14:30:00'),
(203, 'Emma Watson',       'emma.watson@greenstep.app',     '$2y$10$uSixnFrjcOkwKP.thvYswezo23rlMSXNEWSYT5uL3b3RIGacXn50e', 'member', 1680, 0,  '2026-03-22 08:45:00'),
(204, 'James Sutherland',  'james.sutherland@greenstep.app','$2y$10$uSixnFrjcOkwKP.thvYswezo23rlMSXNEWSYT5uL3b3RIGacXn50e', 'member', 860,  0,  '2026-06-01 16:00:00'),
(205, 'Clara Oswald',      'clara.oswald@greenstep.app',    '$2y$10$uSixnFrjcOkwKP.thvYswezo23rlMSXNEWSYT5uL3b3RIGacXn50e', 'member', 540,  0,  '2026-06-10 11:20:00'),
(999, 'GreenStep Admin',   'admin@greenstep.app',           '$2y$10$uSixnFrjcOkwKP.thvYswezo23rlMSXNEWSYT5uL3b3RIGacXn50e', 'admin',  0,    0,  '2026-01-01 00:00:00');

INSERT INTO activity_types (id, category, name, unit, kg_co2_per_unit, info) VALUES
(1, 'Transport', 'Car (Petrol)',       'km',    0.21,  'Average medium-sized gasoline passenger vehicle'),
(2, 'Transport', 'Electric Vehicle',   'km',    0.05,  'Based on regional electricity grid mix cleaner values'),
(3, 'Transport', 'Train',              'km',    0.04,  'National transit electric and diesel passenger average'),
(4, 'Transport', 'Bus',                'km',    0.09,  'Standard city bus network route occupancy factor'),
(5, 'Food',      'Meat-Based Meal',    'meals', 6.00,  'High carbon footprint featuring beef, lamb, or pork ingredients'),
(6, 'Food',      'Plant-Based Meal',   'meals', 0.70,  'Low footprint vegan or vegetarian meal configuration'),
(7, 'Energy',    'Electricity Usage',  'kWh',   0.50,  'Per kilowatt-hour consumed from fossil grid generation'),
(8, 'Waste',     'Recycling Action',   'items', -0.15, 'Negative emission values representing lifecycle credits earned'),
(9, 'Transport', 'Flight (Short Haul)','km',    0.25,  'Aviation tracking multiplier for intra-state segments');

INSERT INTO activity_logs (id, user_id, activity_type_id, amount, emissions_kg, logged_at, logged_date) VALUES
(501, 1, 1, 30, 6.30, '2026-06-26 08:30:00', '2026-06-26'),
(502, 1, 6, 1,  0.70, '2026-06-26 12:45:00', '2026-06-26'),
(503, 1, 5, 1,  6.00, '2026-06-26 18:00:00', '2026-06-26'),
(504, 1, 1, 40, 8.40, '2026-06-25 09:00:00', '2026-06-25'),
(505, 1, 7, 9,  4.50, '2026-06-25 20:00:00', '2026-06-25'),
(506, 1, 3, 20, 0.80, '2026-06-24 09:00:00', '2026-06-24'),
(507, 1, 6, 2,  1.40, '2026-06-24 13:00:00', '2026-06-24');

INSERT INTO tips (id, title, body, category) VALUES
(1, 'Transit Switch',    'Swap two local vehicle commutes this week for public bus trips.', 'Transport'),
(2, 'Meatless Mondays',  'Skipping red meat meals for one day saves substantial production-chain carbon.', 'Food'),
(3, 'Smart Thermostats', 'Lower cooling systems by 1 degree during high heat peaks to preserve regional power grid draw.', 'Energy'),
(4, 'Compost Scrap',     'Sort biodegradable organic waste away from landfills to lower localized methane outputs.', 'Waste');

INSERT INTO challenges (id, title, description, target_type, group_progress_percent, created_by, is_active, is_completed) VALUES
(101, 'Green Commute Race', 'Swap private cars for trains or buses to lower city traffic footprints.', 'Transport', 68.50, NULL, 1, 0),
(102, 'The Vegan Streak',   'Log exclusively vegetarian meals for 7 consecutive days.',                'Food',      42.00, NULL, 1, 0),
(103, 'Zero Waste Heroes',  'Log 20 verified recycling milestones.',                                   'Waste',    100.00, NULL, 0, 1);

INSERT INTO challenge_members (challenge_id, user_id) VALUES
(101, 1), (101, 201), (101, 203),
(102, 202), (102, 204),
(103, 1), (103, 205);

INSERT INTO friendships (user_id, friend_id) VALUES
(1, 201), (201, 1),
(1, 202), (202, 1),
(1, 203), (203, 1);

INSERT INTO friend_requests (id, sender_id, receiver_id, requested_at) VALUES
(1, 204, 1, '2 hours ago'),
(2, 205, 1, 'Yesterday');

INSERT INTO goals (id, user_id, title, target_to_reduce_kg, duration, start_date, emissions_reduced_so_far_kg) VALUES
(1, 1, 'Cut Summer Carbon Footprint', 50.00, '30 Days', '2026-06-01', 22.40);

INSERT INTO eco_photos (id, user_id, image_url, achievement, uploaded_on) VALUES
(401, 1, 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=900&q=80', 'Used stainless steel flask at coffee shop instead of single-use paper cups.', '2026-06-15'),
(402, 1, 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80', 'Brought personal canvas bags for bulk grocery shopping.', '2026-06-13'),
(403, 1, 'https://images.unsplash.com/photo-1571068316344-75bc76f77890?auto=format&fit=crop&w=900&q=80', 'Rode an e-bike to work instead of driving.', '2026-06-10');

ALTER TABLE users           AUTO_INCREMENT = 1000;
ALTER TABLE activity_types  AUTO_INCREMENT = 10;
ALTER TABLE activity_logs   AUTO_INCREMENT = 600;
ALTER TABLE tips            AUTO_INCREMENT = 5;
ALTER TABLE challenges      AUTO_INCREMENT = 104;
ALTER TABLE friend_requests AUTO_INCREMENT = 3;
ALTER TABLE goals           AUTO_INCREMENT = 2;
ALTER TABLE eco_photos      AUTO_INCREMENT = 404;
