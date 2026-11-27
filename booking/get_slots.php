<?php
// =====================================================
// booking/get_slots.php - SEND AVAILABLE TOUR SLOTS AS JSON
// Owner: Yayra (booking system)
//
// This file is not a page people visit. Our JavaScript
// (assets/js/booking.js) calls it in the background when
// a tourist picks a language, for example:
//   /akwaaba/booking/get_slots.php?site_id=1&language_id=2
//
// It replies with a list of upcoming slots in JSON
// (a text format JavaScript can read easily), so the page
// can show dates and times without reloading.
// =====================================================

// Load the database connection ($pdo).
// We only need the database here, not the login system.
require_once __DIR__ . '/../includes/db.php';

// Tell the browser our reply is JSON, not an HTML page.
header('Content-Type: application/json');

// -----------------------------------------------------
// 1. READ AND CHECK THE INPUT
// -----------------------------------------------------

// Read the site and language from the address.
// (int) turns them into whole numbers, so bad input becomes 0.
$siteId = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;
$languageId = isset($_GET['language_id']) ? (int) $_GET['language_id'] : 0;

// If either number is missing or invalid, stop here.
if ($siteId <= 0 || $languageId <= 0) {

    // 400 means "bad request": the input was wrong.
    http_response_code(400);

    // Send an error message as JSON and stop the script.
    echo json_encode(['error' => 'Please choose a site and a language.']);
    exit;
}

// -----------------------------------------------------
// 2. FIND UPCOMING SLOTS THAT STILL HAVE SPACE
// -----------------------------------------------------

// Get each slot's id, date, time, spaces left and guide name.
// We join three tables: tour_slots -> guides -> users,
// because the guide's name is stored in the users table.
$slotQuery = $pdo->prepare(
    "SELECT tour_slots.id,
            tour_slots.tour_date,
            tour_slots.start_time,
            -- spaces left = maximum people minus people already booked
            (tour_slots.capacity - tour_slots.booked_count) AS spaces_left,
            tour_slots.capacity,
            users.name AS guide_name
     FROM tour_slots
     INNER JOIN guides ON tour_slots.guide_id = guides.id
     INNER JOIN users  ON guides.user_id = users.id
     WHERE tour_slots.site_id = ?
       AND tour_slots.language_id = ?
       -- only slots that are not full
       AND tour_slots.booked_count < tour_slots.capacity
       -- only slots in the future (later today counts too)
       AND (tour_slots.tour_date > CURDATE()
            OR (tour_slots.tour_date = CURDATE()
                AND tour_slots.start_time > CURTIME()))
     -- earliest date first, then earliest time
     ORDER BY tour_slots.tour_date, tour_slots.start_time"
);

// Run the query with the site and language filled in safely.
$slotQuery->execute([$siteId, $languageId]);

// Get all matching slots as a list.
$rows = $slotQuery->fetchAll();

// -----------------------------------------------------
// 3. TIDY THE DATA AND SEND IT BACK
// -----------------------------------------------------

// Build a clean list to send to JavaScript.
$slots = [];

foreach ($rows as $row) {

    // Turn the date text (e.g. 2026-12-05) into a timestamp
    // so we can format it in a friendly way.
    $dateStamp = strtotime($row['tour_date']);

    // Add one slot to the list, with ready-to-show labels.
    $slots[] = [
        // The slot's id, sent to the next step when chosen.
        'id' => (int) $row['id'],

        // The raw date, used to group slots by day.
        'date' => $row['tour_date'],

        // Friendly date, e.g. "Sat 5 Dec 2026".
        'date_label' => date('D j M Y', $dateStamp),

        // Friendly time, e.g. "9:00 AM".
        'time_label' => date('g:i A', strtotime($row['start_time'])),

        // How many places are still free, e.g. 13.
        'spaces_left' => (int) $row['spaces_left'],

        // The maximum group size, e.g. 15.
        'capacity' => (int) $row['capacity'],

        // The guide leading this tour.
        'guide_name' => $row['guide_name'],
    ];
}

// Convert the list to JSON text and send it to the browser.
echo json_encode(['slots' => $slots]);
