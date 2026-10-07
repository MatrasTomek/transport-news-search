# transport-search

Komenda Claude Code, która wyszukuje nowości w przepisach transportowych i proponuje tematy na blog https://mawex-biuro.pl/blog.

## Użycie

1. Otwórz Claude Code w tym katalogu: `claude`
2. Wpisz `/tematy` (ostatnie 14 dni) albo `/tematy 30` (ostatnie 30 dni, maks. 365).
3. Raport zapisze się w `raporty/RRRR-MM-DD.md`. W tej samej sesji możesz dopytać, np. „rozwiń temat 3 w szkic wpisu”.

Bez otwierania sesji: `claude -p "/tematy 21"`

## Posty na Facebooka

Po wygenerowaniu raportu wpisz `/post <nr>`, np. `/post 3`. Komenda bierze temat o tym numerze z najnowszego raportu w `raporty/`, otwiera jego źródła i pisze post na fanpage MAWEX.

- Pełna postać: `/post <nr> [ekspercki|lekki] [limit znaków] [wskazówki]`, np. `/post 3 lekki 500 podkreśl termin`.
- Domyślnie styl ekspercki i limit 1000 znaków (zakres 200–3000). Znaki liczy `node`, więc licznik zgadza się z rzeczywistą długością (także z emoji).
- Post wyświetla się w terminalu i zapisuje w `posty/RRRR-MM-DD-temat-<nr>.md`. W tej samej sesji możesz poprosić np. „skróć do 500 znaków” albo „wersja lekka”.
- `/post` bez numeru wypisuje listę tematów z ostatniego raportu.
- Przy limicie poniżej ok. 400 znaków sam link, zachęta i hashtagi mogą nie zmieścić się w limicie. Komenda wtedy ostrzega.

## Obszary

Prawo transportowe · Czas pracy kierowców · Wypożyczalnie samochodów · Księgowość i podatki w transporcie · ZUS i składki · Kierowcy spoza UE

## Dostosowanie

- `zrodla.md`: lista zaufanych źródeł (dopisuj i usuwaj dowolnie).
- `.claude/commands/tematy.md`: instrukcja komendy (obszary, format raportu, zasady).

Raport to materiał roboczy. Przed publikacją zweryfikuj treść przepisów w źródłach.
