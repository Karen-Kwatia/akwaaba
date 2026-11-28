
<?php
// =====================================================
// AKWAABA 360 - HOMEPAGE
// Premium Ghanaian tourism experience
//
// This page:
// 1. Loads authentication and session information.
// 2. Includes the shared website header and footer.
// 3. Displays a rotating photographic hero section.
// 4. Introduces the platform's main features.
// 5. Displays selected Ghanaian destinations.
// 6. Presents a visitor-inspired quotation.
// =====================================================


// Load the authentication system and start the session.
require_once __DIR__ . '/includes/auth.php';

// Include the shared website header and translation system.
require_once __DIR__ . '/includes/header.php';


// =====================================================
// HOMEPAGE IMAGE CONFIGURATION
// =====================================================

// Folder containing the homepage photographs.
$img = '/akwaaba/images/home/';


// Photographs used in the rotating hero background.
// The first photograph is displayed when the page loads.
$heroSlides = [
    [
        'file' => 'cc_hero.jpg',
        'caption' => 'Cape Coast Castle · Central Region'
    ],
    [
        'file' => 'wl_hero.jpg',
        'caption' => 'Wli Waterfalls · Volta Region'
    ],
    [
        'file' => 'ka_hero.jpg',
        'caption' => 'Kakum National Park · Central Region'
    ],
    [
        'file' => 'nz_hero.jpg',
        'caption' => 'Nzulezo Stilt Village · Western Region'
    ],
];


// Destinations displayed in the horizontal discovery section.
$places = [
    ['Cape Coast Castle', 'Central Region', 'cc_hero.jpg'],
    ['Elmina Castle', 'Central Region', 'el_hero.jpg'],
    ['Kakum National Park', 'Central Region', 'ka_hero.jpg'],
    ['Mole National Park', 'Savannah Region', 'mo_hero.jpg'],
    ['Wli Waterfalls', 'Volta Region', 'wl_hero.jpg'],
    ['Lake Bosomtwe', 'Ashanti Region', 'bo_hero.jpg'],
    ['Nzulezo Stilt Village', 'Western Region', 'nz_hero.jpg'],
    ['Larabanga Mosque', 'Savannah Region', 'la_hero.jpg'],
];


// =====================================================
// HEADING FORMATTING HELPER
// =====================================================

// Highlights the final word of a heading in gold.
// HTML escaping protects translated text from unsafe markup.
function ak360_gold_last_word(string $text): string
{
    $text = trim($text);
    $words = explode(' ', $text);

    $last = array_pop($words);

    $head = htmlspecialchars(
        implode(' ', $words),
        ENT_QUOTES,
        'UTF-8'
    );

    $last = htmlspecialchars(
        $last,
        ENT_QUOTES,
        'UTF-8'
    );

    return ($head !== '' ? $head . ' ' : '') . '<em>' . $last . '</em>';
}


// Determine whether a user is currently logged in.
$loggedIn = isset($_SESSION['user_id']);


// Safely retrieve the logged-in user's display name.
$displayName = htmlspecialchars(
    $_SESSION['user_name'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);

?>

<!-- Load the fonts used by the premium editorial design. -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Jost:wght@300;400;500;600&display=swap"
>


<style>

/* =====================================================
   AKWAABA 360 - HOMEPAGE DESIGN SYSTEM
   ===================================================== */

.calm {

    /* Main homepage colours. */
    --night: #12100c;
    --paper: #f4ede1;
    --paper-2: #ebe2d2;
    --ink: #1b1813;
    --ink-soft: #5b5348;

    /* Brand gold accent. */
    --gold: #d9a227;

    /* Layout and typography settings. */
    --max: 1240px;
    --f-display: 'Cormorant Garamond', Georgia, serif;
    --f-body: 'Jost', system-ui, sans-serif;

    font-family: var(--f-body);
    color: var(--ink);
    background: var(--paper);
    line-height: 1.6;
}


/* Apply predictable box sizing to homepage elements. */
.calm *,
.calm *::before,
.calm *::after {
    box-sizing: border-box;
}


/* Keep homepage links consistent with the design. */
.calm a {
    color: inherit;
}


/* =====================================================
   SHARED EDITORIAL ELEMENTS
   ===================================================== */


/* Small uppercase section labels. */
.calm .eyebrow {
    font-size: 11px;
    letter-spacing: .32em;
    text-transform: uppercase;
    color: var(--ink-soft);
    margin: 0 0 18px;
    display: inline-block;
}


/* Circular arrow used in text links. */
.calm .arr {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1px solid currentColor;

    display: inline-grid;
    place-items: center;

    font-size: 18px;

    transition:
        background .3s ease,
        border-color .3s ease,
        color .3s ease,
        transform .3s ease;

    flex: 0 0 auto;
}


/* General editorial call-to-action layout. */
.calm .cta {
    display: inline-flex;
    align-items: center;
    gap: 16px;

    text-decoration: none;
    color: #fff;

    font-size: 12px;
    letter-spacing: .22em;
    text-transform: uppercase;

    transition: color .3s ease;
}


/* Animate the arrow when a visitor hovers over a link. */
.calm .cta:hover .arr {
    background: var(--gold);
    border-color: var(--gold);
    color: var(--night);
    transform: translateX(4px);
}


/* Light version for dark backgrounds. */
.calm .cta--light {
    color: var(--paper);
}


/* =====================================================
   HERO SECTION
   ===================================================== */


/* Full-screen photographic welcome section. */
.chero {
    position: relative;

    height: calc(100svh - 72px);
    min-height: 620px;

    overflow: hidden;

    color: #fff;
    background: var(--night);

    isolation: isolate;
}


/* Individual photographs covering the hero. */
.chero__bg {
    position: absolute;
    inset: 0;

    background-size: cover;
    background-position: center;

    opacity: 0;

    /* Slightly enlarged starting position creates a slow zoom. */
    transform: scale(1.07);

    transition:
        opacity 1.8s cubic-bezier(.22, .61, .36, 1),
        transform 8s cubic-bezier(.22, .61, .36, 1);

    will-change: opacity, transform;
}


/* The currently visible photograph. */
.chero__bg.is-on {
    opacity: 1;
    transform: scale(1);
}


/* Dark photographic overlay improves text readability. */
.chero__shade {
    position: absolute;
    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(14, 12, 9, .82) 0%,
            rgba(14, 12, 9, .55) 38%,
            rgba(14, 12, 9, .18) 75%,
            rgba(14, 12, 9, .12) 100%
        ),
        linear-gradient(
            0deg,
            rgba(14, 12, 9, .62) 0%,
            transparent 40%,
            rgba(14, 12, 9, .10) 100%
        );

    pointer-events: none;
}


/* Main hero content positioning. */
.chero__in {
    position: absolute;

    left: clamp(24px, 8vw, 130px);
    top: 50%;

    transform: translateY(-50%);

    width: min(650px, calc(100% - 48px));

    z-index: 2;
}


/* Hero section's small introductory label. */
.chero .eyebrow {
    color: #e4d8c4;

    margin-bottom: 24px;

    letter-spacing: .34em;
}


/* Main hero heading. */
.chero__h {
    font-family: var(--f-display);

    font-weight: 300;

    font-size: clamp(58px, 7.4vw, 112px);

    line-height: .91;

    margin: 0;

    letter-spacing: -.025em;

    color: #fff;

    max-width: 720px;

    text-wrap: balance;
}


/* Highlighted words in the hero heading. */
.chero__h em,
.c-h em {
    font-style: normal;
    color: var(--gold);
}


/* Short decorative line beneath the heading. */
.chero__rule {
    display: block;

    width: 54px;
    height: 1px;

    background: var(--gold);

    margin: 32px 0 23px;
}


/* Supporting paragraph below the heading. */
.chero__p {
    font-size: 16px;

    line-height: 1.85;

    color: #e8dfd1;

    max-width: 43ch;

    margin: 0 0 36px;

    font-weight: 300;
}


/* Layout for the hero action links. */
.chero__links {
    display: flex;

    align-items: center;
    flex-wrap: wrap;

    gap: 18px 24px;
}


/* Primary hero action. */
.chero .cta--primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-height: 54px;

    padding: 0 23px;

    background: var(--gold);
    border: 1px solid var(--gold);

    color: var(--night);

    font-weight: 600;

    letter-spacing: .17em;

    gap: 13px;

    transition:
        background .3s ease,
        border-color .3s ease,
        color .3s ease,
        transform .3s ease;
}


/* Give the primary action a restrained hover effect. */
.chero .cta--primary:hover {
    background: #edbd55;
    border-color: #edbd55;

    color: var(--night);

    transform: translateY(-3px);
}


/* Keep the primary action's arrow simple. */
.chero .cta--primary .arr {
    width: auto;
    height: auto;

    border: 0;
    border-radius: 0;

    font-size: 20px;
}


/* Secondary action remains visually quieter. */
.chero .cta--secondary {
    color: #fff;

    padding: 12px 0;

    border-bottom: 1px solid rgba(255, 255, 255, .5);

    transition:
        color .3s ease,
        border-color .3s ease;
}


/* Secondary action hover state. */
.chero .cta--secondary:hover {
    color: var(--gold);
    border-color: var(--gold);
}


/* Remove the circular arrow treatment from the secondary action. */
.chero .cta--secondary .arr {
    width: auto;
    height: auto;

    border: 0;
    border-radius: 0;

    font-size: 19px;
}


/* Current photograph caption. */
.chero__caption {
    position: absolute;

    right: clamp(24px, 5vw, 80px);
    bottom: 36px;

    margin: 0;

    font-size: 11px;

    letter-spacing: .24em;
    text-transform: uppercase;

    color: #e4d8c4;

    z-index: 2;

    text-align: right;
}


/* Scroll indicator in the lower-left corner. */
.chero__scroll {
    position: absolute;

    left: clamp(24px, 8vw, 130px);
    bottom: 30px;

    margin: 0;

    font-size: 10px;

    letter-spacing: .35em;
    text-transform: uppercase;

    color: #e4d8c4;

    z-index: 2;
}


/* Animated vertical line beneath the scroll label. */
.chero__scroll::after {
    content: '';

    display: block;

    width: 1px;
    height: 36px;

    background: linear-gradient(#d9cfbd, transparent);

    margin-top: 8px;

    animation: drip 2.2s infinite;

    transform-origin: top;
}


/* Scroll indicator animation. */
@keyframes drip {

    0% {
        transform: scaleY(0);
    }

    60% {
        transform: scaleY(1);
    }

    100% {
        transform: scaleY(1);
        opacity: 0;
    }
}


/* Keyboard focus visibility for hero links. */
.chero a:focus-visible {
    outline: 2px solid #fff;
    outline-offset: 5px;
}


/* =====================================================
   THREE NUMBERED FEATURES
   ===================================================== */


/* Main feature section container. */
.calm-steps {
    max-width: var(--max);

    margin: 0 auto;

    padding: 120px clamp(16px, 4vw, 48px);
}


/* Heading area above the three features. */
.calm-steps__head {
    margin-bottom: 56px;
}


/* Editorial section heading. */
.c-h {
    font-family: var(--f-display);

    font-weight: 300;

    font-size: clamp(36px, 5vw, 68px);

    line-height: 1.05;

    margin: 0;

    letter-spacing: -.015em;

    color: var(--ink);
}


/* Supporting introduction text. */
.calm-steps__intro {
    color: var(--ink-soft);

    font-weight: 300;

    max-width: 52ch;

    margin: 18px 0 0;
}


/* Three-column feature arrangement. */
.calm-steps__grid {
    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: clamp(24px, 5vw, 80px);
}


/* Large gold feature numbers. */
.calm-steps__num {
    font-family: var(--f-display);

    font-size: 64px;

    font-weight: 300;

    color: var(--gold);

    line-height: 1;
}


/* Feature titles. */
.calm-steps h3 {
    font-family: var(--f-display);

    font-weight: 500;

    font-size: 30px;

    margin: 12px 0 10px;

    color: var(--ink);
}


/* Feature descriptions. */
.calm-steps p.desc {
    color: var(--ink-soft);

    font-weight: 300;

    margin: 0;

    max-width: 34ch;
}


/* =====================================================
   DESTINATION DISCOVERY STRIP
   ===================================================== */


/* Dark background for the destination section. */
.strip {
    background: var(--night);

    color: var(--paper);

    padding: 90px 0 100px;
}


/* Destination section heading row. */
.strip__head {
    display: flex;

    justify-content: space-between;
    align-items: center;

    padding: 0 clamp(20px, 7vw, 120px) 30px;
}


/* Muted label on the dark section. */
.strip__head .eyebrow {
    color: #a99f8e;

    margin: 0;
}


/* Horizontally scrollable destination photographs. */
.strip__row {
    display: flex;

    gap: 22px;

    overflow-x: auto;

    padding: 0 clamp(20px, 7vw, 120px) 10px;

    scroll-snap-type: x proximity;

    scroll-padding-left: clamp(20px, 7vw, 120px);

    scrollbar-width: none;

    -webkit-overflow-scrolling: touch;
}


/* Hide the scrollbar in WebKit browsers. */
.strip__row::-webkit-scrollbar {
    display: none;
}


/* Individual destination item. */
.tall {
    flex: 0 0 300px;

    text-decoration: none;

    scroll-snap-align: start;
}


/* Destination photograph. */
.tall figure {
    margin: 0 0 16px;

    aspect-ratio: 3 / 4.2;

    background-size: cover;
    background-position: center;

    filter: saturate(.9);

    transition:
        filter .6s ease,
        transform .8s ease;
}


/* Destination hover treatment. */
.tall:hover figure {
    filter: saturate(1.1);

    transform: scale(.98);
}


/* Destination name. */
.tall b {
    display: block;

    font-family: var(--f-display);

    font-weight: 400;

    font-size: 26px;

    color: var(--paper);
}


/* Destination region. */
.tall span {
    font-size: 12px;

    letter-spacing: .14em;

    text-transform: uppercase;

    color: #a99f8e;
}


/* Keyboard focus for destination links. */
.tall:focus-visible {
    outline: 2px solid var(--gold);
    outline-offset: 5px;
}


/* =====================================================
   STORYTELLING QUOTATION SECTION
   ===================================================== */


/* Full-width photographic quotation section. */
.quote {
    min-height: 80vh;

    display: flex;

    flex-direction: column;

    justify-content: center;
    align-items: center;

    text-align: center;

    padding: 80px 24px;

    color: #fff;

    background:
        linear-gradient(
            rgba(14, 12, 9, .55),
            rgba(14, 12, 9, .55)
        ),
        var(--bg) center / cover;
}


/* Large editorial quotation. */
.quote blockquote {
    font-family: var(--f-display);

    font-weight: 300;

    font-size: clamp(34px, 4.6vw, 64px);

    line-height: 1.08;

    max-width: 20ch;

    margin: 0 0 22px;
}


/* Gold emphasis in the quotation. */
.quote blockquote em {
    font-style: normal;

    color: var(--gold);
}


/* Attribution beneath the quotation. */
.quote p {
    font-size: 12px;

    letter-spacing: .24em;

    text-transform: uppercase;

    color: #e6ddcd;

    margin: 0;
}


/* =====================================================
   PHOTO CREDITS
   ===================================================== */


/* Acknowledgement for the photographs used. */
.credits {
    background: var(--night);

    color: #8d8473;

    font-size: 11px;

    letter-spacing: .04em;

    padding: 18px clamp(20px, 7vw, 120px);

    text-align: center;
}


/* =====================================================
   SCROLL REVEAL ANIMATIONS
   ===================================================== */


/* Initial hidden state of elements revealed while scrolling. */
.calm [data-reveal] {
    opacity: 0;

    transform: translateY(28px);

    transition:
        opacity .9s ease,
        transform .9s cubic-bezier(.2, .8, .2, 1);
}


/* Visible state after an element enters the viewport. */
.calm [data-reveal].is-in {
    opacity: 1;

    transform: none;
}


/* =====================================================
   TABLET RESPONSIVENESS
   ===================================================== */

@media (max-width: 900px) {

    /* Move the hero content towards the lower half. */
    .chero__in {
        top: auto;

        bottom: 125px;

        transform: none;
    }


    /* Slightly reduce feature section spacing. */
    .calm-steps {
        padding-top: 80px;
        padding-bottom: 80px;
    }


    /* Stack the features vertically on tablets. */
    .calm-steps__grid {
        grid-template-columns: 1fr;

        gap: 42px;
    }


    /* Reduce destination item width. */
    .tall {
        flex-basis: 240px;
    }


    /* Reduce hero height slightly on tablets. */
    .chero {
        min-height: 680px;
    }

}


/* =====================================================
   MOBILE RESPONSIVENESS
   ===================================================== */

@media (max-width: 600px) {

    /* Give the hero a comfortable mobile height. */
    .chero {
        height: calc(100svh - 64px);

        min-height: 650px;

        max-height: 850px;
    }


    /* Strengthen the overlay so text remains readable. */
    .chero__shade {
        background:
            linear-gradient(
                90deg,
                rgba(14, 12, 9, .74),
                rgba(14, 12, 9, .35)
            ),
            linear-gradient(
                0deg,
                rgba(14, 12, 9, .78),
                transparent 70%
            );
    }


    /* Position the text carefully on smaller screens. */
    .chero__in {
        left: 23px;

        bottom: 115px;

        width: calc(100% - 46px);
    }


    /* Make the headline fit smaller screens. */
    .chero__h {
        font-size: clamp(53px, 13vw, 76px);

        line-height: .96;
    }


    /* Reduce spacing above the hero heading. */
    .chero .eyebrow {
        font-size: 10px;

        letter-spacing: .24em;

        margin-bottom: 18px;
    }


    /* Shorten the decorative line spacing. */
    .chero__rule {
        margin: 24px 0 17px;
    }


    /* Improve mobile paragraph readability. */
    .chero__p {
        font-size: 14px;

        line-height: 1.7;

        margin-bottom: 25px;
    }


    /* Allow the hero actions to wrap neatly. */
    .chero__links {
        gap: 12px 18px;
    }


    /* Slightly reduce the primary action's dimensions. */
    .chero .cta--primary {
        min-height: 49px;

        padding: 0 17px;

        font-size: 10px;
    }


    /* Reduce the secondary action's text size. */
    .chero .cta--secondary {
        font-size: 10px;

        letter-spacing: .13em;
    }


    /* Move the caption away from the screen edges. */
    .chero__caption {
        right: 20px;

        bottom: 22px;

        max-width: 58%;

        font-size: 9px;

        line-height: 1.5;

        letter-spacing: .14em;
    }


    /* Hide the scroll indicator to avoid crowding. */
    .chero__scroll {
        display: none;
    }


    /* Improve destination section spacing. */
    .strip {
        padding: 65px 0 75px;
    }


    /* Reduce the destination section heading spacing. */
    .strip__head {
        padding-bottom: 24px;
    }


    /* Make destination photographs slightly narrower. */
    .tall {
        flex-basis: 215px;
    }


    /* Improve destination title sizing. */
    .tall b {
        font-size: 23px;
    }


    /* Reduce quotation section height. */
    .quote {
        min-height: 65vh;
    }


    /* Make photo credits easier to read on phones. */
    .credits {
        font-size: 10px;

        line-height: 1.8;
    }

}


/* =====================================================
   REDUCED MOTION ACCESSIBILITY
   ===================================================== */


/* Respect visitors who prefer reduced motion. */
@media (prefers-reduced-motion: reduce) {

    .calm *,
    .calm *::before,
    .calm *::after {

        animation-duration: .01ms !important;

        animation-iteration-count: 1 !important;

        transition-duration: .01ms !important;

        scroll-behavior: auto !important;
    }

}

</style>


<!-- =====================================================
     BEGIN HOMEPAGE CONTENT
     ===================================================== -->

<div class="calm">


    <!-- =================================================
         HERO SECTION
         Rotating destination photographs and welcome text.
         ================================================= -->

    <section class="chero" aria-label="Welcome to Akwaaba 360">


        <!-- Display all hero photographs.
             JavaScript controls which photograph is visible. -->

        <?php foreach ($heroSlides as $i => $slide): ?>

            <div
                class="chero__bg<?php echo $i === 0 ? ' is-on' : ''; ?>"

                data-caption="<?php echo htmlspecialchars(
                    $slide['caption'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"

                aria-hidden="true"

                style="background-image:url('<?php echo $img . rawurlencode($slide['file']); ?>')"
            ></div>

        <?php endforeach; ?>


        <!-- Dark overlay ensures that text remains readable. -->
        <div class="chero__shade" aria-hidden="true"></div>


        <!-- Main welcome text and actions. -->
        <div class="chero__in">


            <?php if ($loggedIn): ?>

                <!-- Personal welcome message for logged-in visitors. -->
                <p class="eyebrow">

                    <?php echo htmlspecialchars(
                        ak360_translate('welcome_label'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </p>


                <h1 class="chero__h">

                    <?php echo htmlspecialchars(
                        ak360_translate('welcome_back'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                    <em><?php echo $displayName; ?>.</em>

                </h1>


            <?php else: ?>

                <!-- Introduction for visitors who are not logged in. -->
                <p class="eyebrow">

                    <?php echo htmlspecialchars(
                        ak360_translate('hero_label'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </p>


                <h1 class="chero__h">

                    <?php echo ak360_gold_last_word(
                        ak360_translate('hero_title')
                    ); ?>

                </h1>


            <?php endif; ?>


            <!-- Decorative divider below the heading. -->
            <span class="chero__rule"></span>


            <!-- Main description of the tourism platform. -->
            <p class="chero__p">

                <?php echo htmlspecialchars(
                    ak360_translate('hero_description'),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </p>


            <!-- Main hero actions. -->
            <div class="chero__links">


                <!-- Primary action takes visitors to destinations. -->
                <a
                    class="cta cta--primary"
                    href="/akwaaba/tours/index.php"
                >

                    <span class="arr" aria-hidden="true">&rarr;</span>

                    <span>
                        <?php echo htmlspecialchars(
                            ak360_translate('explore_ghana'),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </span>

                </a>


                <?php if (!$loggedIn): ?>

                    <!-- Secondary action allows new visitors to register. -->
                    <a
                        class="cta cta--secondary"
                        href="/akwaaba/auth/register.php"
                    >

                        <span class="arr" aria-hidden="true">+</span>

                        <span>
                            <?php echo htmlspecialchars(
                                ak360_translate('create_account'),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </span>

                    </a>

                <?php endif; ?>


            </div>

        </div>


        <!-- Caption identifies the photograph currently displayed. -->
        <p class="chero__caption" id="heroCaption">

            <?php echo htmlspecialchars(
                $heroSlides[0]['caption'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </p>


        <!-- Decorative scroll indicator. -->
        <p class="chero__scroll">Scroll</p>


    </section>



    <!-- =================================================
         THREE MAIN PLATFORM FEATURES
         ================================================= -->

    <section class="calm-steps">


        <!-- Introduction to the platform's main features. -->
        <div class="calm-steps__head" data-reveal>


            <p class="eyebrow">

                <?php echo htmlspecialchars(
                    ak360_translate('journey_label'),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </p>


            <h2 class="c-h">

                <?php echo ak360_gold_last_word(
                    ak360_translate('features_title')
                ); ?>

            </h2>


            <p class="calm-steps__intro">

                <?php echo htmlspecialchars(
                    ak360_translate('features_description'),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </p>


        </div>


        <!-- Three-column feature layout. -->
        <div class="calm-steps__grid">


            <!-- Feature one: virtual destination experiences. -->
            <div data-reveal>

                <span class="calm-steps__num">01</span>

                <h3>
                    <?php echo htmlspecialchars(
                        ak360_translate('virtual_experiences'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </h3>

                <p class="desc">
                    <?php echo htmlspecialchars(
                        ak360_translate('virtual_description'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>

            </div>


            <!-- Feature two: local tour guides. -->
            <div data-reveal style="transition-delay:.1s">

                <span class="calm-steps__num">02</span>

                <h3>
                    <?php echo htmlspecialchars(
                        ak360_translate('local_guides'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </h3>

                <p class="desc">
                    <?php echo htmlspecialchars(
                        ak360_translate('guides_description'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>

            </div>


            <!-- Feature three: sharing visitor experiences. -->
            <div data-reveal style="transition-delay:.2s">

                <span class="calm-steps__num">03</span>

                <h3>
                    <?php echo htmlspecialchars(
                        ak360_translate('share_experiences'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </h3>

                <p class="desc">
                    <?php echo htmlspecialchars(
                        ak360_translate('share_description'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>

            </div>


        </div>

    </section>



    <!-- =================================================
         DESTINATION DISCOVERY SECTION
         ================================================= -->

    <section class="strip">


        <!-- Destination section heading and discovery link. -->
        <div class="strip__head">

            <p class="eyebrow">Places</p>


            <a
                class="cta cta--light"
                href="/akwaaba/tours/index.php"
            >

                <span class="arr" aria-hidden="true">&rarr;</span>

                <span>
                    <?php echo htmlspecialchars(
                        ak360_translate('explore_ghana'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </span>

            </a>

        </div>


        <!-- Horizontally scrollable collection of destinations. -->
        <div class="strip__row">


            <?php foreach ($places as [$name, $region, $file]): ?>

                <a
                    class="tall"
                    href="/akwaaba/tours/index.php"
                    aria-label="<?php echo htmlspecialchars(
                        $name . ', ' . $region,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

                    <!-- Destination photograph. -->
                    <figure
                        style="background-image:url('<?php echo $img . rawurlencode($file); ?>')"
                        aria-hidden="true"
                    ></figure>


                    <!-- Destination name. -->
                    <b>
                        <?php echo htmlspecialchars(
                            $name,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </b>


                    <!-- Destination region. -->
                    <span>
                        <?php echo htmlspecialchars(
                            $region,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </span>


                </a>

            <?php endforeach; ?>


        </div>

    </section>



    <!-- =================================================
         GHANAIAN TRAVEL STORYTELLING SECTION
         ================================================= -->

    <section
        class="quote"
        style="--bg:url('<?php echo $img; ?>el_3.jpg')"
    >


        <!-- Visitor-inspired quotation. -->
        <blockquote data-reveal>

            &ldquo;The canoes in Elmina harbour are a painting that keeps
            <em>moving.</em>&rdquo;

        </blockquote>


        <!-- Quotation attribution. -->
        <p data-reveal>Chiara, Milan</p>


    </section>



    <!-- =================================================
         PHOTOGRAPH ACKNOWLEDGEMENTS
         ================================================= -->

    <p class="credits">

        Photos from Wikimedia Commons:
        Bineys (CC BY 4.0),
        Oluwolehammond,
        Bullionmc,
        Jones Anlimah,
        Kradolferp,
        Jdjdiwla,
        Kwameghana (CC BY-SA 4.0),
        Dieu-Donn&eacute; Gameli (CC BY-SA 3.0).

    </p>


</div>


<script>

/* =====================================================
   AKWAABA 360 - HOMEPAGE INTERACTIONS
   ===================================================== */

(function () {


    // -------------------------------------------------
    // HERO PHOTOGRAPH ROTATION
    // -------------------------------------------------

    // Find all hero photographs.
    var slides = document.querySelectorAll('.chero__bg');

    // Find the caption displaying the current destination.
    var caption = document.getElementById('heroCaption');

    // Detect whether the visitor prefers reduced motion.
    var reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;


    // Keep track of the currently displayed photograph.
    var currentSlide = 0;


    // Only activate the slideshow when multiple photographs exist.
    if (slides.length > 1 && !reduceMotion) {


        // Change the background photograph every 6.5 seconds.
        window.setInterval(function () {


            // Pause photograph changes when the browser tab is hidden.
            if (document.hidden) {
                return;
            }


            // Hide the currently displayed photograph.
            slides[currentSlide].classList.remove('is-on');


            // Move to the next photograph.
            currentSlide = (currentSlide + 1) % slides.length;


            // Display the next photograph.
            slides[currentSlide].classList.add('is-on');


            // Update the destination caption.
            if (caption) {

                caption.textContent =
                    slides[currentSlide].dataset.caption || '';

            }


        }, 6500);

    }



    // -------------------------------------------------
    // SCROLL REVEAL ANIMATIONS
    // -------------------------------------------------

    // Find all elements that should appear while scrolling.
    var items = document.querySelectorAll('[data-reveal]');


    // Use IntersectionObserver when supported by the browser.
    if ('IntersectionObserver' in window) {


        // Observe when each element enters the visible screen area.
        var observer = new IntersectionObserver(function (entries) {


            entries.forEach(function (entry) {


                // Animate the element when it becomes visible.
                if (entry.isIntersecting) {

                    entry.target.classList.add('is-in');

                    // Stop observing after the animation has happened.
                    observer.unobserve(entry.target);

                }

            });


        }, {
            threshold: 0.15
        });


        // Register all elements for observation.
        items.forEach(function (element) {

            observer.observe(element);

        });


    } else {


        // Show all elements immediately in older browsers.
        items.forEach(function (element) {

            element.classList.add('is-in');

        });

    }


})();

</script>


<?php

// Include the shared website footer.
require_once __DIR__ . '/includes/footer.php';

?>
