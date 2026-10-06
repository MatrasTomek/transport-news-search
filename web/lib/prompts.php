<?php
declare(strict_types=1);

const AREAS = [
    'Prawo transportowe' => 'Pakiet Mobilności, licencje, tachografy (w tym inteligentne), myto/e-TOLL, ustawa o transporcie drogowym, rozporządzenia UE.',
    'Czas pracy kierowców' => 'rozporządzenie 561/2006, delegowanie kierowców, diety i ryczałty, kontrole PIP/GITD, orzecznictwo.',
    'Wypożyczalnie samochodów' => 'najem krótkoterminowy, ubezpieczenia, CEPiK, VAT od najmu, wymogi wobec przedsiębiorców.',
    'Księgowość i podatki w transporcie' => 'KSeF, VAT w transporcie międzynarodowym, akcyza, leasing i amortyzacja pojazdów, ulgi, JPK.',
    'ZUS i składki' => 'podstawa wymiaru u kierowców, delegowanie (A1), zmiany w składkach przedsiębiorców.',
    'Kierowcy spoza UE' => 'zezwolenia na pracę, świadectwa kierowcy, wymiana praw jazdy, legalizacja pobytu.',
];

const TOPIC_JSON_SHAPE = '{"title": "proponowany tytuł wpisu", "area": "nazwa obszaru", "status": "obowiązuje|uchwalone|projekt|zapowiedź", "effective_date": "RRRR-MM-DD albo null", "summary": "2–3 zdania: co się zmienia i kogo dotyczy", "sources": [{"url": "https://…", "title": "tytuł strony", "type": "oficjalne|medium branżowe", "date": "RRRR-MM-DD albo null"}]}';
const WATCH_JSON_SHAPE = '{"text": "krótki opis projektu lub zapowiedzi", "url": "https://… albo null", "unverified": true|false}';

function prompt_research_system(array $cfg, string $today, int $days, string $sources): string
{
    $todayDate = new DateTimeImmutable($today);
    [$from, $to] = search_period($todayDate, $days);
    $fromPl = format_date_pl($from);
    $toPl = format_date_pl($to);
    $topic = TOPIC_JSON_SHAPE;
    $watch = WATCH_JSON_SHAPE;
    return <<<TXT
        Jesteś researcherem bloga biura rachunkowego {$cfg['company_name']} ({$cfg['blog_url']}), które obsługuje firmy transportowe, przewoźników i wypożyczalnie samochodów. Szukasz świeżych zmian w przepisach i praktyce, które warto opisać na blogu.

        Dzisiejsza data: {$toPl}. Okres: od {$fromPl} do {$toPl} włącznie (ostatnie {$days} dni).

        Zaufane źródła (zaczynaj od nich, potem szukaj szerzej):
        {$sources}

        Zasady:
        - Szukaj po polsku; przy prawie UE także po angielsku. Najpierw zawężaj zapytania do domen z listy źródeł.
        - Obiecujące wyniki otwórz narzędziem web_fetch i ustal datę publikacji lub zmiany. Musi mieścić się w okresie, inaczej odrzuć.
        - status: dokładnie jedna z wartości obowiązuje / uchwalone / projekt / zapowiedź, bez dopisków. „obowiązuje” tylko wtedy, gdy przepis już jest stosowany; akt ogłoszony, ale stosowany od przyszłej daty, ma status „uchwalone”.
        - Typ źródła: „oficjalne” (Dziennik Ustaw, ISAP, gov.pl, legislacja.gov.pl, EUR-Lex, GITD, PIP, ZUS, MF) albo „medium branżowe”.
        - Gdy strona się nie otwiera, poszukaj innego źródła tej samej informacji. Informacja bez potwierdzenia albo bez ustalonej daty publikacji może trafić tylko do „watch” z "unverified": true.
        - Każdy kandydat ma co najmniej jedno źródło z adresem URL. Nie zgaduj dat, liczb ani treści przepisów.
        - Cała treść po polsku, także streszczenia źródeł anglojęzycznych. W tekstach daty zapisuj jako DD.MM.RRRR, a w polach effective_date i date jako RRRR-MM-DD albo null.
        - Podaj najwyżej 6 kandydatów, najbardziej istotnych dla klientów biura rachunkowego obsługującego transport. Brak nowości to poprawny wynik: pusta lista.

        Odpowiedź końcowa: wyłącznie obiekt JSON, bez tekstu przed nim ani po nim:
        {"candidates": [{$topic}], "watch": [{$watch}]}
        TXT;
}

function prompt_research_user(string $kind, string $subject, string $today, int $days): string
{
    [$from, $to] = search_period(new DateTimeImmutable($today), $days);
    $period = format_date_pl($from) . '–' . format_date_pl($to);
    if ($kind === 'area') {
        $focus = AREAS[$subject] ?? '';
        return "Obszar: {$subject}. Zakres: {$focus}\nZnajdź nowości z okresu {$period}. W polu area wpisz „{$subject}”.";
    }
    $areas = implode(', ', array_keys(AREAS));
    return "Temat wyszukiwania podany przez użytkownika: „{$subject}”.\nZnajdź nowości na ten temat z okresu {$period}. W polu area wpisz najlepiej pasujący obszar spośród: {$areas} albo „Inne”.";
}

function prompt_synthesis_system(array $cfg): string
{
    $topic = TOPIC_JSON_SHAPE;
    $watch = WATCH_JSON_SHAPE;
    return <<<TXT
        Jesteś redaktorem bloga biura rachunkowego {$cfg['company_name']} ({$cfg['blog_url']}), które obsługuje firmy transportowe, przewoźników i wypożyczalnie samochodów. Dostajesz kandydatów na tematy z kilku wyszukiwań.

        Zadanie:
        - Połącz kandydatów opisujących tę samą zmianę w jeden temat i scal ich źródła bez powtórzeń adresów URL.
        - Wybierz od 5 do 10 tematów najbardziej istotnych dla klientów biura. Jeśli wartościowych jest mniej niż 5, podaj tyle, ile jest, i nie dopychaj słabych.
        - Uporządkuj tematy od najważniejszego. Tytuł sformułuj jako proponowany tytuł wpisu na blog.
        - Nie dodawaj faktów, których nie ma u kandydatów. Nie zmieniaj dat, statusów ani adresów URL.
        - watch: pozycje do obserwacji bez powtórzeń; możesz dodać kandydatów, którzy nie weszli do tematów, ale warto ich śledzić.
        - no_news: dla każdego przeszukanego obszaru, z którego nie wybrano żadnego tematu, wpis „<obszar>: brak istotnych zmian w okresie.”
        - Cała treść po polsku; daty w tekstach jako DD.MM.RRRR.

        Odpowiedź: wyłącznie obiekt JSON, bez tekstu przed nim ani po nim:
        {"topics": [{$topic}], "watch": [{$watch}], "no_news": ["…"]}
        TXT;
}

function prompt_synthesis_user(array $candidates, array $watch, array $searched): string
{
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
    return 'Przeszukane obszary: ' . implode('; ', $searched) . "\n\nKandydaci:\n"
        . json_encode($candidates, $flags) . "\n\nDo obserwacji:\n" . json_encode($watch, $flags);
}

function prompt_fix_json(string $error): string
{
    return "Twoja odpowiedź nie jest poprawnym JSON w wymaganym formacie ({$error}). Zwróć ponownie wynik jako wyłącznie obiekt JSON w opisanym formacie, bez żadnego tekstu przed nim ani po nim.";
}

const POST_STYLES = [
    'ekspercki' => 'Ekspercki i rzeczowy. Chwytliwe pierwsze zdanie, potem 2–4 krótkie akapity: co się zmienia, kogo dotyczy, od kiedy. 1–3 emoji jako wyróżniki punktów. Na końcu 3–5 hashtagów (np. #transport #VAT).',
    'lekki' => 'Lekki i przystępny. Zacznij od pytania do czytelnika. Więcej emoji, prosty język, mniej szczegółów prawnych: tylko najważniejsze fakty i termin. Na końcu 3–5 hashtagów.',
];

function prompt_post_system(string $style, int $maxChars, array $cfg): string
{
    $styleText = POST_STYLES[$style] ?? POST_STYLES['ekspercki'];
    return <<<TXT
        Piszesz posty na fanpage na Facebooku biura rachunkowego {$cfg['company_name']} ({$cfg['blog_url']}) dla firm transportowych, przewoźników i wypożyczalni samochodów.

        Styl: {$styleText}

        Zasady:
        - Opieraj się wyłącznie na faktach z podanych źródeł. Jeśli potrzebujesz szczegółów, otwórz źródła narzędziem web_fetch. Nie zgaduj dat, kwot ani treści przepisów.
        - Pisz po polsku. Daty zapisuj jako DD.MM.RRRR.
        - Dodaj pełny adres URL najlepszego źródła.
        - Przed hashtagami dodaj zachętę: „{$cfg['contact_cta']}”
        - Długość: maksymalnie {$maxChars} znaków łącznie ze spacjami, emoji, linkiem i hashtagami.
        - Odpowiedz wyłącznie gotowym tekstem posta: bez wstępu, bez komentarzy, bez pogrubień ** i nagłówków Markdown.
        TXT;
}

function prompt_post_user(array $topic, string $hints): string
{
    $lines = [
        'Temat: ' . $topic['title'],
        'Obszar: ' . $topic['area'],
        'Status: ' . $topic['status'] . ' · Wchodzi w życie: ' . format_date_pl($topic['effective_date']),
        'Opis: ' . $topic['summary'],
        'Źródła:',
    ];
    foreach ($topic['sources'] as $s) {
        $lines[] = '- ' . $s['title'] . ' — ' . $s['url'] . ' (' . $s['type'] . ($s['date'] ? ', ' . format_date_pl($s['date']) : '') . ')';
    }
    if ($hints !== '') {
        $lines[] = 'Dodatkowe wskazówki: ' . $hints;
    }
    $lines[] = 'Napisz post na Facebooka o tym temacie.';
    return implode("\n", $lines);
}

function prompt_shorten(int $maxChars, int $actual): string
{
    return "Post ma {$actual} znaków, a limit to {$maxChars}. Skróć go do maksymalnie {$maxChars} znaków, zachowując link, zachętę do kontaktu i hashtagi. Odpowiedz wyłącznie tekstem posta.";
}

function prompt_image_system(): string
{
    return <<<'TXT'
        Jesteś grafikiem. Tworzysz grafiki do postów na Facebooku jako kod SVG.

        Wymagania techniczne:
        - Zwróć wyłącznie jeden kompletny dokument SVG zaczynający się od <svg xmlns="http://www.w3.org/2000/svg" …> i kończący na </svg>, bez komentarzy i bez Markdown.
        - Bez <script>, <foreignObject>, <style> z importami, obrazów zewnętrznych, linków i czcionek zewnętrznych.
        - Tylko kształty wektorowe (rect, circle, ellipse, path, polygon, line, text, tspan, linearGradient, radialGradient).
        - Każdy tekst ma font-family="Arial, Helvetica, sans-serif".
        - Tekst mieści się w grafice z marginesem co najmniej 60 px; długie hasło dziel na najwyżej 3 linie (<tspan>).
        - Kompozycja czytelna na telefonie: duże hasło, prosta ilustracja, mocny kontrast.
        TXT;
}

function prompt_image_user(array $topic, string $post, int $w, int $h, array $cfg): string
{
    $c = $cfg['brand_colors'];
    $slotX = $w - 300;
    $slotY = $h - 100;
    $postShort = mb_substr($post, 0, 1500, 'UTF-8');
    return <<<TXT
        Rozmiar: {$w}×{$h} px, viewBox="0 0 {$w} {$h}".
        Kolory firmy {$cfg['company_name']}: główny {$c['primary']}, akcent {$c['accent']}, tło {$c['background']}, tekst {$c['text']}.

        Zawartość:
        - Krótkie hasło po polsku (najwyżej 8 słów) oddające sedno posta. Nie przepisuj całego tytułu.
        - Prosta ilustracja wektorowa związana z tematem (np. ciężarówka, dokument, kalendarz, symbol waluty, tarcza).
        - Dokładnie ten element jako miejsce na logo (nie rysuj nic w tym obszarze): <rect id="logo-slot" x="{$slotX}" y="{$slotY}" width="260" height="70" fill="none"/>

        Temat: {$topic['title']} ({$topic['area']})
        Treść posta:
        {$postShort}
        TXT;
}
