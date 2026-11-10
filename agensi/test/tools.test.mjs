import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { validatePack } from '../validate.mjs';
import { writeZip, skillEntries } from '../pack.mjs';

const fx = join(dirname(fileURLToPath(import.meta.url)), 'fixtures');
const has = (errors, skill, text) => errors.some((e) => e.startsWith(`${skill}:`) && e.includes(text));

test('poprawna paczka nie ma błędów', () => {
  assert.deepEqual(validatePack(join(fx, 'good')), []);
});

test('name różne od nazwy folderu', () => {
  assert.ok(has(validatePack(join(fx, 'bad')), 'zla-nazwa', 'name'));
});

test('brak description', () => {
  assert.ok(has(validatePack(join(fx, 'bad')), 'brak-opisu', 'description'));
});

test('składnia tylko dla Claude Code', () => {
  const e = validatePack(join(fx, 'bad'));
  assert.ok(has(e, 'skladnia', '$ARGUMENTS'));
  assert.ok(has(e, 'skladnia', '!`'));
});

test('brak wskazówki instalacji w skillu innym niż firma/company', () => {
  assert.ok(has(validatePack(join(fx, 'bad')), 'skladnia', 'wskazówki instalacji'));
});

test('brakujący plik references/assets', () => {
  assert.ok(has(validatePack(join(fx, 'bad')), 'zla-nazwa', 'references/brak.md'));
});

test('słowa zakazane w wersji en', () => {
  const e = validatePack(join(fx, 'en'), { lang: 'en' });
  assert.ok(has(e, 'post', 'KSeF'));
  assert.ok(has(e, 'post', 'po polsku'));
});

test('zip skilla ma SKILL.md w korzeniu i tę samą treść', () => {
  const out = join(mkdtempSync(join(tmpdir(), 'zip-')), 'content.zip');
  writeZip(out, skillEntries(join(fx, 'good', 'content')));
  const list = execFileSync('unzip', ['-Z1', out], { encoding: 'utf8' }).trim().split('\n').sort();
  assert.deepEqual(list, ['SKILL.md', 'assets/zakres.md']);
  const body = execFileSync('unzip', ['-p', out, 'SKILL.md'], { encoding: 'utf8' });
  assert.equal(body, readFileSync(join(fx, 'good', 'content', 'SKILL.md'), 'utf8'));
});

test('zip ma poprawną datę DOS (nie 1980-00-00)', () => {
  const out = join(mkdtempSync(join(tmpdir(), 'zip-')), 'd.zip');
  writeZip(out, [{ path: 'SKILL.md', data: Buffer.from('x') }]);
  const buf = readFileSync(out);
  assert.equal(buf.readUInt16LE(12), 0x0021);
  const central = buf.indexOf(Buffer.from([0x50, 0x4b, 0x01, 0x02]));
  assert.equal(buf.readUInt16LE(central + 14), 0x0021);
});

test('pakowanie pomija pliki ukryte i systemowe Windows', () => {
  const dir = mkdtempSync(join(tmpdir(), 'skill-'));
  for (const f of ['SKILL.md', '.DS_Store', 'Thumbs.db', 'desktop.ini', '.ostatni.swp']) writeFileSync(join(dir, f), 'x');
  assert.deepEqual(skillEntries(dir).map((e) => e.path), ['SKILL.md']);
});
