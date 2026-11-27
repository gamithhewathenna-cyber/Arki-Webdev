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

$store = $inner(
    ['heading' => 'Store', 'image' => 'uploads/demo/hero-store.jpg', 'image_alt' => 'Home office with desk and bookshelves'],
    'View Our Store',
    [
        ['Tables',        'work-tables.jpg',  'Height-adjustable desk with screens'],
        ['Seating',       'work-seating.jpg', 'Mesh task chair'],
        ['Lounge',        'work-lounge.jpg',  'Orange lounge armchair'],
        ['Storage',       'work-privacy.jpg', 'Storage unit'],
        ['Teacher Tools', 'work-tools.jpg',   'Mobile whiteboard with side table'],
    ]
);
$store['categories']['data']['heading'] = 'Our Categories';

$shopBanner = [
    'data' => [
        'image'        => 'uploads/demo/shop-banner.jpg',
        'image_alt'    => 'Living room with green sofa and timber coffee table',
        'tagline'      => 'Designed For Work. Built For People.',
        'heading'      => "Lorem Ipsum Dolor\nSit Amet Magna",
        'body'         => 'Lorem Ipsum Dolor Sit Amet Consectetur. Magna Sed Quis Nibh Scelerisque Nisl Urna Rhoncus Condimentum Odio. Eu Quis Egestas Malesuada Aenean Sem Elementum.',
        'button_label' => 'Explore Products',
        'button_url'   => '#products',
    ],
];

return [
    'shop' => [
        'banner_work'  => $shopBanner,
        'banner_learn' => $shopBanner,
        'banner_store' => $shopBanner,
        'listing_cta'  => ['copy_from' => 'home', 'copy_key' => 'cta'],
        'product_cta'  => [
            'data' => [
                'image'           => 'uploads/demo/cta-bg.jpg',
                'image_alt'       => 'Bright living space with sofa and round coffee tables',
                'tagline'         => 'More Than A Furniture Store',
                'heading'         => "Ready To Put Ideas\nInto Practice?",
                'body'            => "If something here sparked a thought for your own space, we'd love to help you make it real.",
                'primary_label'   => 'Get In Touch',
                'primary_url'     => '#',
                'secondary_label' => '',
                'secondary_url'   => '',
            ],
        ],
        'product_page' => [
            'data' => [
                'eyebrow'            => 'More Than A Furniture Store',
                'agent_label'        => 'Login To View',
                'family_heading'     => 'Family Of Products',
                'related_heading'    => 'Related Products',
                'related_text'       => 'Lorem ipsum dolor sit amet consectetur. Condimentum convallis nulla nec ultrices convallis non turpis. Euismod lacus nunc in id justo ac sed. Sit habitant donec massa semper nibh maecenas.',
                'related_link_label' => 'Explore Our Projects',
                'related_link_url'   => '#',
            ],
        ],
    ],

    'store' => [
        'banner'     => $store['banner'],
        'intro'      => $store['intro'],
        'categories' => $store['categories'],
        'products'   => [
            'data' => ['heading' => 'Featured Products', 'link_label' => 'View All Products', 'link_url' => 'store/products/', 'source' => 'featured', 'limit' => '8'],
        ],
        'cta'        => ['copy_from' => 'home'],
    ],

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
