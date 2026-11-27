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

$lorem = 'Lorem ipsum dolor sit amet consectetur. Et sed tellus sed purus egestas. Quam nisl sem habitasse sed id elit sed tortor neque. Nec lobortis semper pellentesque donec faucibus tempor.';

return [
    'work' => [
        'banner' => [
            'data' => [
                'image'      => 'uploads/demo/hero-work.jpg',
                'image_alt'  => 'Open-plan office with workstations',
                'tagline'    => 'Lorem Ipsum Dolor Sit Amet',
                'heading'    => 'Work',
                'body'       => 'Lorem Ipsum Dolor Sit Amet Consectetur. Sapien Suspendisse Aliquet Amet Mi Ullamcorper Sagittis Sed Morbi. Odio Sed Blandit Magna Consectetur A Est Ac Ut.',
                'link_label' => 'Explore Products',
                'link_url'   => '#',
            ],
        ],
        'intro' => [
            'data' => [
                'tagline'    => 'Lorem Ipsum Dolor Sit Amet Consectetur. Egestas Eu Sapien.',
                'heading'    => 'Lorem Ipsum Dolor Sit Amet',
                'body'       => $lorem . "\n\nIn varius netus purus ac enim sed id aliquet. Eleifend ultricies in vitae adipiscing ornare faucibus quisque rhoncus vitae. Libero felis arcu a massa mauris et ut odio eget. Quam sollicitudin cras nibh auctor massa lorem. Aenean risus leo fames adipiscing eget a massa. Enim urna lectus sed purus lorem turpis at massa elit."
                              . "\n\nAmet leo elementum donec urna aliquet tempor accumsan aenean. Amet sed velit tristique pretium etiam. At leo sed mauris turpis tortor malesuada quisque.",
                'link_label' => 'About ARKI Environments',
                'link_url'   => '#',
            ],
        ],
        'categories' => [
            'data'  => ['link_label' => 'Explore All Work Products', 'link_url' => '#'],
            'items' => [
                ['title' => 'Tables',  'image' => 'uploads/demo/work-tables.jpg',  'image_alt' => 'Height-adjustable desk with screens', 'url' => '#'],
                ['title' => 'Seating', 'image' => 'uploads/demo/work-seating.jpg', 'image_alt' => 'Mesh task chair',                     'url' => '#'],
                ['title' => 'Lounge',  'image' => 'uploads/demo/work-lounge.jpg',  'image_alt' => 'Orange lounge armchair',              'url' => '#'],
                ['title' => 'Privacy', 'image' => 'uploads/demo/work-privacy.jpg', 'image_alt' => 'Private work pod',                    'url' => '#'],
                ['title' => 'Tools',   'image' => 'uploads/demo/work-tools.jpg',   'image_alt' => 'Mobile whiteboard with side table',   'url' => '#'],
            ],
        ],
        'solutions'   => ['copy_from' => 'home'],
        'projects'    => ['copy_from' => 'home'],
        'brands'      => ['copy_from' => 'home'],
        'inspiration' => ['copy_from' => 'home'],
        'cta'         => ['copy_from' => 'home'],
    ],
];
