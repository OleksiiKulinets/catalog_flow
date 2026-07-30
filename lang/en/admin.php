<?php

return [

    'nav' => [
        'menu' => 'Menu',
        'dashboard' => 'Dashboard',
        'batches' => 'Batches',
        'settings' => 'Settings',
        'log_out' => 'Log Out',
    ],

    'welcome' => [
        'nav_login' => 'Log in',
        'nav_register' => 'Get started',
        'eyebrow' => 'AI-powered batch processing',
        'title' => 'Turn spreadsheets into finished work, in bulk.',
        'subtitle' => 'Upload a dataset, describe what should change, and let AI process every row for you.',
        'cta_primary' => 'Get started for free',
        'cta_secondary' => 'Log in',
        'feature_upload' => [
            'title' => 'Upload any dataset',
            'text' => 'XLSX, CSV, JSON, or a Google Sheets link.',
        ],
        'feature_describe' => [
            'title' => 'Describe the change',
            'text' => 'Tell it what to do in plain language.',
        ],
        'feature_results' => [
            'title' => 'Get structured results',
            'text' => 'Download clean, ready-to-use output.',
        ],
    ],

    'palette' => [
        'title' => 'Quick navigate',
        'hint_select' => 'Press a number to jump',
        'hint_close' => 'Esc to close',
    ],

    'common' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'save' => 'Save',
        'saved' => 'Saved.',
        'cancel' => 'Cancel',
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'create_project' => 'Create project',
        'stats' => [
            'projects' => 'Projects',
            'projects_subtitle' => 'Total projects',
            'running' => 'Running',
            'running_subtitle' => 'In progress',
            'completed_today' => 'Completed today',
            'completed_today_subtitle' => 'Done today',
            'failed' => 'Failed',
            'failed_subtitle' => 'Failed batches',
        ],
        'activity_labels' => [
            'draft' => 'Project created',
            'uploading' => 'Dataset uploading',
            'queued' => 'Batch queued',
            'in_progress' => 'Batch running',
            'finalizing' => 'Batch finalizing',
            'completed' => 'Batch completed',
            'failed' => 'Batch failed',
            'expired' => 'Batch expired',
            'cancelling' => 'Batch cancelling',
            'cancelled' => 'Batch cancelled',
        ],
        'ai_activity' => [
            'title' => 'AI Activity',
            'subtitle' => 'User activity over the last :weeks weeks.',
            'less' => 'Less',
            'more' => 'More',
            'total_requests' => 'Total requests',
            'projects_created' => 'Projects created',
            'batches_completed' => 'Batches completed',
            'batches_failed' => 'Batches failed',
        ],
        'activity_feed' => [
            'title' => 'Activity Feed',
            'empty' => 'No activity yet.',
            'view_all' => 'View all activity',
        ],
        'running' => [
            'title' => 'Running Batches',
            'empty' => 'Nothing running right now.',
        ],
        'recent' => [
            'title' => 'Recent projects',
            'empty' => 'No projects yet — create your first one to get started.',
            'more' => 'View all',
        ],
    ],

];
