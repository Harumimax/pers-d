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
        'badge' => 'Stage 1',
        'title' => 'Ready to start the run',
        'description' => 'For now this screen prepares the canvas shell, controls, and progress area. The actual movement, jump, and shooting mechanics will arrive in the next stage.',
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
    'progress' => [
        'eyebrow' => 'Progress',
        'title' => 'Journey to the finish',
        'description' => 'The picture in this panel will change as the runner gets closer to the level finish. For now the panel shows the static first slide.',
        'preview_label' => 'Slide :number',
        'slide_caption' => 'Slide :current of :total',
        'preview_hint' => 'In the complete game this panel will update automatically while you advance through the level.',
    ],
];
