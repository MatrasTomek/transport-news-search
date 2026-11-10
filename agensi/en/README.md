# Topics and posts for your company — 4 skills for AI agents

A bundle of four skills that find fresh news from a company's industry and market on the web, suggest blog topics and write posts for **Facebook, LinkedIn and Instagram**. Works for any company, industry and country — all it needs is the company's website.

| Skill | What it does |
|---|---|
| `company` | adds a company from its website (profile, areas, trusted sources) and switches the active company |
| `scope` | sets what to search for: your own topics or the default areas from the profile |
| `topics` | finds news from the last N days and saves a report with 5–10 topics, each with sources |
| `post` | writes a post for the chosen platform about a topic from the latest report |

## Requirements

- An agent that supports SKILL.md skills and has web search (WebSearch) and page fetching (WebFetch), e.g. **Claude Code**.
- Optional: **Node.js** — the `post` skill uses it to count characters exactly (emoji included). Without it, length is estimated.

## Installation

Copy the four folders (`company`, `scope`, `topics`, `post`) into your skills folder:

- for all projects: `~/.claude/skills/`
- for one project only: `<project>/.claude/skills/`

If you downloaded separate zip files, unzip each into a folder named after the skill, e.g. `~/.claude/skills/post/SKILL.md`.

Do not install the English and Polish versions in the same place — both have a `post` skill.

## How to use

Start the agent in the folder where you want the data to live (e.g. `claude` in a new folder) and type:

1. `/company add corner-cafe https://cornercafe.com` — the agent reads the site, creates a profile, suggests areas and sources. Review and edit `companies/corner-cafe/profile.md`.
2. `/scope grants, trade fairs` — optionally narrow the search. Without it, the default areas from the profile are used.
3. `/topics 14` — news from the last 14 days (max 365). The report goes to `companies/<id>/reports/`.
4. `/post 3 linkedin` — a post about topic 3 from the latest report. The post goes to `companies/<id>/posts/`.

Full form: `/post <no> [facebook|linkedin|instagram] [expert|casual] [character limit] [hints]`. In the same session you can ask e.g. "shorten to 500 characters" or "Instagram version".

With several companies, switch with `/company <id>`. `/company` with no arguments lists them.

## Data files

Everything lives in the working folder, under `companies/`:

- `active.md` — the active company id,
- `<id>/profile.md` — name, website, business, audience, **Market**, **Content language**, **Platforms**, tone, call to action, hashtags, default areas. Edit freely. Tone, call to action and hashtags can have per-platform versions, e.g. `**Hashtags (Instagram):**`,
- `<id>/sources.md` — trusted sources the search starts from,
- `<id>/scope.md` — the current scope set by the `scope` skill,
- `<id>/reports/`, `<id>/posts/` — results.

## Platforms

| | Facebook | LinkedIn | Instagram |
|---|---|---|---|
| Default / max limit | 1000 / 3000 | 1300 / 3000 | 1000 / 2200 |
| Link | in the post | in the post | "link in bio", URL only in the file |
| Hashtags | 3–5 | 3–5 | 8–15 |
| Extra | — | — | image idea |

## Note

Reports and posts are drafts. Verify the information in the sources before publishing.

## For the bundle author

- `node agensi/validate.mjs en` — checks frontmatter, files and skill content.
- `node agensi/pack.mjs en` — builds a zip per skill and `en-pack.zip` with the whole bundle in `dist/`.
