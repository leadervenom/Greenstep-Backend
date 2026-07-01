-- GreenStep API — Seed / Demo Data
--
-- Run order:
--   1. greenstep.sql   (creates schema)
--   2. query3.sql      (adds password_hash, role, updated_at to users)
--   3. seed_data.sql   (this file)
--
-- All demo accounts use the password:  Password123!
-- (hashed with PHP's password_hash(..., PASSWORD_DEFAULT) / bcrypt cost 10,
--  so password_verify() in AuthController works against them out of the box)
 
USE greenstep_api;
 
SET FOREIGN_KEY_CHECKS = 0;
 
-- -----------------------------------------------------------------
-- 1. USERS
--    id=1   is the "current user" referenced throughout the app's
--           mock data (eco_points 1240 / gained_today 80, leaderboard
--           rank 3, "You (GreenRunner)")
--    201-205 are the friends / pending-request senders used by the
--           friends & leaderboard pages
--    999    is a spare admin account
-- -----------------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, role, eco_points, gained_today, created_at) VALUES
(1,   'You (GreenRunner)', 'you@greenstep.app',        '$2b$10$q5lTM0frdwjI/ajbwXELo.2deOV8gGR57oXTmbPrTnJSzv.VU5f2q', 'member', 1240, 80,  '2026-05-01 09:00:00'),
(201, 'Sarah Connor',      'sarah.connor@greenstep.app','$2b$10$RWmpkgMWp95O6fFitiIlQOlQgR7MiP2fbxpJSULvY1rejt4orz1J2', 'member', 1420, 0,   '2026-04-12 10:15:00'),
(202, 'Alex Mercer',       'alex.mercer@greenstep.app', '$2b$10$.8r6Ja6Pk5xHJR23JEtZs.CHU9W.tQfa5/uqpAPfIHD8myI6zBdum', 'member', 1100, 0,   '2026-04-18 14:30:00'),
(203, 'Emma Watson',       'emma.watson@greenstep.app', '$2b$10$04IVkxYBMfRS21djbdMysedXB5Oq5ZvilQP6wwi2khExr5FLou52O', 'member', 1680, 0,   '2026-03-22 08:45:00'),
(204, 'James Sutherland',  'james.sutherland@greenstep.app', '$2b$10$KwCLTBwpvonFp15b7FuLtOs3cWS61UqLZcGX5l6wn3KXJ4Rn.Ijlq', 'member', 860,  0,   '2026-06-01 16:00:00'),
(205, 'Clara Oswald',      'clara.oswald@greenstep.app','$2b$10$OREjNYtrSA2AFouoft2ATOlam3/Xw/hn5qNbN4/HOeQ9Vu2q14RHS', 'member', 540,  0,   '2026-06-10 11:20:00'),
(999, 'GreenStep Admin',   'admin@greenstep.app',       '$2b$10$eiL5RbXzHzSzSg5SNFpZveaBu.oKri/ltUxpt/LAzby1e9GKle7Om', 'admin',  0,    0,   '2026-01-01 00:00:00');
 
-- -----------------------------------------------------------------
-- 2. ACTIVITY TYPES (matches Data/data.php exactly)
-- -----------------------------------------------------------------
INSERT INTO activity_types (id, category, name, unit, kg_co2_per_unit, info) VALUES
(1, 'Transport', 'Car (Petrol)',  'km',    0.21,  'Car(Petrol) - 0.21kg/km'),
(2, 'Transport', 'Train',        'km',    0.04,  'Train - 0.04kg/km'),
(3, 'Transport', 'Bus',          'km',    0.08,  'Bus - 0.08kg/km'),
(4, 'Transport', 'Flight',       'km',    0.15,  'Flight - 0.15kg/km'),
(5, 'Food',      'Red Meat Meal','meal',  6.00,  'Red Meat - 6.00kg/meal'),
(6, 'Food',      'Mixed Meal',   'meal',  2.50,  'Mixed Meal - 2.50kg/meal'),
(7, 'Food',      'Veg Meal',     'meal',  0.70,  'Veg Meal - 0.70kg/meal'),
(8, 'Energy',    'Electricity',  'kWh',   0.50,  'Electricity - 0.50kg/kWh'),
(9, 'Waste',     'Recycling',    'count', -0.50, 'Recycling - Saves 0.50kg/item');
 
-- -----------------------------------------------------------------
-- 3. ACTIVITY LOGS for user 1 (today's log + recent history)
-- -----------------------------------------------------------------
INSERT INTO activity_logs (id, user_id, activity_type_id, amount, emissions_kg, logged_at, logged_date) VALUES
-- "Today" (2026-06-15) — matches today_log_record / dashboard breakdown
(501, 1, 1, 30,   6.30, '2026-06-15 08:30:00', '2026-06-15'),
(502, 1, 7, 1,    0.70, '2026-06-15 12:45:00', '2026-06-15'),
(503, 1, 5, 1,    6.00, '2026-06-15 18:00:00', '2026-06-15'),
-- Prior days — matches my_history totals
(504, 1, 1, 40,   8.40, '2026-06-14 09:00:00', '2026-06-14'),
(505, 1, 6, 2,    5.00, '2026-06-14 13:00:00', '2026-06-14'),
(506, 1, 8, 9,    4.70, '2026-06-14 20:00:00', '2026-06-14'),
(507, 1, 3, 20,   1.60, '2026-06-13 09:00:00', '2026-06-13'),
(508, 1, 7, 1,    0.70, '2026-06-13 13:00:00', '2026-06-13'),
(509, 1, 1, 25,   5.25, '2026-06-13 18:30:00', '2026-06-13'),
(510, 1, 1, 30,   6.30, '2026-06-12 08:00:00', '2026-06-12'),
(511, 1, 5, 1,    6.00, '2026-06-12 12:30:00', '2026-06-12'),
(512, 1, 8, 5,    2.50, '2026-06-12 19:00:00', '2026-06-12'),
(513, 1, 9, 1,   -0.50, '2026-06-12 19:05:00', '2026-06-12'),
(514, 1, 2, 10,   0.40, '2026-06-12 20:00:00', '2026-06-12');
 
-- -----------------------------------------------------------------
-- 4. TIPS (matches tip_library in Data/data.php)
-- -----------------------------------------------------------------
INSERT INTO tips (id, title, body, category) VALUES
(1, 'Transit Switch',     'Swap two local vehicle commutes this week for public bus trips.', 'Transport'),
(2, 'Meatless Mondays',   'Skipping red meat meals for one day saves substantial production-chain carbon.', 'Food'),
(3, 'Smart Thermostats',  'Lower cooling systems by 1 degree during high heat peaks to preserve regional power grid draw.', 'Energy'),
(4, 'Compost Scrap',      'Sort biodegradable organic waste away from landfills to lower localized methane outputs.', 'Waste');
 
-- -----------------------------------------------------------------
-- 5. CHALLENGES (matches challenges in Data/data.php)
-- -----------------------------------------------------------------
INSERT INTO challenges (id, title, description, target_type, group_progress_percent, is_active, is_completed) VALUES
(101, 'Green Commute Race', 'Swap private cars for trains or buses to lower city traffic footprints.', 'Transport', 68.50,  1, 0),
(102, 'The Vegan Streak',   'Log exclusively vegetarian meals for 7 consecutive days.',                'Food',      42.00,  1, 0),
(103, 'Zero Waste Heroes',  'Log 20 verified recycling milestones.',                                   'Waste',    100.00,  0, 1);
 
-- -----------------------------------------------------------------
-- 6. CHALLENGE MEMBERS
-- -----------------------------------------------------------------
INSERT INTO challenge_members (challenge_id, user_id) VALUES
(101, 1), (101, 201), (101, 203),
(102, 202), (102, 204),
(103, 1), (103, 205);
 
-- -----------------------------------------------------------------
-- 7. FRIENDSHIPS (mutual rows, matching acceptFriendRequest behavior)
--    user 1 is already friends with 201 / 202 / 203
-- -----------------------------------------------------------------
INSERT INTO friendships (user_id, friend_id) VALUES
(1, 201), (201, 1),
(1, 202), (202, 1),
(1, 203), (203, 1);
 
-- -----------------------------------------------------------------
-- 8. FRIEND REQUESTS (pending, matching pending_requests for user 1)
-- -----------------------------------------------------------------
INSERT INTO friend_requests (id, sender_id, receiver_id, requested_at) VALUES
(1, 204, 1, '2026-06-15 06:00:00'),
(2, 205, 1, '2026-06-14 08:00:00');
 
-- -----------------------------------------------------------------
-- 9. GOALS (matches my_goal_page in Data/data.php)
-- -----------------------------------------------------------------
INSERT INTO goals (id, user_id, title, target_to_reduce_kg, duration, start_date, emissions_reduced_so_far_kg) VALUES
(1, 1, 'Cut Summer Carbon Footprint', 50.00, '30 Days', '2026-06-01', 22.40);
 
-- -----------------------------------------------------------------
-- 10. ECO PHOTOS (matches eco_photos_page in Data/data.php)
-- -----------------------------------------------------------------
INSERT INTO eco_photos (id, user_id, image_url, achievement, uploaded_on) VALUES
(401, 1, 'uploads/reusable_cup.jpg',   'Used stainless steel flask at coffee shop instead of single-use paper cups.', '2026-06-15'),
(402, 1, 'uploads/canvas_bag.jpg',     'Brought personal canvas bags for bulk grocery shopping.',                     '2026-06-13'),
(403, 1, 'uploads/ebike_commute.jpg',  'Rode e-bike to work over 15km instead of driving standard auto.',            '2026-06-10');
 
SET FOREIGN_KEY_CHECKS = 1;
 
-- -----------------------------------------------------------------
-- Make sure AUTO_INCREMENT counters are ahead of the manually-
-- inserted IDs above so future INSERTs (registrations, new logs,
-- new challenges, etc.) don't collide with the seed rows.
-- -----------------------------------------------------------------
ALTER TABLE users           AUTO_INCREMENT = 1000;
ALTER TABLE activity_types  AUTO_INCREMENT = 10;
ALTER TABLE activity_logs   AUTO_INCREMENT = 600;
ALTER TABLE tips            AUTO_INCREMENT = 5;
ALTER TABLE challenges      AUTO_INCREMENT = 104;
ALTER TABLE friend_requests AUTO_INCREMENT = 3;
ALTER TABLE goals           AUTO_INCREMENT = 2;
ALTER TABLE eco_photos      AUTO_INCREMENT = 404;