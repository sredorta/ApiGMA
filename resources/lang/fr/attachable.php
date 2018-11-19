<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'failed' => "Problème survenu pendant le traitement du document",
];