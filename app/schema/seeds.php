<?php
/**
 * Starting content for pages added after the first database import.
 *
 * When a page's section doesn't exist in the database yet, it is created
 * from here (on the first visit to the page or its CMS screen) – so new
 * pages need no SQL import. After that, everything is edited in the CMS
 * and this file is no longer used for that section.
 *
 *   'data'      => section fields
 *   'items'     => list of item field arrays (cards, logos …)
 *   'copy_from' => 'home'  start as a copy of the same section on that page
 */

/** Same starting content for every inner page – only banner + categories differ. */
$inner = static function (array $banner, string $moreLabel, array $categories): array {
    $items = [];
    foreach ($categories as [$title, $file, $alt]) {
        $items[] = ['title' => $title, 'image' => 'uploads/demo/' . $file, 'image_alt' => $alt, 'url' => '#'];
    }
    return [
        'banner' => [
            'data' => $banner + [
                'tagline'    => 'Lorem Ipsum Dolor Sit Amet',
                'body'       => 'Lorem Ipsum Dolor Sit Amet Consectetur. Sapien Suspendisse Aliquet Amet Mi Ullamcorper Sagittis Sed Morbi. Odio Sed Blandit Magna Consectetur A Est Ac Ut.',
                'link_label' => 'Explore Products',
                'link_url'   => '#',
            ],
        ],
        'intro' => [
            'data' => [
                'tagline'    => 'Lorem Ipsum Dolor Sit Amet Consectetur. Egestas Eu Sapien.',
                'heading'    => 'Lorem Ipsum Dolor Sit Amet',
                'body'       => "Lorem ipsum dolor sit amet consectetur. Et sed tellus sed purus egestas. Quam nisl sem habitasse sed id elit sed tortor neque. Nec lobortis semper pellentesque donec faucibus tempor."
                              . "\n\nIn varius netus purus ac enim sed id aliquet. Eleifend ultricies in vitae adipiscing ornare faucibus quisque rhoncus vitae. Libero felis arcu a massa mauris et ut odio eget. Quam sollicitudin cras nibh auctor massa lorem. Aenean risus leo fames adipiscing eget a massa. Enim urna lectus sed purus lorem turpis at massa elit."
                              . "\n\nAmet leo elementum donec urna aliquet tempor accumsan aenean. Amet sed velit tristique pretium etiam. At leo sed mauris turpis tortor malesuada quisque.",
                'link_label' => 'About ARKI Environments',
                'link_url'   => '#',
            ],
        ],
        'categories' => [
            'data'  => ['link_label' => $moreLabel, 'link_url' => '#'],
            'items' => $items,
        ],
        'solutions'   => ['copy_from' => 'home'],
        'projects'    => ['copy_from' => 'home'],
        'brands'      => ['copy_from' => 'home'],
        'inspiration' => ['copy_from' => 'home'],
        'cta'         => ['copy_from' => 'home'],
    ];
};

return [
    'work' => $inner(
        ['heading' => 'Work', 'image' => 'uploads/demo/hero-work.jpg', 'image_alt' => 'Open-plan office with workstations'],
        'Explore All Work Products',
        [
            ['Tables',  'work-tables.jpg',  'Height-adjustable desk with screens'],
            ['Seating', 'work-seating.jpg', 'Mesh task chair'],
            ['Lounge',  'work-lounge.jpg',  'Orange lounge armchair'],
            ['Privacy', 'work-privacy.jpg', 'Private work pod'],
            ['Tools',   'work-tools.jpg',   'Mobile whiteboard with side table'],
        ]
    ),

    'learn' => $inner(
        ['heading' => 'Learn', 'image' => 'uploads/demo/hero-learn.jpg', 'image_alt' => 'Classroom with desks and chairs'],
        'Explore All Learn Products',
        [
            ['Tables',        'work-tables.jpg',  'Height-adjustable desk with screens'],
            ['Seating',       'work-seating.jpg', 'Mesh task chair'],
            ['Lounge',        'work-lounge.jpg',  'Orange lounge armchair'],
            ['Storage',       'work-privacy.jpg', 'Storage unit'],
            ['Teacher Tools', 'work-tools.jpg',   'Mobile whiteboard with side table'],
        ]
    ),
];
