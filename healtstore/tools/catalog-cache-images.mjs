import { createHash } from 'node:crypto';
import { mkdir, readdir, readFile, writeFile, access } from 'node:fs/promises';
import { ROOT, get } from './catalog-extract.mjs';
await mkdir('storage/app/public/products/catalog', { recursive: true });
const seen = new Set(),
    urls = new Set();
let done = 0,
    failed = 0;

for (;;) {
    const files = (await readdir(`${ROOT}/records`)).filter(
        (f) => f.endsWith('.json') && !seen.has(f),
    );
    const queue = [];

    for (const file of files) {
        try {
            const p = JSON.parse(
                await readFile(`${ROOT}/records/${file}`, 'utf8'),
            );
            seen.add(file);
            const url = p.images?.[0];

            if (url && !urls.has(url)) {
                urls.add(url);
                queue.push(url);
            }
        } catch {}
    }

    let cursor = 0;
    async function worker() {
        while (cursor < queue.length) {
            const url = queue[cursor++];

            try {
                const basename = createHash('sha256')
                        .update(url)
                        .digest('hex')
                        .slice(0, 32),
                    ext =
                        new URL(url).pathname
                            .match(/\.(jpg|jpeg|png|webp|gif)$/i)?.[1]
                            ?.toLowerCase() ?? 'jpg',
                    dest = `storage/app/public/products/catalog/${basename}.${ext}`;

                try {
                    await access(dest);
                } catch {
                    const { data, type } = await get(url, true);

                    if (
                        !type?.startsWith('image/') ||
                        data.length > 12 * 1024 * 1024 ||
                        data.length < 100
                    ) {
                        throw Error('Invalid image');
                    }

                    await writeFile(dest, data);
                }

                done++;
            } catch {
                failed++;
            }

            if ((done + failed) % 100 === 0) {
                console.log(`cached images ${done} failed ${failed}`);
                await writeFile(
                    `${ROOT}/progress-image-cache.json`,
                    JSON.stringify({
                        done,
                        failed,
                        records_seen: seen.size,
                        at: new Date().toISOString(),
                    }),
                );
            }

            await new Promise((r) => setTimeout(r, 200));
        }
    }
    await Promise.all(Array.from({ length: 4 }, worker));
    let finished = false;

    try {
        const d = JSON.parse(
            await readFile(`${ROOT}/progress-davaxana.json`, 'utf8'),
        );
        const b = JSON.parse(
            await readFile(`${ROOT}/progress-begoosib.json`, 'utf8'),
        );
        finished = d.finished && b.completed_pages === b.pages;
    } catch {}

    if (finished && queue.length === 0) {
        break;
    }

    if (!queue.length) {
        await new Promise((r) => setTimeout(r, 10000));
    }
}

console.log('image cache finished', done, failed);
