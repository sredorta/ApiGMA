<?php

if(env('APP_ENV') === 'testing')
    return['language_test' => 'francais'];
return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed'   => 'Ces identifiants ne correspondent pas à nos enregistrements',
    'throttle' => 'Tentatives de connexion trop nombreuses. Veuillez essayer de nouveau dans :seconds secondes.',
    'expired'  => 'Votre session a éxpiré',
    'invalid'  => 'Votre session n\'est pas valide',
    'error'    => 'Problème de session',
    'login_required' => 'Vous devez être connecté pour éxécuter cette operation',
    'admin_required' => 'Vous devez être connecte en tant qu\'administrateur pour éxécuter cette operation',
    'already_loggedin' => 'Vous ne pouvez pas être déjà connecté pour éxécuter cette operation',
    'user_already_exists' => 'Mobile ou email déjà enregistré dans le système',
    'signup_success' => 'Création du compte reusie. Validez votre compte email et connectez-vous',
    'login_validate' => "Vous dévez valider votre compte email pour pouvoir acceder",
    'account_missing' => "Compte pas trouvé",
    'token_error' => "Le token d'identification n'a pas pu etre géneré",
    'email_failed' => "Cette addresse email ne correspond pas à nos enregistrements",
    'reset_title' => "Demande de nouveau mot de passe pour votre compte",
    'reset_text1' => "Votre nouveau mot de passe est: ",
    'reset_text2' => "Ce mot de passe concerne l'accés : ",
    'reset_subject' => "GMA500: Votre nouveau mot de passe",
    'reset_success' => "Un nouveau mot de passe vous à été envoyé par email",
    'update_success' => "Vos modifications sont bien prises en compte",
    'update_phone_found' => "Ce numero de telephone est déjà enregistré dans le système",
    'update_password' => "Password pas correcte",
    'update_email' => "Cet email est déjà enregistré dans le système",

    'test' => 'test en francais. Bien fait :param'
];
