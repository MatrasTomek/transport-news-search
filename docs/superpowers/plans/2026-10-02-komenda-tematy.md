# Komenda `/tematy` — plan implementacji

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Komenda Claude Code `/tematy [dni]`, która przeszukuje internet pod kątem nowości z 6 obszarów branży transportowej i zapisuje raport z propozycjami tematów na blog mawex-biuro.pl do `raporty/RRRR-MM-DD.md`.

**Architecture:** Całość to pliki Markdown, bez kodu wykonywalnego. `.claude/commands/tematy.md` to prompt z frontmatterem: dozwolone narzędzia, wstrzyknięta przez `!` bieżąca data i lista istniejących raportów. `zrodla.md` to edytowalna lista źródeł, którą komenda czyta przy każdym uruchomieniu. Testy polegają na uruchomieniu komendy nieinteraktywnie (`claude -p "/tematy 14"`) i sprawdzeniu raportu poleceniami `grep`/`ls`.

**Tech Stack:** Claude Code (custom slash commands, WebSearch, WebFetch), Markdown, bash (tylko w krokach testowych).

**Spec:** `docs/superpowers/specs/2026-10-02-tematy-blog-transport-design.md`

## Global Constraints

- Wszystkie treści dla użytkownika (raport, komunikaty, README) po polsku.
- Okres: argument w dniach, domyślnie 14; niepoprawny argument → 14 + informacja w nagłówku raportu.
- 5–10 tematów na raport; każdy temat ma ≥ 1 link do źródła.
- Plik raportu: `raporty/RRRR-MM-DD.md`; przy kolizji przyrostek `-2`, `-3`, …; nigdy nie nadpisywać.
- Daty w treści raportu: DD.MM.RRRR.
- Statusy: `obowiązuje` / `uchwalone` / `projekt` / `zapowiedź`.
- Pilność: 🔴 ≤ 30 dni do wejścia w życie, 🟡 ≤ 90 dni, ⚪ później / nieznana / projekt.
- 6 obszarów: Prawo transportowe; Czas pracy kierowców; Wypożyczalnie samochodów; Księgowość i podatki w transporcie; ZUS i składki; Kierowcy spoza UE.
- Raport kończy się adnotacją: *Materiał roboczy wygenerowany automatycznie. Przed publikacją zweryfikuj treść przepisów w źródłach.*
- Frontmatter komendy: `description`, `argument-hint: [dni]`, `allowed-tools` z WebSearch, WebFetch, Read, Write.

## Review Focus

- Argument z dodatkiem lub w złym formacie (`"30 dni"`, `"-5"`, `" 21 "`, `"abc"`, `"0"`) → poprawna dodatnia liczba całkowita z `" 21 "` jest akceptowana po przycięciu spacji, a reszta daje 14 dni i notkę. Bardzo duże wartości (np. `1000`) są ograniczane do 365 z notką. Test: Task 3, krok 3.
- Okres przechodzący przez granicę miesiąca lub roku (np. `/tematy 40` na początku października) → zakres dat w nagłówku liczony poprawnie. Test: Task 3, krok 2 (40 dni od 02.10 daje 23.08).
- Źródła anglojęzyczne (EUR-Lex, komunikaty UE) → treść tematu jest po polsku, a tytuł źródła może zostać w oryginale. Test: Task 3, krok 4.
- Dwa raporty tego samego dnia → drugi dostaje `-2`, a pierwszy pozostaje bez zmian (porównanie sumy kontrolnej). Test: Task 3, krok 5.
- Obszar bez nowości → trafia do sekcji „Obszary bez istotnych nowości”, a nie do słabego tematu. Test: Task 3, krok 2 (grep sekcji); sama ocena jakości jest ręczna, w kroku 6.

---

## Struktura plików

| Plik | Odpowiedzialność |
|---|---|
| `zrodla.md` | Lista zaufanych źródeł pogrupowana wg 6 obszarów (edytuje użytkownik) |
| `.claude/commands/tematy.md` | Definicja komendy: proces, zasady jakości, format raportu |
| `raporty/.gitkeep` | Utrzymuje katalog raportów w repozytorium |
| `README.md` | Krótka instrukcja użycia |

---

### Task 1: Lista źródeł `zrodla.md`

**Files:**
- Create: `zrodla.md`

**Interfaces:**
- Consumes: nic
- Produces: plik `zrodla.md` w katalogu głównym repozytorium z nagłówkami `## Ogólne — oficjalne`, `## Ogólne — branżowe i prawne` oraz po jednym `## <Obszar>` dla każdego z 6 obszarów. Każda pozycja ma postać `- Nazwa — domena — uwaga`. Task 2 czyta ten plik po nazwie `zrodla.md`.

- [ ] **Step 1: Sprawdź dostępność kandydackich domen**

Run:
```bash
for d in dziennikustaw.gov.pl isap.sejm.gov.pl www.gov.pl legislacja.gov.pl eur-lex.europa.eu www.gitd.gov.pl www.pip.gov.pl www.zus.pl www.podatki.gov.pl ksef.podatki.gov.pl trans.info 40ton.net www.infor.pl podatki.gazetaprawna.pl www.prawo.pl zmpd.pl tlp.org.pl pzwlp.pl www.samar.pl www.gov.pl/web/udsc; do
  printf '%-30s ' "$d"; curl -s -o /dev/null -w '%{http_code}\n' -L --max-time 15 "https://$d" || echo ERR
done
```
Expected: dla większości kod `200`. `403` z treścią „Blocked by network policy” oznacza firewall sandboxa, a nie martwą domenę. Takie domeny zostaw i odnotuj w raporcie z zadania. Usuń tylko domeny z błędem DNS lub kodem `404`/`410`.

- [ ] **Step 2: Utwórz `zrodla.md`**

Treść (pomiń domeny odrzucone w kroku 1):

```markdown
# Zaufane źródła

Komenda `/tematy` zaczyna wyszukiwanie od tych źródeł, potem szuka szerzej.
Źródła oficjalne mają pierwszeństwo przy weryfikacji. Listę możesz dowolnie edytować.
Format pozycji: `- Nazwa — domena — uwaga`.

## Ogólne — oficjalne
- Dziennik Ustaw — dziennikustaw.gov.pl — opublikowane akty prawne
- ISAP — isap.sejm.gov.pl — internetowy system aktów prawnych, teksty jednolite
- Rządowe Centrum Legislacji — legislacja.gov.pl — projekty ustaw i rozporządzeń
- EUR-Lex — eur-lex.europa.eu — prawo UE (rozporządzenia, dyrektywy)

## Ogólne — branżowe i prawne
- Prawo.pl — prawo.pl — zmiany w prawie, komentarze
- Infor — infor.pl — prawo, podatki, kadry
- Gazeta Prawna Podatki — podatki.gazetaprawna.pl — podatki i księgowość

## Prawo transportowe
- Ministerstwo Infrastruktury — gov.pl/web/infrastruktura — komunikaty, projekty
- GITD — gitd.gov.pl — Inspekcja Transportu Drogowego, kontrole, tachografy
- Trans.info — trans.info — portal branży transportowej
- 40ton — 40ton.net — portal przewoźników
- ZMPD — zmpd.pl — Zrzeszenie Międzynarodowych Przewoźników Drogowych
- TLP — tlp.org.pl — Związek Pracodawców Transport i Logistyka Polska

## Czas pracy kierowców
- PIP — pip.gov.pl — Państwowa Inspekcja Pracy
- GITD — gitd.gov.pl — kontrole czasu pracy
- Trans.info — trans.info — interpretacje, orzecznictwo

## Wypożyczalnie samochodów
- PZWLP — pzwlp.pl — Polski Związek Wynajmu i Leasingu Pojazdów
- Samar — samar.pl — rynek motoryzacyjny, flota, najem
- Ministerstwo Cyfryzacji (CEPiK) — gov.pl/web/cyfryzacja — rejestracja pojazdów

## Księgowość i podatki w transporcie
- Ministerstwo Finansów / podatki.gov.pl — podatki.gov.pl — VAT, akcyza, objaśnienia
- KSeF — ksef.podatki.gov.pl — Krajowy System e-Faktur
- Gazeta Prawna Podatki — podatki.gazetaprawna.pl

## ZUS i składki
- ZUS — zus.pl — składki, A1, delegowanie
- Ministerstwo Rodziny, Pracy i Polityki Społecznej — gov.pl/web/rodzina

## Kierowcy spoza UE
- Urząd do Spraw Cudzoziemców — gov.pl/web/udsc — pobyt, legalizacja
- Ministerstwo Rodziny, Pracy i Polityki Społecznej — gov.pl/web/rodzina — zezwolenia na pracę
- GITD — gitd.gov.pl — świadectwa kierowcy
```

- [ ] **Step 3: Zweryfikuj strukturę**

Run: `grep -c '^## ' zrodla.md`
Expected: `8` (2 ogólne + 6 obszarów)

Run: `grep -E '^## ' zrodla.md`
Expected: zawiera dokładnie te nagłówki obszarów: `Prawo transportowe`, `Czas pracy kierowców`, `Wypożyczalnie samochodów`, `Księgowość i podatki w transporcie`, `ZUS i składki`, `Kierowcy spoza UE`.

- [ ] **Step 4: Commit**

```bash
git add zrodla.md
git commit -m "Lista zaufanych źródeł dla komendy /tematy

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Komenda `/tematy`, katalog raportów, README

**Files:**
- Create: `.claude/commands/tematy.md`
- Create: `raporty/.gitkeep`
- Create: `README.md`

**Interfaces:**
- Consumes: `zrodla.md` z Task 1 (czytany po nazwie)
- Produces: komendę `/tematy [dni]`, która zapisuje `raporty/RRRR-MM-DD[-N].md` w formacie ze specyfikacji. Task 3 testuje ją przez `claude -p "/tematy <arg>"`.

- [ ] **Step 1: Napisz test dymny (powinien się nie powieść)**

Run:
```bash
claude -p "/tematy 14" --output-format text 2>&1 | head -5; ls raporty/ 2>&1
```
Expected: komenda nieznana albo Claude traktuje tekst jako zwykły prompt, a `ls` zgłasza brak katalogu `raporty/`.

- [ ] **Step 2: Utwórz `raporty/.gitkeep`**

```bash
mkdir -p raporty && touch raporty/.gitkeep
```

- [ ] **Step 3: Utwórz `.claude/commands/tematy.md`**

Linie `!`…`` są wykonywane przez Claude Code przed wysłaniem promptu, a ich wynik zastępuje te linie. Wymagają odpowiednich wpisów `Bash(...)` w `allowed-tools`.

````markdown
---
description: Wyszukaj nowości w przepisach transportowych i zaproponuj tematy na blog mawex-biuro.pl
argument-hint: [dni]
allowed-tools: WebSearch, WebFetch, Read, Write, Glob, Bash(date:*), Bash(ls:*)
---

Jesteś researcherem bloga biura rachunkowego MAWEX (https://mawex-biuro.pl/blog), które obsługuje firmy transportowe, przewoźników i wypożyczalnie samochodów. Twoim zadaniem jest znaleźć świeże zmiany w przepisach i praktyce, które warto opisać na blogu, i zapisać raport z propozycjami tematów.

## Dane wejściowe

- Argument (liczba dni): `$ARGUMENTS`
- Dzisiejsza data: !`date +%Y-%m-%d`
- Istniejące raporty: !`ls raporty/ 2>/dev/null`

## Krok 1 — okres

Ustal N z argumentu:
- Przytnij spacje. Jeśli to dodatnia liczba całkowita od 1 do 365, N = ta liczba.
- Jeśli to liczba całkowita większa niż 365, N = 365 i zapisz notkę: „Okres ograniczono do 365 dni.”
- W każdym innym przypadku (pusty, tekst, zero, liczba ujemna, ułamek, liczba z dopiskiem typu „30 dni”) N = 14. Jeśli argument nie był pusty, zapisz notkę: „Nieprawidłowy argument „<argument>”, przyjęto domyślne 14 dni.”

Okres to od (dzisiejsza data − N dni) do dzisiejszej daty włącznie. Policz datę początkową starannie, także przy przejściu przez granicę miesiąca lub roku.

## Krok 2 — źródła

Przeczytaj plik `zrodla.md`. Zawiera listę zaufanych źródeł pogrupowaną według obszarów.

## Krok 3 — wyszukiwanie

Dla każdego z 6 obszarów wykonaj kilka zapytań WebSearch. Najpierw zawężaj je do domen z `zrodla.md`, a potem szukaj szerzej. Szukaj po polsku; przy prawie UE także po angielsku.

1. **Prawo transportowe**: Pakiet Mobilności, licencje, tachografy (w tym inteligentne), myto/e-TOLL, ustawa o transporcie drogowym, rozporządzenia UE.
2. **Czas pracy kierowców**: rozporządzenie 561/2006, delegowanie kierowców, diety i ryczałty, kontrole PIP/GITD, orzecznictwo.
3. **Wypożyczalnie samochodów**: najem krótkoterminowy, ubezpieczenia, CEPiK, VAT od najmu, wymogi wobec przedsiębiorców.
4. **Księgowość i podatki w transporcie**: KSeF, VAT w transporcie międzynarodowym, akcyza, leasing i amortyzacja pojazdów, ulgi, JPK.
5. **ZUS i składki**: podstawa wymiaru u kierowców, delegowanie (A1), zmiany w składkach przedsiębiorców.
6. **Kierowcy spoza UE**: zezwolenia na pracę, świadectwa kierowcy, wymiana praw jazdy, legalizacja pobytu.

## Krok 4 — weryfikacja

Obiecujące wyniki otwórz przez WebFetch i ustal:
- **datę publikacji lub zmiany**, która musi mieścić się w okresie (inaczej odrzuć),
- **status**: `obowiązuje` / `uchwalone` / `projekt` / `zapowiedź`,
- **datę wejścia w życie**, jeśli jest podana,
- **typ źródła**: `oficjalne` (Dziennik Ustaw, ISAP, gov.pl, legislacja.gov.pl, EUR-Lex, GITD, PIP, ZUS, MF) albo `medium branżowe`.

Gdy strona nie otwiera się (paywall, blokada, błąd), poszukaj innego źródła tej samej informacji. Jeśli nie znajdziesz potwierdzenia, informacja może trafić najwyżej do sekcji „Do obserwacji” z dopiskiem „(niezweryfikowane)”. Informacja bez ustalonej daty publikacji nie jest tematem. Może trafić najwyżej do „Do obserwacji”.

## Krok 5 — selekcja

Wybierz od 5 do 10 tematów najbardziej istotnych dla klientów biura rachunkowego obsługującego transport. Newsy o tej samej zmianie połącz w jeden temat. Jeśli zweryfikowanych tematów jest mniej niż 5, podaj tyle, ile jest, i nie dopychaj słabych. Obszary bez nowości wypisz w sekcji „Obszary bez istotnych nowości”.

Pilność (liczona od dzisiejszej daty do daty wejścia w życie):
- 🔴 w ciągu 30 dni lub już obowiązuje od niedawna
- 🟡 w ciągu 31–90 dni
- ⚪ później, data nieznana albo projekt/zapowiedź

## Zasady jakości (obowiązkowe)

- Każdy temat ma co najmniej jeden link do źródła. Bez źródła nie ma tematu.
- Nie zgaduj dat, liczb ani treści przepisów. Podawaj tylko to, co jest w źródle.
- Jeśli temat potwierdzają wyłącznie media branżowe, zaznacz to przy źródłach.
- Cała treść raportu jest po polsku, także streszczenia źródeł anglojęzycznych. Tytuł strony źródła może pozostać w oryginale.
- Daty w treści raportu zapisuj w formacie DD.MM.RRRR.

## Krok 6 — zapis

Nazwa pliku: `raporty/<dzisiejsza data RRRR-MM-DD>.md`. Jeśli taki plik jest na liście istniejących raportów, użyj `-2`, a jeśli i ten istnieje, `-3` itd. Nigdy nie nadpisuj istniejącego raportu. Zapisz plik narzędziem Write dokładnie w tym formacie:

```markdown
# Tematy na blog — RRRR-MM-DD
Okres: ostatnie N dni (DD.MM.RRRR–DD.MM.RRRR) · Tematów: K
<notka o argumencie, jeśli dotyczy>

## Podsumowanie
| # | Tytuł | Obszar | Status | Pilność |
|---|-------|--------|--------|---------|
| 1 | … | … | … | 🔴 wchodzi w życie DD.MM.RRRR |

---

## 1. <Proponowany tytuł wpisu>
**Obszar:** … · **Status:** … · **Wchodzi w życie:** DD.MM.RRRR (lub „nieznana”)

**Co się zmienia:** 2–4 zdania.
**Kogo dotyczy:** …
**Dlaczego warto o tym napisać:** znaczenie dla klientów biura rachunkowego.
**Proponowany zarys wpisu:**
- punkt 1
- punkt 2
- punkt 3
**Źródła:**
- [Tytuł strony](https://…) — oficjalne / medium branżowe, DD.MM.RRRR

---

## Obszary bez istotnych nowości
- <obszar>: brak istotnych zmian w okresie.

## Do obserwacji
- <projekt lub zapowiedź, krótko + link> (niezweryfikowane — jeśli dotyczy)

---
*Materiał roboczy wygenerowany automatycznie. Przed publikacją zweryfikuj treść przepisów w źródłach.*
```

Jeśli któraś sekcja końcowa jest pusta, wpisz w niej „brak”.

## Krok 7 — podsumowanie w terminalu

Na koniec wypisz ścieżkę zapisanego pliku i numerowaną listę tytułów tematów z pilnością. Nie wypisuj całego raportu.
````

- [ ] **Step 4: Utwórz `README.md`**

```markdown
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
```

- [ ] **Step 5: Sprawdź, czy komenda jest rozpoznawana**

Run:
```bash
claude -p "/tematy 7" --output-format text 2>&1 | tail -15; ls raporty/
```
Expected: na końcu wyjścia jest ścieżka `raporty/2026-10-02.md` (data dnia uruchomienia) i lista tytułów, a `ls` pokazuje ten plik. Jeśli firewall sandboxa blokuje WebSearch lub WebFetch, odnotuj to i przejdź dalej. Pełny test wykona wtedy użytkownik w Task 3.

- [ ] **Step 6: Usuń raport testowy i zrób commit**

Raporty z testów nie trafiają do repozytorium.
```bash
rm -f raporty/2026-*.md
git add .claude/commands/tematy.md raporty/.gitkeep README.md
git commit -m "Komenda /tematy, katalog raportów i README

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Testy end-to-end i poprawki promptu

**Files:**
- Modify (tylko w razie niepowodzeń): `.claude/commands/tematy.md`, `zrodla.md`

**Interfaces:**
- Consumes: komenda `/tematy` z Task 2, `zrodla.md` z Task 1
- Produces: zweryfikowaną komendę; raporty testowe nie są commitowane

- [ ] **Step 1: Przygotuj czysty katalog raportów**

```bash
rm -f raporty/2026-*.md && ls raporty/
```
Expected: tylko `.gitkeep` (`ls -A`)

- [ ] **Step 2: Pełny przebieg z okresem przez granicę miesiąca**

Run:
```bash
claude -p "/tematy 40" --output-format text 2>&1 | tail -15; R=$(ls raporty/2026-*.md | head -1); echo "$R"
grep -c '^## [0-9]\+\. ' "$R"
grep -E '^Okres:' "$R"
grep -E '^## (Podsumowanie|Obszary bez istotnych nowości|Do obserwacji)$' "$R"
grep -c 'Materiał roboczy wygenerowany automatycznie' "$R"
awk '/^## [0-9]+\. /{t=1;l=0} t&&/\]\(https?:\/\//{l=1} /^---$/{if(t&&!l)print "TEMAT BEZ LINKU"; t=0}' "$R"
```
Expected:
- liczba tematów od 5 do 10 (mniej jest dopuszczalne tylko wtedy, gdy w sekcji „Obszary bez istotnych nowości” są wypisane obszary),
- linia `Okres:` pokazuje `ostatnie 40 dni (23.08.2026–02.10.2026)` przy uruchomieniu 02.10.2026; dla innej daty sprawdź `date -d '-40 days' +%d.%m.%Y`,
- wszystkie 3 nagłówki sekcji są obecne,
- adnotacja występuje dokładnie 1 raz,
- brak linii `TEMAT BEZ LINKU`.

Jeśli coś nie przechodzi, popraw odpowiedni fragment `tematy.md`, wyczyść raporty (krok 1) i powtórz krok.

- [ ] **Step 3: Nieprawidłowe argumenty**

Run:
```bash
for a in "abc" "30 dni" "-5" "0" "1000"; do
  rm -f raporty/2026-*.md
  claude -p "/tematy $a" --output-format text > /dev/null 2>&1
  R=$(ls raporty/2026-*.md | head -1); echo "== $a"; sed -n '2,3p' "$R"
done
```
Expected:
- `abc`, `30 dni`, `-5`, `0` → `Okres: ostatnie 14 dni (…)` oraz notka „Nieprawidłowy argument … przyjęto domyślne 14 dni.”
- `1000` → `Okres: ostatnie 365 dni (…)` oraz notka „Okres ograniczono do 365 dni.”

Ten krok wykonuje 5 pełnych wyszukiwań. Jeśli to za drogie, ogranicz go do `abc` i `1000`.

- [ ] **Step 4: Źródła anglojęzyczne i jakość treści (wyrywkowo)**

Otwórz raport z kroku 2 i wybierz 3 tematy, w tym jeden z linkiem do eur-lex.europa.eu lub innej strony anglojęzycznej, jeśli taki jest. Dla każdego:
- otwórz link i sprawdź, że strona istnieje,
- sprawdź, że data mieści się w okresie,
- sprawdź, że „Co się zmienia” zgadza się z treścią źródła,
- sprawdź, że opis jest po polsku.

Expected: wszystkie 3 przechodzą. Każde zmyślenie to błąd: wzmocnij zasady jakości w `tematy.md` i powtórz krok 2.

- [ ] **Step 5: Drugi raport tego samego dnia**

Run:
```bash
R1=$(ls raporty/2026-*.md | grep -v -- '-[0-9]\.md$' | head -1); S1=$(sha256sum "$R1")
claude -p "/tematy 7" --output-format text > /dev/null 2>&1
ls raporty/; echo "$S1" | sha256sum -c -
```
Expected: istnieje plik `…-2.md` (lub kolejny numer), a `sha256sum -c` wypisuje `OK`, więc pierwszy raport nie został nadpisany.

- [ ] **Step 6: Ocena użytkownika**

Pokaż użytkownikowi raport z kroku 2 i zapytaj, czy tematy są trafne dla bloga. Zgłoszone uwagi wprowadź w `tematy.md` lub `zrodla.md`.

- [ ] **Step 7: Sprzątanie i commit poprawek (jeśli były)**

```bash
rm -f raporty/2026-*.md
git status --short
git add .claude/commands/tematy.md zrodla.md
git commit -m "Poprawki komendy /tematy po testach

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```
Jeśli `git status` nie pokazuje zmian, pomiń commit.

**Uwaga o sandboxie:** jeśli firewall blokuje WebSearch lub WebFetch, kroki 2–5 wykonuje użytkownik na swoim komputerze (te same komendy). Wykonawca zgłasza wtedy, które kroki przeszły, a które zostały przekazane użytkownikowi.
