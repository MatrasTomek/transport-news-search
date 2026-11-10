---
name: company
description: Shows, switches or adds the company that the topics and post skills research news and write posts for. Use when the user wants to pick a company or add a new one from its website.
argument-hint: "[<id> | add <id> [website]]"
allowed-tools: WebFetch, WebSearch, Read, Write, Glob, Bash(date:*)
disable-model-invocation: true
---

# Company

You manage company profiles. Each company has a folder `companies/<id>/` with the files `profile.md`, `sources.md` and `scope.md` and the folders `reports/` and `posts/`. The active company is stored in `companies/active.md` (one line: the id). The `scope`, `topics` and `post` skills always work on the active company.

The `companies/` folder lives in the user's current working directory (where the agent was started), not in this skill's folder. The file templates live in this skill's folder: `assets/profile.md`, `assets/sources.md` and `assets/scope.md`.

## Input

- **Arguments** are the text typed after the skill name, e.g. `/company add corner-cafe https://cornercafe.com`. They may be empty.
- **Today's date:** get it with `date +%Y-%m-%d`.

Company id: lowercase letters a–z, digits and hyphens only, e.g. `corner-cafe`, `smith-accounting`.

## Step 1 — read the arguments

Trim spaces and split the arguments into words.

**A) No arguments.** Find profiles with Glob (`companies/*/profile.md`) and read the **Name** field from each. Read `companies/active.md` if it exists. Print:

```
Active company: <id> — <name>   (or "none — pick a company")

Companies:
  <id> — <name>
  …

Usage:
  /company <id>                  — switch company
  /company add <id> [website]    — add a new company
Then: /scope … → /topics <days> → /post <no>
```

If there are no companies, instead of the list print "No companies yet. Add one: /company add <id> <website>". Save nothing and stop.

**B) The first word is `add`.** Go to step 2.

**C) A single word (id).** If `companies/<id>/profile.md` exists, write `<id>` to `companies/active.md` (overwriting) and print "Active company: <id> — <name>" plus the current scope from `companies/<id>/scope.md`. If there is no such profile, print "There is no company "<id>".", the list of companies as in A, and stop without saving.

## Step 2 — add a company

1. **Id** is the second word. If it is missing or contains characters other than a–z, 0–9 and hyphen, print "Give a company id, e.g. /company add corner-cafe https://…" and stop. If `companies/<id>/profile.md` already exists, print "Company <id> already exists. Edit companies/<id>/profile.md or choose another id." and stop.
2. **Company details.** If a website was given (third word), open it with WebFetch. If needed, also open the "about", "services" or "contact" pages on the same domain. Find out: the name, what the company does, who its customers are, its communication style, the blog or website address, the **country or market** it operates in (from the domain, the site's language, contact details) and the **language** it uses with customers. If no website was given or it does not open, ask the user in the conversation for: the name, what the company does, who the customers are, the website, the market and the content language. Do not guess facts about the company; mark missing fields as "to be completed". If you cannot determine the market or the language from the website, ask the user for them before saving.
3. **Default areas.** Propose 4–6 areas worth monitoring for news for this company (e.g. for a bookshop: publishing market, new releases, events and fairs, retail and e-commerce regulations, taxes and grants for the sector). Each area gets a short description of what to look for.
4. **Trusted sources.** Use WebSearch to find 6–12 reliable sources for this industry in the company's market: official ones (government agencies, registers, industry associations) and trade media. Group them by area. Do not list sources whose existence you have not confirmed in search results.
5. **Platforms.** Default to `facebook`. If the company's website links to LinkedIn or Instagram profiles, add those platforms (e.g. `facebook, linkedin, instagram`). If Instagram is included, add a `**Hashtags (Instagram):**` field with 8–15 hashtags.
6. **Save the files** with Write, filling in the templates from this skill's folder:
   - `companies/<id>/profile.md` following `assets/profile.md` (without the angle-bracket placeholders, with the fields filled in),
   - `companies/<id>/sources.md` following `assets/sources.md`,
   - `companies/<id>/scope.md` following `assets/scope.md`, with today's date as YYYY-MM-DD.

   Finally write `<id>` to `companies/active.md`.
7. **Terminal.** Print: "Added company <id> and set it as active.", the profile (fields and areas), the number of sources, and:
"Fix the profile in companies/<id>/profile.md or tell me what to change. Then: /scope … (or keep the default areas) → /topics <days>."

## Do not

- Do not overwrite an existing profile with `add`.
- Do not write anything outside the `companies/` folder.
- Do not invent sources or facts about the company.
