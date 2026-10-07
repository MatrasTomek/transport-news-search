---
description: Pokaż, przełącz albo dodaj firmę, dla której szukasz tematów i piszesz posty
argument-hint: [<id> | dodaj <id> [adres strony]]
allowed-tools: WebFetch, WebSearch, Read, Write, Glob, Bash(date:*)
---

Zarządzasz profilami firm. Każda firma ma katalog `firmy/<id>/` z plikami `profil.md`, `zrodla.md` i `zakres.md` oraz katalogami `raporty/` i `posty/`. Aktywna firma jest zapisana w `firmy/aktywna.md` (jedna linia: id). Komendy `/content`, `/tematy` i `/post` działają zawsze dla aktywnej firmy.

## Dane wejściowe

- Argumenty: `$ARGUMENTS`
- Dzisiejsza data: !`date +%Y-%m-%d`

Id firmy: tylko małe litery a–z, cyfry i myślniki, np. `mawex`, `ksiegarnia-xyz`.

## Krok 1 — rozpoznaj argumenty

Przytnij spacje i podziel argumenty na słowa.

**A) Brak argumentów.** Znajdź profile narzędziem Glob (`firmy/*/profil.md`) i z każdego przeczytaj pole **Nazwa**. Przeczytaj `firmy/aktywna.md`, jeśli istnieje. Wypisz:

```
Aktywna firma: <id> — <nazwa>   (albo „brak — wybierz firmę”)

Firmy:
  mawex — MAWEX
  …

Użycie:
  /firma <id>                     — przełącz firmę
  /firma dodaj <id> [adres strony] — dodaj nową firmę
Potem: /content … → /tematy <dni> → /post <nr>
```

Niczego nie zapisuj i zakończ.

**B) Pierwsze słowo to `dodaj`.** Przejdź do kroku 2.

**C) Jedno słowo (id).** Jeśli istnieje `firmy/<id>/profil.md`, zapisz `<id>` do `firmy/aktywna.md` (nadpisując) i wypisz „Aktywna firma: <id> — <nazwa>” oraz bieżący zakres z `firmy/<id>/zakres.md`. Jeśli profilu nie ma, wypisz „Nie ma firmy „<id>”.”, listę firm jak w A i zakończ bez zapisu.

## Krok 2 — dodaj firmę

1. **Id** to drugie słowo. Jeśli go brakuje albo zawiera inne znaki niż a–z, 0–9 i myślnik, napisz: „Podaj id firmy, np. /firma dodaj ksiegarnia-xyz https://…” i zakończ. Jeśli `firmy/<id>/profil.md` już istnieje, napisz „Firma <id> już istnieje. Edytuj firmy/<id>/profil.md albo wybierz inne id.” i zakończ.
2. **Informacje o firmie.** Jeśli podano adres strony (trzecie słowo), otwórz ją narzędziem WebFetch. Jeśli to potrzebne, otwórz też podstrony „o nas”, „oferta” lub „kontakt” z tej samej domeny. Ustal: nazwę, czym firma się zajmuje, dla kogo pisze (klienci), styl komunikacji i adres bloga lub strony. Jeśli adresu nie podano albo strona się nie otwiera, zapytaj użytkownika w rozmowie o: nazwę, czym firma się zajmuje, kim są klienci i adres strony. Nie zgaduj faktów o firmie. Brakujące pola opisz jako „do uzupełnienia”.
3. **Obszary domyślne.** Zaproponuj 4–6 obszarów, w których warto szukać nowości dla tej firmy (np. dla księgarni: rynek wydawniczy, nowości i premiery, wydarzenia i targi, przepisy dla handlu i e-commerce, podatki i dotacje dla branży). Każdy obszar ma krótki opis, czego szukać.
4. **Zaufane źródła.** Przez WebSearch znajdź 6–12 wiarygodnych źródeł dla tej branży w Polsce: oficjalne (urzędy, rejestry, organizacje branżowe) i branżowe media. Pogrupuj je według obszarów. Nie wpisuj źródeł, których istnienia nie potwierdziłeś w wynikach wyszukiwania.
5. **Zapisz pliki** narzędziem Write:

`firmy/<id>/profil.md`:
```markdown
# Profil firmy: <nazwa>
<!-- Ten plik czytają komendy /tematy, /post i /content. Edytuj go swobodnie, zachowując nazwy pól. -->

**Nazwa:** <nazwa>
**Strona / blog:** <adres>
**Czym się zajmuje:** <1–2 zdania>
**Dla kogo piszemy:** <odbiorcy postów i wpisów>
**Czego szukać:** <jakie nowości są wartościowe dla tych odbiorców>
**Ton postów:** <np. ciepły i przystępny / rzeczowy i ekspercki>
**Zachęta w poście:** <jedno zdanie zachęty, np. „Zajrzyj do naszej księgarni!”>
**Hashtagi:** <3–6 typowych hashtagów>

## Obszary domyślne
<!-- Używane przez /tematy, gdy zakres jest ustawiony na „domyślne”. Format: - **Nazwa**: czego szukać. -->

- **<obszar>**: <czego szukać>
```

`firmy/<id>/zrodla.md`:
```markdown
# Zaufane źródła — <nazwa>

Komenda `/tematy` zaczyna wyszukiwanie od tych źródeł, potem szuka szerzej. Listę możesz dowolnie edytować.
Format pozycji: `- Nazwa — domena — uwaga`.

## <obszar>
- <Nazwa> — <domena> — <uwaga>
```

`firmy/<id>/zakres.md`:
```markdown
# Zakres wyszukiwania
Ustawiono: DD.MM.RRRR

domyślne
```

Na koniec zapisz `<id>` do `firmy/aktywna.md`.

6. **Terminal.** Wypisz: „Dodano firmę <id> i ustawiono ją jako aktywną.”, treść profilu (pola i obszary), liczbę źródeł oraz:
„Popraw profil w firmy/<id>/profil.md albo napisz, co zmienić. Potem: /content … (albo zostaw domyślne obszary) → /tematy <dni>.”
