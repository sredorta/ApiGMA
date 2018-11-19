<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'missing' => 'Role ou utilizateur pas trouvé',
    'role.assign' => 'Le role de :role vous a été assigné',
    'role.unassign' => 'Le role de :role vous à été supprimé',
];