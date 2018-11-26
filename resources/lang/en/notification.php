<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'missing' => 'Notification not found',
    'welcome' => ':name , welcome to the GMA500 site. You aren\'t yet memeber of the club.',
    'account_added' => 'The account :account has been accepted',
    'account_deleted' => 'The account :account has been removed',
    'group_assign' => 'You are now part of the group :group',
    'group_unassign' => 'You are not anymore part of the group :group',
    'role_assign' => 'The role :role has been assigned to you',
    'role_unassign' => 'The role :role has been unassigned to you',

];
