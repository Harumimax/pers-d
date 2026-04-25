<?php

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'login' => [
        'title' => 'Log in',
        'email' => 'Email',
        'password' => 'Password',
        'remember' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'submit' => 'Log in',
    ],
    'register' => [
        'title' => 'Register',
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'already_registered' => 'Already registered?',
        'submit' => 'Register',
    ],
    'forgot_password' => [
        'description' => 'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.',
        'email' => 'Email',
        'submit' => 'Email Password Reset Link',
    ],
    'reset_password' => [
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'submit' => 'Reset Password',
    ],
    'reset_password_mail' => [
        'subject' => 'Reset your password',
        'intro' => 'You are receiving this email because we received a password reset request for your account.',
        'action_label' => 'Reset Password',
        'expire' => 'This password reset link will expire in :count minutes.',
        'outro' => 'If you did not request a password reset, no further action is required.',
    ],
    'verify_email' => [
        'description' => 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.',
        'resent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend Verification Email',
        'logout' => 'Log Out',
    ],
    'confirm_password' => [
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'password' => 'Password',
        'submit' => 'Confirm',
    ],
];
