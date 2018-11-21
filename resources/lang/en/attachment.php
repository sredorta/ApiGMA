<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'wrong_type' => 'Attachments not allowed for content type :type',
    'wrong_id' => 'Could not find :type with id :id',
    'failed' => 'A problem occurred during the file processing',
];