<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'missing' => 'Notification pas trouvé',
    'welcome' => ':name , bienvenu(e) au site du GMA500. Vous êtes pré-inscrit.',
    'account_added' => "Votre compte :account vient d'être accepté",
    'account_deleted' => "Votre compte :account vient d'être supprimé",
    'group_assign' => 'Le group de :group vous a été assigné',
    'group_unassign' => 'Le group de :group vous à été supprimé',
    'role_assign' => 'Le role de :role vous a été assigné',
    'role_unassign' => 'Le role de :role vous à été supprimé',
];