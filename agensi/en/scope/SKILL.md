---
name: scope
description: Sets what the topics skill searches for the active company — a custom list of topics or the default areas from the company profile. Use when the user wants to narrow down or change which news to look for.
argument-hint: "[topic1, topic2, … | default]"
allowed-tools: Read, Write, Glob, Bash(date:*)
disable-model-invocation: true
---

# Search scope

You set the search scope of the `topics` skill for the active company. The scope is stored in `companies/<id>/scope.md` and applies to that company's future searches until the user changes it. The `companies/` folder lives in the user's current working directory.

## Input

- **Arguments** are the text typed after the skill name, e.g. `/scope grants, trade fairs`. They may be empty.
- **Today's date:** get it with `date +%Y-%m-%d`.

## Step 0 — active company

Use Glob to check whether `companies/active.md` exists and read it. The company id is the first non-empty line. If the file is missing or `companies/<id>/profile.md` does not exist, print: "No company selected. Use /company <id> or /company add <id> <website>. Also install the company skill if you don't have it." and stop.

From here on `F` means the folder `companies/<id>`. Read `F/profile.md`: you need the **Name** field and the list under "Default areas".

## Step 1 — read the arguments

Trim spaces.

**A) No arguments.** Use Glob to check whether `F/scope.md` exists and read it if so. Print "Company: <name>", the current scope (the list of topics or "default areas" with their names from the profile) and the date it was set, followed by:

```
Usage:
  /scope grants, trade fairs, new regulations   — set your own topics (comma-separated)
  /scope default                                — go back to the default areas from the profile
Then: /topics <days>
```

Save nothing and stop.

**B) The argument is "default"** (any letter case). Save `F/scope.md` as:

```markdown
# Search scope
Set on: YYYY-MM-DD

default
```

Go to step 3.

**C) Any other text.** Split it into topics on commas and semicolons. Trim spaces, drop empty entries and duplicates (ignoring case). Keep the user's spelling and order. Text without commas or semicolons is a single topic. If there are more than 8 topics, keep the first 8 and remember the note "Saved the first 8 topics (each topic is a separate round of searches).". Save `F/scope.md` as:

```markdown
# Search scope
Set on: YYYY-MM-DD

- <topic 1>
- <topic 2>
```

## Step 2 — save

Save the file with Write (overwriting the previous scope). Write the date as YYYY-MM-DD.

## Step 3 — terminal

Print:
1. "Company: <name>",
2. "Search scope set:" and the list of topics (or "default areas" with their names from the profile),
3. the note, if any,
4. "Now run /topics <days>, e.g. /topics 14. Also install the topics skill if you don't have it."
