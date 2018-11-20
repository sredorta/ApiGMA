<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'missing' => 'Notification pas trouvé',
    'welcome' => ':name , bienvenu(e) au site du GMA500. Vous êtes pré-inscrit.',
    'account_added' => "Votre compte :account vient d'être accepté",
    'account_deleted' => "Votre compte :account vient d'être supprimé",

];