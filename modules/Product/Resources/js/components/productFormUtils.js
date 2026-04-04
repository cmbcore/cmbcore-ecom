import { createSkuRow } from '@admin/components/SkuVariantManager';

function normalizeNullableNumber(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return Number(value);
}

function normalizeSkuAttributes(attributes = []) {
    return attributes
        .filter((attribute) => attribute && (attribute.attribute_name || attribute.attribute_value))
        .map((attribute) => ({
            attribute_name: attribute.attribute_name ?? '',
            attribute_value: attribute.attribute_value ?? '',
        }));
}

function inferAttributeSets(skus = []) {
    const groups = new Map();

    skus.forEach((sku) => {
        (sku.attributes ?? []).forEach((attribute) => {
            const name = String(attribute.attribute_name ?? '').trim();
            const value = String(attribute.attribute_value ?? '').trim();

            if (!name || !value) {
                return;
            }

            const values = groups.get(name) ?? new Set();
            values.add(value);
            groups.set(name, values);
        });
    });

    return Array.from(groups.entries()).map(([name, values]) => ({
        name,
        values: Array.from(values).join(', '),
    }));
}

function normalizeSku(sku, index = 0) {
    const normalized = createSkuRow(normalizeSkuAttributes(sku?.attributes ?? []), index);

    return {
        ...normalized,
        id: sku?.id,
        client_key: sku?.client_key ?? normalized.client_key,
        name: sku?.name ?? normalized.name,
        sku_code: sku?.sku_code ?? '',
        price: normalizeNullableNumber(sku?.price) ?? 0,
        compare_price: normalizeNullableNumber(sku?.compare_price),
        cost: normalizeNullableNumber(sku?.cost),
        weight: normalizeNullableNumber(sku?.weight),
        stock_quantity: normalizeNullableNumber(sku?.stock_quantity) ?? 0,
        low_stock_threshold: normalizeNullableNumber(sku?.low_stock_threshold) ?? 5,
        barcode: sku?.barcode ?? '',
        status: sku?.status ?? 'active',
        sort_order: normalizeNullableNumber(sku?.sort_order) ?? index,
        attributes: normalizeSkuAttributes(sku?.attributes ?? []),
    };
}

function normalizeResizeSettings(resizeSettings = {}) {
    const widths = Array.isArray(resizeSettings?.widths)
        ? resizeSettings.widths.map((value) => Number(value)).filter((value) => value > 0)
        : [];

    return widths.length > 0 ? { widths } : { widths: [200, 400, 800] };
}

function normalizeMediaItem(item, index = 0) {
    return {
        uid: item?.uid ?? `media-${item?.id ?? index}`,
        id: item?.id,
        type: item?.type ?? 'image',
        filename: item?.filename ?? item?.path ?? `media-${index + 1}`,
        size: normalizeNullableNumber(item?.size) ?? 0,
        url: item?.url ?? null,
        alt_text: item?.alt_text ?? '',
        product_sku_id: normalizeNullableNumber(item?.product_sku_id),
        sku_key: item?.sku_key ?? null,
        resize_settings: normalizeResizeSettings(item?.metadata?.resize_settings ?? item?.resize_settings ?? {}),
    };
}

export function normalizeProductFormValues(product) {
    if (!product) {
        return {
            name: '',
            slug: '',
            description: '',
            short_description: '',
            status: 'draft',
            type: 'simple',
            category_id: undefined,
            brand: '',
            meta_title: '',
            meta_description: '',
            meta_keywords: '',
            rating_value: null,
            review_count: 0,
            sold_count: 0,
            is_featured: false,
            attribute_sets: [],
            skus: [createSkuRow()],
            media: [],
        };
    }

    const skus = (product.skus ?? []).map((sku, index) => normalizeSku(sku, index));

    return {
        name: product.name ?? '',
        slug: product.slug ?? '',
        description: product.description ?? '',
        short_description: product.short_description ?? '',
        status: product.status ?? 'draft',
        type: product.type ?? 'simple',
        category_id: product.category_id ?? undefined,
        brand: product.brand ?? '',
        meta_title: product.meta_title ?? '',
        meta_description: product.meta_description ?? '',
        meta_keywords: product.meta_keywords ?? '',
        rating_value: normalizeNullableNumber(product.rating_value),
        review_count: normalizeNullableNumber(product.review_count) ?? 0,
        sold_count: normalizeNullableNumber(product.sold_count) ?? 0,
        is_featured: Boolean(product.is_featured),
        attribute_sets: inferAttributeSets(skus),
        skus: skus.length > 0 ? skus : [createSkuRow()],
        media: (product.media ?? []).map((item, index) => normalizeMediaItem(item, index)),
    };
}

function serializeSkus(skus = []) {
    return skus.map((sku, index) => ({
        id: sku.id ?? null,
        client_key: sku.client_key ?? `sku-${index}`,
        name: sku.name ?? '',
        sku_code: sku.sku_code ?? '',
        price: normalizeNullableNumber(sku.price) ?? 0,
        compare_price: normalizeNullableNumber(sku.compare_price),
        cost: normalizeNullableNumber(sku.cost),
        weight: normalizeNullableNumber(sku.weight),
        stock_quantity: normalizeNullableNumber(sku.stock_quantity) ?? 0,
        low_stock_threshold: normalizeNullableNumber(sku.low_stock_threshold) ?? 5,
        barcode: sku.barcode ?? '',
        status: sku.status ?? 'active',
        sort_order: normalizeNullableNumber(sku.sort_order) ?? index,
        attributes: normalizeSkuAttributes(sku.attributes ?? []),
    }));
}

function serializeMedia(items = []) {
    const uploads = [];
    const media = items.map((item, index) => {
        const basePayload = {
            alt_text: item.alt_text ?? '',
            position: index,
            sku_key: item.sku_key ?? null,
            product_sku_id: normalizeNullableNumber(item.product_sku_id),
            resize_settings: normalizeResizeSettings(item.resize_settings ?? {}),
        };

        if (item.id) {
            return {
                id: item.id,
                ...basePayload,
            };
        }

        if (item.file instanceof File) {
            const uploadIndex = uploads.length;
            uploads.push(item.file);

            return {
                upload_index: uploadIndex,
                ...basePayload,
            };
        }

        return null;
    }).filter(Boolean);

    return { media, uploads };
}

export function toProductFormData(values, method = 'POST') {
    const formData = new FormData();

    [
        'name',
        'slug',
        'description',
        'short_description',
        'status',
        'type',
        'brand',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'rating_value',
        'review_count',
        'sold_count',
    ].forEach((field) => {
        const value = values[field];

        if (value !== undefined && value !== null) {
            formData.append(field, String(value));
        }
    });

    if (Object.prototype.hasOwnProperty.call(values, 'category_id')) {
        formData.append('category_id', values.category_id ?? '');
    }

    formData.append('is_featured', values.is_featured ? '1' : '0');
    formData.append('skus', JSON.stringify(serializeSkus(values.skus ?? [])));

    const { media, uploads } = serializeMedia(values.media ?? []);
    formData.append('media', JSON.stringify(media));
    uploads.forEach((file, index) => {
        formData.append(`uploads[${index}]`, file);
    });

    if (method.toUpperCase() !== 'POST') {
        formData.append('_method', method.toUpperCase());
    }

    return formData;
}

export function applyProductFormErrors(form, error) {
    const errors = error.response?.data?.errors ?? {};
    const fields = Object.entries(errors).map(([name, messages]) => ({
        name: name.split('.').map((segment) => (/^\d+$/.test(segment) ? Number(segment) : segment)),
        errors: Array.isArray(messages) ? messages : [String(messages)],
    }));

    if (fields.length > 0) {
        form.setFields(fields);
    }
}

export function buildSkuOptions(skus = []) {
    return skus.map((sku, index) => {
        const label = sku.name || sku.sku_code || `SKU ${index + 1}`;

        if (sku.id) {
            return {
                label,
                value: `id:${sku.id}`,
            };
        }

        return {
            label,
            value: `key:${sku.client_key ?? `sku-${index}`}`,
        };
    });
}
