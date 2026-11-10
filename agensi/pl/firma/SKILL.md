---
name: firma
description: Pokazuje, przełącza albo dodaje firmę, dla której skille tematy i post szukają nowości i piszą posty. Użyj, gdy użytkownik chce wybrać firmę albo dodać nową na podstawie adresu jej strony.
argument-hint: "[<id> | dodaj <id> [adres strony]]"
allowed-tools: WebFetch, WebSearch, Read, Write, Glob, Bash(date:*)
disable-model-invocation: true
---

# Firma

Zarządzasz profilami firm. Każda firma ma katalog `firmy/<id>/` z plikami `profil.md`, `zrodla.md` i `zakres.md` oraz katalogami `raporty/` i `posty/`. Aktywna firma jest zapisana w `firmy/aktywna.md` (jedna linia: id). Skille `content`, `tematy` i `post` działają zawsze dla aktywnej firmy.

Katalog `firmy/` leży w bieżącym katalogu roboczym użytkownika (tam, gdzie uruchomiono agenta), a nie w katalogu tego skilla. Szablony plików leżą w katalogu tego skilla: `assets/profil.md`, `assets/zrodla.md` i `assets/zakres.md`.

## Dane wejściowe

- **Argumenty** to tekst wpisany po nazwie skilla, np. `/firma dodaj kawiarnia-nowa https://kawiarnia-nowa.pl`. Może być pusty.
- **Dzisiejsza data:** ustal ją poleceniem `date +%Y-%m-%d`.

Id firmy: tylko małe litery a–z, cyfry i myślniki, np. `kawiarnia-nowa`, `biuro-abc`.

## Krok 1 — rozpoznaj argumenty

Przytnij spacje i podziel argumenty na słowa.

**A) Brak argumentów.** Znajdź profile narzędziem Glob (`firmy/*/profil.md`) i z każdego przeczytaj pole **Nazwa**. Przeczytaj `firmy/aktywna.md`, jeśli istnieje. Wypisz:

```
Aktywna firma: <id> — <nazwa>   (albo „brak — wybierz firmę”)

Firmy:
  <id> — <nazwa>
  …

Użycie:
  /firma <id>                      — przełącz firmę
  /firma dodaj <id> [adres strony] — dodaj nową firmę
Potem: /content … → /tematy <dni> → /post <nr>
```

Jeśli nie ma żadnej firmy, zamiast listy napisz „Brak firm. Dodaj pierwszą: /firma dodaj <id> <adres strony>”. Niczego nie zapisuj i zakończ.

**B) Pierwsze słowo to `dodaj`.** Przejdź do kroku 2.

**C) Jedno słowo (id).** Jeśli istnieje `firmy/<id>/profil.md`, zapisz `<id>` do `firmy/aktywna.md` (nadpisując) i wypisz „Aktywna firma: <id> — <nazwa>” oraz bieżący zakres z `firmy/<id>/zakres.md`. Jeśli profilu nie ma, wypisz „Nie ma firmy „<id>”.”, listę firm jak w A i zakończ bez zapisu.

## Krok 2 — dodaj firmę

1. **Id** to drugie słowo. Jeśli go brakuje albo zawiera inne znaki niż a–z, 0–9 i myślnik, napisz: „Podaj id firmy, np. /firma dodaj kawiarnia-nowa https://…” i zakończ. Jeśli `firmy/<id>/profil.md` już istnieje, napisz „Firma <id> już istnieje. Edytuj firmy/<id>/profil.md albo wybierz inne id.” i zakończ.
2. **Informacje o firmie.** Jeśli podano adres strony (trzecie słowo), otwórz ją narzędziem WebFetch. Jeśli to potrzebne, otwórz też podstrony „o nas”, „oferta” lub „kontakt” z tej samej domeny. Ustal: nazwę, czym firma się zajmuje, dla kogo pisze (klienci), styl komunikacji, adres bloga lub strony, a także **kraj lub rynek**, na którym działa (z adresu, języka strony, danych kontaktowych), i **język**, w którym komunikuje się z klientami. Jeśli adresu nie podano albo strona się nie otwiera, zapytaj użytkownika w rozmowie o: nazwę, czym firma się zajmuje, kim są klienci, adres strony, kraj lub rynek i język treści. Nie zgaduj faktów o firmie. Brakujące pola opisz jako „do uzupełnienia”. Wyjątek: jeśli nie da się ustalić rynku albo języka, wpisz „Polska” i „polski” i zapamiętaj notkę „Nie rozpoznano rynku lub języka — przyjęto Polska / polski. Popraw w profilu, jeśli trzeba.”.
3. **Obszary domyślne.** Zaproponuj 4–6 obszarów, w których warto szukać nowości dla tej firmy (np. dla księgarni: rynek wydawniczy, nowości i premiery, wydarzenia i targi, przepisy dla handlu i e-commerce, podatki i dotacje dla branży). Każdy obszar ma krótki opis, czego szukać.
4. **Zaufane źródła.** Przez WebSearch znajdź 6–12 wiarygodnych źródeł dla tej branży na rynku firmy: oficjalne (urzędy, rejestry, organizacje branżowe) i branżowe media. Pogrupuj je według obszarów. Nie wpisuj źródeł, których istnienia nie potwierdziłeś w wynikach wyszukiwania.
5. **Platformy.** Domyślnie wpisz `facebook`. Jeśli na stronie firmy są linki do profili na LinkedIn lub Instagramie, dopisz te platformy (np. `facebook, linkedin, instagram`). Gdy jest Instagram, dodaj pole `**Hashtagi (Instagram):**` z 8–15 hashtagami.
6. **Zapisz pliki** narzędziem Write, wypełniając szablony z katalogu skilla:
   - `firmy/<id>/profil.md` według `assets/profil.md` (bez komentarzy z nawiasami ostrymi, z wypełnionymi polami),
   - `firmy/<id>/zrodla.md` według `assets/zrodla.md`,
   - `firmy/<id>/zakres.md` według `assets/zakres.md`, z dzisiejszą datą w formacie DD.MM.RRRR.

   Na koniec zapisz `<id>` do `firmy/aktywna.md`.
7. **Terminal.** Wypisz: „Dodano firmę <id> i ustawiono ją jako aktywną.”, treść profilu (pola i obszary), liczbę źródeł, notkę z punktu 2, jeśli jest, oraz:
„Popraw profil w firmy/<id>/profil.md albo napisz, co zmienić. Potem: /content … (albo zostaw domyślne obszary) → /tematy <dni>.”

## Czego nie robić

- Nie nadpisuj istniejącego profilu przy `dodaj`.
- Nie zapisuj niczego poza katalogiem `firmy/`.
- Nie wymyślaj źródeł ani faktów o firmie.
