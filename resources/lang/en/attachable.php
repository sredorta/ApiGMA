<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'failed' => 'A problem occurred during the file processing',
];