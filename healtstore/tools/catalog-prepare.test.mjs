import assert from 'node:assert/strict';
import test from 'node:test';
import { groupProducts, similarity, describe } from './catalog-prepare.mjs';
const p = (name, source = 'begoosib', id = '1', extra = {}) => ({
    name,
    source,
    source_id: id,
    url: `https://${source}.com/product/${id}`,
    brand: 'هولیستیکا',
    facts: {},
    quality: { score: 1 },
    ...extra,
});
test('reordered names match but different pack sizes remain separate', () => {
    const a = p('کپسول مگنیفورت هولیستیکا 32 عددی'),
        b = p('كپسول مگنیفورت 32 عدد هولیستیكا', 'davaxana', '2');
    assert.equal(similarity(a, b), 1);
    assert.equal(groupProducts([a, b]).groups.length, 1);
    assert.equal(
        groupProducts([
            a,
            p('کپسول مگنیفورت هولیستیکا 60 عددی', 'davaxana', '3'),
        ]).groups.length,
        2,
    );
});
test('flavours and dosage are retained', () => {
    assert.equal(
        groupProducts([
            p('پودر گینر شکلات 1000 گرم'),
            p('پودر گینر وانیل 1000 گرم', 'davaxana', '2'),
        ]).groups.length,
        2,
    );
    assert.equal(
        similarity(p('ویتامین D3 1000 واحد'), p('ویتامین D3 2000 واحد')),
        0,
    );
});
test('descriptions use factual fields and exclude competitor marketing', () => {
    const d = describe(
        p('محصول نمونه', 'begoosib', '1', {
            facts: { 'کشور سازنده': 'ایران', 'شرکت سازنده': 'شرکت نمونه' },
            description: 'بهترین خرید فقط از رقیب',
        }),
    );
    assert.match(d, /ایران/);
    assert.doesNotMatch(d, /رقیب/);
});

test('same titles with conflicting pack facts, colours, or brands stay separate', () => {
    const a = p('محصول نمونه', 'begoosib', '1', {
        facts: { تعداد: '30 عدد', رنگ: 'روشن' },
    });
    const b = p('محصول نمونه', 'davaxana', '2', {
        facts: { تعداد: '60 عدد', رنگ: 'روشن' },
    });
    const c = p('محصول نمونه', 'davaxana', '3', {
        facts: { تعداد: '30 عدد', رنگ: 'تیره' },
    });
    const d = p('محصول نمونه', 'davaxana', '4', {
        brand: 'برند دیگر',
        facts: { تعداد: '30 عدد', رنگ: 'روشن' },
    });
    assert.equal(groupProducts([a, b, c, d]).groups.length, 4);
});

test('matching complete English product names can reconcile different Persian wording', () => {
    const a = p('ماسک تخصصی آلفا مو خشک 100 میلی لیتر', 'begoosib', '1', {
        english_name: 'Alpha dry hair mask 100 ml',
    });
    const b = p('ماسک آلفا ترمیمی 100 میلی لیتر', 'davaxana', '2', {
        english_name: 'Alpha dry hair mask 100 ml',
    });
    assert.equal(groupProducts([a, b]).groups.length, 1);
    assert.equal(
        groupProducts([a, { ...b, facts: { رنگ: 'تیره' } }]).groups.length,
        2,
    );
});
import { selectionScore } from './catalog-prepare.mjs';
test('description selection penalizes contradictory pack counts', () => {
    const inconsistent = p('کپسول نمونه 30 عددی', 'begoosib', '1', {
        facts: { تعداد: '60 عدد' },
        quality: { score: 80 },
    });
    const consistent = p('کپسول نمونه 30 عددی', 'davaxana', '2', {
        facts: { تعداد: '30 عدد' },
        quality: { score: 40 },
    });
    assert.ok(selectionScore(consistent) > selectionScore(inconsistent));
});

test('an explicit flavour in the title confirms a missing structured flavour', () => {
    const a = p(
        'پودر کراتین توت فرنگی 316 گرم دی وای نوتریشن',
        'begoosib',
        '1',
        { facts: { طعم: 'توت فرنگی' } },
    );
    const b = p(
        'پودر کراتین توت فرنگی 316 گرم دی وای نوتریشن',
        'davaxana',
        '2',
    );
    assert.equal(groupProducts([a, b]).groups.length, 1);
    assert.equal(
        groupProducts([a, { ...b, name: 'پودر کراتین 316 گرم دی وای نوتریشن' }])
            .groups.length,
        2,
    );
});
