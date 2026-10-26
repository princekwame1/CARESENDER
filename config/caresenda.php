<?php

/*
| CareSenda domain configuration.
|
| Coordinates in `areas` are approximate neighbourhood centroids used to
| estimate distance from the user's detected location. Facility source data
| carries no coordinates, so distances are estimates ("about 2.4 km").
| Each entry: [name, city, lat, lng, [extra match aliases]].
*/

return [
    'dataset' => database_path('data/caresenda-directory.json.gz'),

    'cache_ttl' => (int) env('CARESENDA_CACHE_TTL', 3600),

    // Where social sign-in started in the mobile app returns to (the app's URL scheme).
    'app_auth_callback' => env('CARESENDA_APP_AUTH_CALLBACK', 'caresenda://auth'),

    'coverage' => [
        // Rough bounding box around Greater Accra; detections outside it fall back to manual choice.
        'bounds' => ['south' => 5.40, 'north' => 5.95, 'west' => -0.55, 'east' => 0.20],
        'max_match_km' => 15,
    ],

    'cities' => [
        'Accra' => ['lat' => 5.6037, 'lng' => -0.1870],
        'Tema' => ['lat' => 5.6698, 'lng' => -0.0166],
    ],

    'diagnostic_categories' => [
        'laboratory' => 'Laboratory tests',
        'imaging' => 'Radiology & imaging',
        'other' => 'Other diagnostics',
    ],

    'common_specialties' => ['Gynaecologist', 'Paediatrician', 'Dentist', 'Dermatologist', 'Cardiologist', 'ENT specialist', 'Urologist', 'Ophthalmologist'],

    'common_tests' => ['Full blood count', 'Urinalysis', 'Blood sugar', 'Malaria', 'MRI scan', 'CT scan', 'Ultrasound', 'X-ray'],

    // Public contact details shown on /contact; leave empty to show only the form.
    'contact' => [
        'email' => env('CARESENDA_CONTACT_EMAIL'),
        'phone' => env('CARESENDA_CONTACT_PHONE'),
        'whatsapp' => env('CARESENDA_CONTACT_WHATSAPP'),
        'hours' => env('CARESENDA_CONTACT_HOURS'),
    ],

    // Starting value only: switched on and off in /admin/payments (plans are managed there too).
    'payments_enabled' => (bool) env('CARESENDA_PAYMENTS_ENABLED', false),

    /*
    | Openly licensed photos (public/images/photos/{slug}-{640|1200}.webp).
    | CC BY-SA images must keep their visible credit; all are listed on /credits.
    */
    'photos' => [
        'hero-concept' => ['title' => 'Woman looking for healthcare information on her phone', 'creator' => 'AI-generated concept image', 'license' => 'Original CareSenda design', 'license_url' => null, 'source' => null, 'width' => 1536, 'height' => 1024, 'credit_required' => false],
        'doctor-consultation' => ['title' => 'Doctor talking with a patient during a consultation', 'creator' => null, 'license' => 'Stock photo', 'license_url' => null, 'source' => null, 'width' => 1200, 'height' => 1200, 'credit_required' => false],
        'eye-specialist' => ['title' => 'Eye specialist examining a patient', 'creator' => null, 'license' => 'Stock photo', 'license_url' => null, 'source' => null, 'width' => 1200, 'height' => 600, 'credit_required' => false],
        'doctor-child-care' => ['title' => 'Doctor examining a young patient with his arm in a sling', 'creator' => null, 'license' => 'Stock photo', 'license_url' => null, 'source' => null, 'width' => 1200, 'height' => 800, 'credit_required' => false],
        'clinic-reception' => ['title' => 'Reception desk and waiting area at a health centre', 'creator' => null, 'license' => 'Stock photo', 'license_url' => null, 'source' => null, 'width' => 1200, 'height' => 800, 'credit_required' => false],
        'specialist-consultation' => ['title' => 'Free consultation with a Doctor of mobile health team has Madagacar', 'creator' => 'Docteur Ando', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=64296875', 'width' => 1920, 'height' => 1440, 'credit_required' => true],
        'lab-malaria-test' => ['title' => 'Continuing test community members malaria', 'creator' => null, 'license' => 'CC0 1.0', 'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/', 'source' => 'https://www.rawpixel.com/image/8759387/photo-image-public-domain-covid-19-women', 'width' => 1024, 'height' => 575, 'credit_required' => false],
        'ridge-hospital-accra' => ['title' => 'Ridge Hospital Accra', 'creator' => 'Amuzujoe', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=98474201', 'width' => 1920, 'height' => 1440, 'credit_required' => true],
        'health-fair-ghana' => ['title' => 'Health Fair 2018. April 12', 'creator' => 'U.S. Embassy Ghana', 'license' => 'CC0 1.0', 'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/', 'source' => 'https://www.rawpixel.com/image/8714719/photo-image-public-domain-woman-people', 'width' => 1024, 'height' => 683, 'credit_required' => false],
        'nurse-on-duty' => ['title' => 'Nurse at duty', 'creator' => 'Fquasie', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=63813185', 'width' => 1920, 'height' => 1280, 'credit_required' => true],
        'ug-medical-centre' => ['title' => 'University of Ghana Medical Centre 07', 'creator' => 'Jwale2', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=89725644', 'width' => 1920, 'height' => 1440, 'credit_required' => true],
        'spec-gynaecology' => ['title' => 'Tania is a midwife for UNFPA in Kutapalong refugee camp. Every day around 30 women come to the clinic for check-ups', 'creator' => 'UK Department for International Development', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0/', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=138125720', 'width' => 1920, 'height' => 1280, 'credit_required' => true],
        'spec-paediatrics' => ['title' => 'Immunization in Cameroon (34692993536)', 'creator' => 'CDC Global', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0/', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=64127753', 'width' => 1920, 'height' => 1440, 'credit_required' => true],
        'spec-dentist' => ['title' => 'Chair Dentist', 'creator' => 'Daniel Frank', 'license' => 'CC0 1.0', 'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/', 'source' => 'https://stocksnap.io/photo/chair-dentist-3J10QN5PTS', 'width' => 960, 'height' => 640, 'credit_required' => false],
        'spec-eye-care' => ['title' => 'Lens changing technique in eye test', 'creator' => 'Kwameghana', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=102689888', 'width' => 1920, 'height' => 1277, 'credit_required' => true],
        'spec-physiotherapy' => ['title' => 'Physiotherapy now, next stop - school (6301592477)', 'creator' => 'UK Department for International Development', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0/', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=26240531', 'width' => 1672, 'height' => 1111, 'credit_required' => true],
    ],

    /*
    | Photo shown on search results for a specialty. Keys are word stems matched
    | against the normalised search; anything unmatched uses the general photo.
    */
    'specialty_photos' => [
        'gynaecolog' => ['spec-gynaecology', 'A midwife holding a newborn baby'],
        'obstetric' => ['spec-gynaecology', 'A midwife holding a newborn baby'],
        'antenatal' => ['spec-gynaecology', 'A midwife holding a newborn baby'],
        'midwi' => ['spec-gynaecology', 'A midwife holding a newborn baby'],
        'paediatric' => ['spec-paediatrics', 'A health worker giving a young child an oral vaccine'],
        'child' => ['spec-paediatrics', 'A health worker giving a young child an oral vaccine'],
        'dent' => ['spec-dentist', 'A dental chair in a clinic'],
        'ophthalm' => ['spec-eye-care', 'A patient having an eye test in Ghana'],
        'optom' => ['spec-eye-care', 'A patient having an eye test in Ghana'],
        'eye' => ['spec-eye-care', 'A patient having an eye test in Ghana'],
        'physio' => ['spec-physiotherapy', 'A child walking during physiotherapy'],
    ],

    'areas' => [
        // Central Accra
        ['Accra Central', 'Accra', 5.5490, -0.2050, ['accra central', 'okaishi', 'okaishie', 'makola', 'tudu', 'arena', 'american house', 'octagon']],
        ['Adabraka', 'Accra', 5.5600, -0.2080, []],
        ['Asylum Down', 'Accra', 5.5700, -0.2050, ['ring road']],
        ['Kokomlemle', 'Accra', 5.5780, -0.2120, ['palas town']],
        ['Accra Newtown', 'Accra', 5.5800, -0.2150, ['newtown']],
        ['Kanda', 'Accra', 5.5850, -0.2050, []],
        ['Nima', 'Accra', 5.5850, -0.1950, ['maamobi', 'mamobi']],
        ['Kotobabi', 'Accra', 5.5950, -0.2100, []],
        ['Pig Farm', 'Accra', 5.5900, -0.2050, []],
        ['Alajo', 'Accra', 5.5950, -0.2200, ['alaji']],
        ['Fadama', 'Accra', 5.5450, -0.2200, ['fadama street', 'new fadama']],
        ['Osu', 'Accra', 5.5560, -0.1780, ['danquah', 'kwashibu']],
        ['Ridge', 'Accra', 5.5640, -0.1990, ['west ridge', 'su tower']],
        ['North Ridge', 'Accra', 5.5700, -0.2000, []],
        ['Labone', 'Accra', 5.5650, -0.1700, []],
        ['North Labone', 'Accra', 5.5720, -0.1690, []],
        ['Cantonments', 'Accra', 5.5780, -0.1720, ['cantonment']],
        ['La', 'Accra', 5.5600, -0.1550, ['labadi']],
        ['Burma Camp', 'Accra', 5.5850, -0.1400, ['trade fair', 'burma']],
        ['37', 'Accra', 5.5850, -0.1850, ['37 military', '37 circle', 'near 37']],
        ['Airport Residential', 'Accra', 5.6030, -0.1800, ['airport residential area', 'airport']],
        ['East Airport', 'Accra', 5.6040, -0.1600, ['east airport residential']],
        ['Airport City', 'Accra', 5.6050, -0.1760, ['kotoka']],
        ['Roman Ridge', 'Accra', 5.6000, -0.1950, []],
        ['Dzorwulu', 'Accra', 5.6100, -0.2000, []],
        ['Abelemkpe', 'Accra', 5.6080, -0.2100, ['abelenkpe', 'ablenkpe']],
        ['Apenkwa', 'Accra', 5.6050, -0.2200, []],
        ['North Industrial Area', 'Accra', 5.5880, -0.2350, ['north industrial']],
        ['Tesano', 'Accra', 5.6000, -0.2300, []],
        ['Abeka', 'Accra', 5.5950, -0.2400, []],
        ['Lapaz', 'Accra', 5.6050, -0.2550, ['abeka lapaz']],
        ['Achimota', 'Accra', 5.6200, -0.2250, []],
        ['Mile 7', 'Accra', 5.6300, -0.2450, ['new achimota']],
        ['Abofu', 'Accra', 5.6250, -0.2300, []],
        ['Tantra Hill', 'Accra', 5.6400, -0.2600, ['tantra hills']],
        ['Akweteman', 'Accra', 5.6400, -0.2550, []],
        ['Santa Maria', 'Accra', 5.6050, -0.2750, []],
        // West Accra
        ['Kaneshie', 'Accra', 5.5700, -0.2350, []],
        ['North Kaneshie', 'Accra', 5.5800, -0.2400, []],
        ['Bubuashie', 'Accra', 5.5720, -0.2450, []],
        ['Laterbiokorshie', 'Accra', 5.5600, -0.2400, ['laterbiorshie']],
        ['Mataheko', 'Accra', 5.5600, -0.2550, ['mateheko']],
        ['Shukura', 'Accra', 5.5650, -0.2550, []],
        ['Korle Bu', 'Accra', 5.5370, -0.2280, ['korle-bu']],
        ['Korle Gonno', 'Accra', 5.5300, -0.2250, ['korle-gonno']],
        ['Mamprobi', 'Accra', 5.5350, -0.2400, []],
        ['Chorkor', 'Accra', 5.5300, -0.2500, []],
        ['Dansoman', 'Accra', 5.5450, -0.2650, []],
        ['Sakaman', 'Accra', 5.5550, -0.2700, []],
        ['Odorkor', 'Accra', 5.5750, -0.2750, []],
        ['Kwashieman', 'Accra', 5.5950, -0.2700, []],
        ['Awoshie', 'Accra', 5.5900, -0.2800, ['awoshi']],
        ['Anyaa', 'Accra', 5.5950, -0.2900, []],
        ['Mallam', 'Accra', 5.5750, -0.2950, []],
        ['Ablekuma', 'Accra', 5.6050, -0.3050, ['fanmilk', 'joma']],
        ['Gbawe', 'Accra', 5.5750, -0.3100, []],
        ['Oblogo', 'Accra', 5.5500, -0.3200, []],
        ['Weija', 'Accra', 5.5650, -0.3350, ['mile 11', 'djanaman']],
        ['Old Barrier', 'Accra', 5.5700, -0.3700, []],
        ['Tetegu', 'Accra', 5.5650, -0.3600, []],
        ['Tomefa', 'Accra', 5.5600, -0.3800, []],
        ['Bortianor', 'Accra', 5.5250, -0.3700, ['new bortianor', 'old bortianor']],
        ['Kokrobite', 'Accra', 5.5000, -0.3800, ['krokrobite', 'langma']],
        ['Tuba', 'Accra', 5.5200, -0.3900, []],
        ['Oshiyie', 'Accra', 5.4950, -0.4000, []],
        // North Accra
        ['Sowutuom', 'Accra', 5.6200, -0.2800, []],
        ['Ofankor', 'Accra', 5.6650, -0.2650, []],
        ['Asofan', 'Accra', 5.6100, -0.2700, ['asofa']],
        ['Akweiman', 'Accra', 5.6300, -0.2600, []],
        ['Sapeiman', 'Accra', 5.7250, -0.2950, []],
        ['Teiman', 'Accra', 5.7150, -0.2050, []],
        ['Pokuase', 'Accra', 5.6950, -0.2800, []],
        ['Akramaman', 'Accra', 5.6700, -0.3050, []],
        ['Amasaman', 'Accra', 5.7050, -0.3000, ['amansaman', 'sonitra']],
        ['Pobiman', 'Accra', 5.7000, -0.3250, []],
        ['Medie', 'Accra', 5.7400, -0.3200, []],
        ['Kotoku', 'Accra', 5.7300, -0.3400, []],
        ['Ayikai Doblo', 'Accra', 5.7700, -0.3100, ['doblo']],
        ['Nsakina', 'Accra', 5.7200, -0.2800, []],
        ['Dome', 'Accra', 5.6500, -0.2350, ['kisseman', 'sampaman', 'dome pillar']],
        ['Taifa', 'Accra', 5.6600, -0.2500, []],
        ['Kwabenya', 'Accra', 5.6900, -0.2300, []],
        ['Ashongman', 'Accra', 5.6950, -0.2300, ['old ashongman']],
        ['Haatso', 'Accra', 5.6750, -0.2050, []],
        ['Agbogba', 'Accra', 5.6800, -0.2100, []],
        ['Westlands', 'Accra', 5.6600, -0.1950, ['westland']],
        ['Legon', 'Accra', 5.6500, -0.1870, ['new legon', 'bawaleshie', 'bawalashi']],
        ['North Legon', 'Accra', 5.6700, -0.1700, []],
        ['Abokobi', 'Accra', 5.7300, -0.2000, ['aborkobi']],
        ['Pantang', 'Accra', 5.7200, -0.1850, []],
        ['Oyarifa', 'Accra', 5.7400, -0.1600, []],
        ['Madina', 'Accra', 5.6800, -0.1650, ['madina estate']],
        ['Adenta', 'Accra', 5.7050, -0.1650, ['adenta housing']],
        ['West Adenta', 'Accra', 5.6950, -0.1750, []],
        ['Ashiyie', 'Accra', 5.7100, -0.1400, []],
        ['Frafraha', 'Accra', 5.6900, -0.1300, ['frafraho']],
        ['Amrahia', 'Accra', 5.7600, -0.1300, []],
        ['Oyibi', 'Accra', 5.7600, -0.1100, []],
        // East Accra
        ['East Legon', 'Accra', 5.6350, -0.1600, ['shalom estate']],
        ['Shiashie', 'Accra', 5.6200, -0.1700, []],
        ['Adjiringanor', 'Accra', 5.6400, -0.1350, []],
        ['Trassaco', 'Accra', 5.6550, -0.1250, []],
        ['Ogbojo', 'Accra', 5.6550, -0.1400, ['ogbodjo']],
        ['Ashaley Botwe', 'Accra', 5.6650, -0.1450, ['ashale botwe', 'ashaley down', 'agape down']],
        ['Lakeside Estate', 'Accra', 5.6750, -0.1050, ['lake side', 'nmai-djorn', 'nmai djorn']],
        ['Tse Addo', 'Accra', 5.5800, -0.1250, ['tse-addo']],
        ['Teshie', 'Accra', 5.5850, -0.1050, []],
        ['Nungua', 'Accra', 5.6000, -0.0750, []],
        ['Spintex', 'Accra', 5.6300, -0.1000, ['spintex road']],
        ['Baatsona', 'Accra', 5.6300, -0.0900, ['baatsonaa']],
        // Tema & environs
        ['Sakumono', 'Tema', 5.6150, -0.0500, []],
        ['Lashibi', 'Tema', 5.6450, -0.0450, []],
        ['Klagon', 'Tema', 5.6500, -0.0350, []],
        ['Tema New Town', 'Tema', 5.6350, 0.0050, []],
        ['Tema Community 1', 'Tema', 5.6400, -0.0100, ['comm1', 'community one', 'community 1']],
        ['Tema Community 4', 'Tema', 5.6600, -0.0050, ['community 4', 'comm 4']],
        ['Tema Community 5', 'Tema', 5.6600, -0.0200, ['community 5', 'comm 5']],
        ['Tema Community 6/7', 'Tema', 5.6650, -0.0300, ['community six', 'community 6', 'community 7']],
        ['Tema Community 9', 'Tema', 5.6750, -0.0100, ['community 9', 'community (4,8,9', 'commuinity']],
        ['Tema Community 18', 'Tema', 5.6900, -0.0300, ['community 18']],
        ['Tema Community 22', 'Tema', 5.7000, -0.0350, ['community 22']],
        ['Tema Community 25', 'Tema', 5.7300, 0.0200, ['community 25', 'comm. 25', 'comm 25']],
        ['Tema Free Zone', 'Tema', 5.6830, 0.0250, ['freezone', 'free zone']],
        ['Ashaiman', 'Tema', 5.6950, -0.0300, ['washington']],
        ['Bethlehem', 'Tema', 5.7000, -0.0200, ['bethelehem']],
        ['Sebrepor', 'Tema', 5.7100, -0.0450, ['new sebrepor']],
        ['Adjei Kojo', 'Tema', 5.7000, -0.0900, []],
        ['Katamanso', 'Tema', 5.7350, -0.0700, []],
        ['Michel Camp', 'Tema', 5.7150, 0.0100, ['michelle camp']],
        ['Kpone', 'Tema', 5.7000, 0.0600, []],
        ['Dawhenya', 'Tema', 5.7550, 0.0800, ['dahwenya']],
        ['Afienya', 'Tema', 5.7900, 0.0000, ['mataheko afienya']],
        ['Tema', 'Tema', 5.6698, -0.0166, ['tema']],
    ],
];
