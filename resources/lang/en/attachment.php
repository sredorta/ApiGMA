<?php
if(env('APP_ENV') === 'testing')
    return[];
return [
    'wrong_type' => 'Attachments not allowed for content type :type',
    'wrong_id' => 'Could not find :type with id :id',
    'default' => 'Default attachment :default not found',
    'failed' => 'A problem occurred during the file processing',
    'already' => 'A document :title already exists'

];