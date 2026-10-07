---
description: Napisz post na Facebooka aktywnej firmy na podstawie tematu z jej ostatniego raportu /tematy
argument-hint: <nr> [ekspercki|lekki] [limit znaków] [wskazówki]
allowed-tools: WebFetch, Read, Write, Glob, Bash(date:*), Bash(node:*)
---

Piszesz posty na fanpage na Facebooku firmy opisanej w jej profilu. Na podstawie tematu z raportu komendy `/tematy` napiszesz jeden post i zapiszesz go do pliku.

## Dane wejściowe

- Argumenty: `$ARGUMENTS`
- Dzisiejsza data: !`date +%Y-%m-%d`

## Krok 0 — aktywna firma

Sprawdź narzędziem Glob, czy istnieje `firmy/aktywna.md`, i przeczytaj go. Id firmy to pierwsza niepusta linia. Jeśli pliku nie ma albo nie istnieje `firmy/<id>/profil.md`, napisz: „Nie wybrano firmy. Użyj /firma <id> albo /firma dodaj <id> <adres strony>.” i zakończ.

Dalej `F` oznacza katalog `firmy/<id>`. Przeczytaj `F/profil.md`. Pola **Nazwa**, **Strona / blog**, **Dla kogo piszemy**, **Ton postów**, **Zachęta w poście** i **Hashtagi** określają, jak piszesz.

## Krok 1 — argumenty

Podziel argumenty na słowa (po spacjach).
- **Numer tematu** to pierwsze słowo. Musi być dodatnią liczbą całkowitą. Jeśli go brakuje albo nie jest liczbą, przejdź do kroku 2, wypisz listę tematów (patrz „Brak tematu” w kroku 3) i zakończ.
- Z pozostałych słów, w dowolnej kolejności:
  - **Styl:** słowo `ekspercki` albo `lekki` (bez względu na wielkość liter). Domyślnie `ekspercki`.
  - **Limit znaków:** pierwsza liczba całkowita. Domyślnie 1000. Poniżej 200 → 200, powyżej 3000 → 3000, a w terminalu dopisz notkę „Limit przycięto do N znaków.”.
  - **Wskazówki:** cała reszta tekstu w oryginalnej kolejności. Może być pusta.

## Krok 2 — ostatni raport

Znajdź raporty narzędziem Glob (`F/raporty/*.md`). Weź tylko pliki o nazwie `RRRR-MM-DD.md` albo `RRRR-MM-DD-N.md` (N to liczba). Inne nazwy, np. `2026-10-02-test-40dni.md`, pomiń. Ostatni raport to plik z najpóźniejszą datą, a przy tej samej dacie ten z najwyższym N (plik bez N liczy się jako N = 1). Jeśli nie ma żadnego raportu, napisz: „Brak raportów firmy <nazwa>. Najpierw uruchom /tematy.” i zakończ.

Przeczytaj ten raport narzędziem Read.

## Krok 3 — temat

Tematy w raporcie to sekcje `## <nr>. <tytuł>`. Weź sekcję o podanym numerze: tytuł, obszar, status, datę, opis zmiany lub wydarzenia, „Kogo dotyczy”, „Dlaczego warto o tym napisać” i listę źródeł z adresami URL.

**Brak tematu:** jeśli numer nie istnieje (albo go nie podano), wypisz w terminalu:

```
Firma: <nazwa>
W raporcie <ścieżka raportu> jest K tematów:
1. <tytuł>
2. <tytuł>
…
Użycie: /post <nr> [ekspercki|lekki] [limit znaków] [wskazówki]
```

i zakończ bez zapisywania pliku.

## Krok 4 — źródła

Otwórz źródła tematu narzędziem WebFetch, zaczynając od oficjalnych. Wystarczą 1–3 źródła z potwierdzonymi faktami. Gdy strona się nie otwiera, przejdź do następnej. Jeśli żadne źródło się nie otworzy, oprzyj się na treści raportu i zapamiętaj notkę „Źródeł nie udało się otworzyć — post na podstawie raportu.”.

## Krok 5 — post

Napisz post dla odbiorców z profilu, według zasad:
- **Styl `ekspercki`:** chwytliwe pierwsze zdanie, potem 2–4 krótkie akapity: co się dzieje, kogo dotyczy, od kiedy lub kiedy. 1–3 emoji jako wyróżniki punktów.
- **Styl `lekki`:** zacznij od pytania do czytelnika. Więcej emoji, prosty język, mniej szczegółów: tylko najważniejsze fakty i termin.
- Uwzględnij **Ton postów** z profilu i wskazówki z argumentów, jeśli są.
- Tylko fakty ze źródeł i raportu. Nie zgaduj dat, kwot ani treści przepisów.
- Po polsku. Daty jako DD.MM.RRRR.
- Pełny adres URL najlepszego źródła (oficjalne ma pierwszeństwo).
- Przed hashtagami **Zachęta w poście** z profilu.
- Na końcu 3–5 hashtagów: dobierz do tematu, korzystając z pola **Hashtagi** z profilu.
- Bez formatowania Markdown (bez `**`, `#` nagłówków, list z `-`). Akapity oddzielaj pustą linią.
- Długość: najwyżej tyle znaków, ile wynosi limit, łącznie ze spacjami, emoji, linkiem i hashtagami.

## Krok 6 — liczenie znaków

Nie licz znaków samodzielnie. Zapisz sam tekst posta narzędziem Write do `F/posty/.ostatni-post.txt` (nadpisując), a potem policz (podstaw ścieżkę `F`):

```
node -e "const t=require('fs').readFileSync('firmy/<id>/posty/.ostatni-post.txt','utf8').trim();console.log([...t].length)"
```

Jeśli wynik przekracza limit, skróć post (zachowując link, zachętę i hashtagi), zapisz ponownie i policz jeszcze raz. Najwyżej 2 skrócenia. Jeśli nadal jest za długi, zostaw ostatnią wersję i zapamiętaj ostrzeżenie „Post przekracza limit (N z L znaków) — skróć ręcznie.”. Jeśli `node` nie działa, oszacuj długość, zostaw post i dopisz notkę „Nie udało się policzyć znaków (brak node).”.

## Krok 7 — zapis

Nazwa pliku: `F/posty/<dzisiejsza data RRRR-MM-DD>-temat-<nr>.md`. Sprawdź narzędziem Glob (`F/posty/<data>-temat-<nr>*.md`), czy taki plik istnieje. Jeśli tak, użyj `-2`, a jeśli i ten istnieje, `-3` itd. Nigdy nie nadpisuj istniejącego posta. Zapisz plik narzędziem Write w formacie:

```markdown
# Post FB — <tytuł tematu>
Firma: <nazwa> · Raport: <ścieżka raportu> · Temat nr <nr> · Styl: <styl> · Limit: <limit> · Znaków: <wynik z kroku 6>
<notki i ostrzeżenia, jeśli są>

Źródła użyte w poście:
- <tytuł> — <url>

---

<treść posta dokładnie taka jak w .ostatni-post.txt>
```

## Krok 8 — terminal

Wypisz:
1. „Firma: <nazwa>”,
2. treść posta między liniami `─────` (do skopiowania),
3. `Znaków: N / limit` oraz notki i ostrzeżenia,
4. ścieżkę zapisanego pliku,
5. podpowiedź: „Możesz poprosić np. »skróć do 500 znaków«, »wersja lekka« albo »dodaj przykład«.”

Przy kolejnych prośbach w tej samej sesji popraw post, policz znaki jak w kroku 6 i zaktualizuj zapisany plik (ten sam plik, nowa treść i liczba znaków).
