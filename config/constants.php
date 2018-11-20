<?php
//Contains all global constants

return [
    //'API_URL' => 'https://www.ecube-solutions.com/gma500laravel/public',
    //'SITE_URL' => 'https://www.ecube-solutions.com',
    'API_URL' => 'http://localhost:8000',  //In prod: https://www.ecube-solutions.com/gma500laravel/public
    'SITE_URL' => 'http://localhost:4200', 
    'EMAIL_FROM_ADDRESS' => 'webmaster@gma500.fr',
    'EMAIL_FROM_NAME' => 'GMA500',
    'EMAIL_NOREPLY' => 'no-reply@gma500.fr',
    'TOKEN_LIFE_SHORT' => 120,          //Time in minutes of token life when no keepconnected
    'TOKEN_LIFE_LONG'  => 43200,          //Time in minutes of token life when keepconnected
    'ACCESS_DEFAULT' => 'Pré-inscrit',
    'ACCESS_MEMBER' => 'Membre',
    'ACCESS_ADMIN' => 'Admin',
    'ACCESS_AVAILABLE' => ['Pré-inscrit', 'Membre', 'Admin'],
    'LANGUAGES' => ['en', 'fr'],         //Supported languages
    'THUMBS' => ['full' => 1600, 'large'=> 1024, 'big' => 768, 'medium' => 360 , 'small' => 150, 'tiny'=>50 ]
];