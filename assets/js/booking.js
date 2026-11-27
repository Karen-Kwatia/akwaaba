// =====================================================
// assets/js/booking.js - DATE AND TIME PICKER
// Owner: Yayra (booking system)
//
// Used on booking/book.php. When the tourist picks a
// language, this script asks get_slots.php for the
// available tour slots, then shows:
//   1. a button for each available date
//   2. a button for each time on the chosen date
// When a time is picked, the "Continue" button turns on.
// =====================================================

// Wait until the whole page has loaded before running,
// so all the elements we need already exist.
document.addEventListener('DOMContentLoaded', function () {

    // -------------------------------------------------
    // 1. FIND THE PARTS OF THE PAGE WE WILL CHANGE
    // -------------------------------------------------

    // The language dropdown.
    const languageSelect = document.getElementById('language');

    // The date step (hidden at first) and where its buttons go.
    const dateStep = document.getElementById('date-step');
    const dateOptions = document.getElementById('date-options');

    // The time step (hidden at first) and where its buttons go.
    const timeStep = document.getElementById('time-step');
    const timeOptions = document.getElementById('time-options');

    // The message area, the hidden slot box and the Continue button.
    const message = document.getElementById('slot-message');
    const slotIdInput = document.getElementById('slot-id');
    const continueButton = document.getElementById('continue-button');

    // If the language dropdown isn't on the page (for example,
    // the site has no tours), there is nothing to do, so stop.
    if (!languageSelect) {
        return;
    }

    // The site id, stored on the dropdown by book.php
    // (data-site-id="1"). We send it to get_slots.php.
    const siteId = languageSelect.dataset.siteId;

    // This will hold the slots we get back, grouped by date:
    // { "2026-12-05": [slot, slot], "2026-12-06": [slot] }
    let slotsByDate = {};

    // -------------------------------------------------
    // 2. SMALL HELPER FUNCTIONS
    // -------------------------------------------------

    // Clear the chosen slot and turn the Continue button off.
    // We call this whenever an earlier choice changes.
    function resetChosenSlot() {
        slotIdInput.value = '';
        continueButton.disabled = true;
    }

    // Show a message to the tourist (or clear it with '').
    function showMessage(text) {
        message.textContent = text;
    }

    // Mark one button as selected and un-mark the others
    // in the same group, so only one looks chosen.
    function selectButton(container, chosenButton) {

        // Go through every button in this group...
        container.querySelectorAll('button').forEach(function (button) {

            // ...and remove the "selected" look from each one.
            button.classList.remove('selected');

            // Tell screen readers it is not pressed.
            button.setAttribute('aria-pressed', 'false');
        });

        // Then add the "selected" look to the clicked button.
        chosenButton.classList.add('selected');
        chosenButton.setAttribute('aria-pressed', 'true');
    }

    // -------------------------------------------------
    // 3. WHEN THE TOURIST PICKS A LANGUAGE
    // -------------------------------------------------

    languageSelect.addEventListener('change', function () {

        // A new language means earlier date/time choices no longer apply.
        resetChosenSlot();
        dateOptions.innerHTML = '';
        timeOptions.innerHTML = '';
        dateStep.hidden = true;
        timeStep.hidden = true;

        // The chosen language's id (empty if they picked the placeholder).
        const languageId = languageSelect.value;

        // If they went back to "Choose a language", stop here.
        if (!languageId) {
            showMessage('');
            return;
        }

        // Let the tourist know we are fetching the slots.
        showMessage('Loading available dates...');

        // Build the address of get_slots.php with our two values.
        // encodeURIComponent() makes the values safe to put in a web address.
        const url = 'get_slots.php?site_id=' + encodeURIComponent(siteId)
                  + '&language_id=' + encodeURIComponent(languageId);

        // fetch() asks the server for data in the background,
        // without reloading the page.
        fetch(url)

            // When the reply arrives, read it as JSON.
            .then(function (response) {

                // If the server reported a problem, jump to .catch below.
                if (!response.ok) {
                    throw new Error('Server error');
                }
                return response.json();
            })

            // Now we have the data: build the date buttons.
            .then(function (data) {
                showDates(data.slots);
            })

            // If anything went wrong (no internet, server error...).
            .catch(function () {
                showMessage('Sorry, we could not load the tour dates. Please try again.');
            });
    });

    // -------------------------------------------------
    // 4. SHOW ONE BUTTON PER AVAILABLE DATE
    // -------------------------------------------------

    function showDates(slots) {

        // Start with an empty grouping each time.
        slotsByDate = {};

        // Put each slot into its date's group.
        slots.forEach(function (slot) {

            // If this date has no group yet, create an empty list for it.
            if (!slotsByDate[slot.date]) {
                slotsByDate[slot.date] = [];
            }

            // Add the slot to its date's list.
            slotsByDate[slot.date].push(slot);
        });

        // Get the list of dates (already in order from the server).
        const dates = Object.keys(slotsByDate);

        // If there are no dates, tell the tourist and stop.
        if (dates.length === 0) {
            showMessage('No tours are available in this language right now.');
            return;
        }

        // Clear the loading message.
        showMessage('');

        // Create one button for each date.
        dates.forEach(function (date) {

            // Make a new <button> element.
            const button = document.createElement('button');

            // type="button" stops it from submitting the form when clicked.
            button.type = 'button';
            button.className = 'option-button';

            // Show the friendly date, e.g. "Sat 5 Dec 2026".
            // textContent (not innerHTML) keeps the text safe.
            button.textContent = slotsByDate[date][0].date_label;

            // When this date is clicked, show its times.
            button.addEventListener('click', function () {
                selectButton(dateOptions, button);
                showTimes(date);
            });

            // Add the button to the page.
            dateOptions.appendChild(button);
        });

        // Reveal the date step now that it has buttons.
        dateStep.hidden = false;
    }

    // -------------------------------------------------
    // 5. SHOW ONE BUTTON PER TIME ON THE CHOSEN DATE
    // -------------------------------------------------

    function showTimes(date) {

        // A new date means any earlier time choice no longer applies.
        resetChosenSlot();
        timeOptions.innerHTML = '';

        // Create one button for each slot on this date.
        slotsByDate[date].forEach(function (slot) {

            // Make a new <button> element.
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'option-button time-button';

            // First line: the time, e.g. "9:00 AM".
            const time = document.createElement('strong');
            time.textContent = slot.time_label;

            // Second line: spaces left and guide,
            // e.g. "13 of 15 left · Guide: Yaa Boateng".
            const details = document.createElement('span');
            details.textContent = slot.spaces_left + ' of ' + slot.capacity
                                + ' left · Guide: ' + slot.guide_name;

            // Put both lines inside the button.
            button.appendChild(time);
            button.appendChild(details);

            // When this time is clicked:
            button.addEventListener('click', function () {

                // Show it as the chosen time.
                selectButton(timeOptions, button);

                // Save the slot's id in the hidden form field,
                // so it is sent to the next step.
                slotIdInput.value = slot.id;

                // Turn on the Continue button.
                continueButton.disabled = false;
            });

            // Add the button to the page.
            timeOptions.appendChild(button);
        });

        // Reveal the time step now that it has buttons.
        timeStep.hidden = false;
    }
});
