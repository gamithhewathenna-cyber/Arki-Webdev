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
    'contact' => [
        'contact_intro' => [
            'data' => [
                'tagline'          => 'Designed For Work. Built For People.',
                'heading'          => 'Let’s Talk.',
                'body'             => 'Whether You’re Planning A Whole Workplace Or Just Choosing A Chair, We’d Love To Help. Tell Us A Little About Your Project And The Right Person Will Be In Touch.',
                'direct_heading'   => 'Prefer To Reach Us Directly?',
                'direct_text'      => 'For online store orders, please include your order number so we can help faster.',
                'form_heading'     => 'Send Us A Message',
                'form_text'        => 'Fill in a few details and we’ll get back to you within one business day.',
                'topics'           => "A new workplace project\nWork furniture\nLearn / education furniture\nAn online store order\nBooking a showroom appointment\nSomething else",
                'newsletter_label' => 'I’d like to receive occasional updates from Arki.',
                'button_label'     => 'Send Enquiry',
                'success_message'  => 'Thank you – your message has been sent. We’ll get back to you within one business day.',
                'notify_email'     => '',
            ],
            'items' => [
                ['icon' => 'email', 'label' => 'Email', 'value' => 'info@arkienvironments.au', 'url' => 'mailto:info@arkienvironments.au', 'width' => 'full'],
                ['icon' => 'phone', 'label' => 'Phone', 'value' => '07 3706 6963', 'url' => 'tel:0737066963', 'width' => 'half'],
                ['icon' => 'phone', 'label' => 'Phone', 'value' => '+61 7 3706 6963', 'url' => 'tel:+61737066963', 'width' => 'half'],
            ],
        ],
        'showrooms' => [
            'data' => [
                'image'      => 'uploads/demo/cta-bg.jpg',
                'image_alt'  => 'Showroom lounge',
                'heading'    => 'Come And See Us',
                'body'       => 'Our showrooms are open by appointment, so we can give you our full attention and have the right pieces ready to see and try.',
                'note_title' => 'Not nearby?',
                'note_text'  => 'No problem. We deliver and install for clients across Australia, and can guide your project remotely from first plan to final handover.',
            ],
            'items' => [
                ['city' => 'Sydney', 'address' => "Tenancy 02,\n13 Bowden Street,\nAlexandria NSW 2015", 'hours' => "Monday–Friday\n9 AM - 5 PM",
                 'map_address' => '13 Bowden Street, Alexandria NSW 2015, Australia', 'map_image' => '', 'button_label' => 'Book An Appointment', 'button_url' => '#contact-form'],
                ['city' => 'Brisbane', 'address' => "29 Cairns Street,\nKangaroo Point QLD 4169", 'hours' => "Monday–Friday\n9 AM - 5 PM",
                 'map_address' => '29 Cairns Street, Kangaroo Point QLD 4169, Australia', 'map_image' => '', 'button_label' => 'Book An Appointment', 'button_url' => '#contact-form'],
            ],
        ],
    ],

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

    'about' => [
        'banner' => [
            'data' => [
                'image'      => 'uploads/demo/hero-store.jpg',
                'image_alt'  => 'Bright living space with a white sofa',
                'tagline'    => 'Designed For Work. Built For People.',
                'heading'    => "We Design The Spaces Where\nGood Work Happens",
                'body'       => 'Arki Environments is an Australian workplace and education specialist, and an authorised Steelcase dealer. We bring world-leading, research-driven furniture together with local expertise to create environments people love to be in.',
                'link_label' => '',
                'link_url'   => '',
                'align'      => 'left',
            ],
        ],
        'story' => [
            'data' => [
                'tagline'         => 'Our Story',
                'heading'         => 'Who We Are',
                'body'            => "Arki began with a simple belief: that the right environment changes how people feel, focus and work together. From our showrooms in Sydney and Brisbane, we partner with organisations and schools across Australia to plan, furnish and finish spaces that work as hard as the people in them."
                                   . "\n\nAs one of a select global network of Steelcase dealers, we connect our clients to decades of international research - and deliver it with the care and accountability of a local team.",
                'beliefs_heading' => 'Our beliefs',
                'image'           => 'uploads/demo/gal-1.jpg',
                'image_alt'       => 'Living room with a grey sofa and rattan pendant lights',
            ],
            'items' => [
                ['title' => 'People first',         'text' => 'Every decision starts with how people actually work, learn and connect.'],
                ['title' => 'Insight led',          'text' => 'We choose furniture proven by research, not just trend.'],
                ['title' => 'Built to last',        'text' => 'Considered, durable design that looks as good in ten years as it does today.'],
                ['title' => 'Here for the long term', 'text' => 'We stay involved from the first sketch to the final chair.'],
            ],
        ],
        'process' => [
            'data' => [
                'image'     => 'uploads/demo/proj-2.jpg',
                'image_alt' => 'Dark lounge with a sofa and round coffee table',
                'tagline'   => 'How We Work',
                'heading'   => "One Partner, From Idea\nTo Fit-Out",
                'body'      => 'We make creating a great space simple, guiding you through every stage so nothing is left to chance.',
            ],
            'items' => [
                ['icon' => 'discover', 'title' => 'Discover',          'text' => 'We listen first - to your people, your goals and the way you want to work.'],
                ['icon' => 'layers',   'title' => 'Design & specify',  'text' => 'We plan and specify the right layout, products and finishes for your space and budget.'],
                ['icon' => 'truck',    'title' => 'Deliver & install', 'text' => 'We manage delivery and installation so it lands on time and in place.'],
                ['icon' => 'support',  'title' => 'Support',           'text' => 'We’re here after handover, with warranty, care and advice for years to come.'],
            ],
        ],
        'brands' => [
            'copy_from' => 'home',   // the homepage logos, with these texts
            'data'      => [
                'heading' => "The Best,\nBrought Together",
                'body'    => "We bring together leading global brands - including Steelcase, Orangebox and Smith System - alongside trusted local Australian makers. It means we can shape the right solution for any space, rather than forcing a space to fit one range."
                           . "\n\nAs an authorised Steelcase dealer, we give our clients direct access to Steelcase’s global research, innovation and quality - backed locally by our own team.",
            ],
        ],
        'inspiration' => [
            'copy_from' => 'home',   // the homepage images, with these texts
            'data'      => [
                'tagline'      => 'Inspiration',
                'heading'      => "Design Backed\nBy Evidence",
                'body'         => 'The way we work is always changing - and the spaces we work in should keep up. Through Steelcase’s ongoing global research, we help clients make confident, future-focused decisions grounded in how people really behave, not guesswork.',
                'button_label' => 'Explore Our Insights',
                'note_heading' => 'Designing Responsibly',
                'note_text'    => 'We favour products designed to last and to be responsibly made, reducing waste and the need to replace. Longevity is our first act of sustainability - and we’ll always help clients choose options that tread more lightly.',
            ],
        ],
        'showrooms' => [
            'data' => [
                'style'      => 'light',
                'image'      => '',
                'image_alt'  => '',
                'heading'    => 'Come And See Us',
                'body'       => 'Our showrooms are open by appointment, so we can give you our full attention and have the right pieces ready to see and try.',
                'note_title' => '',
                'note_text'  => 'We deliver and install for clients Australia-wide, and beyond.',
            ],
            'items' => [
                ['city' => 'Sydney', 'address' => "Tenancy 02,\n13 Bowden Street,\nAlexandria NSW 2015", 'hours' => "Monday–Friday\n9 AM - 5 PM",
                 'map_address' => '13 Bowden Street, Alexandria NSW 2015, Australia', 'map_image' => '', 'button_label' => '', 'button_url' => ''],
                ['city' => 'Brisbane', 'address' => "29 Cairns Street,\nKangaroo Point QLD 4169", 'hours' => "Monday–Friday\n9 AM - 5 PM",
                 'map_address' => '29 Cairns Street, Kangaroo Point QLD 4169, Australia', 'map_image' => '', 'button_label' => '', 'button_url' => ''],
            ],
        ],
        'cta' => ['copy_from' => 'home'],
    ],
];
