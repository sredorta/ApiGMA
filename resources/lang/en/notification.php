<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'missing' => 'Notification not found',
    'welcome' => ':name , welcome to the GMA500 site. You aren\'t yet memeber of the club.',


];
