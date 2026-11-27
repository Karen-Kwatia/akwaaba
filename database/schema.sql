
-- Select the Akwaaba 360 database.
USE akwaaba360;


-- ============================================
-- TABLE 1: USERS
-- Stores all user accounts on the platform.
-- A user can be a tourist, guide or administrator.
-- ============================================

CREATE TABLE users (
    -- Unique identifier for each user.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Stores the user's full name.
    name VARCHAR(100) NOT NULL,

    -- Stores the user's email address.
    -- Each email must be unique.
    email VARCHAR(150) NOT NULL UNIQUE,

    -- Stores the securely hashed password.
    password_hash VARCHAR(255) NOT NULL,

    -- Identifies whether the user is a tourist, guide or admin.
    role ENUM('tourist', 'guide', 'admin') NOT NULL DEFAULT 'tourist',

    -- Stores the user's country.
    country VARCHAR(100),

    -- Stores the user's preferred language.
    preferred_language VARCHAR(10),

    -- Records when the account was created.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================
-- TABLE 2: SITES
-- Stores information about Ghanaian tourist sites.
-- ============================================

CREATE TABLE sites (
    -- Unique identifier for each tourist site.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Stores the name of the tourist site.
    name VARCHAR(150) NOT NULL,

    -- Stores the region where the site is located.
    region VARCHAR(100) NOT NULL,

    -- Stores a description of the tourist site.
    description TEXT,

    -- Stores the site's opening hours.
    opening_hours VARCHAR(255),

    -- Stores the geographical latitude of the site.
    latitude DECIMAL(10, 7),

    -- Stores the geographical longitude of the site.
    longitude DECIMAL(10, 7),

    -- Stores the path to the site's cover image.
    cover_image VARCHAR(255),

    -- Describes accessibility information for visitors.
    accessibility TEXT,

    -- Stores the URL of an external 360-degree tour.
    tour_embed_url TEXT,

    -- Stores the URL of an aerial video.
    aerial_video_url TEXT,

    -- Gives credit to the owner of external media.
    media_credit VARCHAR(255)
) ENGINE=InnoDB;


-- ============================================
-- TABLE 3: SITE_PRICES
-- Stores ticket prices for different visitor categories.
-- ============================================

CREATE TABLE site_prices (
    -- Unique identifier for each price record.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the tourist site associated with this price.
    site_id INT NOT NULL,

    -- Identifies the visitor category for the ticket.
    ticket_type ENUM('Ghanaian', 'foreigner', 'student', 'child') NOT NULL,

    -- Stores the ticket price in Ghana cedis.
    price_ghs DECIMAL(10, 2) NOT NULL,

    -- Prevents duplicate prices for the same ticket category at one site.
    UNIQUE (site_id, ticket_type),

    -- Connects each price record to its tourist site.
    FOREIGN KEY (site_id) REFERENCES sites(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- TABLE 4: LANGUAGES
-- Stores languages supported by the platform.
-- ============================================

CREATE TABLE languages (
    -- Unique identifier for each language.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Stores the short language code, such as en or fr.
    code VARCHAR(10) NOT NULL UNIQUE,

    -- Stores the full language name.
    name VARCHAR(50) NOT NULL
) ENGINE=InnoDB;


-- ============================================
-- TABLE 5: GUIDES
-- Stores additional information about tour guides.
-- Each guide must have an existing user account.
-- ============================================

CREATE TABLE guides (
    -- Unique identifier for each guide profile.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Links the guide profile to a user account.
    user_id INT NOT NULL UNIQUE,

    -- Identifies the tourist site where the guide works.
    site_id INT NOT NULL,

    -- Stores the guide's biography.
    bio TEXT,

    -- Stores the path to the guide's profile photo.
    photo VARCHAR(255),

    -- Stores the guide's years of experience.
    years_experience INT DEFAULT 0,

    -- Connects the guide profile to a user account.
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the guide to a tourist site.
    FOREIGN KEY (site_id) REFERENCES sites(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- TABLE 6: GUIDE_LANGUAGES
-- Records the languages spoken by each tour guide.
-- This supports guides who speak multiple languages.
-- ============================================

CREATE TABLE guide_languages (
    -- Identifies the guide associated with this language.
    guide_id INT NOT NULL,

    -- Identifies the language spoken by the guide.
    language_id INT NOT NULL,

    -- Uses both identifiers as a combined primary key.
    -- This prevents the same language being assigned twice
    -- to the same guide.
    PRIMARY KEY (guide_id, language_id),

    -- Connects the record to an existing guide.
    FOREIGN KEY (guide_id) REFERENCES guides(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the record to an existing language.
    FOREIGN KEY (language_id) REFERENCES languages(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;



-- ============================================
-- TABLE 7: TOUR_SLOTS
-- Stores available dates and times for guided tours.
-- ============================================

CREATE TABLE tour_slots (
    -- Unique identifier for each available tour slot.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the tourist site where the tour takes place.
    site_id INT NOT NULL,

    -- Identifies the guide assigned to the tour.
    guide_id INT NOT NULL,

    -- Identifies the language in which the tour will be conducted.
    language_id INT NOT NULL,

    -- Stores the date of the scheduled tour.
    tour_date DATE NOT NULL,

    -- Stores the starting time of the tour.
    start_time TIME NOT NULL,

    -- Stores the maximum number of tourists allowed in the slot.
    capacity INT NOT NULL,

    -- Tracks how many places have already been booked.
    booked_count INT NOT NULL DEFAULT 0,

    -- Prevents a guide from having two slots at the same date and time.
    UNIQUE (guide_id, tour_date, start_time),

    -- Connects the slot to an existing tourist site.
    FOREIGN KEY (site_id) REFERENCES sites(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the slot to an existing tour guide.
    FOREIGN KEY (guide_id) REFERENCES guides(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the slot to an existing language.
    FOREIGN KEY (language_id) REFERENCES languages(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- TABLE 8: BOOKINGS
-- Stores tourist reservations for guided tours.
-- ============================================

CREATE TABLE bookings (
    -- Unique identifier for each booking.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the tourist who made the booking.
    user_id INT NOT NULL,

    -- Identifies the tour slot being booked.
    slot_id INT NOT NULL,

    -- Stores the number of people included in the booking.
    num_people INT NOT NULL,

    -- Stores the total booking cost in Ghana cedis.
    total_ghs DECIMAL(10, 2) NOT NULL,

    -- Tracks the current status of the booking.
    status ENUM('pending', 'confirmed', 'cancelled', 'completed')
        NOT NULL DEFAULT 'pending',

    -- Stores the unique token used to generate the booking QR code.
    qr_token VARCHAR(100) UNIQUE,

    -- Records when the booking was created.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Connects the booking to the tourist's account.
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    -- Connects the booking to its selected tour slot.
    FOREIGN KEY (slot_id) REFERENCES tour_slots(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- TABLE 9: PAYMENTS
-- Stores payment information for tourist bookings.
-- ============================================

CREATE TABLE payments (
    -- Unique identifier for each payment record.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the booking associated with this payment.
    booking_id INT NOT NULL,

    -- Stores the payment method selected by the tourist.
    method ENUM('momo', 'card', 'pay_at_site') NOT NULL,

    -- Stores the amount paid or to be paid in Ghana cedis.
    amount_ghs DECIMAL(10, 2) NOT NULL,

    -- Tracks the current status of the payment.
    status ENUM('pending', 'paid', 'failed', 'refunded')
        NOT NULL DEFAULT 'pending',

    -- Stores the transaction reference from the payment provider.
    provider_ref VARCHAR(150) UNIQUE,

    -- Records when the payment record was created.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Connects the payment to an existing booking.
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;




-- ============================================
-- TABLE 10: SITE_VIDEOS
-- Stores YouTube and TikTok videos associated
-- with each tourist destination.
-- ============================================

CREATE TABLE site_videos (
    -- Unique identifier for each video.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the tourist site associated with the video.
    site_id INT NOT NULL,

    -- Identifies the platform hosting the video.
    platform ENUM('youtube', 'tiktok') NOT NULL,

    -- Stores the original URL of the video.
    video_url VARCHAR(500) NOT NULL,

    -- Stores the URL used to embed the video on our website.
    embed_url VARCHAR(500) NOT NULL,

    -- Stores the title displayed above the video.
    title VARCHAR(200) NOT NULL,

    -- Stores a short description of the video.
    description TEXT,

    -- Determines whether the video is displayed on the website.
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',

    -- Records when the video was added.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Connects the video to an existing tourist site.
    FOREIGN KEY (site_id) REFERENCES sites(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;




-- ============================================
-- TABLE 11: POSTS
-- Stores photos, videos and TikTok content
-- shared by users on the experience feed.
-- ============================================

CREATE TABLE posts (
    -- Unique identifier for each post.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the user who created the post.
    user_id INT NOT NULL,

    -- Identifies the tourist site associated with the post.
    -- This can be empty if the post is not about a specific site.
    site_id INT,

    -- Stores the type of content being shared.
    post_type ENUM('image', 'video', 'tiktok') NOT NULL,

    -- Stores the caption or description written by the user.
    caption TEXT,

    -- Stores the path or URL of an uploaded image or video.
    media_url VARCHAR(500),

    -- Stores the embed URL for an external TikTok video.
    embed_url VARCHAR(500),

    -- Determines whether the post is awaiting approval,
    -- approved for display or removed.
    status ENUM('pending', 'approved', 'removed')
        NOT NULL DEFAULT 'pending',

    -- Records when the post was created.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Connects the post to the user who created it.
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the post to its associated tourist site.
    FOREIGN KEY (site_id) REFERENCES sites(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- TABLE 12: POST_LIKES
-- Records which users like which posts.
-- ============================================

CREATE TABLE post_likes (
    -- Identifies the post receiving the like.
    post_id INT NOT NULL,

    -- Identifies the user who liked the post.
    user_id INT NOT NULL,

    -- Records when the user liked the post.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Prevents the same user from liking a post more than once.
    PRIMARY KEY (post_id, user_id),

    -- Connects the like to an existing post.
    FOREIGN KEY (post_id) REFERENCES posts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the like to an existing user.
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- TABLE 13: COMMENTS
-- Stores comments users leave on posts.
-- ============================================

CREATE TABLE comments (
    -- Unique identifier for each comment.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the post receiving the comment.
    post_id INT NOT NULL,

    -- Identifies the user who wrote the comment.
    user_id INT NOT NULL,

    -- Stores the actual comment written by the user.
    comment TEXT NOT NULL,

    -- Records when the comment was created.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Connects the comment to an existing post.
    FOREIGN KEY (post_id) REFERENCES posts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the comment to the user who wrote it.
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;




-- ============================================
-- TABLE 14: REVIEWS
-- Stores tourists' ratings and reviews
-- of their guided tour experiences.
-- ============================================

CREATE TABLE reviews (
    -- Unique identifier for each review.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the user who wrote the review.
    user_id INT NOT NULL,

    -- Identifies the booking associated with the review.
    booking_id INT NOT NULL UNIQUE,

    -- Stores the rating given by the tourist.
    -- Ratings must be between 1 and 5.
    rating TINYINT NOT NULL,

    -- Stores the written feedback from the tourist.
    comment TEXT,

    -- Records when the review was submitted.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Ensures that ratings are between 1 and 5.
    CHECK (rating BETWEEN 1 AND 5),

    -- Connects the review to the user who wrote it.
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the review to the booking being reviewed.
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- TABLE 15: STAMPS
-- Stores Heritage Passport stamps earned by tourists.
-- A tourist can earn one stamp per tourist site.
-- ============================================

CREATE TABLE stamps (
    -- Unique identifier for each passport stamp.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifies the tourist who earned the stamp.
    user_id INT NOT NULL,

    -- Identifies the tourist site visited.
    site_id INT NOT NULL,

    -- Identifies the booking associated with the visit.
    booking_id INT NOT NULL,

    -- Records when the tourist earned the stamp.
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Prevents a tourist from earning duplicate stamps
    -- for the same site.
    UNIQUE (user_id, site_id),

    -- Connects the stamp to the tourist's account.
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the stamp to the tourist site.
    FOREIGN KEY (site_id) REFERENCES sites(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Connects the stamp to the booking that confirms the visit.
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;



-- Create a table for securely storing persistent login tokens.
CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create a table for storing password reset requests.
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- Create a table to store multiple images for each destination.
CREATE TABLE site_images (
    -- Give every image a unique ID.
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Link the image to its destination in the sites table.
    site_id INT NOT NULL,

    -- Store the image's relative file path.
    image_path VARCHAR(255) NOT NULL,

    -- Describe the image for accessibility.
    alt_text VARCHAR(255),

    -- Control the order in which images appear.
    sort_order INT DEFAULT 1,

    -- Automatically record when the image was added.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Ensure each image belongs to a valid destination.
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);



-- Add a category to classify each tourist destination.
ALTER TABLE sites
ADD COLUMN category VARCHAR(100) NULL AFTER name;


-- Control whether a destination is displayed publicly.
ALTER TABLE sites
ADD COLUMN status ENUM('active', 'inactive')
NOT NULL DEFAULT 'active' AFTER media_credit;
