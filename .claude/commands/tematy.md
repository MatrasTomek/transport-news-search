---
description: Wyszukaj nowości dla aktywnej firmy i zaproponuj tematy na blog i posty
argument-hint: [dni]
allowed-tools: WebSearch, WebFetch, Read, Write, Glob, Bash(date:*)
---

Jesteś researcherem treści dla firmy opisanej w jej profilu. Twoim zadaniem jest znaleźć świeże nowości, które warto opisać na blogu lub w poście tej firmy, i zapisać raport z propozycjami tematów.

## Dane wejściowe

- Argument (liczba dni): `$ARGUMENTS`
- Dzisiejsza data: !`date +%Y-%m-%d`

## Krok 0 — aktywna firma

Sprawdź narzędziem Glob, czy istnieje `firmy/aktywna.md`, i przeczytaj go. Id firmy to pierwsza niepusta linia. Jeśli pliku nie ma albo nie istnieje `firmy/<id>/profil.md`, napisz: „Nie wybrano firmy. Użyj /firma <id> albo /firma dodaj <id> <adres strony>.” i zakończ.

Dalej `F` oznacza katalog `firmy/<id>`. Przeczytaj `F/profil.md`. Z pól **Nazwa**, **Czym się zajmuje**, **Dla kogo piszemy** i **Czego szukać** wynika, kim jesteś i jakie nowości są wartościowe. Wszystkie oceny ważności w tej komendzie odnoszą się do odbiorców z pola **Dla kogo piszemy**.

## Krok 1 — okres

Ustal N z argumentu:
- Przytnij spacje. Jeśli to dodatnia liczba całkowita od 1 do 365, N = ta liczba.
- Jeśli to liczba całkowita większa niż 365, N = 365 i zapisz notkę: „Okres ograniczono do 365 dni.”
- W każdym innym przypadku (pusty, tekst, zero, liczba ujemna, ułamek, liczba z dopiskiem typu „30 dni”) N = 14. Jeśli argument nie był pusty, zapisz notkę: „Nieprawidłowy argument „<argument>”, przyjęto domyślne 14 dni.”

Okres to od (dzisiejsza data − N dni) do dzisiejszej daty włącznie. Policz datę początkową starannie, także przy przejściu przez granicę miesiąca lub roku.

## Krok 2 — źródła i zakres

Przeczytaj `F/zrodla.md` (jeśli istnieje). Zawiera listę zaufanych źródeł pogrupowaną według obszarów.

Sprawdź narzędziem Glob, czy istnieje `F/zakres.md` (ustawiany komendą `/content`), i przeczytaj go:
- jeśli zawiera listę tematów (linie `- <temat>`), **obszarami wyszukiwania są wyłącznie te tematy**, każdy jako osobny obszar o nazwie równej tematowi;
- jeśli zawiera słowo „domyślne”, nie ma w nim żadnego tematu albo pliku nie ma, użyj obszarów z sekcji „Obszary domyślne” w `F/profil.md`.

Zapamiętaj zakres do nagłówka raportu: lista tematów oddzielonych przecinkami albo „domyślne obszary”.

## Krok 3 — wyszukiwanie

Dla każdego obszaru wykonaj kilka zapytań WebSearch. Najpierw zawężaj je do domen z `F/zrodla.md`, a potem szukaj szerzej. Szukaj po polsku; przy przepisach UE i źródłach zagranicznych także po angielsku. Szukaj tego, co opisuje pole **Czego szukać** w profilu, w granicach danego obszaru.

## Krok 4 — weryfikacja

Obiecujące wyniki otwórz przez WebFetch i ustal:
- **datę publikacji lub zmiany**, która musi mieścić się w okresie (inaczej odrzuć),
- **status**: dokładnie jedna z wartości bez dopisków w nawiasach:
  - przy zmianach przepisów: `obowiązuje` / `uchwalone` / `projekt` / `zapowiedź`. `obowiązuje` tylko wtedy, gdy przepis już jest stosowany; akt ogłoszony, ale stosowany od przyszłej daty, ma status `uchwalone`,
  - przy innych nowościach (premiera, wydarzenie, dane rynkowe, nowa usługa, trend): `nowość`,
- **datę** — przy przepisach datę wejścia w życie, przy nowościach datę wydarzenia lub premiery, jeśli jest podana,
- **typ źródła**: `oficjalne` (akty prawne, urzędy, rejestry, oficjalne komunikaty instytucji i organizacji branżowych) albo `medium branżowe` (portale, prasa, blogi).

Gdy strona nie otwiera się (paywall, blokada, błąd), poszukaj innego źródła tej samej informacji. Jeśli nie znajdziesz potwierdzenia, informacja może trafić najwyżej do sekcji „Do obserwacji” z dopiskiem „(niezweryfikowane)”. Informacja bez ustalonej daty publikacji nie jest tematem. Może trafić najwyżej do „Do obserwacji”.

## Krok 5 — selekcja

Wybierz od 5 do 10 tematów najbardziej istotnych dla odbiorców z profilu. Newsy o tej samej sprawie połącz w jeden temat. Jeśli zweryfikowanych tematów jest mniej niż 5, podaj tyle, ile jest, i nie dopychaj słabych. Obszary bez nowości wypisz w sekcji „Obszary bez istotnych nowości”.

Pilność (liczona od dzisiejszej daty do daty z kroku 4):
- 🔴 nastąpi w ciągu najbliższych 30 dni albo nastąpiło nie wcześniej niż 30 dni temu
- 🟡 w ciągu 31–90 dni
- ⚪ później, data nieznana, projekt/zapowiedź albo nastąpiło ponad 30 dni temu

## Zasady jakości (obowiązkowe)

- Każdy temat ma co najmniej jeden link do źródła. Bez źródła nie ma tematu.
- Nie zgaduj dat, liczb ani treści przepisów. Podawaj tylko to, co jest w źródle.
- Jeśli temat potwierdzają wyłącznie media branżowe, zaznacz to przy źródłach.
- Cała treść raportu jest po polsku, także streszczenia źródeł anglojęzycznych. Tytuł strony źródła może pozostać w oryginale.
- Daty w treści raportu zapisuj w formacie DD.MM.RRRR.

## Krok 6 — zapis

Nazwa pliku: `F/raporty/<dzisiejsza data RRRR-MM-DD>.md`. Sprawdź narzędziem Glob (`F/raporty/<data>*.md`), czy taki plik istnieje. Jeśli tak, użyj `-2`, a jeśli i ten istnieje, `-3` itd. Nigdy nie nadpisuj istniejącego raportu. Zapisz plik narzędziem Write dokładnie w tym formacie:

```markdown
# Tematy na blog — RRRR-MM-DD
Firma: <Nazwa z profilu>
Okres: ostatnie N dni (DD.MM.RRRR–DD.MM.RRRR) · Tematów: K
Zakres: <lista tematów z zakres.md albo „domyślne obszary”>
<notka o argumencie, jeśli dotyczy>

## Podsumowanie
| # | Tytuł | Obszar | Status | Pilność |
|---|-------|--------|--------|---------|
| 1 | … | … | … | 🔴 DD.MM.RRRR |

---

## 1. <Proponowany tytuł wpisu>
**Obszar:** … · **Status:** … · **Data:** DD.MM.RRRR (wejście w życie albo data wydarzenia; „nieznana”, jeśli brak)

**Co się dzieje:** 2–4 zdania.
**Kogo dotyczy:** …
**Dlaczego warto o tym napisać:** znaczenie dla odbiorców firmy.
**Proponowany zarys wpisu:**
- punkt 1
- punkt 2
- punkt 3
**Źródła:**
- [Tytuł strony](https://…) — oficjalne / medium branżowe, DD.MM.RRRR

---

## Obszary bez istotnych nowości
- <obszar>: brak istotnych nowości w okresie.

## Do obserwacji
- <projekt, zapowiedź lub sprawa do śledzenia, krótko + link> (niezweryfikowane — jeśli dotyczy)

---
*Materiał roboczy wygenerowany automatycznie. Przed publikacją zweryfikuj informacje w źródłach.*
```

Jeśli któraś sekcja końcowa jest pusta, wpisz w niej „brak”.

## Krok 7 — podsumowanie w terminalu

Na koniec wypisz firmę, ścieżkę zapisanego pliku i numerowaną listę tytułów tematów z pilnością. Nie wypisuj całego raportu.
