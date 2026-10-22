import { createHash } from 'node:crypto';
import { readFile, readdir, writeFile, mkdir, access } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';
import { ROOT, clean, norm, get } from './catalog-extract.mjs';
const tokenCache = new Map();
export function tokens(name) {
    if (tokenCache.has(name)) {
        return tokenCache.get(name);
    }

    const result = [
        ...new Set(
            norm(name)
                .replace(/میلی\s*لیتری?/g, 'میلی لیتر')
                .replace(/میلی\s*گرمی?/g, 'میلی گرم')
                .replace(/\b([0-9]+)\s*عددی/g, '$1 عدد')
                .replace(/عددی/g, 'عدد')
                .replace(/گرمی/g, 'گرم')
                .replace(/لیتری/g, 'لیتر')
                .replace(/کلییر/g, 'کلیر')
                .replace(/اس پی اف|اس اف پی|\bspf\b/g, 'اسپیاف')
                .replace(/(?<!\p{L})(موهای|موی|موها)(?!\p{L})/gu, 'مو')
                .replace(/شست و شوی|شست و شو|شستشوی/g, 'شستشو')
                .replace(/اینتیمکس/g, 'اینتی مکس')
                .replace(/دهانشویه/g, 'دهان شویه')
                .replace(/ویتامین سی|ویتامین c|ویتامین ث/g, 'ویتامین ث')
                .replace(/زینکوویت سی/g, 'زینکوویت ث')
                .replace(/یورو ویتال|یورویتال/g, 'یوروویتال')
                .split(' ')
                .filter(
                    (t) =>
                        t &&
                        ![
                            'برای',
                            'از',
                            'حجم',
                            'بسته',
                            'طعم',
                            'حاوی',
                            'با',
                            'مناسب',
                            'مخصوص',
                            'و',
                            'درصد',
                        ].includes(t),
                ),
        ),
    ].sort();
    tokenCache.set(name, result);

    return result;
}
export function identity(p) {
    return tokens(p.name).join(' ');
}
const brand = (p) => {
    let s = norm(p.brand);

    if (/[\u0600-\u06ff]/.test(s)) {
        s = s.replace(/[a-z0-9.]+/g, ' ');
    }

    return s
        .replace(/کلییر/g, 'کلیر')
        .replace(/یورو ویتال|یورویتال/g, 'یوروویتال')
        .replace(/\s+/g, ' ')
        .trim();
};
const variant = (p) =>
    ['رنگ', 'طعم', 'رایحه'].map((k) => norm(p.facts?.[k] ?? '')).join('|');
const count = (p) =>
    norm(p.facts?.['تعداد'] ?? p.facts?.['تعداد در بسته'] ?? '').match(
        /\d+/,
    )?.[0] ?? '';
const num = (p) =>
    tokens(p.name)
        .filter((t) => /^\d/.test(t))
        .join('|');
export function englishIdentity(p) {
    const raw =
        p.english_name ??
        decodeURIComponent(
            new URL(p.url).pathname.split('/').filter(Boolean).at(-1) ?? '',
        );

    if (!/[a-z]/i.test(raw) || /[\u0600-\u06ff]/.test(raw)) {
        return '';
    }

    const words = norm(raw)
        .replace(/eurho vital/g, 'eurhovital')
        .split(' ')
        .map(
            (t) =>
                ({
                    caps: 'capsules',
                    capsule: 'capsules',
                    tablet: 'tablets',
                    tabs: 'tablets',
                    mll: 'ml',
                    grams: 'g',
                    gram: 'g',
                })[t] ?? t,
        )
        .filter((t) => t && !['for', 'with', 'and', 'the'].includes(t));

    return words.length >= 4 ? words.sort().join(' ') : '';
}
function compatibleVariants(a, b) {
    for (const key of ['رنگ', 'طعم', 'رایحه']) {
        const av = norm(a.facts?.[key] ?? ''),
            bv = norm(b.facts?.[key] ?? '');

        if (av && bv && av !== bv) {
            return false;
        }

        if (
            av &&
            !bv &&
            !av.split(' ').every((t) => norm(b.name).split(' ').includes(t))
        ) {
            return false;
        }

        if (
            bv &&
            !av &&
            !bv.split(' ').every((t) => norm(a.name).split(' ').includes(t))
        ) {
            return false;
        }
    }

    return true;
}
export function similarity(a, b) {
    const x = tokens(a.name),
        y = tokens(b.name);

    if (
        num(a) !== num(b) ||
        !compatibleVariants(a, b) ||
        (count(a) && count(b) && count(a) !== count(b))
    ) {
        return 0;
    }

    const ba = brand(a),
        bb = brand(b);

    if (ba && bb && ba !== bb) {
        return 0;
    }

    const en = englishIdentity(a);

    if (ba && ba === bb && en && en === englishIdentity(b)) {
        return 1;
    }

    const common = x.filter((t) => y.includes(t)).length;

    return (2 * common) / (x.length + y.length);
}
export function groupProducts(records) {
    const groups = [],
        exact = new Map(),
        index = new Map(),
        englishIndex = new Map(),
        review = [];

    for (const p of records.sort(
        (a, b) =>
            a.source.localeCompare(b.source) ||
            Number(a.source_id) - Number(b.source_id),
    )) {
        const key =
            identity(p) + '|' + brand(p) + '|' + variant(p) + '|' + count(p);
        let id = exact.get(key);

        if (id === undefined) {
            const ts = tokens(p.name),
                candidates = new Set(
                    englishIndex.get(englishIdentity(p)) ?? [],
                );

            for (const t of ts
                .filter((t) => t.length > 2 && !/^\d/.test(t))
                .sort(
                    (a, b) =>
                        (index.get(a)?.size ?? 0) - (index.get(b)?.size ?? 0),
                )
                .slice(0, 3)) {
                for (const i of index.get(t) ?? []) {
                    candidates.add(i);
                }
            }

            let best = 0,
                match;

            for (const i of candidates) {
                const representative = groups[i][0];

                if (representative.source === p.source) {
                    continue;
                }

                const score = similarity(representative, p);

                if (score > best) {
                    best = score;
                    match = i;
                }
            }

            if (best >= 0.9999) {
                id = match;
            } else if (best >= 0.8) {
                review.push({
                    a: groups[match][0].url,
                    b: p.url,
                    a_name: groups[match][0].name,
                    b_name: p.name,
                    similarity: best,
                    decision: 'kept_separate_to_preserve_variants',
                });
            }
        }

        if (id === undefined) {
            id = groups.length;
            groups.push([]);
        }

        groups[id].push(p);
        exact.set(key, id);
        const englishKey = englishIdentity(p);

        if (englishKey) {
            if (!englishIndex.has(englishKey)) {
                englishIndex.set(englishKey, new Set());
            }

            englishIndex.get(englishKey).add(id);
        }

        for (const t of tokens(p.name)) {
            if (!index.has(t)) {
                index.set(t, new Set());
            }

            index.get(t).add(id);
        }
    }

    return { groups, review };
}
export function describe(p) {
    const f = p.facts ?? {},
        b = clean(p.brand).split(/[–(]/)[0].trim();
    const sentences = [
        `${p.name}${b && !norm(p.name).includes(norm(b)) ? ` از برند ${b}` : ''}.`,
    ];
    const form = f['فرم محصول'] ?? f['نوع محصول'];
    const count = f['تعداد'] ?? f['تعداد در بسته'];
    const volume = f['حجم'];

    if (form) {
        sentences.push(
            `شکل عرضهٔ این محصول ${form} است${count ? ` و بستهٔ آن ${count} دارد` : ''}.`,
        );
    } else if (count) {
        sentences.push(`این محصول در بستهٔ ${count} عرضه می‌شود.`);
    }

    if (volume && !norm(p.name).includes(norm(volume))) {
        sentences.push(`حجم بسته‌بندی ${volume} است.`);
    }

    const country = f['کشور سازنده'];

    if (country) {
        sentences.push(`کشور تولیدکننده: ${country}.`);
    }

    const maker = f['شرکت سازنده'];

    if (maker) {
        sentences.push(`تولیدکنندهٔ درج‌شده برای محصول، ${maker} است.`);
    }

    const material = f['ماده شاخص'];

    if (material && material.length < 120) {
        sentences.push(`مواد شاخص معرفی‌شده: ${material}.`);
    }

    const target = f['مناسب برای'] ?? f['نوع پوست'] ?? f['نوع مو'];

    if (target && target.length < 80) {
        sentences.push(`این مدل برای ${target} ارائه شده است.`);
    }

    return sentences.join('\n\n');
}
const rootAlias = {
    'مکمل ورزشی': 'مکمل ورزشی',
    'مکمل دارویی': 'مکمل دارویی',
    'تجهیزات و لوازم طبی': 'تجهیزات پزشکی',
    'آرایشی و بهداشتی': 'آرایشی و بهداشتی',
};
const aliases = {
    'حافظه و اعصاب': 'مغز و اعصاب',
    'تقویت سیستم ایمنی': 'سیستم ایمنی و سرما خوردگی',
    'مراقبت مو': 'مراقبت و بهداشت مو',
    'ویتامین و مواد معدنی': 'ویتامین ها و مواد معدنی',
    'دستگاه گوارش': 'گوارش',
    'تقویت پوست و مو': 'پوست، مو و ناخن',
    'تنظیم وزن': 'کاهش وزن',
    'مکمل کودکان': 'مکمل های تخصصی کودک',
};
export function selectionScore(p) {
    const facts = p.facts ?? {};
    const form = clean(facts['فرم محصول'] ?? facts['نوع محصول'] ?? '');
    const forms = [
        ['کپسول', 'سافت ژل', 'سافتژل'],
        ['قرص'],
        ['کرم'],
        ['ژل', 'ژل کرم'],
        ['شامپو'],
        ['سرم'],
        ['قطره'],
        ['شربت'],
        ['پودر', 'ساشه'],
    ];
    const primary = clean(p.name).split(' ')[0];
    const family = forms.find((f) => f.includes(primary));
    const conflicts = [];

    if (
        form &&
        family &&
        forms.some((f) => f.some((x) => form.includes(x))) &&
        !family.some((x) => form.includes(x))
    ) {
        conflicts.push('product_form_conflict');
    }

    const titleCount = clean(p.name).match(/(\d+)\s*عدد/)?.[1];

    if (titleCount && count(p) && titleCount !== count(p)) {
        conflicts.push('pack_count_conflict');
    }

    p.quality.consistency_issues = conflicts;
    p.quality.effective_score = p.quality.score - conflicts.length * 100;

    return p.quality.effective_score;
}
async function prepare() {
    const files = (await readdir(`${ROOT}/records`)).filter((f) =>
        f.endsWith('.json'),
    );
    const records = [];

    for (let i = 0; i < files.length; i += 64) {
        records.push(
            ...(await Promise.all(
                files
                    .slice(i, i + 64)
                    .map(async (file) =>
                        JSON.parse(
                            await readFile(`${ROOT}/records/${file}`, 'utf8'),
                        ),
                    ),
            )),
        );

        if (i % 1024 === 0) {
            console.log(`loaded ${records.length}/${files.length}`);
        }
    }

    let oldUrls = new Map();

    try {
        const data = JSON.parse(
            await readFile(`${ROOT}/discovery-davaxana.json`, 'utf8'),
        );
        oldUrls = new Map(
            data.urls.map((url) => [
                url.match(/\/product\/(\d+)/)[1],
                url.split('/').at(-1),
            ]),
        );
    } catch {}

    for (const p of records) {
        if (p.source === 'davaxana' && oldUrls.has(p.source_id)) {
            p.english_name = oldUrls.get(p.source_id);
        }

        if (p.source === 'davaxana') {
            p.url = `https://davaxana.com/product/${p.source_id}/catalog`;
        }
    }

    const categories = JSON.parse(
        await readFile(`${ROOT}/categories-begoosib.json`, 'utf8'),
    );
    const cm = new Map(categories.map((c) => [c.id, c]));
    const paths = categories.map((c) => {
        let cur = c;
        const path = [],
            seen = new Set();

        while (cur && !seen.has(cur.id)) {
            seen.add(cur.id);
            path.unshift(clean(cur.name));
            cur = cm.get(cur.parent);
        }

        return path;
    });
    const named = new Map(paths.map((p) => [norm(p.at(-1)), p]));
    const categoryPath = (p) => {
        let path = p.categories.map(clean);

        if (p.source === 'davaxana') {
            path = path.map((x) => aliases[norm(x)] ?? x);
            const match = named.get(norm(path.at(-1)));

            if (match) {
                return match;
            }

            path[0] = rootAlias[norm(path[0])] ?? path[0];
        }

        return path;
    };
    const { groups, review } = groupProducts(records),
        products = [],
        duplicates = [];

    for (const g of groups) {
        const ranked = [...g].sort(
            (a, b) => selectionScore(b) - selectionScore(a),
        );
        const chosen = ranked[0];
        const sources = g.map((p) => ({
            key: `${p.source}-${p.source_id}`,
            url: p.url,
            quality: p.quality,
        }));
        const imageCandidates = [
            ...new Set(
                ranked.flatMap((p) => p.images.slice(0, 8)).filter(Boolean),
            ),
        ];
        const row = {
            key: sources[0].key,
            sources,
            name: chosen.name,
            description: describe(chosen),
            short_description: chosen.name,
            category_path: categoryPath(chosen),
            description_source: chosen.url,
            description_mode: 'original_from_structured_facts',
            image_candidates: imageCandidates,
        };
        products.push(row);

        if (g.length > 1) {
            duplicates.push({
                name: chosen.name,
                selected: chosen.url,
                reason: 'consistency_then_structured_fact_completeness_then_description_detail',
                sources,
            });
        }
    }

    await writeFile(
        `${ROOT}/catalog.json`,
        JSON.stringify(
            {
                version: 1,
                generated_at: new Date().toISOString(),
                category_paths: paths,
                products,
            },
            null,
            2,
        ),
    );
    await writeFile(
        `${ROOT}/comparison.json`,
        JSON.stringify(
            {
                records: records.length,
                unique: products.length,
                duplicate_groups: duplicates.length,
                duplicates,
                needs_review: review,
            },
            null,
            2,
        ),
    );
    console.log(
        JSON.stringify({
            records: records.length,
            unique: products.length,
            duplicate_groups: duplicates.length,
            needs_review: review.length,
        }),
    );
}
async function images() {
    const file = `${ROOT}/catalog.json`,
        catalog = JSON.parse(await readFile(file, 'utf8'));
    let cursor = 0,
        done = 0,
        failed = 0;
    await mkdir('storage/app/public/products/catalog', { recursive: true });
    async function worker() {
        while (cursor < catalog.products.length) {
            const p = catalog.products[cursor++];
            p.main_image = null;
            let downloaded = false;

            for (const url of p.image_candidates) {
                try {
                    const basename = createHash('sha256')
                        .update(url)
                        .digest('hex')
                        .slice(0, 32);
                    const ext =
                        new URL(url).pathname
                            .match(/\.(jpg|jpeg|png|webp|gif)$/i)?.[1]
                            ?.toLowerCase() ?? 'jpg';
                    const path = `products/catalog/${basename}.${ext}`,
                        dest = `storage/app/public/${path}`;

                    try {
                        await access(dest);
                    } catch {
                        const { data, type } = await get(url, true);
                        downloaded = true;

                        if (
                            !type?.startsWith('image/') ||
                            data.length > 12 * 1024 * 1024 ||
                            data.length < 100
                        ) {
                            throw Error('Invalid image');
                        }

                        await writeFile(dest, data);
                    }

                    p.main_image = path;
                    break;
                } catch (e) {
                    p.image_error = e.message;
                }
            }

            done++;

            if (!p.main_image) {
                failed++;
            }

            if (done % 100 === 0) {
                console.log(
                    `images ${done}/${catalog.products.length}; missing ${failed}`,
                );
                await writeFile(
                    `${ROOT}/progress-images.json`,
                    JSON.stringify({
                        done,
                        total: catalog.products.length,
                        failed,
                        at: new Date().toISOString(),
                    }),
                );
            }

            if (downloaded) {
                await new Promise((r) => setTimeout(r, 200));
            }
        }
    }
    await Promise.all(Array.from({ length: 6 }, worker));
    await writeFile(
        `${ROOT}/catalog-ready.json`,
        JSON.stringify(catalog, null, 2),
    );
    await writeFile(
        `${ROOT}/progress-images.json`,
        JSON.stringify({
            done,
            total: catalog.products.length,
            failed,
            finished: true,
        }),
    );
}

if (
    process.argv[1] &&
    import.meta.url === pathToFileURL(process.argv[1]).href
) {
    if (process.argv.includes('--images')) {
        await images();
    } else {
        await prepare();
    }
}
