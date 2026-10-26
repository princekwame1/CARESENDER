<?php

/*
| Editable website content, organised by page.
| fields: key => [page, label, type (text|textarea|rich|image), default, image alt default]
| 'rich' fields use the Quill editor and may contain simple formatting (bold, italic, links, lists).
| Image defaults are photo slugs from config/caresenda.php ('photos').
| Values saved in the dashboard (site_contents table) override these defaults.
*/

return [
    'pages' => [
        'general' => ['General', 'Announcement banner and footer'],
        'home' => ['Home', 'Hero, steps, tiles and banners'],
        'about' => ['About us', 'Story, values and call to action'],
        'verify' => ['How we check', 'How verification works'],
        'contact' => ['Contact us', 'Contact details and form'],
        'search' => ['Search', 'Search results page'],
    ],

    'fields' => [
        // General
        'site.notice' => ['general', 'Announcement across the top of every page', 'text', ''],
        'footer.tagline' => ['general', 'Footer tagline', 'rich', 'Helping you find the right care in Accra and Tema, and know it’s there before you go.'],
        'footer.note' => ['general', 'Footer “Good to know” note', 'rich', 'Our team phones facilities to check who’s available and when. Distances are estimates, and we don’t list every facility in Accra and Tema yet. We’re still adding more.'],

        // Home: hero
        'home.hero_label' => ['home', 'Hero · small label above the headline', 'text', 'Accra & Tema · checked by phone'],
        'home.hero_title' => ['home', 'Hero · headline', 'textarea', 'Looking for a particular specialist or need a medical test?'],
        'home.hero_text' => ['home', 'Hero · intro paragraph', 'rich', '<p><strong>Find out <em>where and when</em> specialists and medical tests are available.</strong></p><p>CareSenda’s service is to check availability for you. Our team contacts facilities in Accra and Tema regularly to confirm which specialists and tests are available, their days and times, and any service interruptions. We publish those updates so you can plan your visit.</p>'],
        'home.hero_image' => ['home', 'Hero · photo', 'image', 'doctor-consultation', 'A doctor talking with a patient about her care'],
        // Home: steps
        'home.steps_label' => ['home', 'Steps · small label', 'text', 'How it works'],
        'home.steps_title' => ['home', 'Steps · heading', 'text', 'From search to appointment in four steps'],
        'home.step1_title' => ['home', 'Step 1 · title', 'text', 'Tell us what you need'],
        'home.step1_text' => ['home', 'Step 1 · text', 'rich', 'Type a specialist, a test or a place. Short forms like FBC or LFT work too.'],
        'home.step2_title' => ['home', 'Step 2 · title', 'text', 'See what’s nearby'],
        'home.step2_text' => ['home', 'Step 2 · text', 'rich', 'Share your location and we’ll list the closest places first.'],
        'home.step3_title' => ['home', 'Step 3 · title', 'text', 'Check before you go'],
        'home.step3_text' => ['home', 'Step 3 · text', 'rich', 'See which days they offer it and when we last confirmed it with them.'],
        'home.step4_title' => ['home', 'Step 4 · title', 'text', 'Call or head over'],
        'home.step4_text' => ['home', 'Step 4 · text', 'rich', 'Call to book, send a WhatsApp message, or open directions in Maps.'],
        // Home: tiles
        'home.tiles_label' => ['home', 'Tiles · small label', 'text', 'Where to start'],
        'home.tiles_title' => ['home', 'Tiles · heading', 'text', 'What are you looking for?'],
        'home.tiles_text' => ['home', 'Tiles · intro', 'rich', 'Pick a starting point. Every listing tells you where the service is, how to reach them, and whether we’ve confirmed it yet.'],
        'home.tile_tests_title' => ['home', 'Tests tile · title', 'text', 'Tests and scans'],
        'home.tile_tests_text' => ['home', 'Tests tile · text', 'rich', 'Blood and urine tests, X-rays, ultrasound, CT and MRI, and which labs can do them.'],
        'home.tile_tests_image' => ['home', 'Tests tile · photo', 'image', 'doctor-child-care', ''],
        'home.tile_specialists_title' => ['home', 'Specialists tile · title', 'text', 'Specialists'],
        'home.tile_specialists_text' => ['home', 'Specialists tile · text', 'rich', 'Gynaecologists, paediatricians, dentists, ENT and more.'],
        'home.tile_specialists_image' => ['home', 'Specialists tile · photo', 'image', 'eye-specialist', 'An eye specialist examining a patient’s eyes'],
        'home.tile_facilities_title' => ['home', 'Facilities tile · title', 'text', 'Health facilities'],
        'home.tile_facilities_text' => ['home', 'Facilities tile · text', 'rich', 'Hospitals, clinics, labs and polyclinics around you.'],
        'home.tile_facilities_image' => ['home', 'Facilities tile · photo', 'image', 'clinic-reception', 'Reception desk and waiting area at a health centre'],
        // Home: trust + closing
        'home.trust_label' => ['home', '“Why you can trust it” · small label', 'text', 'Why you can trust it'],
        'home.trust_title' => ['home', '“Why you can trust it” · heading', 'text', 'We call, so you don’t have to'],
        'home.trust_text' => ['home', '“Why you can trust it” · text', 'rich', 'Online listings go out of date fast. Doctors move, machines break and clinic days change. So our team phones each place to ask who’s working there, which days they see patients, and whether a test can actually be done.'],
        'home.trust_image' => ['home', '“Why you can trust it” · photo', 'image', 'health-fair-ghana', 'A nurse checks a man’s blood pressure at a health fair in Ghana'],
        'home.cta_label' => ['home', 'Closing banner · small label', 'text', 'Near you'],
        'home.cta_title' => ['home', 'Closing banner · heading', 'text', 'Care may be closer than you think.'],
        'home.cta_text' => ['home', 'Closing banner · text', 'rich', 'Turn on your location and we’ll show the nearest specialists, tests and facilities first.'],

        // About
        'about.title' => ['about', 'Headline', 'text', 'Making it easier to find care in Ghana'],
        'about.intro' => ['about', 'Introduction', 'rich', "Finding the right specialist or test often means calling around, travelling across town, and sometimes arriving to find the doctor isn’t in or the machine is down. CareSenda exists to fix that.\n\nWe bring together information on hospitals, clinics and labs in Accra and Tema, and our team phones facilities to confirm what’s actually available, so you can plan your visit with confidence."],
        'about.image' => ['about', 'Photo', 'image', 'health-fair-ghana', 'A nurse checks a man’s blood pressure at a health fair in Ghana'],
        'about.card1_title' => ['about', 'Card 1 · title', 'text', 'What we do'],
        'about.card1_text' => ['about', 'Card 1 · text', 'rich', 'We list specialists, diagnostic tests and health facilities in one place, sorted by how close they are to you.'],
        'about.card2_title' => ['about', 'Card 2 · title', 'text', 'How we keep it accurate'],
        'about.card2_text' => ['about', 'Card 2 · text', 'rich', 'Our team calls facilities to check who’s working, which days they see patients, and whether tests can be done.'],
        'about.card3_title' => ['about', 'Card 3 · title', 'text', 'What we believe'],
        'about.card3_text' => ['about', 'Card 3 · text', 'rich', 'Nobody should lose a day, or money for transport, on a trip to a clinic that can’t help them that day.'],
        'about.cta_title' => ['about', 'Call to action · heading', 'text', 'Run a clinic, hospital or lab?'],
        'about.cta_text' => ['about', 'Call to action · text', 'rich', 'Tell us about your services and opening days so patients can find you, or let us know if something in your listing is out of date.'],

        // How we check
        'verify.title' => ['verify', 'Headline', 'text', 'We check, so you don’t waste a trip'],
        'verify.intro' => ['verify', 'Intro paragraph', 'rich', 'Our team calls facilities directly. We ask whether the specialist still works there, which days and hours they see patients, whether a test can be done, and whether anything is broken or closed.'],
        'verify.image' => ['verify', 'Photo', 'image', 'nurse-on-duty', 'A nurse at work on a hospital ward in Ghana'],
        'verify.card1_title' => ['verify', 'Card 1 · title', 'text', 'Specialists'],
        'verify.card1_text' => ['verify', 'Card 1 · text', 'rich', 'Are they still working there? Which days and hours? What does a consultation cost?'],
        'verify.card2_title' => ['verify', 'Card 2 · title', 'text', 'Diagnostic tests'],
        'verify.card2_text' => ['verify', 'Card 2 · text', 'rich', 'Can they do the test right now? Is the machine working? What does it cost?'],
        'verify.card3_title' => ['verify', 'Card 3 · title', 'text', 'Interruptions'],
        'verify.card3_text' => ['verify', 'Card 3 · text', 'rich', 'A broken machine, a doctor on leave, or a temporary closure that affects a service.'],
        'verify.card4_title' => ['verify', 'Card 4 · title', 'text', 'When we last checked'],
        'verify.card4_text' => ['verify', 'Card 4 · text', 'rich', 'Every confirmed service shows the date we last spoke to them.'],
        'verify.labels_title' => ['verify', 'Labels section · heading', 'text', 'What the labels mean'],
        'verify.label_verified' => ['verify', '“Checked by our team” explanation', 'rich', 'We phoned the facility and confirmed the details on the date shown.'],
        'verify.label_confirmed' => ['verify', '“Confirmed by CareSenda” explanation', 'rich', 'Our management confirmed the facility offers this test (recorded 22 September 2026). We still need to check prices and hours.'],
        'verify.label_checklist' => ['verify', '“Waiting for a phone check” explanation', 'rich', 'A routine test most labs offer, but we haven’t phoned this lab to confirm it yet.'],
        'verify.label_pending' => ['verify', '“Not checked yet” explanation', 'rich', 'It’s in a public register or an insurer’s list, but we haven’t checked it ourselves yet.'],
        'verify.distance_title' => ['verify', 'Distances note · heading', 'text', 'A note on distances'],
        'verify.distance_text' => ['verify', 'Distances note · text', 'rich', 'We place each facility at the centre of its neighbourhood, so distances are rough. Use Directions to get to the exact address.'],

        // Contact
        'contact.title' => ['contact', 'Headline', 'text', 'We’d love to hear from you'],
        'contact.intro' => ['contact', 'Intro paragraph', 'rich', 'Spotted something wrong in a listing? Run a clinic or lab and want it added? Or just have a question? Send us a message and someone from our team will get back to you.'],
        'contact.phone' => ['contact', 'Phone number', 'text', ''],
        'contact.whatsapp' => ['contact', 'WhatsApp number', 'text', ''],
        'contact.email' => ['contact', 'Email address', 'text', ''],
        'contact.hours' => ['contact', 'Office hours', 'text', ''],
        'contact.image' => ['contact', 'Photo', 'image', 'hero-concept', 'A woman getting in touch on her phone'],
        'contact.image_title' => ['contact', 'Photo label · title', 'text', 'Real people read every message'],
        'contact.image_text' => ['contact', 'Photo label · text', 'text', 'Our team checks the inbox regularly'],
        'contact.emergency_title' => ['contact', 'Emergency note · heading', 'text', 'Need medical help right now?'],
        'contact.emergency_text' => ['contact', 'Emergency note · text', 'rich', 'CareSenda isn’t an emergency service. In an emergency, call 112 or go straight to the nearest hospital.'],
        'contact.form_title' => ['contact', 'Form heading', 'text', 'Send us a message'],
        'contact.sent_title' => ['contact', 'After sending · heading', 'text', 'Thanks, your message is on its way'],
        'contact.sent_text' => ['contact', 'After sending · text', 'rich', 'We’ve got it. Someone from our team will get back to you using the details you gave us.'],

        // Search
        'search.notice' => ['search', 'Note above the search results', 'rich', 'Results are free for now while we set up Mobile Money payments. Each listing shows whether we’ve confirmed it.'],
        'search.image_all' => ['search', 'Header photo · all care', 'image', 'health-fair-ghana', 'A nurse checks a man’s blood pressure at a health fair in Ghana'],
        'search.image_specialist' => ['search', 'Header photo · specialists', 'image', 'specialist-consultation', 'A doctor checking a patient’s blood pressure'],
        'search.image_test' => ['search', 'Header photo · tests', 'image', 'lab-malaria-test', 'Health workers carrying out malaria tests'],
        'search.image_facility' => ['search', 'Header photo · facilities', 'image', 'ug-medical-centre', 'University of Ghana Medical Centre'],
    ],
];
