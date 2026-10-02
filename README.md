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
