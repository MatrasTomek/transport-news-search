# Tematy i posty dla firmy — 4 skille dla agenta AI

Paczka czterech skilli, które dla wybranej firmy wyszukują w internecie świeże nowości z jej branży i rynku, proponują tematy na blog i piszą posty na **Facebooka, LinkedIn i Instagram**. Działa dla dowolnej firmy, branży i kraju — wystarczy podać adres strony.

| Skill | Co robi |
|---|---|
| `firma` | dodaje firmę na podstawie jej strony (profil, obszary, zaufane źródła), przełącza aktywną firmę |
| `content` | ustawia, czego szukać: własne tematy albo obszary domyślne z profilu |
| `tematy` | wyszukuje nowości z ostatnich N dni i zapisuje raport z 5–10 tematami (każdy ze źródłami) |
| `post` | pisze post na wybraną platformę o temacie z ostatniego raportu |

## Wymagania

- Agent obsługujący skille w formacie SKILL.md z dostępem do wyszukiwania (WebSearch) i pobierania stron (WebFetch), np. **Claude Code**.
- Opcjonalnie **Node.js** — skill `post` używa go do dokładnego liczenia znaków (także emoji). Bez niego długość jest szacowana.

## Instalacja

Skopiuj cztery foldery (`firma`, `content`, `tematy`, `post`) do katalogu skilli:

- dla wszystkich projektów: `~/.claude/skills/`
- tylko dla jednego projektu: `<projekt>/.claude/skills/`

Jeśli pobrałeś pojedyncze pliki zip, rozpakuj każdy do folderu o nazwie skilla, np. `~/.claude/skills/post/SKILL.md`.

Nie instaluj w tym samym miejscu wersji polskiej i angielskiej — obie mają skill `post`.

## Kolejność pracy

Uruchom agenta w katalogu, w którym mają leżeć dane (np. `claude` w nowym folderze), i wpisz:

1. `/firma dodaj kawiarnia-nowa https://kawiarnia-nowa.pl` — agent czyta stronę, tworzy profil, proponuje obszary i źródła. Sprawdź i popraw `firmy/kawiarnia-nowa/profil.md`.
2. `/content dotacje, targi branżowe` — opcjonalnie zawęź wyszukiwanie. Bez tego używane są obszary domyślne z profilu.
3. `/tematy 14` — nowości z ostatnich 14 dni (maks. 365). Raport trafia do `firmy/<id>/raporty/`.
4. `/post 3 linkedin` — post o temacie nr 3 z ostatniego raportu. Post trafia do `firmy/<id>/posty/`.

Pełna postać: `/post <nr> [facebook|linkedin|instagram] [ekspercki|lekki] [limit znaków] [wskazówki]`. W tej samej sesji możesz poprosić np. „skróć do 500 znaków” albo „wersja na Instagram”.

Przy kilku firmach przełączaj je komendą `/firma <id>`. `/firma` bez argumentów pokazuje listę.

## Pliki danych

Wszystko leży w katalogu roboczym, w folderze `firmy/`:

- `aktywna.md` — id aktywnej firmy,
- `<id>/profil.md` — nazwa, strona, branża, odbiorcy, **Kraj / rynek**, **Język treści**, **Platformy**, ton, zachęta, hashtagi, obszary domyślne. Edytuj swobodnie. Ton, zachęta i hashtagi mogą mieć wersje dla platform, np. `**Hashtagi (Instagram):**`,
- `<id>/zrodla.md` — zaufane źródła, od których zaczyna się wyszukiwanie,
- `<id>/zakres.md` — bieżący zakres ustawiony skillem `content`,
- `<id>/raporty/`, `<id>/posty/` — wyniki.

## Platformy

| | Facebook | LinkedIn | Instagram |
|---|---|---|---|
| Limit domyślny / maks. | 1000 / 3000 | 1300 / 3000 | 1000 / 2200 |
| Link | w treści | w treści | „link w bio”, URL tylko w pliku |
| Hashtagi | 3–5 | 3–5 | 8–15 |
| Dodatkowo | — | — | propozycja grafiki |

## Uwaga

Raporty i posty to materiał roboczy. Przed publikacją zweryfikuj informacje w źródłach.

## Dla twórcy paczki

- `node agensi/validate.mjs pl` — sprawdza frontmatter, pliki i treść skilli.
- `node agensi/pack.mjs pl` — tworzy w `dist/` zip każdego skilla i `pl-pack.zip` z całą paczką.
