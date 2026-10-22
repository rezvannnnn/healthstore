import { mkdir, writeFile, rename, access, appendFile } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';
import { parse } from '@vue/compiler-dom';
import { decodeHTML } from 'entities';
export const ROOT = 'storage/app/catalog-import';
export const clean = (s) =>
    decodeHTML(String(s ?? ''))
        .normalize('NFKC')
        .replace(/[يك]/g, (x) => (x === 'ي' ? 'ی' : 'ک'))
        .replace(/[۰-۹]/g, (x) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(x)))
        .replace(/[٠-٩]/g, (x) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(x)))
        .replace(/[\u200c\u200f\u200e\u0640]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
export const norm = (s) =>
    clean(s)
        .toLowerCase()
        .replace(/[^\p{L}\p{N}.]+/gu, ' ')
        .trim();
const walk = (n, f) => {
    f(n);

    for (const c of n.children ?? []) {
        walk(c, f);
    }
};
const attr = (n, k) =>
    n.props?.find((p) => p.type === 6 && p.name === k)?.value?.content ?? '';
const text = (n) =>
    n.type === 2
        ? n.content
        : ['script', 'style'].includes(n.tag)
          ? ''
          : (n.children ?? []).map(text).join(' ');
const all = (n, f) => {
    const out = [];
    walk(n, (x) => {
        if (x.type === 1 && f(x)) {
            out.push(x);
        }
    });

    return out;
};
const plain = (s) => clean(String(s ?? '').replace(/<[^>]*>/g, ' '));
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
export async function get(url, binary = false) {
    if (/^https:\/\/davaxana\.com\/product\/\d+\//.test(url)) {
        const id = url.match(/\/product\/(\d+)/)[1];
        url = `https://davaxana.com/product/${id}/catalog`;
    }

    const host = new URL(url).hostname;

    if (!['davaxana.com', 'file.davaxana.com', 'begoosib.com'].includes(host)) {
        throw Error(`Unapproved host ${host}`);
    }

    for (let attempt = 0; attempt < 4; attempt++) {
        try {
            const r = await fetch(url, {
                headers: {
                    'User-Agent':
                        'DarukhoonehCatalogImport/1.0 (public product catalog; contact via darukhooneh.ir)',
                },
                signal: AbortSignal.timeout(35000),
            });

            if (r.status === 429 || r.status >= 500) {
                await sleep(
                    Math.min(
                        30000,
                        Number(r.headers.get('retry-after') ?? 2) *
                            1000 *
                            (attempt + 1),
                    ),
                );
                continue;
            }

            if (!r.ok) {
                const e = Error(`HTTP ${r.status}`);
                e.permanent = [400, 401, 403, 404, 410].includes(r.status);

                throw e;
            }

            return binary
                ? {
                      data: Buffer.from(await r.arrayBuffer()),
                      type: r.headers.get('content-type'),
                  }
                : { body: await r.text(), headers: r.headers };
        } catch (e) {
            if (e.permanent || attempt === 3) {
                throw e;
            }

            await sleep(1000 * (attempt + 1));
        }
    }

    throw Error('Retry limit');
}
async function save(path, value) {
    await mkdir(path.slice(0, path.lastIndexOf('/')), { recursive: true });
    await writeFile(path + '.tmp', JSON.stringify(value, null, 2));
    await rename(path + '.tmp', path);
}
const exists = async (p) => {
    try {
        await access(p);

        return true;
    } catch {
        return false;
    }
};
export function quality(description, facts) {
    const s = plain(description);

    return {
        length: s.length,
        sections: (description.match(/<h[2-4]\b/g) ?? []).length,
        fact_count: Object.keys(facts).length,
        score: Math.min(s.length, 4000) / 100 + Object.keys(facts).length * 5,
        mode: 'original-description-from-structured-facts',
    };
}
const safeFact = (k, v) =>
    v &&
    v.length <= 180 &&
    !/توضیح|ویژگی|مصرف|نگهداری|انقضا|قیمت|موجودی|هشدار/.test(k);
export function parseDava(html, url) {
    const tree = parse(html, { onError: () => {} });
    const h1 = all(tree, (n) => n.tag === 'h1')[0];

    if (!h1) {
        throw Error('Missing product title');
    }

    const name = clean(text(h1));

    if (!name || /خطا|یافت نشد/.test(name)) {
        throw Error('Invalid product title');
    }

    const facts = {};
    let categories = [];
    const table = all(tree, (n) => n.tag === 'table')[0];

    for (const row of table ? all(table, (n) => n.tag === 'tr') : []) {
        const v = clean(text(row));

        if (/گروه\s*:/.test(v)) {
            categories = v
                .replace(/^.*?گروه\s*:\s*/, '')
                .split(/\s+-\s+/)
                .map(clean)
                .filter(Boolean);
            continue;
        }

        const pair = v.match(/^([^:]+):\s*(.+)$/);

        if (pair) {
            const k = clean(pair[1]);
            const val = clean(pair[2]);

            if (safeFact(k, val)) {
                facts[k] = val;
            }
        }
    }

    const meta = all(
        tree,
        (n) => n.tag === 'meta' && attr(n, 'property') === 'og:image',
    )[0];
    const desc = all(tree, (n) =>
        attr(n, 'class').split(' ').includes('text-p-description'),
    )[0];
    const brand = facts['برند'] ?? '';

    return {
        source: 'davaxana',
        source_id: url.match(/\/product\/(\d+)/)?.[1],
        url,
        name,
        brand,
        categories,
        facts,
        images: meta ? [attr(meta, 'content')] : [],
        quality: quality(text(desc ?? {}), facts),
        retrieved_at: new Date().toISOString(),
    };
}
function parseBego(p, categoryMap) {
    const facts = {};

    for (const a of p.attributes ?? []) {
        const val = clean(a.terms.map((t) => t.name).join('، '));
        const k = clean(a.name);

        if (safeFact(k, val)) {
            facts[k] = val;
        }
    }

    const paths = (p.categories ?? [])
        .map((c) => {
            const path = [];
            let item = categoryMap.get(c.id);
            const seen = new Set();

            while (item && !seen.has(item.id)) {
                seen.add(item.id);
                path.unshift(clean(item.name));
                item = categoryMap.get(item.parent);
            }

            return path.length ? path : [clean(c.name)];
        })
        .sort((a, b) => b.length - a.length);

    return {
        source: 'begoosib',
        source_id: String(p.id),
        url: p.permalink,
        name: clean(p.name),
        brand: facts['برند'] ?? clean(p.brands?.[0]?.name ?? ''),
        categories: paths[0] ?? [],
        category_paths: paths,
        facts,
        images: (p.images ?? []).map((i) => i.src),
        quality: quality(p.description ?? '', facts),
        retrieved_at: new Date().toISOString(),
    };
}
async function bego() {
    const categories = [];

    for (let page = 1; page <= 3; page++) {
        const { body } = await get(
            `https://begoosib.com/wp-json/wc/store/v1/products/categories?per_page=100&page=${page}`,
        );
        categories.push(...JSON.parse(body));
        await sleep(350);
    }

    await save(
        `${ROOT}/categories-begoosib.json`,
        categories.map((c) => ({
            id: c.id,
            parent: c.parent,
            name: clean(c.name),
            slug: c.slug,
        })),
    );
    const map = new Map(categories.map((c) => [c.id, c]));
    let expected = 0,
        pages = 1;

    for (let page = 1; page <= pages; page++) {
        const { body, headers } = await get(
            `https://begoosib.com/wp-json/wc/store/v1/products?per_page=100&page=${page}&orderby=id&order=asc`,
        );
        expected = Number(headers.get('x-wp-total'));
        pages = Number(headers.get('x-wp-totalpages'));

        for (const p of JSON.parse(body)) {
            const out = parseBego(p, map);
            await save(`${ROOT}/records/begoosib-${out.source_id}.json`, out);
        }

        await save(`${ROOT}/progress-begoosib.json`, {
            expected,
            pages,
            completed_pages: page,
            completed: Math.min(page * 100, expected),
            at: new Date().toISOString(),
        });
        console.log(`begoosib page ${page}/${pages}`);
        await sleep(500);
    }
}
async function dava() {
    const { body } = await get('https://davaxana.com/sitemap.xml');
    const urls = [
        ...new Set(
            [...body.matchAll(/<loc>(.*?)<\/loc>/gs)]
                .map((m) => decodeHTML(m[1]))
                .filter((u) => /\/product\/\d+\//.test(u))
                .map((u) =>
                    u.replace(/^https?:\/\/[^/]+/, 'https://davaxana.com'),
                ),
        ),
    ];
    await save(`${ROOT}/discovery-davaxana.json`, {
        expected: urls.length,
        urls,
    });
    let cursor = 0,
        completed = 0,
        failed = 0;
    async function worker() {
        while (cursor < urls.length) {
            const url = urls[cursor++],
                id = url.match(/\/product\/(\d+)/)[1];
            const dest = `${ROOT}/records/davaxana-${id}.json`;

            if (await exists(dest)) {
                completed++;
                continue;
            }

            try {
                const { body } = await get(url);
                const out = parseDava(body, url);
                await save(dest, out);
                completed++;
            } catch (e) {
                failed++;
                await appendFile(
                    `${ROOT}/failures.jsonl`,
                    JSON.stringify({
                        source: 'davaxana',
                        url,
                        error: e.message,
                        at: new Date().toISOString(),
                    }) + '\n',
                );
            }

            if ((completed + failed) % 25 === 0) {
                await save(`${ROOT}/progress-davaxana.json`, {
                    expected: urls.length,
                    completed,
                    failed,
                    at: new Date().toISOString(),
                });
                console.log(
                    `davaxana ${completed}/${urls.length} failed ${failed}`,
                );
            }

            await sleep(350);
        }
    }
    await Promise.all(
        Array.from(
            {
                length: Math.max(
                    1,
                    Math.min(
                        20,
                        Number(
                            process.argv
                                .find((a) => a.startsWith('--concurrency='))
                                ?.split('=')[1] ?? 6,
                        ),
                    ),
                ),
            },
            worker,
        ),
    );
    await save(`${ROOT}/progress-davaxana.json`, {
        expected: urls.length,
        completed,
        failed,
        finished: true,
        at: new Date().toISOString(),
    });
}

if (
    process.argv[1] &&
    import.meta.url === pathToFileURL(process.argv[1]).href
) {
    await mkdir(`${ROOT}/records`, { recursive: true });
    const results = await Promise.allSettled(
        process.argv.includes('--dava-only')
            ? [dava()]
            : process.argv.includes('--bego-only')
              ? [bego()]
              : [bego(), dava()],
    );

    for (const r of results) {
        if (r.status === 'rejected') {
            console.error(r.reason);
            process.exitCode = 1;
        }
    }
}
