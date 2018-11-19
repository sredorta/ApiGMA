<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'missing' => 'Notification pas trouvé',
    'welcome' => ':name , bienvenu(e) au site du GMA500. Vous êtes pré-inscrit.',


];