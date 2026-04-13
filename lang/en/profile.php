<?php

return [
    'statistics' => [
        'title' => 'Remainder Statistic',
        'rows' => [
            'completed_sessions' => 'Completed sessions',
            'first_completed_session' => 'First completed session',
            'last_completed_session' => 'Last completed session',
            'preferred_mode' => 'Preferred mode',
            'preferred_direction' => 'Preferred direction',
            'total_words' => 'Total words in all games',
            'correct_answers' => 'Total correct answers',
            'incorrect_answers' => 'Total incorrect answers',
            'accuracy_percentage' => 'Correct answers percentage',
        ],
        'fallbacks' => [
            'no_completed_sessions' => 'No completed sessions yet',
            'not_enough_data' => 'Not enough data yet',
            'no_answers' => 'No answers yet',
        ],
        'mode' => [
            'manual' => 'Manual translation input',
            'choice' => 'Multiple choice',
            'both_equally' => 'Both equally',
        ],
        'direction' => [
            'foreign_to_ru' => 'Foreign language to Russian',
            'ru_to_foreign' => 'Russian to foreign language',
            'both_equally' => 'Both equally',
        ],
    ],
    'settings' => [
        'title' => 'Profile Settings',
    ],
    'personal_information' => [
        'title' => 'Personal Information',
        'description' => 'Update your account\'s profile information and email address.',
        'name' => 'Name',
        'email' => 'Email',
        'unverified' => 'Your email address is unverified.',
        'resend_verification' => 'Click here to re-send the verification email.',
        'verification_sent' => 'A new verification link has been sent to your email address.',
        'save' => 'Save Changes',
        'saved' => 'Saved.',
    ],
    'password' => [
        'title' => 'Change Password',
        'description' => 'Ensure your account is using a long, random password to stay secure.',
        'current' => 'Current Password',
        'new' => 'New Password',
        'confirm' => 'Confirm Password',
        'save' => 'Save Changes',
        'saved' => 'Saved.',
    ],
    'delete' => [
        'title' => 'Delete Account',
        'description' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
        'trigger' => 'Delete Account',
        'confirm_title' => 'Are you sure you want to delete your account?',
        'confirm_text' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
        'password_placeholder' => 'Password',
        'cancel' => 'Cancel',
        'confirm' => 'Delete Account',
    ],
];
