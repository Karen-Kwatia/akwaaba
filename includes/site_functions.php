
<?php

// Load the existing database connection.
require_once __DIR__ . '/db.php';

/**
 * Retrieve active tourist destinations.
 *
 * Optional region and category filters can be supplied.
 */
function getActiveSites($region = null, $category = null)
{
    global $pdo;

    // Start with a query that retrieves active destinations.
    $sql = "SELECT * FROM sites WHERE status = 'active'";

    // Store filter values separately for prepared statements.
    $params = [];

    // Filter by region when one is provided.
    if (!empty($region)) {
        $sql .= " AND region = :region";
        $params[':region'] = $region;
    }

    // Filter by category when one is provided.
    if (!empty($category)) {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }

    // Display destinations alphabetically.
    $sql .= " ORDER BY name ASC";

    // Prepare and execute the database query.
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Return all matching destinations.
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve one active tourist destination by its ID.
 */
function getSiteById($siteId)
{
    global $pdo;

    // Retrieve the selected active destination.
    $sql = "SELECT * FROM sites
            WHERE id = :id AND status = 'active'
            LIMIT 1";

    // Prepare and execute the query securely.
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $siteId]);

    // Return one destination or false if it does not exist.
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Retrieve all images belonging to a destination.
 */
function getSiteImages($siteId)
{
    global $pdo;

    // Retrieve images in their intended display order.
    $sql = "SELECT * FROM site_images
            WHERE site_id = :site_id
            ORDER BY sort_order ASC, id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':site_id' => $siteId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve ticket prices for a destination.
 */
function getSitePrices($siteId)
{
    global $pdo;

    // Retrieve the available visitor ticket categories and prices.
    $sql = "SELECT * FROM site_prices
            WHERE site_id = :site_id
            ORDER BY price_ghs ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':site_id' => $siteId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve active videos belonging to a destination.
 */
function getSiteVideos($siteId)
{
    global $pdo;

    // Retrieve only videos approved for display.
    $sql = "SELECT * FROM site_videos
            WHERE site_id = :site_id
            AND status = 'active'
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':site_id' => $siteId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve guides associated with a destination,
 * including the languages each guide speaks.
 */
function getSiteGuides($siteId)
{
    global $pdo;

    // Retrieve guide profiles and their corresponding user names.
    $sql = "SELECT
                guides.id AS guide_id,
                guides.bio,
                guides.photo,
                guides.years_experience,
                users.name AS guide_name,
                GROUP_CONCAT(
                    DISTINCT languages.name
                    ORDER BY languages.name
                    SEPARATOR ', '
                ) AS languages_spoken
            FROM guides

            // Connect each guide profile to its user account.
            INNER JOIN users
                ON guides.user_id = users.id

            // Retrieve languages assigned to each guide.
            LEFT JOIN guide_languages
                ON guides.id = guide_languages.guide_id

            // Retrieve the actual language names.
            LEFT JOIN languages
                ON guide_languages.language_id = languages.id

            // Filter guides by the selected destination.
            WHERE guides.site_id = :site_id

            // Group language records under each guide.
            GROUP BY
                guides.id,
                guides.bio,
                guides.photo,
                guides.years_experience,
                users.name

            // Display guides alphabetically.
            ORDER BY users.name ASC";

    // Prepare and execute the database query.
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':site_id' => $siteId]);

    // Return guide information and their spoken languages.
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve available regions for active destinations.
 */
function getSiteRegions()
{
    global $pdo;

    // Retrieve each region only once.
    $sql = "SELECT DISTINCT region
            FROM sites
            WHERE status = 'active'
            ORDER BY region ASC";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Retrieve available categories for active destinations.
 */
function getSiteCategories()
{
    global $pdo;

    // Retrieve each non-empty category only once.
    $sql = "SELECT DISTINCT category
            FROM sites
            WHERE status = 'active'
            AND category IS NOT NULL
            AND category <> ''
            ORDER BY category ASC";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
