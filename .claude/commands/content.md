---
description: Ustaw, czego ma dotyczyć wyszukiwanie /tematy dla aktywnej firmy (lista tematów albo domyślne obszary)
argument-hint: [temat1, temat2, … | domyślne]
allowed-tools: Read, Write, Glob, Bash(date:*)
---

Ustawiasz zakres wyszukiwania komendy `/tematy` dla aktywnej firmy. Zakres jest zapisany w `firmy/<id>/zakres.md` i obowiązuje dla kolejnych wyszukiwań tej firmy, aż użytkownik go zmieni.

## Dane wejściowe

- Argumenty: `$ARGUMENTS`
- Dzisiejsza data: !`date +%Y-%m-%d`

## Krok 0 — aktywna firma

Sprawdź narzędziem Glob, czy istnieje `firmy/aktywna.md`, i przeczytaj go. Id firmy to pierwsza niepusta linia. Jeśli pliku nie ma albo nie istnieje `firmy/<id>/profil.md`, napisz: „Nie wybrano firmy. Użyj /firma <id> albo /firma dodaj <id> <adres strony>.” i zakończ.

Dalej `F` oznacza katalog `firmy/<id>`. Przeczytaj `F/profil.md`: potrzebujesz pola **Nazwa** i listy z sekcji „Obszary domyślne”.

## Krok 1 — rozpoznaj argumenty

Przytnij spacje.

**A) Brak argumentów.** Sprawdź narzędziem Glob, czy istnieje `F/zakres.md`. Jeśli tak, przeczytaj go. Wypisz w terminalu „Firma: <nazwa>”, bieżący zakres (listę tematów albo „domyślne obszary” z ich nazwami z profilu) i datę ustawienia, a pod spodem:

```
Użycie:
  /content KSeF, e-CMR, tachografy   — ustaw własne tematy (oddziel przecinkami)
  /content domyślne                  — wróć do obszarów domyślnych z profilu firmy
Potem: /tematy <dni>
```

Niczego nie zapisuj i zakończ.

**B) Argument to „domyślne”** (także „domyslne”, „default”, bez względu na wielkość liter). Zapisz `F/zakres.md` w formacie:

```markdown
# Zakres wyszukiwania
Ustawiono: DD.MM.RRRR

domyślne
```

Przejdź do kroku 3.

**C) Każdy inny tekst.** Podziel go na tematy po przecinkach i średnikach. Przytnij spacje, usuń puste pozycje i powtórzenia (bez względu na wielkość liter). Zachowaj pisownię i kolejność użytkownika. Tekst bez przecinków i średników to jeden temat. Jeśli tematów jest więcej niż 8, zostaw pierwsze 8 i zapamiętaj notkę „Zapisano pierwsze 8 tematów (każdy temat to osobna seria wyszukiwań).”. Zapisz `F/zakres.md` w formacie:

```markdown
# Zakres wyszukiwania
Ustawiono: DD.MM.RRRR

- <temat 1>
- <temat 2>
```

## Krok 2 — zapis

Zapisz plik narzędziem Write (nadpisując poprzedni zakres). Datę zapisz jako DD.MM.RRRR.

## Krok 3 — terminal

Wypisz:
1. „Firma: <nazwa>”,
2. „Zakres wyszukiwania ustawiony:” i listę tematów (albo „domyślne obszary” z ich nazwami z profilu),
3. notkę, jeśli jest,
4. „Teraz uruchom `/tematy <dni>`, np. `/tematy 14`.”
