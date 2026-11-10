---
name: post
description: Pisze post na Facebooka, LinkedIn albo Instagram aktywnej firmy na podstawie tematu z jej ostatniego raportu skilla tematy i zapisuje go do pliku. Użyj, gdy użytkownik prosi o post o temacie z raportu (np. „post 3 na LinkedIn”).
argument-hint: "<nr> [facebook|linkedin|instagram] [ekspercki|lekki] [limit znaków] [wskazówki]"
allowed-tools: WebFetch, Read, Write, Glob, Bash(date:*), Bash(node:*)
---

# Post w mediach społecznościowych

Piszesz posty dla firmy opisanej w jej profilu. Na podstawie tematu z raportu skilla `tematy` napiszesz jeden post na wybraną platformę i zapiszesz go do pliku. Katalog `firmy/` leży w bieżącym katalogu roboczym użytkownika. Zasady dla platform są w katalogu tego skilla: `references/platformy.md` — przeczytaj je przed pisaniem.

## Dane wejściowe

- **Argumenty** to tekst wpisany po nazwie skilla, np. `/post 3 linkedin lekki 800 podkreśl termin`. Gdy wywołujesz skill na prośbę z rozmowy, wyciągnij te same informacje z prośby.
- **Dzisiejsza data:** ustal ją poleceniem `date +%Y-%m-%d`.

## Krok 0 — aktywna firma

Sprawdź narzędziem Glob, czy istnieje `firmy/aktywna.md`, i przeczytaj go. Id firmy to pierwsza niepusta linia. Jeśli pliku nie ma albo nie istnieje `firmy/<id>/profil.md`, napisz: „Nie wybrano firmy. Użyj /firma <id> albo /firma dodaj <id> <adres strony>. Zainstaluj też skill firma, jeśli go nie masz.” i zakończ.

Dalej `F` oznacza katalog `firmy/<id>`. Przeczytaj `F/profil.md`. Pola **Nazwa**, **Strona / blog**, **Dla kogo piszemy**, **Ton postów**, **Zachęta w poście**, **Hashtagi**, **Platformy** i **Język treści** określają, jak piszesz. Jeśli nie ma pola **Język treści**, pisz po polsku.

**Pola dla platform:** jeśli profil ma wersję pola dla wybranej platformy, np. `**Hashtagi (Instagram):**` albo `**Ton postów (LinkedIn):**`, użyj jej zamiast pola ogólnego.

## Krok 1 — argumenty

Podziel argumenty na słowa (po spacjach).
- **Numer tematu** to pierwsze słowo. Musi być dodatnią liczbą całkowitą. Jeśli go brakuje albo nie jest liczbą, przejdź do kroku 2, wypisz listę tematów (patrz „Brak tematu” w kroku 3) i zakończ.
- Z pozostałych słów, w dowolnej kolejności (bez względu na wielkość liter):
  - **Platforma:** `facebook`/`fb`, `linkedin`/`li` albo `instagram`/`ig`. Domyślnie pierwsza platforma z pola **Platformy** w profilu, a jeśli pola nie ma — Facebook. Jeśli wybranej platformy nie ma w polu **Platformy**, post i tak powstaje, a w terminalu dopisz notkę „Platformy <nazwa> nie ma w profilu firmy.”.
  - **Styl:** `ekspercki` albo `lekki`. Domyślnie `ekspercki`.
  - **Limit znaków:** pierwsza liczba całkowita. Domyślnie limit domyślny platformy (Facebook 1000, LinkedIn 1300, Instagram 1000). Poniżej 200 → 200, powyżej maksimum platformy (Facebook 3000, LinkedIn 3000, Instagram 2200) → maksimum, a w terminalu dopisz notkę „Limit przycięto do N znaków.”.
  - **Wskazówki:** cała reszta tekstu w oryginalnej kolejności. Może być pusta.

## Krok 2 — ostatni raport

Znajdź raporty narzędziem Glob (`F/raporty/*.md`). Weź tylko pliki o nazwie `RRRR-MM-DD.md` albo `RRRR-MM-DD-N.md` (N to liczba). Inne nazwy, np. `2026-10-02-test.md`, pomiń. Ostatni raport to plik z najpóźniejszą datą, a przy tej samej dacie ten z najwyższym N (plik bez N liczy się jako N = 1). Jeśli nie ma żadnego raportu, napisz: „Brak raportów firmy <nazwa>. Najpierw uruchom /tematy. Zainstaluj też skill tematy, jeśli go nie masz.” i zakończ.

Przeczytaj ten raport narzędziem Read.

## Krok 3 — temat

Tematy w raporcie to sekcje `## <nr>. <tytuł>`. Weź sekcję o podanym numerze: tytuł, obszar, status, datę, opis („Co się dzieje”), „Kogo dotyczy”, „Dlaczego warto o tym napisać” i listę źródeł z adresami URL.

**Brak tematu:** jeśli numer nie istnieje (albo go nie podano), wypisz w terminalu:

```
Firma: <nazwa>
W raporcie <ścieżka raportu> jest K tematów:
1. <tytuł>
2. <tytuł>
…
Użycie: /post <nr> [facebook|linkedin|instagram] [ekspercki|lekki] [limit znaków] [wskazówki]
```

i zakończ bez zapisywania pliku.

## Krok 4 — źródła

Otwórz źródła tematu narzędziem WebFetch, zaczynając od oficjalnych. Wystarczą 1–3 źródła z potwierdzonymi faktami. Gdy strona się nie otwiera, przejdź do następnej. Jeśli żadne źródło się nie otworzy, oprzyj się na treści raportu i zapamiętaj notkę „Źródeł nie udało się otworzyć — post na podstawie raportu.”.

## Krok 5 — post

Napisz post dla odbiorców z profilu, według zasad platformy z `references/platformy.md` oraz:
- **Styl `ekspercki`:** chwytliwy początek, potem 2–4 krótkie akapity: co się dzieje, kogo dotyczy, od kiedy lub kiedy.
- **Styl `lekki`:** zacznij od pytania do czytelnika. Prosty język, mniej szczegółów: tylko najważniejsze fakty i termin.
- Uwzględnij **Ton postów** z profilu i wskazówki z argumentów, jeśli są.
- Tylko fakty ze źródeł i raportu. Nie zgaduj dat, kwot ani treści przepisów.
- W języku treści z profilu. Daty w formacie zwyczajowym dla tego języka (po polsku DD.MM.RRRR).
- Facebook i LinkedIn: pełny adres URL najlepszego źródła (oficjalne ma pierwszeństwo). Instagram: bez żadnego URL w treści — usuń adresy także z zachęty i wskazówek.
- Przed hashtagami **Zachęta w poście** z profilu, a na końcu hashtagi w liczbie właściwej dla platformy, dobrane do tematu na podstawie pola **Hashtagi**.
- Długość: najwyżej tyle znaków, ile wynosi limit, łącznie ze spacjami, emoji, linkiem i hashtagami.
- Instagram: osobno przygotuj **Propozycję grafiki** (nie wchodzi do treści posta).

## Krok 6 — liczenie znaków

Nie licz znaków samodzielnie. Zapisz sam tekst posta (bez propozycji grafiki) narzędziem Write do `F/posty/.ostatni-post.txt` (nadpisując), a potem policz, podstawiając id firmy:

```
node -e "const t=require('fs').readFileSync('firmy/<id>/posty/.ostatni-post.txt','utf8').trim();console.log([...t].length)"
```

Jeśli wynik przekracza limit, skróć post (zachowując link — na Facebooku i LinkedIn — oraz zachętę i hashtagi; na Instagramie nie dodawaj linku), zapisz ponownie i policz jeszcze raz. Najwyżej 2 skrócenia. Jeśli nadal jest za długi, zostaw ostatnią wersję i zapamiętaj ostrzeżenie „Post przekracza limit (N z L znaków) — skróć ręcznie.”. Jeśli `node` nie działa, oszacuj długość, zostaw post i dopisz notkę „Nie udało się policzyć znaków (brak node).”. Przy limicie poniżej ok. 400 znaków link, zachęta i hashtagi mogą same nie zmieścić się w limicie — wtedy dopisz ostrzeżenie.

## Krok 7 — zapis

Platforma w nazwie pliku: `facebook`, `linkedin` albo `instagram`. Nazwa pliku: `F/posty/<dzisiejsza data RRRR-MM-DD>-temat-<nr>-<platforma>.md`. Sprawdź narzędziem Glob (`F/posty/<data>-temat-<nr>-<platforma>*.md`), czy taki plik istnieje. Jeśli tak, użyj `-2`, a jeśli i ten istnieje, `-3` itd. Nigdy nie nadpisuj istniejącego posta. Zapisz plik narzędziem Write w formacie:

```markdown
# Post <Facebook|LinkedIn|Instagram> — <tytuł tematu>
Firma: <nazwa> · Raport: <ścieżka raportu> · Temat nr <nr> · Platforma: <platforma> · Styl: <styl> · Limit: <limit> · Znaków: <wynik z kroku 6>
<notki i ostrzeżenia, jeśli są>

Źródła użyte w poście:
- <tytuł> — <url>

Propozycja grafiki: <tylko Instagram; na innych platformach pomiń tę linię>

---

<treść posta dokładnie taka jak w .ostatni-post.txt>
```

## Krok 8 — terminal

Wypisz:
1. „Firma: <nazwa> · Platforma: <platforma>”,
2. treść posta między liniami `─────` (do skopiowania),
3. na Instagramie: „Propozycja grafiki: …”,
4. `Znaków: N / limit` oraz notki i ostrzeżenia,
5. ścieżkę zapisanego pliku,
6. podpowiedź: „Możesz poprosić np. »skróć do 500 znaków«, »wersja lekka« albo »wersja na LinkedIn«.”

## Kolejne prośby w tej samej sesji

- Poprawka tego samego posta (np. „skróć do 500 znaków”, „wersja lekka”): popraw, policz znaki jak w kroku 6 i zaktualizuj zapisany plik (ten sam plik, nowa treść i liczba znaków).
- Wersja na inną platformę (np. „wersja na Instagram”): napisz nowy post według zasad tej platformy i zapisz **nowy** plik z jej nazwą, jak w kroku 7.
