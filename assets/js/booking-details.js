// =====================================================
// assets/js/booking-details.js - LIVE TICKET TOTAL
// Owner: Yayra (booking system)
//
// Used on booking/details.php. Every time the tourist
// changes a ticket number, this script:
//   1. Adds up the number of people.
//   2. Works out the total price.
//   3. Warns if they chose more people than spaces left.
//   4. Turns the "Confirm booking" button on or off.
// The server checks everything again when the form is sent,
// so this is only to help the tourist while they choose.
// =====================================================

// Wait until the page has fully loaded before running.
document.addEventListener('DOMContentLoaded', function () {

    // -------------------------------------------------
    // 1. FIND THE PARTS OF THE PAGE WE NEED
    // -------------------------------------------------

    // The ticket form. If it isn't on the page, stop.
    const form = document.getElementById('ticket-form');
    if (!form) {
        return;
    }

    // All the number boxes (one per ticket type).
    const quantityInputs = form.querySelectorAll('.ticket-qty');

    // Where we show the number of people and the total price.
    const peopleCount = document.getElementById('people-count');
    const totalPrice = document.getElementById('total-price');

    // The warning area and the Confirm button.
    const message = document.getElementById('ticket-message');
    const bookButton = document.getElementById('book-button');

    // How many spaces are left, stored on the form by details.php
    // (data-spaces-left="13"). Number() turns the text into a number.
    const spacesLeft = Number(form.dataset.spacesLeft);

    // -------------------------------------------------
    // 2. WORK OUT THE TOTALS
    // -------------------------------------------------

    function updateTotals() {

        // Start counting from zero each time.
        let people = 0;
        let total = 0;

        // Go through every ticket number box.
        quantityInputs.forEach(function (input) {

            // Read the number typed in. parseInt() turns text into
            // a whole number; if the box is empty it gives NaN
            // ("not a number"), so we use 0 instead.
            let count = parseInt(input.value, 10);
            if (isNaN(count) || count < 0) {
                count = 0;
            }

            // Read this ticket type's price from data-price.
            const price = Number(input.dataset.price);

            // Add to the running totals.
            people += count;
            total += count * price;
        });

        // Show the totals on the page.
        // toFixed(2) always shows two decimals, e.g. 40.00
        peopleCount.textContent = people;
        totalPrice.textContent = total.toFixed(2);

        // -------------------------------------------------
        // 3. CHECK THE CHOICE AND SET THE BUTTON
        // -------------------------------------------------

        if (people > spacesLeft) {

            // Too many people: warn and turn the button off.
            message.textContent = 'Only ' + spacesLeft + ' spaces are left on this tour.';
            bookButton.disabled = true;

        } else if (people === 0) {

            // No tickets yet: no warning, but the button stays off.
            message.textContent = '';
            bookButton.disabled = true;

        } else {

            // A valid choice: clear the warning and turn the button on.
            message.textContent = '';
            bookButton.disabled = false;
        }
    }

    // -------------------------------------------------
    // 4. RUN updateTotals WHENEVER A NUMBER CHANGES
    // -------------------------------------------------

    // 'input' fires on every change: typing or clicking the arrows.
    quantityInputs.forEach(function (input) {
        input.addEventListener('input', updateTotals);
    });

    // Stop double bookings from double clicks: once the form is
    // sent, turn the button off and show that it's working.
    form.addEventListener('submit', function () {
        bookButton.disabled = true;
        bookButton.textContent = 'Booking...';
    });

    // Run once at the start so the totals show 0 correctly.
    updateTotals();
});
