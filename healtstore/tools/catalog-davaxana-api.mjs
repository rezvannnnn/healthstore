import { readFile, writeFile, mkdir, access } from 'node:fs/promises';
import { ROOT, get, clean, quality } from './catalog-extract.mjs';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const present = async (p) => {
    try {
        await access(p);

        return true;
    } catch {
        return false;
    }
};
async function api(path) {
    const { body } = await get(
        `https://davaxana.com/v1/ProductManagement/${path}`,
    );
    const p = JSON.parse(body);

    if (p.IsSuccess === false) {
        throw Error('Product not publicly available');
    }

    return p.Value ?? p;
}
const safe = (k, v) =>
    v &&
    v.length <= 180 &&
    !/توضیح|ویژگی|مصرف|نگهداری|انقضا|قیمت|موجودی|هشدار/.test(k);
function convert(p) {
    const facts = {};

    for (const item of [
        ...(p.HighlightedProductPropertyValue ?? []),
        ...(p.ProductPropertyGroups ?? []).flatMap(
            (g) => g.ProductPropertyValue ?? [],
        ),
    ]) {
        const k = clean(item.PropertyTitle),
            v = clean(item.PropertyValue);

        if (safe(k, v)) {
            facts[k] = v;
        }
    }

    return {
        source: 'davaxana',
        source_id: String(p.Id),
        url: `https://davaxana.com/product/${p.Id}/catalog`,
        name: clean(p.Name),
        english_name: clean(p.ShortName),
        brand: facts['برند'] ?? clean(p.BrandName),
        categories: [clean(p.GroupName), clean(p.SubGroupName)].filter(Boolean),
        facts,
        images: [
            p.PicPath,
            ...(p.ProductPics ?? []).map((p) => p.FilePath),
        ].filter(Boolean),
        quality: quality(p.ProductDescriptions?.Description ?? '', facts),
        retrieved_at: new Date().toISOString(),
    };
}
await mkdir(`${ROOT}/listing`, { recursive: true });
const first = await api('Filters/Read?Page=1&OrderBy=0');
let expected = first.TotalCount;
const pages = Math.ceil(expected / 16);
let cursor = 1,
    listed = 0;
const live = new Map();
async function listingWorker() {
    while (cursor <= pages) {
        const page = cursor++,
            file = `${ROOT}/listing/davaxana-page-${page}.json`;
        let list;

        if (await present(file)) {
            list = JSON.parse(await readFile(file, 'utf8'));
        } else {
            const data =
                page === 1
                    ? first
                    : await api(`Filters/Read?Page=${page}&OrderBy=0`);
            list = data.FilteredProducts.map((p) => ({
                id: String(p.Id),
                name: clean(p.Name),
                image: p.PicPath,
            }));
            await writeFile(file, JSON.stringify(list));
        }

        for (const p of list) {
            live.set(p.id, p);
        }

        listed++;

        if (listed % 10 === 0) {
            console.log(`listing ${listed}/${pages}; unique ${live.size}`);
            await writeFile(
                `${ROOT}/progress-davaxana-live.json`,
                JSON.stringify({
                    phase: 'listing',
                    expected,
                    pages,
                    listed,
                    unique: live.size,
                    at: new Date().toISOString(),
                }),
            );
        }

        await sleep(250);
    }
}
await Promise.all(Array.from({ length: 4 }, listingWorker));
let old;

try {
    old = JSON.parse(await readFile(`${ROOT}/discovery-davaxana.json`, 'utf8'));
} catch {
    const { body } = await get('https://davaxana.com/sitemap.xml');
    old = {
        urls: [
            ...body.matchAll(/<loc>([^<]*\/product\/\d+\/[^<]*)<\/loc>/g),
        ].map((m) => m[1]),
    };
}

const ids = [
    ...new Set([
        ...old.urls.map((u) => u.match(/\/product\/(\d+)/)[1]),
        ...live.keys(),
    ]),
];
await writeFile(
    `${ROOT}/discovery-davaxana-live.json`,
    JSON.stringify(
        { expected, listed_unique: live.size, pages, union: ids.length, ids },
        null,
        2,
    ),
);
console.log(`listed ${live.size}/${expected}; union ${ids.length}`);

if (process.argv.includes('--list-only')) {
    process.exit(0);
}

cursor = 0;
let completed = 0,
    failed = 0,
    skipped = 0;
const failures = [];
async function detailsWorker() {
    while (cursor < ids.length) {
        const id = ids[cursor++],
            file = `${ROOT}/records/davaxana-${id}.json`;

        if (await present(file)) {
            completed++;
            skipped++;
            continue;
        }

        try {
            const p = await api(`ProductInformation/Read?ProductId=${id}`);
            const out = convert(p);

            if (!out.name || out.source_id !== id) {
                throw Error('Invalid product response');
            }

            await writeFile(file, JSON.stringify(out, null, 2));
            completed++;
        } catch (e) {
            failed++;
            failures.push({
                id,
                url: `https://davaxana.com/product/${id}/catalog`,
                error: e.message,
            });
        }

        if ((completed + failed) % 50 === 0) {
            console.log(`details ${completed}/${ids.length}; failed ${failed}`);
            await writeFile(
                `${ROOT}/progress-davaxana.json`,
                JSON.stringify({
                    expected: ids.length,
                    completed,
                    failed,
                    skipped,
                    at: new Date().toISOString(),
                }),
            );
        }

        await sleep(200);
    }
}
await Promise.all(
    Array.from(
        {
            length: Math.max(
                1,
                Math.min(
                    16,
                    Number(
                        process.argv
                            .find((a) => a.startsWith('--concurrency='))
                            ?.split('=')[1] ?? 8,
                    ),
                ),
            ),
        },
        detailsWorker,
    ),
);
await writeFile(
    `${ROOT}/unavailable-davaxana.json`,
    JSON.stringify(failures, null, 2),
);
await writeFile(
    `${ROOT}/progress-davaxana.json`,
    JSON.stringify({
        expected: ids.length,
        completed,
        failed,
        skipped,
        finished: true,
        at: new Date().toISOString(),
    }),
);
console.log('Davaxana extraction complete', completed, failed);
