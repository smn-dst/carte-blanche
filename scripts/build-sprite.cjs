/**
 * Génère public/images/sprite.svg à partir des SVG dans assets/icons/.
 *
 * Usage : npm run build:icons
 *
 * Pour ajouter une icône :
 * 1. Déposer un fichier .svg dans assets/icons/ (ex. mon-icone.svg).
 * 2. Lancer npm run build:icons.
 * 3. Dans les templates : <svg><use href="{{ asset('images/sprite.svg') }}#icon-mon-icone"/></svg>
 */
const fs = require('fs');
const path = require('path');
const SVGSpriter = require('svg-sprite');

const ROOT = path.resolve(__dirname, '..');
const ICONS_DIR = path.join(ROOT, 'assets', 'icons');
const OUT_FILE = path.join(ROOT, 'public', 'images', 'sprite.svg');
const TEMPLATE_FILE = path.join(ROOT, 'templates', '_sprite.svg');

const config = {
  mode: {
    symbol: {
      dest: '.',
      sprite: 'sprite.svg',
      example: false,
      symbol: {
        id: 'icon-%s',
      },
    },
  },
  shape: {
    id: {
      generator: (name) => `icon-${name}`,
    },
  },
};

const spriter = new SVGSpriter(config);

try {
  const files = fs.readdirSync(ICONS_DIR).filter((f) => f.endsWith('.svg'));
  if (files.length === 0) {
    console.warn('Aucun fichier .svg dans assets/icons/');
  }
  for (const file of files) {
    const filePath = path.join(ICONS_DIR, file);
    spriter.add(path.resolve(filePath), file, fs.readFileSync(filePath, 'utf8'));
  }

  spriter.compile((err, result) => {
    if (err) {
      console.error(err);
      process.exit(1);
    }
    const sprite = result.symbol.sprite;
    fs.mkdirSync(path.dirname(OUT_FILE), { recursive: true });
    let contents = typeof sprite.contents === 'string' ? sprite.contents : Buffer.isBuffer(sprite.contents) ? sprite.contents.toString('utf8') : String(sprite.contents);
    if (!contents.includes('style=')) {
      contents = contents.replace(/<svg\s/, '<svg style="position:absolute;width:0;height:0" ');
    }
    contents = contents.replace(/id="icon-([^"]+)\.svg"/g, 'id="icon-$1"');
    fs.writeFileSync(OUT_FILE, contents);
    fs.writeFileSync(TEMPLATE_FILE, contents);
    console.log(`Sprite généré : ${files.length} icône(s) → public/images/sprite.svg + templates/_sprite.svg`);
  });
} catch (e) {
  console.error(e);
  process.exit(1);
}
