<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'wrong' => 'Le contenu de :type ne peut pas recevoir des fichiers attachés',
    'wrong_id' => "Identifiant :id n'a pas pu être trouvé pour :type",
    'failed' => "Problème survenu pendant le traitement du document",
];