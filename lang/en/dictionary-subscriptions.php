<?php

return [
    'messages' => [
        'invitation_sent' => 'If this email can receive dictionary invitations, the subscription link has been sent.',
        'subscription_added' => 'Dictionary ":dictionary" was added to your dictionaries in subscription mode.',
    ],
    'errors' => [
        'cannot_invite_owner' => 'You already own this dictionary.',
        'owner_already_has_access' => 'The dictionary owner already has access.',
        'email_mismatch' => 'This invitation can only be accepted by the invited email address.',
        'expired' => 'This invitation has expired.',
        'invalid' => 'This invitation is invalid or no longer available.',
    ],
    'page' => [
        'title' => 'Dictionary subscription invitation',
        'invalid' => 'This invitation is invalid or no longer available.',
        'expired' => 'This invitation has expired.',
        'accepted' => 'Dictionary ":dictionary" is already available in your dictionaries.',
        'email_mismatch' => 'You are signed in with a different email. Please sign in with :email to accept this invitation.',
        'guest_intro' => 'User :owner invited you to subscribe to dictionary ":dictionary". Sign in or create an account to continue.',
        'ready_to_accept' => 'User :owner invited you to subscribe to dictionary ":dictionary".',
        'accept_button' => 'Subscribe to dictionary',
        'open_dictionaries' => 'Open my dictionaries',
        'login' => 'Log in',
        'register' => 'Create account',
    ],
    'email' => [
        'subject' => 'Invitation to subscribe to dictionary ":dictionary"',
        'greeting' => 'Hello!',
        'ignore' => 'If you do not want to subscribe to this dictionary, you can ignore this email.',
        'link_hint' => 'Use this link to open the invitation:',
        'existing' => [
            'intro' => 'You are registered on WordKeeper. User :owner invited you to subscribe to dictionary ":dictionary".',
            'text' => "Hello!\n\nYou are registered on https://wordkeeper.space/.\nUser :owner invited you to subscribe to dictionary \":dictionary\".\nIf you do not want to subscribe, you can ignore this message.\nIf you want to continue, open this link:\n:link",
        ],
        'new_user' => [
            'intro' => 'User :owner invited you to subscribe to dictionary ":dictionary", but you do not have a WordKeeper account yet.',
            'register_hint' => 'Create an account first, then open the invitation link to subscribe.',
            'text' => "Hello!\n\nYou are not registered on https://wordkeeper.space/.\nUser :owner invited you to subscribe to dictionary \":dictionary\".\nIf you do not want to subscribe, you can ignore this message.\nCreate an account here first:\n:register\n\nAfter registration, open this link to subscribe:\n:link",
        ],
    ],
];
