<?php

return [

    'admin' => [

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

    ],

    'auth' => [

        'continue_with_google' => 'Continue with Google',
        'or_divider' => 'or',
        'google_failed' => 'Google sign-in failed or was cancelled. Please try again.',
        'login' => [
            'title' => 'Welcome back',
            'subtitle' => 'Sign in to continue to your account.',
            'remember_me' => 'Remember me',
            'forgot_password' => 'Forgot your password?',
            'submit' => 'Log in',
        ],
        'register' => [
            'title' => 'Create an account',
            'subtitle' => 'Get started in a few seconds.',
            'already_registered' => 'Already registered?',
            'submit' => 'Register',
        ],
        'forgot_password' => [
            'intro' => 'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.',
            'submit' => 'Email Password Reset Link',
        ],
        'reset_password' => [
            'submit' => 'Reset Password',
        ],
        'confirm_password' => [
            'intro' => 'This is a secure area of the application. Please confirm your password before continuing.',
            'submit' => 'Confirm',
        ],
        'verify_email' => [
            'intro' => "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.",
            'sent' => 'A new verification link has been sent to the email address you provided during registration.',
            'resend' => 'Resend Verification Email',
        ],

    ],

    'batches' => [

        'status' => [
            'draft' => 'Draft',
            'uploading' => 'Uploading',
            'queued' => 'Queued',
            'in_progress' => 'In progress',
            'finalizing' => 'Finalizing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'expired' => 'Expired',
            'cancelling' => 'Cancelling',
            'cancelled' => 'Cancelled',
        ],

        'batches' => [
            'title' => 'Batches',
            'subtitle' => 'Every batch you have run, in one place.',
            'create' => [
                'title' => 'New project',
                'subtitle' => 'Upload a dataset, choose a model, and describe what should change.',
                'data_model_heading' => 'Data & Model',
                'prompt' => 'Prompt',
                'prompt_placeholder' => 'Describe what you want done with this data, e.g. "Translate the description column to Spanish and keep everything else unchanged."',
                'model' => 'Model',
                'output_format' => 'Output format',
                'output_formats' => [
                    'csv' => 'CSV',
                    'xlsx' => 'Excel (XLSX)',
                    'json' => 'JSON',
                    'google_sheet' => 'Google Sheets',
                ],
                'dataset' => 'Dataset',
                'tab_upload_file' => 'Upload file',
                'tab_google_sheets' => 'Google Sheets',
                'dropzone_text' => 'Drag & drop your file here, or',
                'browse' => 'browse',
                'dropzone_hint' => 'XLSX, CSV or JSON · up to 25MB',
                'google_sheets_placeholder' => 'Paste a Google Sheets link',
                'google_sheets_shared_hint' => 'Public sheets: share as "Anyone with the link". Private sheets: use your connected Google account below.',
                'google_connected' => 'Connected as :email — private sheets are supported.',
                'google_not_connected' => 'Not connected — only publicly shared sheets are supported.',
                'google_connect_cta' => 'Connect Google account',
                'uses_api_key' => 'Uses your saved OpenAI API key',
                'run_batch' => 'Create batch',
                'created' => 'Batch created — :rows rows imported from :name.',
                'google_sheets_errors' => [
                    'invalid_url' => 'That doesn\'t look like a valid Google Sheets link.',
                    'not_public' => 'This sheet isn\'t publicly accessible. Share it as "Anyone with the link", or connect your Google account below (private sheets aren\'t supported yet).',
                    'fetch_failed' => 'Could not reach Google Sheets. Please try again.',
                    'too_large' => 'This sheet is too large to import (max 25MB).',
                ],
            ],
            'search_placeholder' => 'Search batches...',
            'filter_status' => 'Status',
            'filter_model' => 'Model',
            'filter_date' => 'Date',
            'all_statuses' => 'All statuses',
            'all_models' => 'All models',
            'all_dates' => 'Any time',
            'date_today' => 'Today',
            'date_week' => 'Last 7 days',
            'date_month' => 'Last 30 days',
            'sort_newest' => 'Newest first',
            'sort_oldest' => 'Oldest first',
            'column_name' => 'Name',
            'column_status' => 'Status',
            'column_model' => 'Model',
            'column_progress' => 'Progress',
            'column_created' => 'Created',
            'column_actions' => 'Actions',
            'view' => 'View',
            'showing' => 'Showing :from-:to of :total',
            'previous' => 'Previous',
            'next' => 'Next',
            'empty' => 'No batches match your filters.',
        ],

        'batch' => [
            'back' => 'Back to Batches',
            'overview' => 'Overview',
            'model' => 'Model',
            'dataset' => 'Dataset',
            'created' => 'Created',
            'progress' => 'Progress',
            'status' => 'Status',
            'prompt_used' => 'Prompt',
            'download' => 'Download',
            'results' => 'Results',
            'results_placeholder' => 'Results will appear here once this batch finishes processing.',
            'draft_placeholder' => "This batch hasn't been submitted for processing yet. Sending batches to the AI provider isn't available yet — this is coming soon.",
        ],

    ],

    'settings' => [

        'title' => 'Settings',
        'nav' => [
            'profile' => 'Profile',
            'api_key' => 'API Key',
            'language' => 'Language',
            'password' => 'Password',
            'danger_zone' => 'Danger Zone',
        ],
        'info' => [
            'title' => 'Profile Information',
            'subtitle' => "Update your account's profile information and email address.",
            'unverified' => 'Your email address is unverified.',
            'resend_link' => 'Click here to re-send the verification email.',
            'verification_sent' => 'A new verification link has been sent to your email address.',
        ],
        'api_key' => [
            'title' => 'OpenAI API Key',
            'subtitle' => 'Your key is encrypted at rest and is only used to run your batches against OpenAI. You can remove it at any time.',
            'connected_on' => 'Connected on :date',
            'remove' => 'Remove key',
            'removed' => 'API key removed.',
            'label' => 'API Key',
            'save' => 'Save Key',
            'find_hint' => 'You can find your key on the',
            'find_hint_link' => 'OpenAI API keys page',
        ],
        'language' => [
            'title' => 'Language',
            'subtitle' => 'Choose the language used across the interface.',
            'english' => 'English',
            'ukrainian' => 'Українська',
            'save' => 'Save Language',
        ],
        'password_section' => [
            'title' => 'Update Password',
            'subtitle' => 'Ensure your account is using a long, random password to stay secure.',
            'set_title' => 'Set a Password',
            'set_subtitle' => 'You signed up with Google and don\'t have a password yet. Set one to also be able to log in with your email.',
            'current' => 'Current Password',
            'new' => 'New Password',
        ],
        'danger' => [
            'title' => 'Delete Account',
            'subtitle' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
            'confirm_title' => 'Are you sure you want to delete your account?',
            'confirm_subtitle' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
        ],

    ],

];
