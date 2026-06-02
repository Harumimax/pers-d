<?php

return [
    'hero' => [
        'eyebrow' => 'Mini game',
        'title' => 'WordKeeper platformer',
        'description' => 'This page is the first UI slice of a monochrome side-view platformer. The gameplay loop will be added in the next steps.',
    ],
    'canvas' => [
        'aria' => 'WordKeeper browser game canvas',
    ],
    'start' => [
        'title' => 'Ready to start the run',
        'description' => 'Jump into the monochrome level, avoid danger, and use your shots carefully. You have three lives before the run ends.',
        'controls_aria' => 'Game controls',
        'controls' => [
            'left_right_keys' => '← →',
            'left_right' => 'Move left and right',
            'jump_key' => '↑',
            'jump' => 'Jump',
            'shoot_key' => 'Space',
            'shoot' => 'Shoot',
        ],
        'action' => 'Start',
    ],
    'hud' => [
        'lives' => 'Lives',
    ],
    'win' => [
        'title' => 'Level complete',
        'description' => 'You reached the finish. Ready for another run?',
        'action' => 'Play again',
    ],
    'lose' => [
        'title' => 'Game over',
        'description' => 'All lives are gone. Restart the level and try another run.',
        'action' => 'Restart level',
    ],
    'progress' => [
        'title' => 'Journey to the finish',
        'preview_label' => 'Slide :number',
    ],
    'mobile' => [
        'title' => 'This game works on desktop',
        'description' => 'Open the page on a device with a keyboard to play the platformer comfortably.',
    ],
];
