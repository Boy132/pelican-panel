<?php

return [
    'email' => [
        'title' => 'Opdater din e-mail',
        'updated' => 'Din e-mailadresse er blevet opdateret.',
    ],
    'password' => [
        'title' => 'Skift din adgangskode',
        'requirements' => 'Din nye adgangskode skal være mindst 8 tegn lang.',
        'updated' => 'Din adgangskode er blevet opdateret.',
    ],
    'two_factor' => [
        'button' => 'Konfigurer 2-Faktor godkendelse',
        'disabled' => '2-faktor godkendelse er blevet deaktiveret på din konto. Du vil ikke længere blive bedt om at angive en token ved login.',
        'enabled' => '2-faktor godkendelse er blevet aktiveret på din konto! Fra nu af, når du logger ind, vil du blive bedt om at angive koden genereret af din enhed.',
        'invalid' => 'Den angivne nøgle var ugyldig.',
        'setup' => [
            'title' => 'Opsætning af 2-faktor godkendelse',
            'help' => 'Kan ikke scanne koden? Indtast koden nedenfor i din applikation:',
            'field' => 'Indtast token',
        ],
        'disable' => [
            'title' => 'Deaktiver 2-faktor godkendelse',
            'field' => 'Indtast nøgle',
        ],
    ],
];
