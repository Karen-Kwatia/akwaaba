
<?php
// Start the session if it has not already been started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store all English, Twi, French and Spanish translations.
$languageTranslations = [

    // =====================================================
    // ENGLISH TRANSLATIONS
    // =====================================================
    'en' => [

        // Website information.
        'site_title' => 'Akwaaba 360 | Explore Ghana',
        'site_description' => 'Discover Ghana with Akwaaba 360.',
        'language_english' => 'English',
        'language_twi' => 'Twi',

        // Shared navigation.
        'home' => 'Home',
        'explore_sites' => 'Explore Sites',
        'dashboard' => 'Dashboard',
        'preferences' => 'Preferences',
        'guide_dashboard' => 'Guide Dashboard',
        'admin_dashboard' => 'Admin Dashboard',
        'logout' => 'Logout',
        'login' => 'Login',
        'register' => 'Register',

        // Homepage welcome section.
        'welcome_label' => 'WELCOME TO YOUR AKWAABA EXPERIENCE',
        'welcome_back' => 'Welcome back,',
        'hero_label' => 'EXPERIENCE THE BEAUTY OF GHANA',
        'hero_title' => 'Discover Ghana with Akwaaba 360',
        'hero_description' => "Explore Ghana's beautiful tourist destinations, experience its rich culture and connect with knowledgeable local tour guides.",
        'explore_ghana' => 'Explore Ghana',
        'create_account' => 'Create an Account',

        // Homepage features section.
        'journey_label' => 'YOUR JOURNEY BEGINS HERE',
        'features_title' => 'Explore What Akwaaba 360 Offers',
        'features_description' => 'Discover Ghana through immersive experiences and meaningful connections with local guides.',

        // Homepage feature cards.
        'virtual_experiences' => 'Virtual Experiences',
        'virtual_description' => 'Preview tourist destinations through immersive videos and virtual experiences before visiting.',
        'local_guides' => 'Book Local Tour Guides',
        'guides_description' => 'Find knowledgeable local guides and arrange tours that match your preferred language.',
        'share_experiences' => 'Share Your Experiences',
        'share_description' => 'Share travel moments, discover visitor stories and collect stamps in your Heritage Passport.',

        // Login page.
        'login_title' => 'Welcome Back to Akwaaba 360',
        'login_tagline' => 'Discover Ghana. Experience More.',
        'login_description' => 'Welcome back! Sign in to continue your journey.',
        'language_label' => 'Language',
        'email_address' => 'Email Address:',
        'email_placeholder' => 'Enter your email address',
        'password' => 'Password:',
        'password_placeholder' => 'Enter your password',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
        'remember_me' => 'Remember Me',
        'login_button' => 'Login',
        'no_account' => "Don't have an account?",
        'create_account_link' => 'Create an account',
        'forgot_password' => 'Forgot your password?',
        'invalid_credentials' => 'Invalid email address or password.',
        'login_footer' => 'Akwaaba 360 | Explore the beauty of Ghana',

        // Registration page.
        'register_title' => 'Create Your Akwaaba 360 Account',
        'full_name' => 'Full Name:',
        'country' => 'Country:',
        'confirm_password' => 'Confirm Password:',
        'select_language' => 'Select a language',
        'registration_required' => 'Please complete all required fields.',
        'password_mismatch' => 'Your passwords do not match.',
        'invalid_email' => 'Please enter a valid email address.',
        'password_length' => 'Your password must contain at least 8 characters.',
        'invalid_language' => 'Please select a valid language.',
        'email_exists' => 'An account with this email already exists.',
        'registration_button' => 'Create Account',

        // Forgot password page.
        'forgot_password_title' => 'Forgot Your Password?',
        'forgot_password_description' => 'Enter your registered email address to request a password reset.',
        'request_password_reset' => 'Request Password Reset',
        'password_reset_request_message' => 'If an account exists with that email, a password reset link will be sent.',
        'forgot_password_invalid_email' => 'Please enter a valid email address.',

        // Reset password page.
        'reset_password_title' => 'Reset Your Password',
        'new_password' => 'New Password:',
        'confirm_new_password' => 'Confirm New Password:',
        'reset_password_button' => 'Reset Password',
        'reset_invalid_token' => 'This password reset link is invalid or incomplete.',
        'reset_expired_token' => 'This password reset link has expired or is invalid.',
        'reset_password_mismatch' => 'The passwords do not match.',
        'reset_password_length' => 'Your password must contain at least 8 characters.',
        'reset_password_success' => 'Your password has been reset successfully. You can now log in.',
        'back_to_login' => 'Back to Login',

        // Tourist dashboard.
        'dashboard_welcome_label' => 'YOUR AKWAABA EXPERIENCE',
        'dashboard_welcome' => 'Welcome back,',
        'dashboard_description' => 'Your next Ghanaian adventure starts here. Discover amazing destinations, connect with local guides and create unforgettable experiences.',
        'explore_destinations' => 'Explore Destinations',
        'manage_preferences' => 'Manage Preferences',
        'your_account' => 'Your Account',
        'account_type' => 'Account type',

        // Guide dashboard.
        'guide_dashboard_title' => 'Akwaaba 360 Guide Dashboard',
        'guide_welcome' => 'Welcome,',
        'guide_account_role' => 'Account role',

        // Admin dashboard.
        'admin_dashboard_title' => 'Akwaaba 360 Admin Dashboard',
        'admin_welcome' => 'Welcome,',
        'admin_description' => 'You have access to the administration area.',

        // Preferences page.
        'preferences_title' => 'Your Preferences',
        'preferred_language' => 'Preferred Language:',
        'preferred_currency' => 'Preferred Currency:',
        'save_preferences' => 'Save Preferences',
        'back_to_dashboard' => 'Back to Dashboard',
        'preferences_saved' => 'Your preferences have been saved successfully!',
        'preferences_error' => 'Please select a valid language and currency.',

        // Currency options.
        'currency_GHS' => 'Ghanaian Cedi (GHS)',
        'currency_USD' => 'US Dollar (USD)',
        'currency_EUR' => 'Euro (EUR)',
        'currency_GBP' => 'British Pound (GBP)',

        // Account roles.
        'role_tourist' => 'Tourist',
        'role_user' => 'User',
        'role_guide' => 'Tour Guide',
        'role_admin' => 'Administrator'
    ],

    // =====================================================
    // TWI TRANSLATIONS
    // =====================================================
    'tw' => [

        // Website information.
        'site_title' => 'Akwaaba 360 | Hwehwɛ Ghana',
        'site_description' => 'Hwehwɛ Ghana ne Akwaaba 360.',
        'language_english' => 'Borɔfo',
        'language_twi' => 'Twi',

        // Shared navigation.
        'home' => 'Fie',
        'explore_sites' => 'Hwehwɛ Mmeae',
        'dashboard' => 'Dwumadie Beae',
        'preferences' => 'Nhyehyɛe',
        'guide_dashboard' => 'Akwankyerɛfo Dwumadie Beae',
        'admin_dashboard' => 'Soɛfo Dwumadie Beae',
        'logout' => 'Firi mu',
        'login' => 'Hyɛn mu',
        'register' => 'Kyerɛw wo din',

        // Homepage welcome section.
        'welcome_label' => 'AKWAABA WƆ AKWAABA 360',
        'welcome_back' => 'Akwaaba bio,',
        'hero_label' => 'HWEHWƐ GHANA FƐFƐƐFƐ',
        'hero_title' => 'Hwehwɛ Ghana ne Akwaaba 360',
        'hero_description' => 'Kɔ Ghana mmeae a nsrahwɛfo ani gye ho, hu yɛn amammerɛ a ɛyɛ fɛ na hyia akwankyerɛfo a wɔwɔ nimdeɛ.',
        'explore_ghana' => 'Hwehwɛ Ghana',
        'create_account' => 'Bɔ Wo Akontaabuo',

        // Homepage features section.
        'journey_label' => 'WO AKWANTUO FI ASE HA',
        'features_title' => 'Hu Deɛ Akwaaba 360 De Ma Wo',
        'features_description' => 'Fa suahu ahorow ne mpɔtam hɔ akwankyerɛfo so hwehwɛ Ghana.',

        // Homepage feature cards.
        'virtual_experiences' => 'Virtual Suahu',
        'virtual_description' => 'Hwɛ mmeae a nsrahwɛfo kɔ no video ne virtual suahu ahorow ansa na woakɔ hɔ.',
        'local_guides' => 'Bɔ Mpɔtam Hɔ Akwankyerɛfo Ho Nhyehyɛe',
        'guides_description' => 'Hwehwɛ akwankyerɛfo a wɔwɔ nimdeɛ na yɛ akwantu nhyehyɛe wɔ kasa a wopɛ mu.',
        'share_experiences' => 'Kyɛ Wo Suahu',
        'share_description' => 'Kyɛ w’akwantu mu anigye, hu nsrahwɛfo afoforo suahu na boaboa nsrahwɛ nsɔano ano wɔ wo Heritage Passport mu.',

        // Login page.
        'login_title' => 'Akwaaba Bio Wɔ Akwaaba 360',
        'login_tagline' => 'Hwehwɛ Ghana. Nya Suahu Pii.',
        'login_description' => 'Akwaaba bio! Hyɛn mu na toa w’akwantu no so.',
        'language_label' => 'Kasa',
        'email_address' => 'Email Address:',
        'email_placeholder' => 'Kyerɛw wo email address',
        'password' => 'Ahintasɛm:',
        'password_placeholder' => 'Kyerɛw wo ahintasɛm',
        'show_password' => 'Kyerɛ ahintasɛm',
        'hide_password' => 'Fa ahintasɛm no sie',
        'remember_me' => 'Kae Me',
        'login_button' => 'Hyɛn mu',
        'no_account' => 'Wonni akontaabu anaa?',
        'create_account_link' => 'Bɔ akontaabu foforo',
        'forgot_password' => 'Wo werɛ afi wo ahintasɛm?',
        'invalid_credentials' => 'Email address anaa ahintasɛm no nyɛ nokware.',
        'login_footer' => 'Akwaaba 360 | Hwehwɛ Ghana ahoɔfɛ',

        // Registration page.
        'register_title' => 'Bɔ Wo Akontaabu wɔ Akwaaba 360',
        'full_name' => 'Wo Din Nyinaa:',
        'country' => 'Ɔman:',
        'confirm_password' => 'San Kyerɛw Ahintasɛm:',
        'select_language' => 'Paw kasa',
        'registration_required' => 'Yɛsrɛ wo, hyɛ mmeae a ɛho hia nyinaa ma.',
        'password_mismatch' => 'Wo ahintasɛm abien no nhyia.',
        'invalid_email' => 'Yɛsrɛ wo, kyerɛw email address a ɛyɛ nokware.',
        'password_length' => 'Ɛsɛ sɛ wo ahintasɛm no nya nkyerɛwde 8 anaa nea ɛboro saa.',
        'invalid_language' => 'Yɛsrɛ wo, paw kasa a ɛfata.',
        'email_exists' => 'Akontaabu bi wɔ hɔ dedaw a wɔde email yi yɛɛ.',
        'registration_button' => 'Bɔ Akontaabu',

        // Forgot password page.
        'forgot_password_title' => 'Wo Werɛ Afi Wo Ahintasɛm?',
        'forgot_password_description' => 'Kyerɛw email address a wode yɛɛ wo akontaabu no na bisa sɛ wɔmfa password foforo link mma wo.',
        'request_password_reset' => 'Bisa Password Foforo Link',
        'password_reset_request_message' => 'Sɛ email yi wɔ akontaabu bi mu a, wɔbɛsoma password foforo link.',
        'forgot_password_invalid_email' => 'Yɛsrɛ wo, kyerɛw email address a ɛfata.',

        // Reset password page.
        'reset_password_title' => 'Yɛ Ahintasɛm Foforo',
        'new_password' => 'Ahintasɛm Foforo:',
        'confirm_new_password' => 'San Kyerɛw Ahintasɛm Foforo:',
        'reset_password_button' => 'Yɛ Ahintasɛm Foforo',
        'reset_invalid_token' => 'Password foforo link yi nyɛ nokware anaa enni mu.',
        'reset_expired_token' => 'Password foforo link yi bere atwam anaa ɛnyɛ nokware.',
        'reset_password_mismatch' => 'Ahintasɛm abien no nhyia.',
        'reset_password_length' => 'Ɛsɛ sɛ wo ahintasɛm no nya nkyerɛwde 8 anaa nea ɛboro saa.',
        'reset_password_success' => 'Woayɛ ahintasɛm foforo yiye. Afei wobɛtumi ahyɛn mu.',
        'back_to_login' => 'San Kɔ Login',

        // Tourist dashboard.
        'dashboard_welcome_label' => 'WO AKWAABA SUAHU',
        'dashboard_welcome' => 'Akwaaba bio,',
        'dashboard_description' => 'Wo akwantu a ɛdi hɔ wɔ Ghana no fi ha. Hwehwɛ mmeae a ɛyɛ nwonwa, hyia mpɔtam hɔ akwankyerɛfo na nya suahu a wo werɛ remfi da.',
        'explore_destinations' => 'Hwehwɛ Mmeae a Wobɛkɔ',
        'manage_preferences' => 'Sesa Wo Nhyehyɛe',
        'your_account' => 'Wo Akontaabu',
        'account_type' => 'Akontaabu no su',

        // Guide dashboard.
        'guide_dashboard_title' => 'Akwaaba 360 Akwankyerɛfo Dwumadie Beae',
        'guide_welcome' => 'Akwaaba,',
        'guide_account_role' => 'Wo dwumadie',

        // Admin dashboard.
        'admin_dashboard_title' => 'Akwaaba 360 Soɛfo Dwumadie Beae',
        'admin_welcome' => 'Akwaaba,',
        'admin_description' => 'Wowɔ kwan sɛ wobɛkɔ soɛfoɔ dwumadie beae.',

        // Preferences page.
        'preferences_title' => 'Wo Nhyehyɛe',
        'preferred_language' => 'Kasa a Wopɛ:',
        'preferred_currency' => 'Sika a Wopɛ:',
        'save_preferences' => 'Sie Nhyehyɛe',
        'back_to_dashboard' => 'San Kɔ Wo Dwumadie Beae',
        'preferences_saved' => 'Wo nhyehyɛe no asie yie!',
        'preferences_error' => 'Yɛsrɛ wo, paw kasa ne sika a ɛfata.',

        // Currency options.
        'currency_GHS' => 'Ghana Sidi (GHS)',
        'currency_USD' => 'Amerika Dɔla (USD)',
        'currency_EUR' => 'Euro (EUR)',
        'currency_GBP' => 'Britania Pɔn (GBP)',

        // Account roles.
        'role_tourist' => 'Nsrahwɛfo',
        'role_user' => 'Ɔdefo',
        'role_guide' => 'Akwankyerɛfo',
        'role_admin' => 'Soɛfo'
    ],

    // =====================================================
    // FRENCH TRANSLATIONS
    // =====================================================
    'fr' => [

        // Website information.
        'site_title' => 'Akwaaba 360 | Explorer le Ghana',
        'site_description' => 'Découvrez le Ghana avec Akwaaba 360.',
        'language_english' => 'Anglais',
        'language_twi' => 'Twi',

        // Shared navigation.
        'home' => 'Accueil',
        'explore_sites' => 'Explorer les sites',
        'dashboard' => 'Tableau de bord',
        'preferences' => 'Préférences',
        'guide_dashboard' => 'Tableau de bord du guide',
        'admin_dashboard' => 'Tableau de bord administrateur',
        'logout' => 'Déconnexion',
        'login' => 'Connexion',
        'register' => 'S’inscrire',

        // Homepage welcome section.
        'welcome_label' => 'BIENVENUE DANS VOTRE EXPÉRIENCE AKWAABA',
        'welcome_back' => 'Bon retour,',
        'hero_label' => 'DÉCOUVREZ LA BEAUTÉ DU GHANA',
        'hero_title' => 'Découvrez le Ghana avec Akwaaba 360',
        'hero_description' => 'Explorez les magnifiques destinations touristiques du Ghana, découvrez sa riche culture et rencontrez des guides touristiques locaux expérimentés.',
        'explore_ghana' => 'Explorer le Ghana',
        'create_account' => 'Créer un compte',

        // Homepage features section.
        'journey_label' => 'VOTRE VOYAGE COMMENCE ICI',
        'features_title' => 'Découvrez les services d’Akwaaba 360',
        'features_description' => 'Découvrez le Ghana grâce à des expériences immersives et des échanges enrichissants avec des guides locaux.',

        // Homepage feature cards.
        'virtual_experiences' => 'Expériences virtuelles',
        'virtual_description' => 'Découvrez les destinations touristiques grâce à des vidéos immersives et des expériences virtuelles avant votre visite.',
        'local_guides' => 'Réserver des guides locaux',
        'guides_description' => 'Trouvez des guides locaux expérimentés et organisez des visites dans la langue de votre choix.',
        'share_experiences' => 'Partagez vos expériences',
        'share_description' => 'Partagez vos souvenirs de voyage, découvrez les récits d’autres visiteurs et collectionnez des tampons dans votre passeport du patrimoine.',

        // Login page.
        'login_title' => 'Bienvenue sur Akwaaba 360',
        'login_tagline' => 'Découvrez le Ghana. Vivez plus.',
        'login_description' => 'Bon retour ! Connectez-vous pour poursuivre votre voyage.',
        'language_label' => 'Langue',
        'email_address' => 'Adresse e-mail :',
        'email_placeholder' => 'Saisissez votre adresse e-mail',
        'password' => 'Mot de passe :',
        'password_placeholder' => 'Saisissez votre mot de passe',
        'show_password' => 'Afficher le mot de passe',
        'hide_password' => 'Masquer le mot de passe',
        'remember_me' => 'Se souvenir de moi',
        'login_button' => 'Se connecter',
        'no_account' => 'Vous n’avez pas de compte ?',
        'create_account_link' => 'Créer un compte',
        'forgot_password' => 'Mot de passe oublié ?',
        'invalid_credentials' => 'Adresse e-mail ou mot de passe invalide.',
        'login_footer' => 'Akwaaba 360 | Explorez la beauté du Ghana',

        // Registration page.
        'register_title' => 'Créer votre compte Akwaaba 360',
        'full_name' => 'Nom complet :',
        'country' => 'Pays :',
        'confirm_password' => 'Confirmer le mot de passe :',
        'select_language' => 'Sélectionnez une langue',
        'registration_required' => 'Veuillez remplir tous les champs obligatoires.',
        'password_mismatch' => 'Vos mots de passe ne correspondent pas.',
        'invalid_email' => 'Veuillez saisir une adresse e-mail valide.',
        'password_length' => 'Votre mot de passe doit contenir au moins 8 caractères.',
        'invalid_language' => 'Veuillez sélectionner une langue valide.',
        'email_exists' => 'Un compte avec cette adresse e-mail existe déjà.',
        'registration_button' => 'Créer un compte',

        // Forgot password page.
        'forgot_password_title' => 'Mot de passe oublié ?',
        'forgot_password_description' => 'Saisissez votre adresse e-mail enregistrée pour demander la réinitialisation de votre mot de passe.',
        'request_password_reset' => 'Demander la réinitialisation',
        'password_reset_request_message' => 'Si un compte existe avec cette adresse e-mail, un lien de réinitialisation sera envoyé.',
        'forgot_password_invalid_email' => 'Veuillez saisir une adresse e-mail valide.',

        // Reset password page.
        'reset_password_title' => 'Réinitialiser votre mot de passe',
        'new_password' => 'Nouveau mot de passe :',
        'confirm_new_password' => 'Confirmer le nouveau mot de passe :',
        'reset_password_button' => 'Réinitialiser le mot de passe',
        'reset_invalid_token' => 'Ce lien de réinitialisation est invalide ou incomplet.',
        'reset_expired_token' => 'Ce lien de réinitialisation a expiré ou est invalide.',
        'reset_password_mismatch' => 'Les mots de passe ne correspondent pas.',
        'reset_password_length' => 'Votre mot de passe doit contenir au moins 8 caractères.',
        'reset_password_success' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
        'back_to_login' => 'Retour à la connexion',

        // Tourist dashboard.
        'dashboard_welcome_label' => 'VOTRE EXPÉRIENCE AKWAABA',
        'dashboard_welcome' => 'Bon retour,',
        'dashboard_description' => 'Votre prochaine aventure au Ghana commence ici. Découvrez des destinations extraordinaires, rencontrez des guides locaux et vivez des expériences inoubliables.',
        'explore_destinations' => 'Explorer les destinations',
        'manage_preferences' => 'Gérer les préférences',
        'your_account' => 'Votre compte',
        'account_type' => 'Type de compte',

        // Guide dashboard.
        'guide_dashboard_title' => 'Tableau de bord du guide Akwaaba 360',
        'guide_welcome' => 'Bienvenue,',
        'guide_account_role' => 'Rôle du compte',

        // Admin dashboard.
        'admin_dashboard_title' => 'Tableau de bord administrateur Akwaaba 360',
        'admin_welcome' => 'Bienvenue,',
        'admin_description' => 'Vous avez accès à l’espace d’administration.',

        // Preferences page.
        'preferences_title' => 'Vos préférences',
        'preferred_language' => 'Langue préférée :',
        'preferred_currency' => 'Devise préférée :',
        'save_preferences' => 'Enregistrer les préférences',
        'back_to_dashboard' => 'Retour au tableau de bord',
        'preferences_saved' => 'Vos préférences ont été enregistrées avec succès !',
        'preferences_error' => 'Veuillez sélectionner une langue et une devise valides.',

        // Currency options.
        'currency_GHS' => 'Cedi ghanéen (GHS)',
        'currency_USD' => 'Dollar américain (USD)',
        'currency_EUR' => 'Euro (EUR)',
        'currency_GBP' => 'Livre sterling (GBP)',

        // Account roles.
        'role_tourist' => 'Touriste',
        'role_user' => 'Utilisateur',
        'role_guide' => 'Guide touristique',
        'role_admin' => 'Administrateur'
    ],

    // =====================================================
    // SPANISH TRANSLATIONS
    // =====================================================
    'es' => [

        // Website information.
        'site_title' => 'Akwaaba 360 | Explora Ghana',
        'site_description' => 'Descubre Ghana con Akwaaba 360.',
        'language_english' => 'Inglés',
        'language_twi' => 'Twi',

        // Shared navigation.
        'home' => 'Inicio',
        'explore_sites' => 'Explorar lugares',
        'dashboard' => 'Panel de control',
        'preferences' => 'Preferencias',
        'guide_dashboard' => 'Panel del guía',
        'admin_dashboard' => 'Panel del administrador',
        'logout' => 'Cerrar sesión',
        'login' => 'Iniciar sesión',
        'register' => 'Registrarse',

        // Homepage welcome section.
        'welcome_label' => 'BIENVENIDO A TU EXPERIENCIA AKWAABA',
        'welcome_back' => 'Bienvenido de nuevo,',
        'hero_label' => 'DESCUBRE LA BELLEZA DE GHANA',
        'hero_title' => 'Descubre Ghana con Akwaaba 360',
        'hero_description' => 'Explora los hermosos destinos turísticos de Ghana, descubre su rica cultura y conecta con guías turísticos locales con experiencia.',
        'explore_ghana' => 'Explorar Ghana',
        'create_account' => 'Crear una cuenta',

        // Homepage features section.
        'journey_label' => 'TU VIAJE COMIENZA AQUÍ',
        'features_title' => 'Descubre lo que ofrece Akwaaba 360',
        'features_description' => 'Descubre Ghana a través de experiencias inmersivas y conexiones significativas con guías locales.',

        // Homepage feature cards.
        'virtual_experiences' => 'Experiencias virtuales',
        'virtual_description' => 'Conoce los destinos turísticos mediante vídeos inmersivos y experiencias virtuales antes de visitarlos.',
        'local_guides' => 'Reservar guías turísticos locales',
        'guides_description' => 'Encuentra guías locales con experiencia y organiza visitas en el idioma que prefieras.',
        'share_experiences' => 'Comparte tus experiencias',
        'share_description' => 'Comparte tus recuerdos de viaje, descubre las historias de otros visitantes y colecciona sellos en tu Pasaporte del Patrimonio.',

        // Login page.
        'login_title' => 'Bienvenido de nuevo a Akwaaba 360',
        'login_tagline' => 'Descubre Ghana. Vive más.',
        'login_description' => '¡Bienvenido de nuevo! Inicia sesión para continuar tu viaje.',
        'language_label' => 'Idioma',
        'email_address' => 'Correo electrónico:',
        'email_placeholder' => 'Introduce tu correo electrónico',
        'password' => 'Contraseña:',
        'password_placeholder' => 'Introduce tu contraseña',
        'show_password' => 'Mostrar contraseña',
        'hide_password' => 'Ocultar contraseña',
        'remember_me' => 'Recordarme',
        'login_button' => 'Iniciar sesión',
        'no_account' => '¿No tienes una cuenta?',
        'create_account_link' => 'Crear una cuenta',
        'forgot_password' => '¿Olvidaste tu contraseña?',
        'invalid_credentials' => 'Dirección de correo electrónico o contraseña no válida.',
        'login_footer' => 'Akwaaba 360 | Explora la belleza de Ghana',

        // Registration page.
        'register_title' => 'Crea tu cuenta de Akwaaba 360',
        'full_name' => 'Nombre completo:',
        'country' => 'País:',
        'confirm_password' => 'Confirmar contraseña:',
        'select_language' => 'Selecciona un idioma',
        'registration_required' => 'Completa todos los campos obligatorios.',
        'password_mismatch' => 'Las contraseñas no coinciden.',
        'invalid_email' => 'Introduce una dirección de correo electrónico válida.',
        'password_length' => 'Tu contraseña debe contener al menos 8 caracteres.',
        'invalid_language' => 'Selecciona un idioma válido.',
        'email_exists' => 'Ya existe una cuenta con este correo electrónico.',
        'registration_button' => 'Crear cuenta',

        // Forgot password page.
        'forgot_password_title' => '¿Olvidaste tu contraseña?',
        'forgot_password_description' => 'Introduce el correo electrónico registrado para solicitar un restablecimiento de contraseña.',
        'request_password_reset' => 'Solicitar restablecimiento',
        'password_reset_request_message' => 'Si existe una cuenta con ese correo electrónico, se enviará un enlace para restablecer la contraseña.',
        'forgot_password_invalid_email' => 'Introduce una dirección de correo electrónico válida.',

        // Reset password page.
        'reset_password_title' => 'Restablecer tu contraseña',
        'new_password' => 'Nueva contraseña:',
        'confirm_new_password' => 'Confirmar nueva contraseña:',
        'reset_password_button' => 'Restablecer contraseña',
        'reset_invalid_token' => 'Este enlace para restablecer la contraseña no es válido o está incompleto.',
        'reset_expired_token' => 'Este enlace para restablecer la contraseña ha caducado o no es válido.',
        'reset_password_mismatch' => 'Las contraseñas no coinciden.',
        'reset_password_length' => 'Tu contraseña debe contener al menos 8 caracteres.',
        'reset_password_success' => 'Tu contraseña se ha restablecido correctamente. Ahora puedes iniciar sesión.',
        'back_to_login' => 'Volver al inicio de sesión',

        // Tourist dashboard.
        'dashboard_welcome_label' => 'TU EXPERIENCIA AKWAABA',
        'dashboard_welcome' => 'Bienvenido de nuevo,',
        'dashboard_description' => 'Tu próxima aventura en Ghana comienza aquí. Descubre destinos increíbles, conecta con guías locales y disfruta de experiencias inolvidables.',
        'explore_destinations' => 'Explorar destinos',
        'manage_preferences' => 'Gestionar preferencias',
        'your_account' => 'Tu cuenta',
        'account_type' => 'Tipo de cuenta',

        // Guide dashboard.
        'guide_dashboard_title' => 'Panel del guía de Akwaaba 360',
        'guide_welcome' => 'Bienvenido,',
        'guide_account_role' => 'Rol de la cuenta',

        // Admin dashboard.
        'admin_dashboard_title' => 'Panel del administrador de Akwaaba 360',
        'admin_welcome' => 'Bienvenido,',
        'admin_description' => 'Tienes acceso al área de administración.',

        // Preferences page.
        'preferences_title' => 'Tus preferencias',
        'preferred_language' => 'Idioma preferido:',
        'preferred_currency' => 'Moneda preferida:',
        'save_preferences' => 'Guardar preferencias',
        'back_to_dashboard' => 'Volver al panel de control',
        'preferences_saved' => '¡Tus preferencias se han guardado correctamente!',
        'preferences_error' => 'Selecciona un idioma y una moneda válidos.',

        // Currency options.
        'currency_GHS' => 'Cedi ghanés (GHS)',
        'currency_USD' => 'Dólar estadounidense (USD)',
        'currency_EUR' => 'Euro (EUR)',
        'currency_GBP' => 'Libra esterlina (GBP)',

        // Account roles.
        'role_tourist' => 'Turista',
        'role_user' => 'Usuario',
        'role_guide' => 'Guía turístico',
        'role_admin' => 'Administrador'
    ]
];

// =====================================================
// DETERMINE THE USER'S PREFERRED LANGUAGE
// =====================================================

// Check the session first, then the saved browser cookie.
// Use English as the default language.
$currentLanguage = $_SESSION['preferred_language']
    ?? $_COOKIE['preferred_language']
    ?? 'en';

// Use English if the selected language is unsupported.
if (!array_key_exists($currentLanguage, $languageTranslations)) {
    $currentLanguage = 'en';
}

// =====================================================
// TRANSLATION FUNCTION
// =====================================================

function ak360_translate($key)
{
    // Make the translation array and selected language
    // accessible inside this function.
    global $languageTranslations, $currentLanguage;

    // Return the selected translation.
    // Fall back to English, then to the key itself.
    return $languageTranslations[$currentLanguage][$key]
        ?? $languageTranslations['en'][$key]
        ?? $key;
}
?>
