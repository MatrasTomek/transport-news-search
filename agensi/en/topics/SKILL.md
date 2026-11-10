---
name: topics
description: Searches the web for fresh news relevant to the active company (regulations, events, trends in its industry and market) and saves a report with 5–10 suggested topics for blog articles and social posts. Use when the user asks for blog topics, industry news or post ideas.
argument-hint: "[days]"
allowed-tools: WebSearch, WebFetch, Read, Write, Glob, Bash(date:*)
---

# Topics for the blog and posts

You are a content researcher for the company described in its profile. Your job is to find fresh news worth covering on the company's blog or social media and to save a report with suggested topics. The `companies/` folder lives in the user's current working directory. The report template lives in this skill's folder: `assets/report.md`.

## Input

- **Argument** (number of days) is the text typed after the skill name, e.g. `/topics 14`. It may be empty.
- **Today's date:** get it with `date +%Y-%m-%d`.

## Step 0 — active company

Use Glob to check whether `companies/active.md` exists and read it. The company id is the first non-empty line. If the file is missing or `companies/<id>/profile.md` does not exist, print: "No company selected. Use /company <id> or /company add <id> <website>. Also install the company skill if you don't have it." and stop.

From here on `F` means the folder `companies/<id>`. Read `F/profile.md`. The **Name**, **What the company does**, **Audience** and **What to look for** fields tell you who you are and which news is valuable. All judgements of importance refer to the readers in the **Audience** field.

The **Market** and **Content language** fields tell you where to search and which language to write in. If **Market** is missing or empty, search globally and add the note "Market not set in profile — searched globally." to the report header. If **Content language** is missing or empty, write in English.

## Step 1 — period

Work out N from the argument:
- Trim spaces. If it is a whole number from 1 to 365, N = that number.
- If it is a whole number greater than 365, N = 365 and add the note: "Period limited to 365 days."
- In any other case (empty, text, zero, negative, a fraction, a number with extra words such as "30 days"), N = 14. If the argument was not empty, add the note: "Invalid argument "<argument>", using the default of 14 days."

The period runs from (today − N days) to today inclusive. Work out the start date carefully, including across month and year boundaries.

## Step 2 — sources and scope

Read `F/sources.md` (if it exists). It lists trusted sources grouped by area.

Use Glob to check whether `F/scope.md` exists (set by the `scope` skill) and read it:
- if it contains a list of topics (lines `- <topic>`), **the search areas are only those topics**, each as a separate area named after the topic;
- if it contains a line that is exactly `default`, has no topics, or does not exist, use the areas under "Default areas" in `F/profile.md`.

Remember the scope for the report header: the topics separated by commas, or "default areas".

## Step 3 — search

For each area run several WebSearch queries. Start by restricting them to the domains in `F/sources.md`, then search more broadly. Search in the content language and for the company's market (that country's or region's agencies, regulations and media). For cross-border regulation (e.g. EU) and foreign sources, also search in English. Look for what the **What to look for** field describes, within each area.

## Step 4 — verification

Open promising results with WebFetch and establish:
- the **publication or change date**, which must fall within the period (otherwise discard it),
- the **status**: exactly one of these values, with nothing added in brackets:
  - for regulatory changes: `in force` / `adopted` / `draft` / `announced`. Use `in force` only when the rule already applies; an act that has been published but applies from a future date is `adopted`,
  - for other news (launch, event, market data, new service, trend): `news`,
- the **date** — for regulations the effective date, for news the date of the event or launch if given,
- the **source type**: `official` (legal acts, government agencies, registers, official statements of institutions and industry associations) or `trade media` (portals, press, blogs).

If a page does not open (paywall, block, error), look for another source of the same information. If you cannot confirm it, the item may at most go to "To watch" marked "(unverified)". An item without an established publication date is not a topic; it may at most go to "To watch".

## Step 5 — selection

Pick 5 to 10 topics most relevant to the audience in the profile. Merge news about the same matter into one topic. If fewer than 5 topics are verified, list as many as there are and do not pad with weak ones. List areas without news under "Areas with no significant news".

Urgency (counted from today to the date from step 4):
- 🔴 within the next 30 days, or happened no more than 30 days ago
- 🟡 in 31–90 days
- ⚪ later, unknown date, draft/announcement, or happened more than 30 days ago

## Quality rules (mandatory)

- Every topic has at least one source link. No source, no topic.
- Do not guess dates, figures or the content of regulations. Report only what the source says.
- If a topic is confirmed only by trade media, say so next to the sources.
- The report skeleton (headings, field labels, statuses, source types, urgency) is always in English, exactly as in the template — the `post` skill finds topics by these labels.
- Free text (titles, descriptions, "Who it affects", "Why write about it", the outline, "To watch" entries) is written in the content language from the profile, including summaries of sources in other languages. Source page titles may stay in the original language.
- Write dates in the report as YYYY-MM-DD.

## Step 6 — save

File name: `F/reports/<today YYYY-MM-DD>.md`. Use Glob (`F/reports/<date>*.md`) to check whether it exists. If it does, use `-2`, and if that exists too, `-3`, and so on. Never overwrite an existing report. Save the file with Write in exactly the format of the template `assets/report.md` (repeat the topic block for every topic). If a closing section is empty, write "none" in it.

## Step 7 — terminal summary

Finally print the company, the path of the saved file and a numbered list of topic titles with urgency. Do not print the whole report. Add: "Write a post: /post <no> [facebook|linkedin|instagram]. Also install the post skill if you don't have it."
