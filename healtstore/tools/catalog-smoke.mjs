import { writeFile } from 'node:fs/promises';
import http from 'node:http';
const root = 'http://127.0.0.1:8001';
function read(url, headers = {}) {
    return new Promise((resolve, reject) => {
        const request = http.get(url, { headers }, (response) => {
            const chunks = [];
            response.on('data', (chunk) => chunks.push(chunk));
            response.on('end', () =>
                resolve({
                    status: response.statusCode,
                    type: response.headers['content-type'],
                    body: Buffer.concat(chunks),
                }),
            );
            response.on('error', reject);
        });
        request.setTimeout(30000, () =>
            request.destroy(new Error('HTTP timeout')),
        );
        request.on('error', reject);
    });
}
const initial = await read(root);
const match = initial.body
    .toString()
    .match(/<script[^>]*data-page="app"[^>]*>([\s\S]*?)<\/script>/);

if (!match) {
    throw Error('Missing Inertia bootstrap');
}

const initialPage = JSON.parse(match[1]);
const headers = {
    'X-Inertia': 'true',
    'X-Inertia-Version': initialPage.version,
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'text/html',
};
const summary = [
    { path: '/', status: initial.status, component: initialPage.component },
];
async function page(path) {
    const r = await read(root + path, headers);
    let data;

    try {
        data = JSON.parse(r.body.toString());
    } catch {}

    summary.push({
        path,
        status: r.status,
        component: data?.component,
        total: data?.props?.pagination?.total,
    });

    return data;
}
await page('/products');
await page('/categories');
const results = await page('/products?search=DKH-000001');
const product = results?.props?.products?.[0];

if (!product) {
    throw Error('Imported product missing from storefront');
}

const details = await page(`/products/${encodeURIComponent(product.slug)}`);
summary.push({
    sample_sku: details.props.product.sku,
    price: details.props.product.price,
});

if (product.image) {
    const imageUrl = new URL(product.image, root);
    const response = await read(imageUrl);
    summary.push({
        image: imageUrl.href,
        status: response.status,
        type: response.type,
        bytes: response.body.length,
    });
}

const category = initialPage.props.categories?.find(
    (c) => c.products_count > 100,
);

if (category) {
    await page(`/categories/${encodeURIComponent(category.slug)}`);
}

await writeFile(
    'storage/app/catalog-import/storefront-smoke.json',
    JSON.stringify(summary, null, 2),
);
console.log(JSON.stringify(summary, null, 2));

if (summary.some((r) => r.status && r.status !== 200)) {
    process.exitCode = 1;
}
