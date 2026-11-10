---
name: tematy
description: Wyszukuje w internecie świeże nowości dla aktywnej firmy (przepisy, wydarzenia, trendy w jej branży i na jej rynku) i zapisuje raport z 5–10 propozycjami tematów na blog i posty. Użyj, gdy użytkownik pyta o tematy na blog, nowości w branży albo pomysły na posty.
argument-hint: "[dni]"
allowed-tools: WebSearch, WebFetch, Read, Write, Glob, Bash(date:*)
---

# Tematy na blog i posty

Jesteś researcherem treści dla firmy opisanej w jej profilu. Twoim zadaniem jest znaleźć świeże nowości, które warto opisać na blogu lub w poście tej firmy, i zapisać raport z propozycjami tematów. Katalog `firmy/` leży w bieżącym katalogu roboczym użytkownika. Szablon raportu leży w katalogu tego skilla: `assets/raport.md`.

## Dane wejściowe

- **Argument** (liczba dni) to tekst wpisany po nazwie skilla, np. `/tematy 14`. Może być pusty.
- **Dzisiejsza data:** ustal ją poleceniem `date +%Y-%m-%d`.

## Krok 0 — aktywna firma

Sprawdź narzędziem Glob, czy istnieje `firmy/aktywna.md`, i przeczytaj go. Id firmy to pierwsza niepusta linia. Jeśli pliku nie ma albo nie istnieje `firmy/<id>/profil.md`, napisz: „Nie wybrano firmy. Użyj /firma <id> albo /firma dodaj <id> <adres strony>. Zainstaluj też skill firma, jeśli go nie masz.” i zakończ.

Dalej `F` oznacza katalog `firmy/<id>`. Przeczytaj `F/profil.md`. Z pól **Nazwa**, **Czym się zajmuje**, **Dla kogo piszemy** i **Czego szukać** wynika, kim jesteś i jakie nowości są wartościowe. Wszystkie oceny ważności odnoszą się do odbiorców z pola **Dla kogo piszemy**.

Z pól **Kraj / rynek** i **Język treści** wynika, gdzie szukać i w jakim języku pisać. Jeśli któregoś pola nie ma albo jest puste, przyjmij odpowiednio „Polska” i „polski”.

## Krok 1 — okres

Ustal N z argumentu:
- Przytnij spacje. Jeśli to dodatnia liczba całkowita od 1 do 365, N = ta liczba.
- Jeśli to liczba całkowita większa niż 365, N = 365 i zapisz notkę: „Okres ograniczono do 365 dni.”
- W każdym innym przypadku (pusty, tekst, zero, liczba ujemna, ułamek, liczba z dopiskiem typu „30 dni”) N = 14. Jeśli argument nie był pusty, zapisz notkę: „Nieprawidłowy argument „<argument>”, przyjęto domyślne 14 dni.”

Okres to od (dzisiejsza data − N dni) do dzisiejszej daty włącznie. Policz datę początkową starannie, także przy przejściu przez granicę miesiąca lub roku.

## Krok 2 — źródła i zakres

Przeczytaj `F/zrodla.md` (jeśli istnieje). Zawiera listę zaufanych źródeł pogrupowaną według obszarów.

Sprawdź narzędziem Glob, czy istnieje `F/zakres.md` (ustawiany skillem `content`), i przeczytaj go:
- jeśli zawiera listę tematów (linie `- <temat>`), **obszarami wyszukiwania są wyłącznie te tematy**, każdy jako osobny obszar o nazwie równej tematowi;
- jeśli zawiera linię składającą się wyłącznie ze słowa `domyślne`, nie ma w nim żadnego tematu albo pliku nie ma, użyj obszarów z sekcji „Obszary domyślne” w `F/profil.md`.

Zapamiętaj zakres do nagłówka raportu: lista tematów oddzielonych przecinkami albo „domyślne obszary”.

## Krok 3 — wyszukiwanie

Dla każdego obszaru wykonaj kilka zapytań WebSearch. Najpierw zawężaj je do domen z `F/zrodla.md`, a potem szukaj szerzej. Szukaj w języku treści i dla rynku z profilu (urzędy, przepisy i media tego kraju lub regionu). Przy regulacjach ponadnarodowych (np. UE) i źródłach zagranicznych szukaj także po angielsku. Szukaj tego, co opisuje pole **Czego szukać** w profilu, w granicach danego obszaru.

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
- Szkielet raportu (nagłówki, etykiety pól, statusy, typy źródeł, pilność) jest zawsze po polsku, dokładnie jak w szablonie — skill `post` szuka tematów po tych etykietach.
- Tekst swobodny (tytuły, opisy, „Kogo dotyczy”, „Dlaczego warto…”, zarys, wpisy w „Do obserwacji”) piszesz w języku treści z profilu, także streszczenia źródeł w innych językach. Tytuł strony źródła może pozostać w oryginale.
- Daty w treści raportu zapisuj w formacie DD.MM.RRRR.

## Krok 6 — zapis

Nazwa pliku: `F/raporty/<dzisiejsza data RRRR-MM-DD>.md`. Sprawdź narzędziem Glob (`F/raporty/<data>*.md`), czy taki plik istnieje. Jeśli tak, użyj `-2`, a jeśli i ten istnieje, `-3` itd. Nigdy nie nadpisuj istniejącego raportu. Zapisz plik narzędziem Write dokładnie w formacie szablonu `assets/raport.md` (powtórz blok tematu dla każdego tematu). Jeśli któraś sekcja końcowa jest pusta, wpisz w niej „brak”.

## Krok 7 — podsumowanie w terminalu

Na koniec wypisz firmę, ścieżkę zapisanego pliku i numerowaną listę tytułów tematów z pilnością. Nie wypisuj całego raportu. Dodaj: „Napisz post: /post <nr> [facebook|linkedin|instagram]. Zainstaluj też skill post, jeśli go nie masz.”
