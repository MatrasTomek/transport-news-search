# Komenda `/tematy` — wyszukiwanie tematów na blog transportowy

**Data:** 2026-10-02
**Status:** projekt do akceptacji

## Cel

Komenda Claude Code, która po uruchomieniu przeszukuje internet pod kątem nowości w przepisach i praktyce dotyczącej branży transportowej, a następnie zapisuje raport z gotowymi propozycjami tematów na bloga https://mawex-biuro.pl/blog (biuro rachunkowe obsługujące firmy transportowe).

**Kryterium sukcesu:** po uruchomieniu `/tematy` w katalogu `raporty/` powstaje raport z 5–10 propozycjami tematów. Każdy temat ma działające linki do źródeł, a jego daty mieszczą się w zadanym okresie. Treść nadaje się jako punkt wyjścia do napisania wpisu po weryfikacji przez człowieka.

## Założenia i decyzje

| Decyzja | Wybór | Uzasadnienie |
|---|---|---|
| Wynik | Propozycje tematów z uzasadnieniem (nie surowe linki, nie gotowe artykuły) | Wybór użytkownika |
| Silnik | Claude Code w ramach subskrypcji (WebSearch + WebFetch), bez klucza API | Wybór użytkownika |
| Uruchamianie | Własna komenda Claude Code `/tematy [dni]` | Wybór użytkownika; możliwość dopytania o temat w tej samej sesji |
| Zapis | Plik Markdown z datą w `raporty/` | Wybór użytkownika; bez rejestru historii tematów |
| Okres | Parametr w dniach, domyślnie 14 | Uruchamianie nieregularne |
| Źródła | Edytowalna lista zaufanych źródeł + swobodne wyszukiwanie | Wiarygodność przy zachowaniu szerokiego zasięgu |
| Rynek | Polska + przepisy UE dotyczące polskich przewoźników | Założenie zaakceptowane w rozmowie |

Poza zakresem: automatyczne uruchamianie wg harmonogramu, deduplikacja między raportami, generowanie pełnych artykułów, publikacja na blogu.

## Struktura plików

```
transport-search/
  .claude/commands/tematy.md   # definicja komendy (instrukcja dla Claude'a)
  zrodla.md                    # edytowalna lista zaufanych źródeł, pogrupowana wg obszarów
  raporty/                     # raporty: RRRR-MM-DD.md
  docs/superpowers/specs/      # specyfikacje
```

## Uruchamianie

1. Otworzyć Claude Code w katalogu `transport-search/`.
2. Wpisać `/tematy` (14 dni) lub `/tematy N` (ostatnie N dni).
3. Claude czyta `zrodla.md`, wyszukuje, weryfikuje, zapisuje raport i wyświetla w terminalu listę tytułów.
4. W tej samej sesji można dopytać, np. „rozwiń temat 3 w szkic wpisu”.

### Frontmatter komendy

- `description` — krótki opis widoczny na liście komend
- `argument-hint: [dni]`
- `allowed-tools: WebSearch, WebFetch, Read, Write`

## Obszary tematyczne

1. **Prawo transportowe** — Pakiet Mobilności, licencje, tachografy, myto/e-TOLL, ustawa o transporcie drogowym, rozporządzenia UE.
2. **Czas pracy kierowców** — rozporządzenie 561/2006, delegowanie kierowców, diety i ryczałty, kontrole PIP/GITD, orzecznictwo.
3. **Wypożyczalnie samochodów** — najem krótkoterminowy, ubezpieczenia, CEPiK, VAT od najmu, wymogi wobec przedsiębiorców.
4. **Księgowość i podatki w transporcie** — KSeF, VAT w transporcie międzynarodowym, akcyza, leasing i amortyzacja pojazdów, ulgi, JPK.
5. **ZUS i składki** — podstawa wymiaru u kierowców, delegowanie (A1), zmiany w składkach przedsiębiorców.
6. **Kierowcy spoza UE** — zezwolenia na pracę, świadectwa kierowcy, wymiana praw jazdy, legalizacja pobytu.

## Proces

1. **Argument:** odczytać liczbę dni z `$ARGUMENTS`. Jeśli brak, niepoprawna lub ≤ 0, przyjąć 14 i zaznaczyć to w raporcie. Wyliczyć zakres dat od (dziś − N) do dziś.
2. **Źródła:** przeczytać `zrodla.md`.
3. **Wyszukiwanie:** dla każdego z 6 obszarów wykonać kilka zapytań WebSearch, zaczynając od źródeł z `zrodla.md` (np. zapytania zawężone do domeny), a potem szerzej.
4. **Weryfikacja:** obiecujące wyniki otworzyć przez WebFetch i ustalić:
   - datę publikacji / zmiany, która musi mieścić się w okresie,
   - status: *obowiązuje / uchwalone / projekt / zapowiedź*,
   - datę wejścia w życie, jeśli jest znana,
   - typ źródła: *oficjalne* (Dziennik Ustaw/ISAP, gov.pl, EUR-Lex, GITD, PIP, ZUS, MF) albo *medium branżowe*.
5. **Selekcja:** wybrać 5–10 tematów najistotniejszych dla klientów biura rachunkowego obsługującego transport. Newsy o tej samej zmianie połączyć w jeden temat.
6. **Zapis:** utworzyć `raporty/` w razie potrzeby i zapisać `raporty/RRRR-MM-DD.md`. Jeśli plik istnieje, użyć przyrostka `-2`, `-3`, … i nie nadpisywać.
7. **Podsumowanie w terminalu:** ścieżka raportu i lista tytułów.

### Zasady jakości

- Każdy temat musi mieć co najmniej jeden link do źródła; bez źródła nie ma tematu.
- Nie zgadywać dat ani treści przepisów; podawać tylko to, co jest w źródle.
- Temat potwierdzony wyłącznie przez media branżowe oznaczyć jako taki.
- Brak nowości w obszarze zgłosić wprost; nie dopychać słabych tematów.
- Daty w treści raportu w formacie DD.MM.RRRR.

## Format raportu

```markdown
# Tematy na blog — RRRR-MM-DD
Okres: ostatnie N dni (DD.MM–DD.MM.RRRR) · Tematów: K

## Podsumowanie
| # | Tytuł | Obszar | Status | Pilność |
|---|-------|--------|--------|---------|

---

## 1. <Proponowany tytuł wpisu>
**Obszar:** … · **Status:** … · **Wchodzi w życie:** DD.MM.RRRR (lub „nieznana”)

**Co się zmienia:** 2–4 zdania.
**Kogo dotyczy:** …
**Dlaczego warto o tym napisać:** znaczenie dla klientów biura.
**Proponowany zarys wpisu:** 3–5 punktów.
**Źródła:**
- [Tytuł strony](link) — oficjalne / medium branżowe, DD.MM.RRRR

---

## Obszary bez istotnych nowości
- <obszar>: brak zmian w okresie.

## Do obserwacji
- Projekty/zapowiedzi niegotowe na wpis oraz informacje niezweryfikowane (z adnotacją).

---
*Materiał roboczy wygenerowany automatycznie. Przed publikacją zweryfikuj treść przepisów w źródłach.*
```

**Pilność:**
- 🔴 wchodzi w życie w ciągu 30 dni
- 🟡 w ciągu 90 dni
- ⚪ później, data nieznana albo projekt

## Obsługa problemów

| Sytuacja | Zachowanie |
|---|---|
| Niepoprawny argument | Domyślne 14 dni + informacja w nagłówku raportu |
| WebFetch nie działa (paywall, blokada, błąd) | Szukać innego źródła tej samej informacji; bez potwierdzenia temat trafia najwyżej do „Do obserwacji” jako niezweryfikowany |
| Nieustalona data publikacji | Temat odpada lub trafia do „Do obserwacji” |
| Brak katalogu `raporty/` | Utworzyć |
| Raport z dzisiejszą datą istnieje | Przyrostek `-2`, `-3`, … |

## `zrodla.md` — zawartość początkowa

Lista pogrupowana wg 6 obszarów; każda pozycja to nazwa, domena i krótka uwaga. Źródła startowe:

- **Oficjalne:** dziennikustaw.gov.pl, isap.sejm.gov.pl, gov.pl (Ministerstwo Infrastruktury, Ministerstwo Finansów, MRPiPS), legislacja.gov.pl, eur-lex.europa.eu, gitd.gov.pl, pip.gov.pl, zus.pl, podatki.gov.pl, ksef.podatki.gov.pl
- **Branżowe / prawne:** trans.info, 40ton.net, infor.pl, podatki.gazetaprawna.pl, prawo.pl, zmpd.pl (Zrzeszenie Międzynarodowych Przewoźników Drogowych), tlp.org.pl (Transport i Logistyka Polska)
- **Wypożyczalnie / motoryzacja:** pzwlp.pl (Polski Związek Wynajmu i Leasingu Pojazdów), samar.pl

Domeny zostaną sprawdzone przy implementacji, a niedziałające usunięte. Listę użytkownik edytuje ręcznie; komenda nie wymaga określonego formatu poza czytelnym Markdownem.

## Testowanie

Komenda to prompt, nie kod, więc testy są manualne:

1. `/tematy 14` — raport powstał i ma pełną strukturę (tabela, sekcje tematów ze źródłami, sekcje końcowe, adnotacja).
2. Wyrywkowa weryfikacja 2–3 tematów: link działa, data mieści się w okresie, streszczenie zgadza się ze źródłem.
3. Przypadki brzegowe: `/tematy abc` (domyślne 14 dni + adnotacja) i drugie uruchomienie tego samego dnia (plik z `-2`).
4. Ocena trafności tematów przez użytkownika, a potem ewentualne poprawki w `tematy.md` / `zrodla.md`.

Uwaga: w sandboxie część stron może być blokowana przez firewall; pełny test wykonuje użytkownik na swoim komputerze.
