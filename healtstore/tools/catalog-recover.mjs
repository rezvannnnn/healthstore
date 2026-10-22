import { readFile, writeFile, readdir } from 'node:fs/promises';
import { ROOT, get, clean, quality } from './catalog-extract.mjs';
const missing = JSON.parse(
    await readFile(`${ROOT}/unavailable-davaxana.json`, 'utf8'),
);
let cursor = 0,
    recovered = 0;
const unavailable = [],
    basic = [];
async function worker() {
    while (cursor < missing.length) {
        const item = missing[cursor++];

        try {
            const { body } = await get(
                `https://davaxana.com/v1/ProductManagement/ProductInformation/ReadBasic?ProductId=${item.id}`,
            );
            const response = JSON.parse(body),
                p = response.Value;

            if (
                response.IsSuccess === false ||
                !p?.Name ||
                String(p.Id) !== item.id
            ) {
                throw Error('No public product basics');
            }

            const out = {
                source: 'davaxana',
                source_id: item.id,
                url: item.url,
                name: clean(p.Name),
                english_name: clean(p.ShortName),
                brand: '',
                categories: [
                    clean(p.GroupTitle),
                    clean(p.SubGroupTitle),
                ].filter(Boolean),
                facts: {},
                images: p.PicPath ? [p.PicPath] : [],
                quality: quality('', {}),
                source_detail_status: 'basic_only',
                retrieved_at: new Date().toISOString(),
            };
            await writeFile(
                `${ROOT}/records/davaxana-${item.id}.json`,
                JSON.stringify(out, null, 2),
            );
            recovered++;
            basic.push({
                id: item.id,
                name: out.name,
                full_detail_error: item.error,
            });
        } catch {
            unavailable.push(item);
        }

        await new Promise((r) => setTimeout(r, 200));
    }
}
await Promise.all(Array.from({ length: 6 }, worker));
await writeFile(
    `${ROOT}/unavailable-davaxana.json`,
    JSON.stringify(unavailable, null, 2),
);
await writeFile(
    `${ROOT}/basic-only-products.json`,
    JSON.stringify(basic, null, 2),
);
const progress = JSON.parse(
    await readFile(`${ROOT}/progress-davaxana.json`, 'utf8'),
);
progress.completed = (await readdir(`${ROOT}/records`)).filter((f) =>
    /^davaxana-\d+\.json$/.test(f),
).length;
progress.failed = unavailable.length;
progress.basic_only = basic.length;
await writeFile(`${ROOT}/progress-davaxana.json`, JSON.stringify(progress));
console.log({
    recovered,
    unavailable: unavailable.length,
    completed: progress.completed,
});
