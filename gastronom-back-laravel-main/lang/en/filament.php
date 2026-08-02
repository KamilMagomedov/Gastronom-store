<?php

return [
    'actions' => [
        'delete' => [
            'label' => 'Delete',
            'modal' => [
                'heading' => 'Delete :label',
                'description' => 'Are you sure you want to delete this item? This action cannot be undone.',
            ],
        ],
        'edit' => [
            'label' => 'Edit',
        ],
        'save' => [
            'label' => 'Save',
        ],
        'cancel' => [
            'label' => 'Cancel',
        ],
    ],
    'pages' => [
        'edit-record' => [
            'title' => 'Editing :label',
        ],
        'create-record' => [
            'title' => 'Create :label',
        ],
    ],
];
