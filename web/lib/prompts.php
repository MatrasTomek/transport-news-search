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
