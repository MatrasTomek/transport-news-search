# Skille na Agensi (PL + EN) — plan wdrożenia

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Cel:** dwie paczki po 4 skille SKILL.md (`agensi/pl/`, `agensi/en/`), uniwersalne dla dowolnej firmy, branży i kraju, z `/post` na Facebook, LinkedIn i Instagram, gotowe do spakowania w zip i wgrania na Agensi.

**Architektura:** każdy skill to samodzielny folder (`SKILL.md` + `assets/`/`references/`). Treść powstaje z obecnych komend `.claude/commands/*.md` przez uogólnienie (rynek/język z profilu) i rozszerzenie `post` o platformy. Dwa skrypty Node bez zależności: walidator (`agensi/validate.mjs`) i pakowacz (`agensi/pack.mjs`, własny zapis ZIP przez `zlib`, bo w systemie nie ma `zip`).

**Stos:** Markdown (SKILL.md), Node.js 22 (`node:fs`, `node:zlib`, `node:test`).

**Spec:** `docs/superpowers/specs/2026-10-29-agensi-skille-design.md`

## Ograniczenia globalne

- **Bez commitów i pushów.** Kroki „commit” są pominięte, a git obsługuje użytkownik. `docs/` zostaje lokalnie.
- Nie zmieniać `.claude/commands/*` ani `firmy/`.
- `name` we frontmatterze = nazwa folderu, tylko `[a-z0-9-]`. `description` ma 1–3 zdania.
- W treści SKILL.md nie ma `` !` `` ani `$ARGUMENTS`.
- `disable-model-invocation: true` tylko w `firma`, `content`, `company` i `scope`.
- Foldery danych: PL `firmy/<id>/{profil.md,zrodla.md,zakres.md,raporty/,posty/}` + `firmy/aktywna.md`. EN `companies/<id>/{profile.md,sources.md,scope.md,reports/,posts/}` + `companies/active.md`.
- Nowe pola profilu: PL `**Kraj / rynek:**`, `**Język treści:**`, `**Platformy:**`. EN `**Market:**`, `**Content language:**`, `**Platforms:**`. Warianty dla platform: `**Hashtagi (Instagram):**`, `**Hashtags (Instagram):**` itp.
- Limity postów: FB 1000/3000, LinkedIn 1300/3000, Instagram 1000/2200, minimum zawsze 200.
- Szkielet raportu w języku skilla, a tekst swobodny w języku treści.
- Brak pól w PL oznacza Polskę i polski. Brak `Market` w EN oznacza wyszukiwanie globalne i notkę `Market not set in profile — searched globally.`. Brak języka w EN oznacza angielski.
- W EN żadnych śladów PL i transportu: `KSeF`, `e-CMR`, `tachograf`, `Polsk`, `po polsku`. W PL też bez `KSeF`, `e-CMR` i `tachograf` w przykładach.

## Na co zwrócić uwagę w przeglądzie

1. Stare profile bez nowych pól (Mawex) z paczką PL: `tematy` i `post` muszą działać jak dziś, bez pytań. Sprawdzane w zadaniu 6 (próba na kopii `firmy/mawex`).
2. `/post` dla platformy, która nie jest na liście **Platformy** w profilu: post powstaje, a terminal pokazuje notkę, bez odmowy. Opisane w zadaniu 4 i sprawdzane w zadaniu 6.
3. Instagram: URL nie może trafić do treści posta, a liczba znaków liczy się bez bloku metadanych. Sprawdzane w zadaniu 6.
4. Skill zainstalowany bez pozostałych, np. tylko `post` bez `firma`: ma się pojawić komunikat „Zainstaluj też skill firma…”, a nie błąd. Walidator sprawdza, czy ten tekst jest w każdym SKILL.md poza firma/company (zadanie 1).
5. Pakowanie: zip pojedynczego skilla ma `SKILL.md` w korzeniu, bez README i LISTING. Test w zadaniu 1.

---

### Zadanie 1: walidator i pakowacz

**Pliki:**
- Utwórz: `agensi/validate.mjs`, `agensi/pack.mjs`, `agensi/test/tools.test.mjs`, `agensi/test/fixtures/` (mini paczka z 1 poprawnym i 1 błędnym skillem)

**Interfejsy:**
- Produkuje: `node agensi/validate.mjs <pl|en>` zwraca 0 albo 1 i listę błędów `<skill>: <opis>`. `node agensi/pack.mjs <pl|en>` tworzy `agensi/<lang>/dist/<skill>.zip` ×4 oraz `agensi/<lang>/dist/<lang>-pack.zip` (całość: README, LISTING, 4 foldery).
- Eksporty do testów: `validatePack(dir) -> string[]` (błędy), `writeZip(outPath, entries: {path, data: Buffer}[])`.

- [ ] **Krok 1: testy** w `agensi/test/tools.test.mjs` (`node:test`):
  - `validatePack(fixtures/bad)` zgłasza: `name` ≠ folder, brak `description`, występuje `$ARGUMENTS`, występuje `` !` ``, w skillu innym niż firma/company brakuje wskazówki instalacji.
  - Przy katalogu z `en` w ścieżce zgłasza słowa zakazane (`KSeF`, `e-CMR`, `tachograf`, `Polsk`, `po polsku`, bez względu na wielkość liter).
  - Sprawdza, że każda ścieżka `assets/…` i `references/…` wspomniana w SKILL.md istnieje.
  - `validatePack(fixtures/good)` zwraca `[]`.
  - `writeZip` + `unzip -l` (albo odczyt przez `unzip -p`) daje te same pliki i treść. Zip skilla ma `SKILL.md` w korzeniu, a nie `skill/SKILL.md`.
- [ ] **Krok 2:** `node --test agensi/test/` ma się nie powieść (brak modułów).
- [ ] **Krok 3:** implementacja. Frontmatter parsować prosto (linie `klucz: wartość` między `---`). Wskazówka instalacji to regex `/Zainstaluj też skill|Also install the .* skill/`. ZIP: metoda 8 (deflate) przez `zlib.deflateRawSync`, CRC przez `zlib.crc32`, lokalne nagłówki + central directory + EOCD, ścieżki z `/`.
- [ ] **Krok 4:** `node --test agensi/test/` przechodzi.

### Zadanie 2: paczka PL — `firma` i `content`

**Pliki:**
- Utwórz: `agensi/pl/firma/SKILL.md`, `agensi/pl/firma/assets/{profil.md,zrodla.md,zakres.md}`, `agensi/pl/content/SKILL.md`
- Źródło: `.claude/commands/firma.md`, `.claude/commands/content.md`

**Interfejsy:**
- Produkuje: format profilu (szablon `assets/profil.md`) z polami jak dziś oraz `**Kraj / rynek:**`, `**Język treści:**`, `**Platformy:**`. Zachęta, Ton i Hashtagi mają opcjonalne warianty `(Facebook|LinkedIn|Instagram)` opisane w komentarzu szablonu. Od tego formatu zależą zadania 3–5.

- [ ] **Krok 1:** `firma/SKILL.md`. Frontmatter: `name: firma`, `description`, `argument-hint`, `allowed-tools: WebFetch, WebSearch, Read, Write, Glob, Bash(date:*)`, `disable-model-invocation: true`. Kroki jak w `firma.md`, z tymi zmianami:
  - datę ustala się słownie,
  - szablony czyta się z `assets/` obok SKILL.md, a zapisuje do `firmy/` w katalogu roboczym,
  - rynek i język rozpoznaje się ze strony; gdy się nie da, wpisuje „Polska”/„polski” i informuje w terminalu,
  - Platformy domyślnie „facebook”,
  - źródła szuka się „dla tej branży na rynku z profilu”,
  - przykłady są neutralne.
- [ ] **Krok 2:** `content/SKILL.md` (`name: content`, `disable-model-invocation: true`, wskazówka „Zainstaluj też skill firma”). Przykład `/content` neutralny, np. `/content dotacje, targi, nowe przepisy`.
- [ ] **Krok 3:** `node agensi/validate.mjs pl` nie zgłasza błędów dla tych dwóch skilli (pozostałych jeszcze nie ma; walidator nie wymaga kompletu).

### Zadanie 3: paczka PL — `tematy`

**Pliki:**
- Utwórz: `agensi/pl/tematy/SKILL.md`, `agensi/pl/tematy/assets/raport.md`
- Źródło: `.claude/commands/tematy.md`

**Interfejsy:**
- Konsumuje: pola profilu z zadania 2.
- Produkuje: raport z sekcjami `## <nr>. <tytuł>` i etykietami jak dziś („Co się dzieje”, „Źródła:”…), zgodny ze starymi raportami. Szablon leży w `assets/raport.md`.

- [ ] **Krok 1:** SKILL.md jak `tematy.md`, z tymi zmianami:
  - wyszukiwanie w języku treści i dla rynku z profilu (brak pól oznacza Polskę i polski); przy przepisach ponadnarodowych także po angielsku,
  - tekst swobodny w języku treści, etykiety po polsku,
  - daty DD.MM.RRRR,
  - w nagłówku raportu dochodzi linia `Rynek: … · Język: …`,
  - szablon przeniesiony do `assets/raport.md`.
  Obecne zasady jakości, statusy i pilność zostają bez zmian.
- [ ] **Krok 2:** `node agensi/validate.mjs pl` bez błędów.

### Zadanie 4: paczka PL — `post` z platformami

**Pliki:**
- Utwórz: `agensi/pl/post/SKILL.md`, `agensi/pl/post/references/platformy.md`
- Źródło: `.claude/commands/post.md`

**Interfejsy:**
- Konsumuje: profil (zadanie 2), raport (zadanie 3).
- Produkuje: plik `firmy/<id>/posty/<RRRR-MM-DD>-temat-<nr>-<facebook|linkedin|instagram>.md` z nagłówkiem `# Post <Facebook|LinkedIn|Instagram> — <tytuł>`. Plik tymczasowy do liczenia to `posty/.ostatni-post.txt`.

- [ ] **Krok 1:** `references/platformy.md` z tabelą ze specu (limity, link, hashtagi, ton, „Propozycja grafiki” dla Instagrama).
- [ ] **Krok 2:** SKILL.md jak `post.md`, z tymi zmianami:
  - argumenty: platforma (słowo `facebook|linkedin|instagram|fb|li|ig`), styl i limit w dowolnej kolejności; domyślna platforma to pierwsza z **Platformy**, a bez pola Facebook; platforma spoza listy w profilu daje notkę „Platformy <x> nie ma w profilu.”, ale post powstaje,
  - limity i clamp według platformy,
  - pole wariantowe (np. `Hashtagi (Instagram)`) ma pierwszeństwo przed ogólnym,
  - Instagram: bez URL w treści, zachęta „link w bio”, URL tylko w sekcji źródeł pliku; „Propozycja grafiki” w pliku i terminalu, poza treścią liczoną przez node,
  - posty w języku treści,
  - prośba w tej samej sesji o „wersję na <platformę>” tworzy nowy plik.
  Liczenie przez node bez zmian.
- [ ] **Krok 3:** `node agensi/validate.mjs pl` bez błędów.

### Zadanie 5: README, LISTING i paczka EN

**Pliki:**
- Utwórz: `agensi/pl/README.md`, `agensi/pl/LISTING.md`, `agensi/en/{README.md,LISTING.md}`, `agensi/en/company/SKILL.md` + `assets/{profile.md,sources.md,scope.md}`, `agensi/en/scope/SKILL.md`, `agensi/en/topics/SKILL.md` + `assets/report.md`, `agensi/en/post/SKILL.md` + `references/platforms.md`

**Interfejsy:**
- Konsumuje: gotowe skille PL (zadania 2–4) jako wzorzec 1:1.
- Produkuje: EN z argumentami `add` (zamiast `dodaj`), `default` (zamiast `domyślne`), `expert|casual`; statusy `in force|adopted|draft|announced|news`; typy źródeł `official|trade media`; daty YYYY-MM-DD; etykiety raportu `What's happening`, `Who it affects`, `Why write about it`, `Suggested outline`, `Sources:`.

- [ ] **Krok 1:** README PL i EN: czym jest paczka, wymagania, instalacja (`~/.claude/skills/` albo `.claude/skills/`, oraz zip z Agensi), kolejność pracy, pliki danych, ostrzeżenie o konflikcie nazwy `post` między PL i EN, „materiał roboczy”. Do tego `node agensi/pack.mjs` dla twórcy.
- [ ] **Krok 2:** LISTING PL i EN: tytuł, krótki opis (≤ 160 znaków), długi opis, 4 skille po 1 zdaniu, tagi, przykład sesji.
- [ ] **Krok 3:** naturalne tłumaczenie 4 skilli na EN, z regułami braku pól z ograniczeń globalnych. Wskazówka instalacji: „Also install the company skill”.
- [ ] **Krok 4:** `node agensi/validate.mjs pl && node agensi/validate.mjs en` bez błędów, a następnie `node agensi/pack.mjs pl && node agensi/pack.mjs en` i `unzip -l` każdego zipa (SKILL.md w korzeniu, 5 zipów na język).

### Zadanie 6: próba na żywo

**Pliki:** tylko w scratchpadzie, repo bez zmian.

- [ ] **Krok 1:** w katalogu tymczasowym skopiować `agensi/pl/*` do `.claude/skills/`, a `firmy/mawex` (bez raportów) do `firmy/` z `aktywna.md = mawex`. Uruchomić `claude -p "/tematy 7"`, a potem `claude -p "/post 1 instagram"`. Oczekiwane: raport z linią `Rynek: Polska · Język: polski`, post bez `http` w treści, linia „Propozycja grafiki”, plik `…-temat-1-instagram.md`.
- [ ] **Krok 2:** to samo dla EN z nowym profilem `/company add` (np. `claude -p "/company add bluebottle https://bluebottlecoffee.com"`), potem `/topics 7` i `/post 1 linkedin`. Oczekiwane: treść po angielsku, Market rozpoznany, plik `…-linkedin.md`.
- [ ] **Krok 3:** jeśli `claude -p` nie działa w sandboxie, zapisać to i przekazać użytkownikowi gotowe komendy do ręcznej próby.
