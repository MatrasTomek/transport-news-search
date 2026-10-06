# transport-search

Komenda Claude Code, która wyszukuje nowości w przepisach transportowych i proponuje tematy na blog https://mawex-biuro.pl/blog.

## Użycie

1. Otwórz Claude Code w tym katalogu: `claude`
2. Wpisz `/tematy` (ostatnie 14 dni) albo `/tematy 30` (ostatnie 30 dni, maks. 365).
3. Raport zapisze się w `raporty/RRRR-MM-DD.md`. W tej samej sesji możesz dopytać, np. „rozwiń temat 3 w szkic wpisu”.

Bez otwierania sesji: `claude -p "/tematy 21"`

## Obszary

Prawo transportowe · Czas pracy kierowców · Wypożyczalnie samochodów · Księgowość i podatki w transporcie · ZUS i składki · Kierowcy spoza UE

## Dostosowanie

- `zrodla.md`: lista zaufanych źródeł (dopisuj i usuwaj dowolnie).
- `.claude/commands/tematy.md`: instrukcja komendy (obszary, format raportu, zasady).

Raport to materiał roboczy. Przed publikacją zweryfikuj treść przepisów w źródłach.

## Aplikacja web (`web/`)

Ta sama funkcja co `/tematy`, ale w przeglądarce, po zalogowaniu. Wyniki są w tabeli, a przy każdym temacie jest przycisk „Pisz post” (post na Facebooka w zadanym limicie znaków) i „Wygeneruj obraz” (grafika PNG/SVG). Silnikiem jest Claude API (płatne według użycia: tokeny i opłata za każde wyszukiwanie web search).

### Uruchomienie lokalne

1. Wymagane PHP 8.1+ z rozszerzeniami `pdo_sqlite`, `curl`, `dom`, `mbstring`.
2. `cd web && cp config.example.php config.php`
3. `php tools/hash.php` → wpisz hasło, wynik wklej do `password_hash` w `config.php`.
4. Wpisz klucz API (`anthropic_api_key`), kolory firmy i ewentualnie model.
5. Opcjonalnie wgraj logo jako `web/data/logo.png`.
6. `php -S 127.0.0.1:8000` i otwórz http://127.0.0.1:8000
7. Testy: `php tests/run.php`

### Wdrożenie na OVH

1. Przygotuj `config.php` jak wyżej (lokalnie).
2. Wgraj przez FTP całą zawartość katalogu `web/` do katalogu strony (np. `www/`), razem z `config.php` i plikami `.htaccess` i `.ovhconfig`.
3. Katalog `data/` musi mieć prawo zapisu. Baza `data/app.sqlite` i `data/zrodla.md` powstaną przy pierwszym wejściu.
4. W panelu OVH włącz bezpłatny certyfikat SSL (Let's Encrypt), a potem odkomentuj przekierowanie na HTTPS w `.htaccess`.
5. W konsoli Anthropic ustaw miesięczny limit wydatków.

**Aktualizacja:** wgraj ponownie pliki, ale **nie nadpisuj** katalogu `data/` ani `config.php`, bo straciłbyś historię wyszukiwań i swoją listę źródeł.

**Limit czasu:** każdy krok wyszukiwania to jedno wywołanie API, które czeka najwyżej `api_timeout` sekund (domyślnie 150). Jeśli hosting przerywa żądania wcześniej, zmniejsz `api_timeout` w `config.php`. Przerwany krok można wznowić przyciskiem „Wznów” lub „Ponów”.

Błędy aplikacji są zapisywane w `data/app.log` (niedostępnym z przeglądarki).
