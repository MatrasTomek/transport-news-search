// Sprawdza paczkę skilli przed wgraniem na Agensi.
// Użycie: node agensi/validate.mjs <pl|en>
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import { basename, dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const STANDALONE = ['firma', 'company'];
const INSTALL_HINT = /Zainstaluj też skill|Also install the .* skill/;
const BANNED_EN = ['KSeF', 'e-CMR', 'tachograf', 'Polsk', 'po polsku'];
const BANNED_PL = ['KSeF', 'e-CMR', 'tachograf'];

function frontmatter(text) {
  const m = text.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!m) return null;
  const fields = {};
  for (const line of m[1].split(/\r?\n/)) {
    const kv = line.match(/^([\w-]+):\s*(.*)$/);
    if (kv) fields[kv[1]] = kv[2].trim();
  }
  return fields;
}

export function validatePack(dir, { lang = basename(dir) } = {}) {
  const errors = [];
  const banned = lang === 'en' ? BANNED_EN : BANNED_PL;
  const skills = readdirSync(dir).filter((d) => existsSync(join(dir, d, 'SKILL.md')));
  for (const skill of skills) {
    const text = readFileSync(join(dir, skill, 'SKILL.md'), 'utf8');
    const fm = frontmatter(text);
    if (!fm) {
      errors.push(`${skill}: brak frontmattera`);
      continue;
    }
    if (fm.name !== skill) errors.push(`${skill}: name „${fm.name ?? ''}” różne od nazwy folderu`);
    if (!/^[a-z0-9-]+$/.test(skill)) errors.push(`${skill}: nazwa folderu może mieć tylko a–z, 0–9 i myślniki`);
    if (!fm.description) errors.push(`${skill}: brak description`);
    if (text.includes('$ARGUMENTS')) errors.push(`${skill}: zawiera $ARGUMENTS (tylko Claude Code)`);
    if (text.includes('!`')) errors.push(`${skill}: zawiera !\` (tylko Claude Code)`);
    if (!STANDALONE.includes(skill) && !INSTALL_HINT.test(text)) errors.push(`${skill}: brak wskazówki instalacji skilla firma/company`);
    for (const ref of new Set(text.match(/\b(?:assets|references|scripts)\/[\w./-]+\.\w+/g) ?? [])) {
      if (!existsSync(join(dir, skill, ref))) errors.push(`${skill}: brak pliku ${ref}`);
    }
    const all = [text, ...listFiles(join(dir, skill)).filter((f) => !f.endsWith('SKILL.md')).map((f) => readFileSync(f, 'utf8'))].join('\n');
    for (const word of banned) {
      if (all.toLowerCase().includes(word.toLowerCase())) errors.push(`${skill}: słowo zakazane „${word}”`);
    }
  }
  return errors;
}

export function listFiles(dir) {
  return readdirSync(dir).flatMap((name) => {
    const p = join(dir, name);
    return statSync(p).isDirectory() ? listFiles(p) : [p];
  });
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const lang = process.argv[2];
  if (!['pl', 'en'].includes(lang)) {
    console.error('Użycie: node agensi/validate.mjs <pl|en>');
    process.exit(2);
  }
  const errors = validatePack(join(dirname(fileURLToPath(import.meta.url)), lang), { lang });
  for (const e of errors) console.log(e);
  console.log(errors.length ? `Błędów: ${errors.length}` : `OK: paczka ${lang} bez błędów`);
  process.exit(errors.length ? 1 : 0);
}
