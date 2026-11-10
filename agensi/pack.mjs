// Pakuje skille do zipów na Agensi: po jednym na skill i jeden z całą paczką.
// Użycie: node agensi/pack.mjs <pl|en>   →   agensi/<lang>/dist/*.zip
import { existsSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { crc32, deflateRawSync } from 'node:zlib';
import { listFiles } from './validate.mjs';

const toPosix = (p) => p.split('\\').join('/');
const DOS_DATE = 0x0021; // 1980-01-01, najmniejsza poprawna data w formacie ZIP
const JUNK = /^(\.|Thumbs\.db$|desktop\.ini$)/i;

export function skillEntries(skillDir, prefix = '') {
  return listFiles(skillDir)
    .filter((f) => !toPosix(relative(skillDir, f)).split('/').some((part) => JUNK.test(part)))
    .sort()
    .map((f) => ({ path: prefix + toPosix(relative(skillDir, f)), data: readFileSync(f) }));
}

export function writeZip(outPath, entries) {
  const locals = [];
  const centrals = [];
  let offset = 0;
  for (const { path, data } of entries) {
    const name = Buffer.from(path, 'utf8');
    const packed = deflateRawSync(data);
    const crc = crc32(data);
    const local = Buffer.alloc(30);
    local.writeUInt32LE(0x04034b50, 0);
    local.writeUInt16LE(20, 4); // wersja potrzebna do rozpakowania
    local.writeUInt16LE(0x0800, 6); // nazwy w UTF-8
    local.writeUInt16LE(8, 8); // deflate
    local.writeUInt16LE(DOS_DATE, 12);
    local.writeUInt32LE(crc, 14);
    local.writeUInt32LE(packed.length, 18);
    local.writeUInt32LE(data.length, 22);
    local.writeUInt16LE(name.length, 26);
    const central = Buffer.alloc(46);
    central.writeUInt32LE(0x02014b50, 0);
    central.writeUInt16LE(20, 4);
    central.writeUInt16LE(20, 6);
    central.writeUInt16LE(0x0800, 8);
    central.writeUInt16LE(8, 10);
    central.writeUInt16LE(DOS_DATE, 14);
    central.writeUInt32LE(crc, 16);
    central.writeUInt32LE(packed.length, 20);
    central.writeUInt32LE(data.length, 24);
    central.writeUInt16LE(name.length, 28);
    central.writeUInt32LE(offset, 42);
    locals.push(local, name, packed);
    centrals.push(central, name);
    offset += local.length + name.length + packed.length;
  }
  const centralSize = centrals.reduce((n, b) => n + b.length, 0);
  const end = Buffer.alloc(22);
  end.writeUInt32LE(0x06054b50, 0);
  end.writeUInt16LE(entries.length, 8);
  end.writeUInt16LE(entries.length, 10);
  end.writeUInt32LE(centralSize, 12);
  end.writeUInt32LE(offset, 16);
  writeFileSync(outPath, Buffer.concat([...locals, ...centrals, end]));
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const lang = process.argv[2];
  if (!['pl', 'en'].includes(lang)) {
    console.error('Użycie: node agensi/pack.mjs <pl|en>');
    process.exit(2);
  }
  const packDir = join(dirname(fileURLToPath(import.meta.url)), lang);
  const dist = join(packDir, 'dist');
  rmSync(dist, { recursive: true, force: true });
  mkdirSync(dist);
  const skills = readdirSync(packDir).filter((d) => existsSync(join(packDir, d, 'SKILL.md')));
  const all = [];
  for (const skill of skills) {
    writeZip(join(dist, `${skill}.zip`), skillEntries(join(packDir, skill)));
    all.push(...skillEntries(join(packDir, skill), `${skill}/`));
    console.log(`dist/${skill}.zip`);
  }
  for (const doc of ['README.md', 'LISTING.md']) {
    if (existsSync(join(packDir, doc))) all.unshift({ path: doc, data: readFileSync(join(packDir, doc)) });
  }
  writeZip(join(dist, `${lang}-pack.zip`), all);
  console.log(`dist/${lang}-pack.zip`);
}
