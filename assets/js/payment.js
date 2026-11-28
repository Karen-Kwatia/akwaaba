// =====================================================
// assets/js/payment.js - PAYMENT PAGE HELPER
// Owner: Yayra (booking system)
//
// Used on booking/pay.php. It:
//   1. Shows only the fields for the chosen payment method.
//   2. Changes the button text to match the method.
//   3. Adds spaces to card numbers and a "/" to expiry dates
//      as the tourist types.
//   4. Checks the fields before sending, so mistakes are
//      caught straight away. (The server checks again.)
// =====================================================

// Wait until the page has fully loaded.
document.addEventListener('DOMContentLoaded', function () {

    // -------------------------------------------------
    // 1. FIND THE PARTS OF THE PAGE WE NEED
    // -------------------------------------------------

    // The form. If it isn't on the page, stop.
    const form = document.getElementById('payment-form');
    if (!form) {
        return;
    }

    // The three method radio buttons and the three field boxes.
    const methodInputs = form.querySelectorAll('input[name="method"]');
    const fieldBoxes = form.querySelectorAll('.pay-fields');

    // The button, the message area and the amount (e.g. "105.00").
    const payButton = document.getElementById('pay-button');
    const message = document.getElementById('payment-message');
    const amount = payButton.dataset.amount;

    // The individual fields we check or format.
    const momoNumber = document.getElementById('momo-number');
    const cardName = document.getElementById('card-name');
    const cardNumber = document.getElementById('card-number');
    const cardExpiry = document.getElementById('card-expiry');
    const cardCvv = document.getElementById('card-cvv');

    // -------------------------------------------------
    // 2. SHOW THE FIELDS FOR THE CHOSEN METHOD
    // -------------------------------------------------

    // Returns the value of the chosen method, e.g. "momo".
    function chosenMethod() {
        return form.querySelector('input[name="method"]:checked').value;
    }

    function showChosenFields() {

        // Which method is chosen right now.
        const method = chosenMethod();

        // Show the box whose data-method matches; hide the others.
        fieldBoxes.forEach(function (box) {
            box.hidden = box.dataset.method !== method;
        });

        // Match the button text to the method.
        if (method === 'pay_at_site') {
            payButton.textContent = 'Reserve and pay at site';
        } else {
            payButton.textContent = 'Pay GHS ' + amount;
        }

        // Clear any old message.
        message.textContent = '';
    }

    // Run showChosenFields whenever a different method is picked.
    methodInputs.forEach(function (input) {
        input.addEventListener('change', showChosenFields);
    });

    // -------------------------------------------------
    // 3. FORMAT NUMBERS AS THE TOURIST TYPES
    // -------------------------------------------------

    // Mobile Money: keep only digits (remove letters and spaces).
    momoNumber.addEventListener('input', function () {
        momoNumber.value = momoNumber.value.replace(/\D/g, '').slice(0, 10);
    });

    // Card number: digits only, in groups of 4, e.g. "4242 4242 4242 4242".
    cardNumber.addEventListener('input', function () {

        // Keep only digits, at most 19.
        const digits = cardNumber.value.replace(/\D/g, '').slice(0, 19);

        // Add a space after every 4 digits.
        cardNumber.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ');
    });

    // Expiry: digits only, with "/" after the month, e.g. "08/28".
    cardExpiry.addEventListener('input', function () {
        const digits = cardExpiry.value.replace(/\D/g, '').slice(0, 4);
        cardExpiry.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
    });

    // CVV: digits only.
    cardCvv.addEventListener('input', function () {
        cardCvv.value = cardCvv.value.replace(/\D/g, '').slice(0, 4);
    });

    // -------------------------------------------------
    // 4. CHECK THE FIELDS BEFORE SENDING
    // -------------------------------------------------

    // Returns an error message, or '' if everything looks fine.
    function findProblem() {

        const method = chosenMethod();

        if (method === 'momo') {

            // 10 digits starting with 0, e.g. 0241234567.
            if (!/^0\d{9}$/.test(momoNumber.value)) {
                return 'Please enter a valid 10-digit Mobile Money number, e.g. 0241234567.';
            }
        }

        if (method === 'card') {

            // Card number without spaces: 13 to 19 digits.
            if (!/^\d{13,19}$/.test(cardNumber.value.replace(/\s/g, ''))) {
                return 'Please enter a valid card number.';
            }
            if (cardName.value.trim() === '') {
                return 'Please enter the name on the card.';
            }
            // Month 01-12, a "/", then 2 digits for the year.
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiry.value)) {
                return 'Please enter the expiry date as MM/YY, e.g. 08/28.';
            }
            if (!/^\d{3,4}$/.test(cardCvv.value)) {
                return 'Please enter the 3 or 4 digit CVV.';
            }
        }

        // Pay at site needs nothing, and everything else passed.
        return '';
    }

    form.addEventListener('submit', function (event) {

        // Look for a problem in the fields.
        const problem = findProblem();

        if (problem) {

            // Stop the form from being sent and show the problem.
            event.preventDefault();
            message.textContent = problem;
            return;
        }

        // All good: turn the button off (no double payments)
        // and show that the payment is being processed.
        payButton.disabled = true;
        payButton.textContent = 'Processing payment...';
    });

    // Set up the page correctly when it first opens.
    showChosenFields();
});
