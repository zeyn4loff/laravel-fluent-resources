<?php

return [
    'default_language_id' => 1,
    'language_key' => 'language_id',
    'defaults' => [
        'hidden_fields' => ['created_at', 'updated_at', 'deleted_at'],
        'external_hidden_fields' => ['id', 'internal_notes'],
    ],
    'auto_replace_translations' => false,
    'convert_keys_to_camel_case' => false,
];