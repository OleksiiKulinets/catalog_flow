<?php

return [

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

];
