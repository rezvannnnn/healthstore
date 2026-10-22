import fs from 'node:fs/promises';
import path from 'node:path';
import crypto from 'node:crypto';
const images = JSON.parse(await fs.readFile('healtstore/CATALOG-IMAGE-SOURCES.json', 'utf8'));
let cursor = 0, completed = 0;
const failures = [];
async function worker() {
  while (cursor < images.length && failures.length < 100) {
    const image = images[cursor++];
    if (!image.path.startsWith('healtstore/storage/app/public/products/') || image.path.includes('..')) throw Error('Unsafe image path');
    let succeeded = false;
    for (let attempt = 0; attempt < 3 && !succeeded; attempt++) {
      try {
        const response = await fetch(image.url, { signal: AbortSignal.timeout(30000), headers: { 'User-Agent': 'Mozilla/5.0 CatalogSnapshot/1.0' } });
        if (!response.ok) throw Error(`HTTP ${response.status}`);
        const bytes = Buffer.from(await response.arrayBuffer());
        if (bytes.length !== image.bytes || crypto.createHash('sha256').update(bytes).digest('hex') !== image.sha256) throw Error('Image differs from local snapshot');
        await fs.mkdir(path.dirname(image.path), { recursive: true });
        await fs.writeFile(image.path, bytes);
        succeeded = true;
      } catch (error) {
        if (attempt === 2) failures.push({ path: image.path, message: error.message });
      }
    }
    completed++;
    if (completed % 100 === 0) console.log(`Verified ${completed}/${images.length}; failures ${failures.length}`);
  }
}
await Promise.all(Array.from({ length: 8 }, worker));
if (failures.length || completed !== images.length) { console.error(JSON.stringify({ completed, failures })); process.exit(1); }
console.log(`All ${completed} images match the local snapshot byte for byte.`);
