
-- Select the Akwaaba 360 database.
USE akwaaba360;


-- ============================================
-- SAMPLE DATA 1: USERS
-- Creates sample tourist, guide and admin accounts.
-- These are demonstration accounts, not real users.
-- ============================================

INSERT INTO users
(name, email, password_hash, role, country, preferred_language)
VALUES
('Ama Mensah', 'ama@example.com', 'DEMO_HASH_AMA', 'tourist', 'Ghana', 'en'),
('Kwame Asante', 'kwame@example.com', 'DEMO_HASH_KWAME', 'tourist', 'Ghana', 'tw'),
('Yaa Boateng', 'yaa@example.com', 'DEMO_HASH_YAA', 'guide', 'Ghana', 'en'),
('Admin User', 'admin@example.com', 'DEMO_HASH_ADMIN', 'admin', 'Ghana', 'en');


-- ============================================
-- SAMPLE DATA 2: SITES
-- Adds some popular tourist destinations in Ghana.
-- ============================================

INSERT INTO sites
(name, region, description, opening_hours, latitude, longitude, cover_image, accessibility, tour_embed_url, aerial_video_url, media_credit)
VALUES
(
    'Cape Coast Castle',
    'Central Region',
    'A historic castle associated with the transatlantic slave trade.',
    'Monday - Sunday, 9:00 AM - 5:00 PM',
    5.1053,
    -1.2466,
    'assets/images/cape-coast-castle.jpg',
    'Guided tours available.',
    NULL,
    NULL,
    'Sample project record'
),
(
    'Kakum National Park',
    'Central Region',
    'A national park known for its rainforest canopy walkway.',
    'Monday - Sunday, 8:00 AM - 4:00 PM',
    5.3550,
    -1.3900,
    'assets/images/kakum-national-park.jpg',
    'Canopy walkway access depends on visitor requirements.',
    NULL,
    NULL,
    'Sample project record'
),
(
    'Mole National Park',
    'Savannah Region',
    'A wildlife park known for elephant sightings and safari experiences.',
    'Monday - Sunday, 6:00 AM - 6:00 PM',
    9.2610,
    -1.8500,
    'assets/images/mole-national-park.jpg',
    'Safari activities available.',
    NULL,
    NULL,
    'Sample project record'
);


-- ============================================
-- SAMPLE DATA 3: LANGUAGES
-- Adds languages supported by the platform.
-- ============================================

INSERT INTO languages (code, name)
VALUES
('en', 'English'),
('tw', 'Twi'),
('fr', 'French'),
('es', 'Spanish');



-- ============================================
-- SAMPLE DATA 4: SITE_PRICES
-- Adds demonstration ticket prices for each site.
-- These prices are placeholders, not official fees.
-- ============================================

INSERT INTO site_prices
(site_id, ticket_type, price_ghs)
VALUES
-- Ticket prices for Cape Coast Castle.
(1, 'Ghanaian', 20.00),
(1, 'foreigner', 50.00),
(1, 'student', 10.00),
(1, 'child', 5.00),

-- Ticket prices for Kakum National Park.
(2, 'Ghanaian', 20.00),
(2, 'foreigner', 50.00),
(2, 'student', 10.00),
(2, 'child', 5.00),

-- Ticket prices for Mole National Park.
(3, 'Ghanaian', 20.00),
(3, 'foreigner', 50.00),
(3, 'student', 10.00),
(3, 'child', 5.00);


-- ============================================
-- SAMPLE DATA 5: GUIDES
-- Creates a sample guide profile.
-- The guide must already have a user account.
-- ============================================

INSERT INTO guides
(user_id, site_id, bio, photo, years_experience)
VALUES
(
    3,
    1,
    'An experienced guide who enjoys sharing the history of Cape Coast Castle.',
    'assets/images/yaa-guide.jpg',
    4
);



-- ============================================
-- SAMPLE DATA 6: GUIDE_LANGUAGES
-- Assigns languages spoken by each tour guide.
-- ============================================

INSERT INTO guide_languages
(guide_id, language_id)
VALUES
-- Yaa Boateng speaks English.
(1, 1),

-- Yaa Boateng also speaks Twi.
(1, 2);


-- ============================================
-- SAMPLE DATA 7: TOUR_SLOTS
-- Creates sample guided-tour schedules.
-- ============================================

INSERT INTO tour_slots
(site_id, guide_id, language_id, tour_date, start_time, capacity, booked_count)
VALUES
-- First tour session: English-speaking visitors.
(1, 1, 1, '2026-12-05', '09:00:00', 15, 0),

-- Second tour session: Twi-speaking visitors.
(1, 1, 2, '2026-12-05', '11:00:00', 15, 0);



-- ============================================
-- SAMPLE DATA 8: BOOKINGS
-- Creates sample tourist reservations.
-- ============================================

INSERT INTO bookings
(user_id, slot_id, num_people, total_ghs, status, qr_token)
VALUES
-- Ama books two places on the English tour.
(1, 1, 2, 40.00, 'confirmed', 'AKW-AMA-001'),

-- Kwame books one place on the Twi tour.
(2, 2, 1, 20.00, 'confirmed', 'AKW-KWA-002');


-- Update the number of booked places in each tour slot.
UPDATE tour_slots
SET booked_count = 2
WHERE id = 1;

UPDATE tour_slots
SET booked_count = 1
WHERE id = 2;


-- ============================================
-- SAMPLE DATA 9: SITE_VIDEOS
-- Adds example YouTube and TikTok videos.
-- These are placeholder URLs for demonstration.
-- Replace them with valid video URLs before testing embeds.
-- ============================================

INSERT INTO site_videos
(site_id, platform, video_url, embed_url, title, description, status)
VALUES
(
    1,
    'youtube',
    'https://www.youtube.com/watch?v=VIDEO_ID_1',
    'https://www.youtube.com/embed/VIDEO_ID_1',
    'Exploring Cape Coast Castle',
    'A video introducing the history and attractions of Cape Coast Castle.',
    'active'
),
(
    2,
    'tiktok',
    'https://www.tiktok.com/@example/video/123456789',
    'https://www.tiktok.com/embed/v2/123456789',
    'Discover Kakum National Park',
    'A short video showcasing the rainforest and canopy walkway.',
    'active'
);


-- ============================================
-- SAMPLE DATA 10: PAYMENTS
-- Creates payment records for the sample bookings.
-- ============================================

INSERT INTO payments
(booking_id, method, amount_ghs, status, provider_ref)
VALUES
-- Ama's booking was paid using mobile money.
(1, 'momo', 40.00, 'paid', 'DEMO-PAY-AMA-001'),

-- Kwame will pay at the tourist site.
(2, 'pay_at_site', 20.00, 'pending', 'DEMO-PAY-KWA-002');



-- ============================================
-- SAMPLE DATA 11: POSTS
-- Creates sample posts for the community feed.
-- ============================================

INSERT INTO posts
(user_id, site_id, post_type, caption, media_url, embed_url, status)
VALUES
(
    1,
    1,
    'image',
    'Had an amazing experience exploring Cape Coast Castle!',
    'uploads/cape-coast-experience.jpg',
    NULL,
    'approved'
),
(
    2,
    2,
    'tiktok',
    'Kakum National Park is definitely worth visiting!',
    NULL,
    'https://www.tiktok.com/embed/v2/123456789',
    'approved'
);


-- ============================================
-- SAMPLE DATA 12: POST_LIKES
-- Records users who liked community posts.
-- ============================================

INSERT INTO post_likes
(post_id, user_id)
VALUES
-- Kwame likes Ama's post.
(1, 2),

-- Ama likes Kwame's post.
(2, 1),

-- Yaa also likes Ama's post.
(1, 3);


-- ============================================
-- SAMPLE DATA 13: COMMENTS
-- Adds sample comments to community posts.
-- ============================================

INSERT INTO comments
(post_id, user_id, comment)
VALUES
(
    1,
    2,
    'This looks amazing! I would love to visit again.'
),
(
    2,
    1,
    'The canopy walkway looks incredible!'
);


-- ============================================
-- SAMPLE DATA 14: REVIEWS
-- Stores a tourist's review after completing a tour.
-- ============================================

-- Mark Ama's booking as completed.
-- This allows us to demonstrate a completed tour review.
UPDATE bookings
SET status = 'completed'
WHERE id = 1;

INSERT INTO reviews
(user_id, booking_id, rating, comment)
VALUES
(
    1,
    1,
    5,
    'The guide was knowledgeable and the experience was excellent!'
);


-- ============================================
-- SAMPLE DATA 15: STAMPS
-- Awards Heritage Passport stamps to tourists.
-- ============================================

INSERT INTO stamps
(user_id, site_id, booking_id)
VALUES
(
    1,
    1,
    1
);



-- =========================================
-- SITE IMAGES
-- Sample images for the three destinations.
-- =========================================

-- Add images for Cape Coast Castle (site ID 1).
INSERT INTO site_images (site_id, image_path, alt_text, sort_order)
VALUES
(1, 'uploads/sites/cape_coast_castle/aerial-view.jpg', 'Aerial view of Cape Coast Castle', 1),
(1, 'uploads/sites/cape_coast_castle/left-sided-view.jpg', 'Left-sided view of Cape Coast Castle', 2),
(1, 'uploads/sites/cape_coast_castle/right-sided-view.jpg', 'Right-sided view of Cape Coast Castle', 3);

-- Add images for Kakum National Park (site ID 2).
INSERT INTO site_images (site_id, image_path, alt_text, sort_order)
VALUES
(2, 'uploads/sites/kakum_national_park/canopy-walk-1.jpg', 'Canopy walkway at Kakum National Park', 1),
(2, 'uploads/sites/kakum_national_park/canopy-walk-2.jpeg', 'Another view of the Kakum canopy walkway', 2),
(2, 'uploads/sites/kakum_national_park/treehouse.jpg', 'Treehouse at Kakum National Park', 3);

-- Add images for Mole National Park (site ID 3).
INSERT INTO site_images (site_id, image_path, alt_text, sort_order)
VALUES
(3, 'uploads/sites/mole_national_park/animals.jpg', 'Wildlife at Mole National Park', 1),
(3, 'uploads/sites/mole_national_park/aerial-view.jpg', 'Aerial view of Mole National Park', 2),
(3, 'uploads/sites/mole_national_park/mole-park-entrance.jpg', 'Entrance to Mole National Park', 3);



-- Update Cape Coast Castle.
UPDATE sites
SET
    category = 'Historical Site',
    status = 'active'
WHERE name = 'Cape Coast Castle';

-- Update Kakum National Park.
UPDATE sites
SET
    category = 'National Park',
    status = 'active'
WHERE name = 'Kakum National Park';

-- Update Mole National Park.
UPDATE sites
SET
    category = 'National Park',
    status = 'active'
WHERE name = 'Mole National Park';

-- Display the updated destination records.
SELECT id, name, category, status
FROM sites
ORDER BY id;
