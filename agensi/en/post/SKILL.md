---
name: post
description: Writes a Facebook, LinkedIn or Instagram post for the active company based on a topic from its latest topics report and saves it to a file. Use when the user asks for a post about a report topic (e.g. "post 3 for LinkedIn").
argument-hint: "<no> [facebook|linkedin|instagram] [expert|casual] [character limit] [hints]"
allowed-tools: WebFetch, Read, Write, Glob, Bash(date:*), Bash(node:*)
---

# Social media post

You write posts for the company described in its profile. Based on a topic from a `topics` report you write one post for the chosen platform and save it to a file. The `companies/` folder lives in the user's current working directory. The platform rules live in this skill's folder: `references/platforms.md` — read them before writing.

## Input

- **Arguments** are the text typed after the skill name, e.g. `/post 3 linkedin casual 800 stress the deadline`. When you run this skill from a request in the conversation, take the same information from the request.
- **Today's date:** get it with `date +%Y-%m-%d`.

## Step 0 — active company

Use Glob to check whether `companies/active.md` exists and read it. The company id is the first non-empty line. If the file is missing or `companies/<id>/profile.md` does not exist, print: "No company selected. Use /company <id> or /company add <id> <website>. Also install the company skill if you don't have it." and stop.

From here on `F` means the folder `companies/<id>`. Read `F/profile.md`. The **Name**, **Website / blog**, **Audience**, **Post tone**, **Call to action**, **Hashtags**, **Platforms** and **Content language** fields decide how you write. If there is no **Content language** field, write in English.

**Per-platform fields:** if the profile has a version of a field for the chosen platform, e.g. `**Hashtags (Instagram):**` or `**Post tone (LinkedIn):**`, use it instead of the general field.

## Step 1 — arguments

Split the arguments into words (on spaces).
- **Topic number** is the first word. It must be a positive whole number. If it is missing or not a number, go to step 2, print the list of topics (see "No topic" in step 3) and stop.
- From the remaining words, in any order (ignoring case):
  - **Platform:** `facebook`/`fb`, `linkedin`/`li` or `instagram`/`ig`. Default: the first platform in the profile's **Platforms** field, or Facebook if there is no such field. If the chosen platform is not in **Platforms**, write the post anyway and add the note "<platform> is not in the company profile." in the terminal.
  - **Style:** `expert` or `casual`. Default `expert`.
  - **Character limit:** the first whole number. Default: the platform's default limit (Facebook 1000, LinkedIn 1300, Instagram 1000). Below 200 → 200; above the platform maximum (Facebook 3000, LinkedIn 3000, Instagram 2200) → the maximum, and add the note "Limit capped at N characters." in the terminal.
  - **Hints:** all remaining text in its original order. May be empty.

## Step 2 — latest report

Find reports with Glob (`F/reports/*.md`). Take only files named `YYYY-MM-DD.md` or `YYYY-MM-DD-N.md` (N is a number). Skip other names, e.g. `2026-10-02-test.md`. The latest report is the file with the latest date, and for the same date the one with the highest N (a file without N counts as N = 1). If there are no reports, print: "No reports for <name>. Run /topics first. Also install the topics skill if you don't have it." and stop.

Read that report with Read.

## Step 3 — topic

Topics in the report are sections `## <no>. <title>`. Take the section with the given number: title, area, status, date, "What's happening", "Who it affects", "Why write about it" and the list of sources with URLs.

**No topic:** if the number does not exist (or none was given), print:

```
Company: <name>
The report <report path> has K topics:
1. <title>
2. <title>
…
Usage: /post <no> [facebook|linkedin|instagram] [expert|casual] [character limit] [hints]
```

and stop without saving a file.

## Step 4 — sources

Open the topic's sources with WebFetch, official ones first. 1–3 sources with confirmed facts are enough. If a page does not open, move to the next one. If none opens, rely on the report and remember the note "Sources could not be opened — post based on the report.".

## Step 5 — the post

Write the post for the audience in the profile, following the platform rules in `references/platforms.md` and:
- **Style `expert`:** a catchy opening, then 2–4 short paragraphs: what is happening, who it affects, from when or when.
- **Style `casual`:** open with a question to the reader. Plain language, fewer details: only the key facts and the date.
- Follow the **Post tone** from the profile and the hints from the arguments, if any.
- Only facts from the sources and the report. Do not guess dates, amounts or the content of regulations.
- In the content language from the profile. Dates in the format usual for that language.
- Facebook and LinkedIn: the full URL of the best source (official first). Instagram: no URL anywhere in the post — remove addresses from the call to action and hints too.
- The **Call to action** from the profile before the hashtags, then the number of hashtags right for the platform, chosen for the topic based on the **Hashtags** field.
- Length: at most the character limit, including spaces, emoji, the link and hashtags.
- Instagram: also prepare an **Image idea** separately (not part of the post text).

## Step 6 — counting characters

Do not count characters yourself. Save just the post text (without the image idea) with Write to `F/posts/.last-post.txt` (overwriting), then count, substituting the company id:

```
node -e "const t=require('fs').readFileSync('companies/<id>/posts/.last-post.txt','utf8').trim();console.log([...t].length)"
```

If the result is over the limit, shorten the post (keeping the link — on Facebook and LinkedIn — plus the call to action and hashtags; never add a link on Instagram), save again and count again. At most 2 rounds of shortening. If it is still too long, keep the last version and remember the warning "Post exceeds the limit (N of L characters) — shorten it by hand.". If `node` does not work, estimate the length, keep the post and add the note "Could not count characters (node not available).". With a limit below about 400 characters, the link, call to action and hashtags alone may not fit — add a warning in that case.

## Step 7 — save

Platform in the file name: `facebook`, `linkedin` or `instagram`. File name: `F/posts/<today YYYY-MM-DD>-topic-<no>-<platform>.md`. Use Glob (`F/posts/<date>-topic-<no>-<platform>*.md`) to check whether it exists. If it does, use `-2`, and if that exists too, `-3`, and so on. Never overwrite an existing post. Save the file with Write in this format:

```markdown
# <Facebook|LinkedIn|Instagram> post — <topic title>
Company: <name> · Report: <report path> · Topic no. <no> · Platform: <platform> · Style: <style> · Limit: <limit> · Characters: <result from step 6>
<notes and warnings, if any>

Sources used in the post:
- <title> — <url>

Image idea: <Instagram only; omit this line on other platforms>

---

<the post text exactly as in .last-post.txt>
```

## Step 8 — terminal

Print:
1. "Company: <name> · Platform: <platform>",
2. the post text between `─────` lines (ready to copy),
3. on Instagram: "Image idea: …",
4. `Characters: N / limit` plus notes and warnings,
5. the path of the saved file,
6. a tip: "You can ask e.g. »shorten to 500 characters«, »casual version« or »LinkedIn version«."

## Follow-up requests in the same session

- A fix to the same post (e.g. "shorten to 500 characters", "casual version"): revise it, count characters as in step 6 and update the saved file (same file, new text and character count).
- A version for another platform (e.g. "Instagram version"): write a new post following that platform's rules and save a **new** file with its name, as in step 7.
