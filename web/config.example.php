<?php
// Skopiuj do config.php i uzupełnij. config.php nie trafia do gita.
return [
    'login' => 'admin',
    'password_hash' => '',              // wynik: php tools/hash.php
    'anthropic_api_key' => '',          // https://platform.claude.com → API keys
    'model' => 'claude-sonnet-5-5',
    'web_search_tool' => 'web_search_20250305',
    'web_fetch_tool' => 'web_fetch_20250910',
    'api_timeout' => 150,               // sekundy; krótszy niż limit czasu PHP na hostingu
    'company_name' => 'MAWEX',
    'blog_url' => 'https://mawex-biuro.pl/blog',
    'contact_cta' => 'Masz pytania? Skontaktuj się z biurem MAWEX.',
    'brand_colors' => [
        'primary' => '#1f4e79',
        'accent' => '#f2a900',
        'background' => '#ffffff',
        'text' => '#1a1a1a',
    ],
];
