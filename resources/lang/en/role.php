<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'missing' => 'Missing role or user',
    'role.assign' => 'The role of :role has been assigned to you',
    'role.unassign' => 'The role of :role has been removed to you',
];