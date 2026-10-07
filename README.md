# transport-search

Komendy Claude Code, które wyszukują nowości dla wybranej firmy, proponują tematy na blog i piszą posty na Facebooka. Działają w terminalu, w ramach subskrypcji Claude Code (bez klucza API).

## Kolejność pracy

Otwórz Claude Code w tym katalogu (`claude`) i wpisz:

1. `/firma mawex` — wybierz firmę (albo dodaj nową, patrz niżej).
2. `/content KSeF, e-CMR` — opcjonalnie ustaw, czego szukać. Bez tego używane są obszary domyślne z profilu firmy.
3. `/tematy 14` — wyszukaj nowości z ostatnich 14 dni (maks. 365). Raport zapisze się w `firmy/<id>/raporty/`.
4. `/post 3` — napisz post o temacie nr 3 z ostatniego raportu. Post zapisze się w `firmy/<id>/posty/`.

Wybór firmy i zakres są zapamiętywane w plikach, więc po ponownym otwarciu terminala wystarczy `/tematy` i `/post`.

## Firmy

- `/firma` — pokazuje aktywną firmę i listę firm.
- `/firma <id>` — przełącza firmę, np. `/firma mawex`.
- `/firma dodaj <id> <adres strony>` — dodaje firmę, np. `/firma dodaj ksiegarnia-xyz https://ksiegarnia-xyz.pl`. Claude czyta stronę, tworzy profil, proponuje obszary domyślne i zaufane źródła dla branży. Bez adresu zapyta o informacje w rozmowie. Id: małe litery, cyfry i myślniki.

Każda firma ma katalog `firmy/<id>/`:

- `profil.md` — nazwa, strona, czym się zajmuje, dla kogo piszemy, czego szukać, ton postów, zachęta w poście, hashtagi, obszary domyślne. Edytuj swobodnie.
- `zrodla.md` — zaufane źródła, od których zaczyna się wyszukiwanie.
- `zakres.md` — bieżący zakres ustawiony przez `/content`.
- `raporty/`, `posty/` — wyniki.

## Zakres wyszukiwania (`/content`)

- `/content KSeF, e-CMR, tachografy` — tematy oddzielone przecinkami (najwyżej 8). `/tematy` szuka wtedy tylko w nich.
- `/content` — pokazuje bieżący zakres.
- `/content domyślne` — przywraca obszary domyślne z profilu firmy.

## Posty na Facebooka (`/post`)

- Pełna postać: `/post <nr> [ekspercki|lekki] [limit znaków] [wskazówki]`, np. `/post 3 lekki 500 podkreśl termin`.
- Domyślnie styl ekspercki i limit 1000 znaków (zakres 200–3000). Znaki liczy `node`, więc licznik zgadza się z rzeczywistą długością (także z emoji).
- Ton, zachętę i hashtagi bierze z profilu firmy. W tej samej sesji możesz poprosić np. „skróć do 500 znaków” albo „wersja lekka”.
- `/post` bez numeru wypisuje listę tematów z ostatniego raportu.
- Przy limicie poniżej ok. 400 znaków sam link, zachęta i hashtagi mogą nie zmieścić się w limicie. Komenda wtedy ostrzega.

Bez otwierania sesji: `claude -p "/tematy 21"`.

Raporty i posty to materiał roboczy. Przed publikacją zweryfikuj informacje w źródłach.
