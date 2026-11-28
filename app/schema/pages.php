<?php
/**
 * Content schema – the single source of truth for what the CMS can edit.
 *
 * pages → sections → fields (+ optional repeatable "items").
 * The admin builds its forms from this file and the front end renders
 * templates/sections/{section_key}.php for each active section of a page.
 *
 * Field types:
 *   text       single line
 *   multiline  short text where line breaks are kept (e.g. 2-line headings)
 *   textarea   longer text; blank lines become separate paragraphs
 *   url        link (relative path, https://, mailto:, tel:, #anchor)
 *   image      uploaded image (JPG, PNG, WebP, GIF) – must have a 'crop' spec
 *   select     choose from 'options'
 *
 * Image cropping (mandatory): every image field declares 'crop'. The admin
 * opens a cropper as soon as a file is chosen and only the cropped result is
 * uploaded; the server rejects images that don't match the crop shape.
 *   'crop' => ['w' => 840, 'h' => 1200]                 one fixed shape (output size in px)
 *   'crop' => [..., 'fit' => true]                      logos: may zoom out, transparent padding, saved as PNG
 *   'crop' => ['presets' => [['Label', w, h], ...]]     let the editor pick one of several shapes
 *
 * Adding a new inner page later = add a block here + a template per section.
 */

$link = ['type' => 'url', 'label' => 'Link URL', 'help' => 'e.g. /projects/ or https://… Leave blank to hide.'];

$schema = [

    /* =================================================================
     *  HOMEPAGE
     * ================================================================= */
    'home' => [
        'label'    => 'Homepage',
        'url'      => '',
        'sortable' => true,
        'sections' => [

            'hero' => [
                'label'       => 'Hero – Feature Cards',
                'description' => 'The three large image cards at the top of the page (Work / Learn / Store).',
                'fields'      => [],
                'items'       => [
                    'label'  => 'Card',
                    'title'  => 'title',
                    'thumb'  => 'image',
                    'fields' => [
                        'title'     => ['type' => 'text', 'label' => 'Title', 'required' => true],
                        'subtitle'  => ['type' => 'text', 'label' => 'Subtitle'],
                        'image'     => ['type' => 'image', 'label' => 'Background image', 'crop' => ['w' => 840, 'h' => 1200]],
                        'image_alt' => ['type' => 'text', 'label' => 'Image description (alt text)'],
                        'url'       => $link,
                    ],
                ],
            ],

            'intro' => [
                'label'       => 'Introduction',
                'description' => 'Centred tagline, main heading and introduction paragraphs.',
                'fields'      => [
                    'tagline'    => ['type' => 'text', 'label' => 'Tagline'],
                    'heading'    => ['type' => 'multiline', 'label' => 'Heading (main page heading – H1)'],
                    'body'       => ['type' => 'textarea', 'label' => 'Body text', 'help' => 'Leave a blank line between paragraphs.'],
                    'link_label' => ['type' => 'text', 'label' => 'Link label'],
                    'link_url'   => ['label' => 'Link URL'] + $link,
                ],
            ],

            'gallery' => [
                'label'       => 'Image Gallery',
                'description' => 'Mosaic of images. Layout repeats every 5 images: 2 wide on the first row, 3 on the second (narrow – wide – narrow).',
                'fields'      => [],
                'items'       => [
                    'label'  => 'Image',
                    'title'  => 'alt',
                    'thumb'  => 'image',
                    'fields' => [
                        'image' => ['type' => 'image', 'label' => 'Image', 'required' => true,
                            'help' => 'Pick the shape that matches where the image sits: images 1–2 are the large top row, image 3 and 5 the small squares, image 4 the wide middle of the second row.',
                            'crop' => ['presets' => [
                                ['Large – top row', 1600, 1040],
                                ['Small – square', 1000, 1000],
                                ['Wide – bottom middle', 1800, 860],
                            ]]],
                        'alt'   => ['type' => 'text', 'label' => 'Image description (alt text)'],
                        'url'   => $link,
                    ],
                ],
            ],

            'solutions' => [
                'label'       => 'Our Solutions',
                'description' => 'Grey band with outlined solution cards.',
                'fields'      => [
                    'tagline' => ['type' => 'text', 'label' => 'Tagline'],
                    'heading' => ['type' => 'multiline', 'label' => 'Heading'],
                    'body'    => ['type' => 'textarea', 'label' => 'Intro text'],
                ],
                'items'       => [
                    'label'  => 'Solution card',
                    'title'  => 'title',
                    'fields' => [
                        'title'      => ['type' => 'multiline', 'label' => 'Title', 'required' => true],
                        'text'       => ['type' => 'textarea', 'label' => 'Description'],
                        'link_label' => ['type' => 'text', 'label' => 'Link label', 'default' => 'Explore More'],
                        'url'        => $link,
                    ],
                ],
            ],

            'projects' => [
                'label'       => 'Projects Showcase',
                'description' => 'Intro text on the left with a scrolling row of project cards.',
                'fields'      => [
                    'tagline'      => ['type' => 'text', 'label' => 'Tagline'],
                    'heading'      => ['type' => 'multiline', 'label' => 'Heading'],
                    'body'         => ['type' => 'textarea', 'label' => 'Intro text'],
                    'button_label' => ['type' => 'text', 'label' => 'Button label'],
                    'button_url'   => ['label' => 'Button URL'] + $link,
                ],
                'items'       => [
                    'label'  => 'Project',
                    'title'  => 'title',
                    'thumb'  => 'image',
                    'fields' => [
                        'image'      => ['type' => 'image', 'label' => 'Image', 'crop' => ['w' => 828, 'h' => 1110]],
                        'image_alt'  => ['type' => 'text', 'label' => 'Image description (alt text)'],
                        'title'      => ['type' => 'text', 'label' => 'Project name', 'required' => true],
                        'meta'       => ['type' => 'text', 'label' => 'Location / category line', 'help' => 'e.g. Melbourne, VIC · Workplace'],
                        'link_label' => ['type' => 'text', 'label' => 'Link label', 'default' => 'View Products'],
                        'url'        => $link,
                    ],
                ],
            ],

            'brands' => [
                'label'       => 'Brand Partners',
                'description' => 'Centred heading with a logo slider.',
                'fields'      => [
                    'heading' => ['type' => 'multiline', 'label' => 'Heading'],
                    'body'    => ['type' => 'textarea', 'label' => 'Intro text'],
                ],
                'items'       => [
                    'label'  => 'Brand logo',
                    'title'  => 'name',
                    'thumb'  => 'logo',
                    'fields' => [
                        'name' => ['type' => 'text', 'label' => 'Brand name', 'required' => true],
                        'logo' => ['type' => 'image', 'label' => 'Logo', 'help' => 'Transparent PNG works best. Zoom out to leave space around the logo.', 'crop' => ['w' => 600, 'h' => 300, 'fit' => true]],
                        'url'  => $link,
                    ],
                ],
            ],

            'inspiration' => [
                'label'       => 'Inspiration',
                'description' => 'Heading row with a button, followed by large images.',
                'fields'      => [
                    'tagline'      => ['type' => 'text', 'label' => 'Tagline'],
                    'heading'      => ['type' => 'multiline', 'label' => 'Heading'],
                    'body'         => ['type' => 'textarea', 'label' => 'Intro text'],
                    'button_label' => ['type' => 'text', 'label' => 'Button label'],
                    'button_url'   => ['label' => 'Button URL'] + $link,
                ],
                'items'       => [
                    'label'  => 'Inspiration image',
                    'title'  => 'alt',
                    'thumb'  => 'image',
                    'fields' => [
                        'image' => ['type' => 'image', 'label' => 'Image', 'required' => true, 'crop' => ['w' => 1600, 'h' => 1013]],
                        'alt'   => ['type' => 'text', 'label' => 'Image description (alt text)'],
                        'url'   => $link,
                    ],
                ],
            ],

            'testimonials' => [
                'label'       => 'Testimonials',
                'description' => 'Beige panel with a scrolling row of customer reviews.',
                'fields'      => [
                    'heading' => ['type' => 'multiline', 'label' => 'Heading'],
                ],
                'items'       => [
                    'label'  => 'Testimonial',
                    'title'  => 'name',
                    'thumb'  => 'photo',
                    'fields' => [
                        'name'  => ['type' => 'text', 'label' => 'Customer name', 'required' => true],
                        'role'  => ['type' => 'text', 'label' => 'Company / role'],
                        'photo' => ['type' => 'image', 'label' => 'Photo', 'crop' => ['w' => 400, 'h' => 400]],
                        'quote' => ['type' => 'textarea', 'label' => 'Testimonial', 'required' => true],
                    ],
                ],
            ],

            'cta' => [
                'label'       => 'Call To Action',
                'description' => 'Full-width image with an overlay card.',
                'fields'      => [
                    'image'           => ['type' => 'image', 'label' => 'Background image', 'help' => 'Keep the main subject on the left – the text card covers the right side.', 'crop' => ['w' => 2400, 'h' => 1125]],
                    'image_alt'       => ['type' => 'text', 'label' => 'Image description (alt text)'],
                    'tagline'         => ['type' => 'text', 'label' => 'Tagline'],
                    'heading'         => ['type' => 'multiline', 'label' => 'Heading'],
                    'body'            => ['type' => 'textarea', 'label' => 'Body text'],
                    'primary_label'   => ['type' => 'text', 'label' => 'Primary button label'],
                    'primary_url'     => ['label' => 'Primary button URL'] + $link,
                    'secondary_label' => ['type' => 'text', 'label' => 'Secondary link label'],
                    'secondary_url'   => ['label' => 'Secondary link URL'] + $link,
                ],
            ],
        ],
    ],

    /* =================================================================
     *  GLOBAL (shared by every page)
     * ================================================================= */
    'global' => [
        'label'    => 'Header & Footer',
        'sortable' => false,
        'sections' => [

            'header' => [
                'label'       => 'Header',
                'description' => 'Logo and the utility links at the top right.',
                'fields'      => [
                    'logo'        => ['type' => 'image', 'label' => 'Logo', 'help' => 'Shown at the top left of every page. Transparent PNG works best.', 'crop' => ['w' => 1060, 'h' => 200, 'fit' => true]],
                    'logo_light'  => ['type' => 'image', 'label' => 'Logo – white version', 'help' => 'Used over dark banner images (e.g. the Work page). Leave empty to show the normal logo in white automatically.', 'crop' => ['w' => 1060, 'h' => 200, 'fit' => true]],
                    'logo_alt'    => ['type' => 'text', 'label' => 'Logo alt text'],
                    'login_label' => ['type' => 'text', 'label' => 'Login button label'],
                    'login_url'   => ['type' => 'url', 'label' => 'Login URL'],
                    'search_url'  => ['type' => 'url', 'label' => 'Search URL', 'help' => 'Leave blank to hide the search icon.'],
                    'cart_url'    => ['type' => 'url', 'label' => 'Cart URL', 'help' => 'Leave blank to hide the cart icon.'],
                ],
            ],

            'navigation' => [
                'label'       => 'Main Navigation',
                'description' => 'Menu links in the header.',
                'fields'      => [],
                'items'       => [
                    'label'  => 'Menu link',
                    'title'  => 'label',
                    'fields' => [
                        'label'   => ['type' => 'text', 'label' => 'Label', 'required' => true],
                        'url'     => ['type' => 'url', 'label' => 'URL'],
                        'mega'    => ['type' => 'select', 'label' => 'Dropdown type', 'options' => [
                            ''         => 'Simple dropdown (the links below)',
                            'products' => 'Product categories – Work / Learn / Store (from Products → Lists)',
                        ], 'help' => 'Product categories shows every Work, Learn and Store category automatically.'],
                        'submenu' => ['type' => 'textarea', 'label' => 'Dropdown links (optional)', 'help' => 'One per line in the format:  Label | /url/  (not used for the product categories dropdown)'],
                    ],
                ],
            ],

            'footer' => [
                'label'       => 'Footer',
                'description' => 'Footer brand columns, column titles, payments and legal links.',
                'fields'      => [
                    'brand_logo'     => ['type' => 'image', 'label' => 'Column 1 – logo', 'crop' => ['w' => 600, 'h' => 300, 'fit' => true]],
                    'brand_text'     => ['type' => 'textarea', 'label' => 'Column 1 – text'],
                    'dealer_logo'    => ['type' => 'image', 'label' => 'Column 2 – logo', 'crop' => ['w' => 800, 'h' => 200, 'fit' => true]],
                    'dealer_title'   => ['type' => 'text', 'label' => 'Column 2 – title'],
                    'dealer_text'    => ['type' => 'textarea', 'label' => 'Column 2 – text'],
                    'links_title'    => ['type' => 'text', 'label' => 'Column 3 – title'],
                    'products_title' => ['type' => 'text', 'label' => 'Column 4 – title'],
                    'payments_title' => ['type' => 'text', 'label' => 'Column 5 – title'],
                    'payments_text'  => ['type' => 'textarea', 'label' => 'Column 5 – text'],
                    'payments_image' => ['type' => 'image', 'label' => 'Column 5 – payment logos image', 'crop' => ['w' => 540, 'h' => 200, 'fit' => true]],
                    'copyright'      => ['type' => 'text', 'label' => 'Copyright text', 'help' => 'Use {year} for the current year.'],
                    'terms_label'    => ['type' => 'text', 'label' => 'Terms link label'],
                    'terms_url'      => ['type' => 'url', 'label' => 'Terms link URL'],
                    'privacy_label'  => ['type' => 'text', 'label' => 'Privacy link label'],
                    'privacy_url'    => ['type' => 'url', 'label' => 'Privacy link URL'],
                ],
            ],

            'footer_links' => [
                'label'       => 'Footer Link Lists',
                'description' => 'Links shown in footer columns 3 and 4.',
                'fields'      => [],
                'items'       => [
                    'label'  => 'Footer link',
                    'title'  => 'label',
                    'fields' => [
                        'column' => ['type' => 'select', 'label' => 'Column', 'options' => ['links' => 'Column 3 (Link)', 'products' => 'Column 4 (Product)']],
                        'label'  => ['type' => 'text', 'label' => 'Label', 'required' => true],
                        'url'    => ['type' => 'url', 'label' => 'URL'],
                    ],
                ],
            ],

            'footer_contacts' => [
                'label'       => 'Footer Contact & Social Buttons',
                'description' => 'Pill buttons above the copyright bar.',
                'fields'      => [],
                'items'       => [
                    'label'  => 'Button',
                    'title'  => 'label',
                    'fields' => [
                        'icon'  => ['type' => 'select', 'label' => 'Icon', 'options' => [
                            'location' => 'Location', 'phone' => 'Phone', 'email' => 'Email',
                            'facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'link' => 'Generic link',
                        ]],
                        'label' => ['type' => 'text', 'label' => 'Label', 'required' => true],
                        'url'   => ['type' => 'url', 'label' => 'URL', 'help' => 'e.g. tel:+61300000000, mailto:hello@…, https://maps…'],
                    ],
                ],
            ],
        ],
    ],
];

/* =================================================================
 *  INNER PAGES – Work (/work/), Learn (/learn/), Store (/store/) …
 *  Built from shared section definitions; each page's content is
 *  separate. First-visit content comes from app/schema/seeds.php.
 * ================================================================= */
$home = $schema['home']['sections'];

$bannerSection = [
    'label'       => 'Banner',
    'description' => 'Full-width image at the top with the page title. The header sits on top of it in white.',
    'fields'      => [
        'image'      => ['type' => 'image', 'label' => 'Background image', 'help' => 'A dark overlay is added so the white text stays readable.', 'crop' => ['w' => 2400, 'h' => 870]],
        'image_alt'  => ['type' => 'text', 'label' => 'Image description (alt text)'],
        'tagline'    => ['type' => 'text', 'label' => 'Tagline'],
        'heading'    => ['type' => 'multiline', 'label' => 'Page title (H1)', 'required' => true],
        'body'       => ['type' => 'textarea', 'label' => 'Text'],
        'link_label' => ['type' => 'text', 'label' => 'Link label'],
        'link_url'   => ['label' => 'Link URL'] + $link,
    ],
];

$introSection = ['description' => 'Centred tagline, heading and introduction paragraphs below the banner.'] + $home['intro'];

$categoriesSection = [
    'label'       => 'Product Categories',
    'description' => 'Row of white product cards with a name under each. With a heading, the link sits top right; without, it sits centred below.',
    'fields'      => [
        'heading'    => ['type' => 'text', 'label' => 'Heading (optional)', 'help' => 'e.g. Our Categories'],
        'link_label' => ['type' => 'text', 'label' => 'Link label', 'help' => 'e.g. Explore All Work Products'],
        'link_url'   => ['label' => 'Link URL'] + $link,
    ],
    'items'       => [
        'label'  => 'Category',
        'title'  => 'title',
        'thumb'  => 'image',
        'fields' => [
            'title'     => ['type' => 'text', 'label' => 'Name', 'required' => true],
            'image'     => ['type' => 'image', 'label' => 'Product image', 'help' => 'Product on a white or transparent background. Zoom out to leave space around it.', 'crop' => ['w' => 740, 'h' => 1000, 'fit' => true]],
            'image_alt' => ['type' => 'text', 'label' => 'Image description (alt text)'],
            'url'       => $link,
        ],
    ],
];

$productsSection = [
    'label'       => 'Featured Products',
    'description' => 'Heading with a link top right, then product cards loaded from Products (4 per row). Manage the products themselves under Products.',
    'fields'      => [
        'heading'    => ['type' => 'text', 'label' => 'Heading', 'help' => 'e.g. Featured Products'],
        'link_label' => ['type' => 'text', 'label' => 'Link label', 'help' => 'e.g. View All Products'],
        'link_url'   => ['label' => 'Link URL'] + $link,
        'source'     => ['type' => 'select', 'label' => 'Which products', 'options' => [
            'featured'   => 'Featured products',
            'new'        => 'New arrivals',
            'bestseller' => 'Best sellers',
            'newest'     => 'Newest products',
        ]],
        'limit'      => ['type' => 'select', 'label' => 'How many', 'options' => ['4' => '4 (one row)', '8' => '8 (two rows)', '12' => '12 (three rows)']],
    ],
];

$ctaFields = $home['cta']['fields'];

/* Product listing + product detail pages: banners, call to action, headings */
$schema['shop'] = [
    'label'    => 'Storefront Settings',
    'nav'      => false,           // shown under Products in the CMS menu
    'sortable' => false,
    'sections' => [
        'banner_work'  => ['tab' => 'Work Banner', 'label' => 'Work – Products Banner',  'description' => 'Banner at the top of the Work product listing (/work/products/). Hide it with the Visible switch.'] + ['fields' => [
            'image'        => ['type' => 'image', 'label' => 'Background image', 'help' => 'The text sits on the left – keep that side calm.', 'crop' => ['w' => 2400, 'h' => 600]],
            'image_alt'    => ['type' => 'text', 'label' => 'Image description (alt text)'],
            'tagline'      => ['type' => 'text', 'label' => 'Subtitle'],
            'heading'      => ['type' => 'multiline', 'label' => 'Main heading (H1)'],
            'body'         => ['type' => 'textarea', 'label' => 'Description'],
            'button_label' => ['type' => 'text', 'label' => 'Button text'],
            'button_url'   => ['label' => 'Button link'] + $link,
        ]],
    ],
];
$schema['shop']['sections']['banner_learn'] = ['tab' => 'Learn Banner', 'label' => 'Learn – Products Banner', 'description' => 'Banner at the top of the Learn product listing (/learn/products/).'] + $schema['shop']['sections']['banner_work'];
$schema['shop']['sections']['banner_store'] = ['tab' => 'Store Banner', 'label' => 'Store – Products Banner', 'description' => 'Banner at the top of the Store product listing (/store/products/).'] + $schema['shop']['sections']['banner_work'];
$schema['shop']['sections']['listing_cta']  = ['tab' => 'Listing CTA', 'label' => 'Product Listing – Call To Action', 'description' => 'Image banner below the product grid on all three listing pages.', 'fields' => $ctaFields];
$schema['shop']['sections']['product_cta']  = ['tab' => 'Product Page CTA', 'label' => 'Product Page – Call To Action', 'description' => 'Default banner on every product page. A product can override it under Inventory & Visibility.', 'fields' => $ctaFields];
$schema['shop']['sections']['product_page'] = [
    'tab'         => 'Product Page Texts',
    'label'       => 'Product Page – Headings',
    'description' => 'Texts used on every product page.',
    'fields'      => [
        'eyebrow'            => ['type' => 'text', 'label' => 'Small line above the product name', 'help' => 'e.g. More Than A Furniture Store'],
        'agent_label'        => ['type' => 'text', 'label' => 'Agent / dealer price text', 'help' => 'Shown instead of the agent price, e.g. Login To View'],
        'family_heading'     => ['type' => 'text', 'label' => 'Family of products – heading'],
        'related_heading'    => ['type' => 'text', 'label' => 'Related products – heading'],
        'related_text'       => ['type' => 'textarea', 'label' => 'Related products – text'],
        'related_link_label' => ['type' => 'text', 'label' => 'Related products – link label'],
        'related_link_url'   => ['label' => 'Related products – link URL'] + $link,
    ],
];

/** Work / Learn layout: banner, intro, categories, then the homepage section types. */
$innerPage = static fn(string $label, string $url): array => [
    'label'    => $label,
    'url'      => $url,
    'sortable' => true,
    'sections' => [
        'banner'      => $bannerSection,
        'intro'       => $introSection,
        'categories'  => $categoriesSection,
        'solutions'   => $home['solutions'],
        'projects'    => $home['projects'],
        'brands'      => $home['brands'],
        'inspiration' => $home['inspiration'],
        'cta'         => $home['cta'],
    ],
];

$schema['work']  = $innerPage('Work Page', 'work/');
$schema['learn'] = $innerPage('Learn Page', 'learn/');

/* Work and Learn pages: the category cards are built automatically from Products – one card
   per category of that storefront, with a random image of one of its products (no manual cards). */
$autoCategories = static fn(string $name): array => [
    'label'       => 'Product Categories (automatic)',
    'description' => "One card per $name category (Products → Lists → $name Categories), each showing a random image of a published $name product in that category – a new mix on every visit. Categories without products are left out. Only the texts below are edited here.",
    'auto'        => true,
    'fields'      => [
        'heading'    => ['type' => 'text', 'label' => 'Heading (optional)', 'help' => 'e.g. Our Categories'],
        'link_label' => ['type' => 'text', 'label' => 'Link label', 'help' => "e.g. Explore All $name Products"],
        'link_url'   => ['label' => 'Link URL'] + $link,
    ],
];
$schema['work']['sections']['categories']  = $autoCategories('Work');
$schema['learn']['sections']['categories'] = $autoCategories('Learn');

/** Store layout: banner, intro, categories (with heading), featured products, call to action. */
$schema['store'] = [
    'label'    => 'Store Page',
    'url'      => 'store/',
    'sortable' => true,
    'sections' => [
        'banner'     => $bannerSection,
        'intro'      => $introSection,
        'categories' => $categoriesSection,
        'products'   => $productsSection,
        'cta'        => $home['cta'],
    ],
];

/* =================================================================
 *  CONTACT US (/contact/)
 *  Enquiries from the form are saved and listed under CMS → Enquiries.
 * ================================================================= */
$schema['contact'] = [
    'label'    => 'Contact Page',
    'url'      => 'contact/',
    'sortable' => true,
    'sections' => [
        'contact_intro' => [
            'tab'         => 'Introduction & Form',
            'label'       => 'Introduction, Contact Details & Form',
            'description' => 'Big “Let’s Talk.” heading and contact buttons on the left, the enquiry form on the right.',
            'fields'      => [
                'tagline'          => ['type' => 'text', 'label' => 'Tagline'],
                'heading'          => ['type' => 'multiline', 'label' => 'Main heading (H1)'],
                'body'             => ['type' => 'textarea', 'label' => 'Introduction text'],
                'direct_heading'   => ['type' => 'text', 'label' => 'Contact details – heading', 'help' => 'e.g. Prefer To Reach Us Directly?'],
                'direct_text'      => ['type' => 'textarea', 'label' => 'Contact details – text'],
                'form_heading'     => ['type' => 'text', 'label' => 'Form – heading'],
                'form_text'        => ['type' => 'textarea', 'label' => 'Form – text'],
                'topics'           => ['type' => 'textarea', 'label' => 'Form – “I’m enquiring about…” options', 'help' => 'One option per line. Leave empty to hide the dropdown.'],
                'newsletter_label' => ['type' => 'text', 'label' => 'Form – newsletter tick box text', 'help' => 'Leave empty to hide the tick box.'],
                'button_label'     => ['type' => 'text', 'label' => 'Form – button text'],
                'success_message'  => ['type' => 'textarea', 'label' => 'Message shown after sending'],
                'notify_email'     => ['type' => 'text', 'label' => 'Send a copy of each enquiry to', 'help' => 'Email address(es), separated by commas. Leave empty to only keep enquiries in the CMS (Enquiries).'],
            ],
            'items'       => [
                'label'  => 'Contact button',
                'title'  => 'value',
                'fields' => [
                    'icon'  => ['type' => 'select', 'label' => 'Icon', 'options' => ['email' => 'Email', 'phone' => 'Phone', 'location' => 'Location', 'link' => 'Link']],
                    'label' => ['type' => 'text', 'label' => 'Small label', 'help' => 'e.g. Email, Phone'],
                    'value' => ['type' => 'text', 'label' => 'Shown text', 'required' => true, 'help' => 'e.g. info@arkienvironments.au'],
                    'url'   => ['type' => 'url', 'label' => 'Link', 'help' => 'e.g. mailto:info@… or tel:+61737066963'],
                    'width' => ['type' => 'select', 'label' => 'Width', 'options' => ['full' => 'Full width', 'half' => 'Half width (two side by side)']],
                ],
            ],
        ],

        'showrooms' => [
            'tab'         => 'Showrooms',
            'label'       => 'Showrooms – Come And See Us',
            'description' => 'Dark image band with a card per showroom (address, hours, map) and a note below.',
            'fields'      => [
                'image'      => ['type' => 'image', 'label' => 'Background image', 'help' => 'Shown dark behind the text.', 'crop' => ['w' => 2400, 'h' => 1130]],
                'image_alt'  => ['type' => 'text', 'label' => 'Image description (alt text)'],
                'heading'    => ['type' => 'multiline', 'label' => 'Heading'],
                'body'       => ['type' => 'textarea', 'label' => 'Text'],
                'note_title' => ['type' => 'text', 'label' => 'Note – title', 'help' => 'e.g. Not nearby?'],
                'note_text'  => ['type' => 'textarea', 'label' => 'Note – text'],
            ],
            'items'       => [
                'label'  => 'Showroom',
                'title'  => 'city',
                'fields' => [
                    'city'         => ['type' => 'text', 'label' => 'City / name', 'required' => true],
                    'address'      => ['type' => 'multiline', 'label' => 'Address', 'help' => 'Press Enter for a new line.'],
                    'hours'        => ['type' => 'multiline', 'label' => 'Opening hours'],
                    'map_address'  => ['type' => 'text', 'label' => 'Map location', 'help' => 'Address to show on the Google map, e.g. 13 Bowden Street, Alexandria NSW 2015. Leave empty to show the image below instead.'],
                    'map_image'    => ['type' => 'image', 'label' => 'Map image (optional)', 'help' => 'Only used when no map location is given.', 'crop' => ['w' => 1180, 'h' => 820]],
                    'button_label' => ['type' => 'text', 'label' => 'Button text', 'default' => 'Book An Appointment'],
                    'button_url'   => ['label' => 'Button link'] + $link,
                ],
            ],
        ],
    ],
];

/* =================================================================
 *  ABOUT US (/about/)
 * ================================================================= */
$aboutBanner = $bannerSection;
$aboutBanner['fields']['align'] = ['type' => 'select', 'label' => 'Text position', 'options' => ['left' => 'Left', 'center' => 'Centred']];

$aboutInspiration = $home['inspiration'];
$aboutInspiration['description'] = 'Heading, text and button, two images, then an optional grey box with a short statement.';
$aboutInspiration['fields']['note_heading'] = ['type' => 'text', 'label' => 'Grey box – heading', 'help' => 'e.g. Designing Responsibly. Leave empty to hide the box.'];
$aboutInspiration['fields']['note_text']    = ['type' => 'textarea', 'label' => 'Grey box – text'];

$aboutShowrooms = $schema['contact']['sections']['showrooms'];
$aboutShowrooms['description'] = 'Showroom cards (address, hours, map) with a short note below.';
$aboutShowrooms['fields'] = ['style' => ['type' => 'select', 'label' => 'Style', 'options' => ['light' => 'Light – no background image', 'dark' => 'Dark image band (like the Contact page)']]] + $aboutShowrooms['fields'];
$aboutShowrooms['fields']['image']['help'] = 'Only used with the dark style.';

$schema['about'] = [
    'label'    => 'About Page',
    'url'      => 'about/',
    'sortable' => true,
    'sections' => [
        'banner' => $aboutBanner,

        'story' => [
            'tab'         => 'Who We Are',
            'label'       => 'Our Story – Who We Are',
            'description' => 'Text with a list of beliefs on the left, a large rounded image on the right.',
            'fields'      => [
                'tagline'         => ['type' => 'text', 'label' => 'Tagline', 'help' => 'e.g. Our Story'],
                'heading'         => ['type' => 'multiline', 'label' => 'Heading'],
                'body'            => ['type' => 'textarea', 'label' => 'Text', 'help' => 'Leave a blank line between paragraphs.'],
                'beliefs_heading' => ['type' => 'text', 'label' => 'List heading', 'help' => 'e.g. Our beliefs'],
                'image'           => ['type' => 'image', 'label' => 'Image', 'crop' => ['w' => 1200, 'h' => 1220]],
                'image_alt'       => ['type' => 'text', 'label' => 'Image description (alt text)'],
            ],
            'items'       => [
                'label'  => 'Belief',
                'title'  => 'title',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Title', 'required' => true],
                    'text'  => ['type' => 'text', 'label' => 'Short text'],
                ],
            ],
        ],

        'process' => [
            'tab'         => 'How We Work',
            'label'       => 'How We Work – Steps',
            'description' => 'Dark image band with the heading on the left and white step cards (2 × 2) on the right.',
            'fields'      => [
                'image'     => ['type' => 'image', 'label' => 'Background image', 'help' => 'Shown dark behind the heading.', 'crop' => ['w' => 2400, 'h' => 1100]],
                'image_alt' => ['type' => 'text', 'label' => 'Image description (alt text)'],
                'tagline'   => ['type' => 'text', 'label' => 'Tagline', 'help' => 'e.g. How We Work'],
                'heading'   => ['type' => 'multiline', 'label' => 'Heading'],
                'body'      => ['type' => 'textarea', 'label' => 'Text'],
            ],
            'items'       => [
                'label'  => 'Step',
                'title'  => 'title',
                'fields' => [
                    'icon'  => ['type' => 'select', 'label' => 'Icon', 'options' => ['discover' => 'Magnifier (discover)', 'layers' => 'Layers (design)', 'truck' => 'Truck (delivery)', 'support' => 'Headset (support)', 'check-circle' => 'Tick']],
                    'title' => ['type' => 'text', 'label' => 'Title', 'required' => true],
                    'text'  => ['type' => 'textarea', 'label' => 'Text'],
                ],
            ],
        ],

        'brands'      => $home['brands'],
        'inspiration' => $aboutInspiration,
        'showrooms'   => $aboutShowrooms,
        'cta'         => $home['cta'],
    ],
];

return $schema;
