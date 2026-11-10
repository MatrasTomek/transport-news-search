# Skille na Agensi (PL + EN) — projekt

Data: 29.10.2026

## Po co

Użytkownik chce wystawić program transport-search na marketplace https://agensi.io jako skille do pobrania i zainstalowania u siebie: wersję polską i angielską. Obie wersje mają działać dla **dowolnej firmy z dowolnej branży i kraju**, a nie tylko dla logistyki w Polsce. Posty mają powstawać na **Facebook, LinkedIn i Instagram**.

## Cele

- Dwie paczki gotowe do wgrania: `agensi/pl/` i `agensi/en/` w tym repo.
- W każdej paczce 4 skille w formacie SKILL.md (standard Agensi), odpowiadające obecnym komendom z `.claude/commands/`:

  | Obecna komenda | Skill PL (folder = `name`) | Skill EN (folder = `name`) |
  |---|---|---|
  | `/firma` | `firma` | `company` |
  | `/content` | `content` | `scope` |
  | `/tematy` | `tematy` | `topics` |
  | `/post` | `post` | `post` |

- Uniwersalność: kraj, rynek i język treści wynikają z profilu firmy, a nie z treści skilli.
- `/post` obsługuje 3 platformy.
- Każda paczka ma README z instalacją, kolejnością pracy i wymaganiami, a także gotowe teksty na stronę produktu na Agensi.

## Poza zakresem

- Nie zmieniamy obecnych komend w `.claude/commands/` ani danych w `firmy/` (Mawex, TwojaLogistyka).
- Nie wgrywamy niczego na Agensi, nie ustalamy ceny, nie commitujemy (użytkownik robi to sam).
- Żadnej aplikacji web, API ani hostingu, tylko skille do terminala.

## Antycele (porażka, nawet jeśli „działa”)

- Skill, który po cichu zakłada Polskę, język polski albo branżę transportową w wersji EN.
- Skille, które nie przejdą walidacji Agensi: `name` różne od nazwy folderu albo niedozwolone znaki w `name`.
- Dane klienta (profile, raporty) zapisywane w katalogu instalacji skilla zamiast w katalogu projektu użytkownika.

## Ograniczenia formatu (z agensi.io/learn/skill-md-format-reference)

- Wymagane pola frontmattera: `name` (małe litery, cyfry, myślniki; identyczne z nazwą folderu) i `description` (1–3 zdania: co robi i kiedy użyć).
- Opcjonalne pola: `argument-hint`, `allowed-tools`, `disable-model-invocation` i inne. Agenci, którzy ich nie znają, ignorują je.
- Układ folderu: `SKILL.md`, opcjonalnie `references/`, `assets/` i `scripts/`.
- Każdy skill przechodzi 8-punktowy skan bezpieczeństwa. Szczegóły kryteriów nie są publiczne.

## Decyzje

### Struktura paczki

```
agensi/pl/
  README.md                 # instalacja, kolejność pracy, wymagania
  LISTING.md                # tytuł, opis, tagi na stronę Agensi
  firma/SKILL.md
  firma/assets/profil.md    # szablony przeniesione z treści firma.md
  firma/assets/zrodla.md
  firma/assets/zakres.md
  content/SKILL.md
  tematy/SKILL.md
  tematy/assets/raport.md   # szablon raportu
  post/SKILL.md
  post/references/platformy.md  # zasady per platforma
```

`agensi/en/` ma tę samą strukturę, z angielskimi nazwami folderów i plików: `company/assets/profile.md`, `sources.md`, `scope.md`, `topics/assets/report.md`, `post/references/platforms.md`.

### Miejsce danych

- Dane leżą w katalogu, w którym użytkownik uruchamia agenta: w PL `firmy/`, w EN `companies/`. Struktura jak dziś: `<id>/profil.md|profile.md`, `zrodla.md|sources.md`, `zakres.md|scope.md`, `raporty/|reports/`, `posty/|posts/` oraz plik aktywnej firmy `aktywna.md|active.md`.
- Szablony skill czyta ze swojego folderu `assets/`, ale zapisuje zawsze do katalogu roboczego użytkownika.

### Przenośność

- Zamiast składni typowej tylko dla Claude Code w treści, np. `` !`date` `` i `$ARGUMENTS`, skille opisują krok słowami: „ustal dzisiejszą datę (np. `date +%Y-%m-%d`)” oraz „argumenty to tekst podany po nazwie skilla”. Dzięki temu działają też, gdy agent ładuje skill przez MCP Agensi. Pole `allowed-tools` zostaje we frontmatterze, bo inni agenci je ignorują.
- `disable-model-invocation: true` dla `firma`/`company` i `content`/`scope`, bo tylko zmieniają stan i mają działać wyłącznie na wyraźne wywołanie. `tematy` i `post` mogą być wywoływane automatycznie, a ich `description` zawiera frazy wyzwalające.

### Uniwersalność (obie wersje)

- Nowe pola profilu: **Kraj / rynek** (np. „Polska”, „Germany”, „global”) oraz **Język treści** (raporty i posty powstają w tym języku). `/firma dodaj` ustala je ze strony firmy, a gdy się nie da, pyta.
- `/tematy`: wyszukiwanie najpierw w języku treści i dla danego rynku. Przy przepisach ponadnarodowych (np. UE) szuka też po angielsku. Źródła przy dodawaniu firmy wyszukuje się „dla tej branży na tym rynku”, a nie „w Polsce”.
- Format dat zależy od języka treści: PL DD.MM.RRRR, EN np. YYYY-MM-DD. Statusy w raporcie są w języku skilla (PL: obowiązuje/uchwalone/projekt/zapowiedź/nowość, EN: in force/adopted/draft/announced/news).
- Przykłady w treści skilli są neutralne branżowo (np. księgarnia, kawiarnia, biuro rachunkowe), bez KSeF, e-CMR i tachografów.
- Wersja PL ma domyślnie: rynek Polska, język polski. Wersja EN: brak domyślnego rynku (pyta), język angielski.

### `/post` — platformy

- Wywołanie: `/post <nr> [facebook|linkedin|instagram] [ekspercki|lekki] [limit] [wskazówki]` (EN: `expert|casual`). Słowa w dowolnej kolejności, jak dziś.
- Nowe pole profilu **Platformy** (np. „facebook, linkedin”). Domyślną platformą jest pierwsza pozycja z listy, a jeśli pola nie ma, Facebook.
- Zasady dla platform (w `references/platformy.md`):

  | | Facebook | LinkedIn | Instagram |
  |---|---|---|---|
  | Domyślny / maks. limit | 1000 / 3000 | 1300 / 3000 | 1000 / 2200 |
  | Link | w treści | w treści | nie w treści; zachęta „link w bio”, URL tylko w pliku |
  | Hashtagi | 3–5 | 3–5 | 8–15, osobny blok na końcu |
  | Ton / emoji | jak dziś | profesjonalny, mało emoji; pierwsze 2 linie jako haczyk (widoczne przed „…więcej”) | wizualny, więcej emoji |
  | Dodatek | — | — | linia „Propozycja grafiki” (opis obrazka lub karuzeli) w pliku i terminalu |

  Minimalny limit to 200 dla wszystkich platform.
- Pola profilu **Ton postów**, **Zachęta w poście** i **Hashtagi** mogą mieć wersje dla platform (np. `**Hashtagi (Instagram):**`). Jeśli wersji nie ma, obowiązuje pole ogólne, więc stare profile działają bez zmian.
- Plik: `posty/<data>-temat-<nr>-<platforma>.md`, nagłówek „Post Facebook|LinkedIn|Instagram — <tytuł>”. Reguła `-2`, `-3` (bez nadpisywania) zostaje.
- Liczenie znaków przez `node` zostaje bez zmian, z obecną notką, gdy `node` brak. W tej samej sesji można poprosić np. „wersja na LinkedIn”: powstaje wtedy nowy plik dla innej platformy, a nie nadpisanie.

### Język raportu (decyzja budowniczego)

- Szkielet raportu, czyli nagłówki, etykiety pól, statusy i typy źródeł, jest zawsze w języku skilla. Pozwala to `/post` wyszukiwać sekcje po stałych etykietach. Wersja PL czyta też stare raporty z obecnych komend.
- W języku treści z profilu powstaje tylko tekst swobodny: tytuły, opisy, zarys i posty.

### Brakujące pola profilu (decyzja budowniczego)

- **PL:** brak pola Kraj / rynek lub Język treści oznacza Polskę i język polski. Dzięki temu stare profile działają bez zmian. `/firma dodaj` rozpoznaje rynek i język ze strony, a gdy się nie uda, wpisuje wartości domyślne i informuje o tym w terminalu.
- **EN:** `/company add` pyta o rynek i język, gdy nie da się ich rozpoznać. Jeśli w profilu nie ma pola Market, `/topics` szuka globalnie, a w nagłówku raportu dopisuje notkę „Market not set in profile — searched globally.”. Bez pola języka przyjmuje angielski. W obu wersjach to nie jest ciche założenie.

### Pakowanie pod Agensi (decyzja budowniczego)

Agensi przyjmuje zip z `SKILL.md`. Publicznie nie opisano, jak tworzyć pakiety kilku skilli, dlatego paczka ma obsłużyć oba warianty:
- Każdy folder skilla jest samowystarczalny i można go spakować osobno (zip z `SKILL.md` w korzeniu). Gdy brakuje danych tworzonych przez inny skill, skill podaje, który zainstalować, np. „Zainstaluj też skill firma”.
- W `agensi/<lang>/dist/` znajduje się skrypt (`pack.sh` albo `node`) tworzący 4 osobne zipy i jeden zip z całą paczką. README i LISTING są w paczce, ale nie trafiają do zipów pojedynczych skilli.
- LISTING ma opis całej paczki i krótkie opisy 4 skilli, więc pasuje do obu sposobów wystawienia.

### README i LISTING

- README: czym jest paczka, wymagania (Claude Code lub inny agent z wyszukiwaniem i pobieraniem stron; opcjonalnie Node.js), instalacja (skopiowanie 4 folderów do `~/.claude/skills/` albo `<projekt>/.claude/skills/`), kolejność pracy, opis plików danych, uwaga „materiał roboczy — weryfikuj przed publikacją”.
- README ostrzega, że wersji PL i EN nie należy instalować w tym samym miejscu, bo obie mają skill `post`.
- LISTING: tytuł, krótki i długi opis, tagi, przykład użycia w danym języku.

## Zostawione budowniczemu

- Dokładne brzmienie `description` i tekstów w terminalu oraz przeredagowanie treści skilli pod nowe pola, z zachowaniem logiki obecnych kroków (walidacja argumentów, wybór ostatniego raportu, pilność 🔴🟡⚪, zasady jakości, brak nadpisywania).
- Tłumaczenie na angielski: naturalne, nie dosłowne.
- Ręczna weryfikacja: walidacja frontmattera (name = folder) skryptem oraz próbne uruchomienie skilli w katalogu tymczasowym, jeśli to możliwe.
