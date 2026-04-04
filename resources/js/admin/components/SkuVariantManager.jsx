import React, { startTransition, useEffect, useState } from 'react';
import { Button, Card, Form, Input, InputNumber, Select, Space, Tag } from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { showDeleteConfirm } from '@admin/utils/confirm';

function createSkuKey() {
    if (window.crypto?.randomUUID) {
        return `sku-${window.crypto.randomUUID()}`;
    }

    return `sku-${Date.now()}-${Math.round(Math.random() * 100000)}`;
}

function normalizeAttributeSets(attributeSets = []) {
    return attributeSets
        .map((item) => ({
            name: String(item?.name ?? '').trim(),
            values: String(item?.values ?? '')
                .split(',')
                .map((value) => value.trim())
                .filter(Boolean),
        }))
        .filter((item) => item.name && item.values.length > 0);
}

function buildCombinations(groups) {
    if (groups.length === 0) {
        return [];
    }

    return groups.reduce(
        (combinations, group) => combinations.flatMap((combo) => group.values.map((value) => [
            ...combo,
            { attribute_name: group.name, attribute_value: value },
        ])),
        [[]],
    );
}

function attributeSignature(attributes = []) {
    return attributes
        .map((attribute) => `${attribute.attribute_name}:${attribute.attribute_value}`)
        .join('|');
}

export function createSkuRow(attributes = [], index = 0) {
    return {
        client_key: createSkuKey(),
        name: attributes.map((attribute) => attribute.attribute_value).join(' - '),
        sku_code: '',
        price: 0,
        compare_price: null,
        cost: null,
        weight: null,
        stock_quantity: 0,
        low_stock_threshold: 5,
        barcode: '',
        status: 'active',
        sort_order: index,
        attributes,
    };
}

export default function SkuVariantManager({ form, productType = 'simple' }) {
    const { t } = useLocale();
    const [bulkPrice, setBulkPrice] = useState(null);
    const [bulkStock, setBulkStock] = useState(null);

    useEffect(() => {
        if (productType !== 'simple') {
            return;
        }

        const currentSkus = form.getFieldValue('skus') ?? [];

        if (currentSkus.length === 1) {
            return;
        }

        form.setFieldValue('skus', [currentSkus[0] ?? createSkuRow()]);
        form.setFieldValue('attribute_sets', []);
    }, [form, productType]);

    function handleGenerate() {
        const groups = normalizeAttributeSets(form.getFieldValue('attribute_sets') ?? []);

        if (groups.length === 0) {
            return;
        }

        const combinations = buildCombinations(groups);
        const existingSkus = form.getFieldValue('skus') ?? [];
        const existingSkuMap = new Map(
            existingSkus.map((sku) => [attributeSignature(sku.attributes ?? []), sku]),
        );

        const nextSkus = combinations.map((attributes, index) => {
            const signature = attributeSignature(attributes);
            const existingSku = existingSkuMap.get(signature);

            if (existingSku) {
                return {
                    ...existingSku,
                    name: existingSku.name || attributes.map((attribute) => attribute.attribute_value).join(' - '),
                    sort_order: index,
                    attributes,
                };
            }

            return createSkuRow(attributes, index);
        });

        startTransition(() => {
            form.setFieldValue('skus', nextSkus);
        });
    }

    function handleApplyBulk(field, value) {
        if (value === null || value === undefined || value === '') {
            return;
        }

        const nextSkus = (form.getFieldValue('skus') ?? []).map((sku) => ({
            ...sku,
            [field]: value,
        }));

        startTransition(() => {
            form.setFieldValue('skus', nextSkus);
        });
    }

    return (
        <Space direction="vertical" size={24} style={{ display: 'flex' }}>
            {productType === 'variable' ? (
                <Card title={t('sku_variants.attribute_sets.title')}>
                    <Form.List name="attribute_sets">
                        {(fields, { add, remove }) => (
                            <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                                {fields.map((field) => (
                                    <Space key={field.key} align="start" wrap>
                                        <Form.Item
                                            label={t('sku_variants.attribute_sets.fields.name')}
                                            name={[field.name, 'name']}
                                            style={{ minWidth: 180 }}
                                        >
                                            <Input placeholder={t('sku_variants.attribute_sets.placeholders.name')} />
                                        </Form.Item>

                                        <Form.Item
                                            label={t('sku_variants.attribute_sets.fields.values')}
                                            name={[field.name, 'values']}
                                            style={{ minWidth: 320 }}
                                        >
                                            <Input placeholder={t('sku_variants.attribute_sets.placeholders.values')} />
                                        </Form.Item>

                                        <Button
                                            danger
                                            icon={<FontIcon name="delete" />}
                                            onClick={() => showDeleteConfirm({
                                                title: 'Xóa nhóm thuộc tính?',
                                                content: 'Nhóm thuộc tính này và các giá trị của nó sẽ bị gỡ khỏi form.',
                                                onConfirm: () => remove(field.name),
                                            })}
                                        >
                                            {t('sku_variants.actions.remove_group')}
                                        </Button>
                                    </Space>
                                ))}

                                <Space wrap>
                                    <Button icon={<FontIcon name="create" />} onClick={() => add()}>
                                        {t('sku_variants.actions.add_group')}
                                    </Button>
                                    <Button type="primary" icon={<FontIcon name="generate" />} onClick={handleGenerate}>
                                        {t('sku_variants.actions.generate')}
                                    </Button>
                                </Space>
                            </Space>
                        )}
                    </Form.List>
                </Card>
            ) : null}

            <Card title={t('sku_variants.bulk.title')}>
                <Space wrap>
                    <InputNumber
                        min={0}
                        value={bulkPrice}
                        onChange={setBulkPrice}
                        placeholder={t('sku_variants.bulk.placeholders.price')}
                    />
                    <Button onClick={() => handleApplyBulk('price', bulkPrice)}>
                        {t('sku_variants.bulk.actions.apply_price')}
                    </Button>

                    <InputNumber
                        min={0}
                        value={bulkStock}
                        onChange={setBulkStock}
                        placeholder={t('sku_variants.bulk.placeholders.stock')}
                    />
                    <Button onClick={() => handleApplyBulk('stock_quantity', bulkStock)}>
                        {t('sku_variants.bulk.actions.apply_stock')}
                    </Button>
                </Space>
            </Card>

            <Form.List name="skus">
                {(fields, { add, remove }) => (
                    <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                        {fields.map((field, index) => (
                            <Card
                                key={field.key}
                                title={t('sku_variants.item_title', { number: index + 1 })}
                                extra={
                                    productType === 'variable' || fields.length > 1 ? (
                                        <Button
                                            danger
                                            icon={<FontIcon name="delete" />}
                                            onClick={() => showDeleteConfirm({
                                                title: 'Xóa SKU?',
                                                content: 'SKU này sẽ bị gỡ khỏi form sản phẩm hiện tại.',
                                                onConfirm: () => remove(field.name),
                                            })}
                                        >
                                            {t('common.delete')}
                                        </Button>
                                    ) : null
                                }
                            >
                                <Form.Item name={[field.name, 'client_key']} hidden>
                                    <Input />
                                </Form.Item>

                                <Form.Item name={[field.name, 'sort_order']} hidden>
                                    <Input />
                                </Form.Item>

                                <Space wrap size={16} align="start">
                                    <Form.Item label={t('sku_variants.fields.name')} name={[field.name, 'name']} style={{ minWidth: 220 }}>
                                        <Input placeholder={t('sku_variants.placeholders.name')} />
                                    </Form.Item>

                                    <Form.Item label={t('sku_variants.fields.sku_code')} name={[field.name, 'sku_code']} style={{ minWidth: 180 }}>
                                        <Input placeholder={t('sku_variants.placeholders.sku_code')} />
                                    </Form.Item>

                                    <Form.Item label={t('sku_variants.fields.status')} name={[field.name, 'status']} style={{ minWidth: 180 }}>
                                        <Select
                                            options={[
                                                { label: t('sku_variants.status_options.active'), value: 'active' },
                                                { label: t('sku_variants.status_options.inactive'), value: 'inactive' },
                                            ]}
                                        />
                                    </Form.Item>
                                </Space>

                                <Space wrap size={16} align="start">
                                    <Form.Item label={t('sku_variants.fields.price')} name={[field.name, 'price']} rules={[{ required: true, message: t('sku_variants.validation.price_required') }]}>
                                        <InputNumber min={0} style={{ width: 160 }} />
                                    </Form.Item>

                                    <Form.Item label={t('sku_variants.fields.compare_price')} name={[field.name, 'compare_price']}>
                                        <InputNumber min={0} style={{ width: 160 }} />
                                    </Form.Item>

                                    <Form.Item label={t('sku_variants.fields.cost')} name={[field.name, 'cost']}>
                                        <InputNumber min={0} style={{ width: 160 }} />
                                    </Form.Item>

                                    <Form.Item label={t('sku_variants.fields.weight')} name={[field.name, 'weight']}>
                                        <InputNumber min={0} style={{ width: 160 }} />
                                    </Form.Item>
                                </Space>

                                <Space wrap size={16} align="start">
                                    <Form.Item label={t('sku_variants.fields.stock_quantity')} name={[field.name, 'stock_quantity']} rules={[{ required: true, message: t('sku_variants.validation.stock_required') }]}>
                                        <InputNumber min={0} style={{ width: 160 }} />
                                    </Form.Item>

                                    <Form.Item label={t('sku_variants.fields.low_stock_threshold')} name={[field.name, 'low_stock_threshold']}>
                                        <InputNumber min={0} style={{ width: 180 }} />
                                    </Form.Item>

                                    <Form.Item label={t('sku_variants.fields.barcode')} name={[field.name, 'barcode']} style={{ minWidth: 220 }}>
                                        <Input placeholder={t('sku_variants.placeholders.barcode')} />
                                    </Form.Item>
                                </Space>

                                <Form.List name={[field.name, 'attributes']}>
                                    {(attributeFields, attributeOperations) => (
                                        <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                                            <div className="sku-variant-manager__attribute-header">
                                                <span>{t('sku_variants.fields.attributes')}</span>
                                                <Button size="small" icon={<FontIcon name="create" />} onClick={() => attributeOperations.add()}>
                                                    {t('sku_variants.actions.add_attribute')}
                                                </Button>
                                            </div>

                                            {attributeFields.length > 0 ? (
                                                <div className="sku-variant-manager__attribute-tags">
                                                    {(form.getFieldValue(['skus', field.name, 'attributes']) ?? []).map((attribute, attributeIndex) => (
                                                        <Tag key={`${field.key}-tag-${attributeIndex}`}>
                                                            {attribute?.attribute_name}: {attribute?.attribute_value}
                                                        </Tag>
                                                    ))}
                                                </div>
                                            ) : null}

                                            {attributeFields.map((attributeField) => (
                                                <Space key={attributeField.key} align="start" wrap>
                                                    <Form.Item
                                                        label={t('sku_variants.fields.attribute_name')}
                                                        name={[attributeField.name, 'attribute_name']}
                                                    >
                                                        <Input placeholder={t('sku_variants.placeholders.attribute_name')} />
                                                    </Form.Item>

                                                    <Form.Item
                                                        label={t('sku_variants.fields.attribute_value')}
                                                        name={[attributeField.name, 'attribute_value']}
                                                    >
                                                        <Input placeholder={t('sku_variants.placeholders.attribute_value')} />
                                                    </Form.Item>

                                                    <Button
                                                        danger
                                                        icon={<FontIcon name="delete" />}
                                                        onClick={() => showDeleteConfirm({
                                                            title: 'Xóa thuộc tính?',
                                                            content: 'Thuộc tính này sẽ bị gỡ khỏi SKU.',
                                                            onConfirm: () => attributeOperations.remove(attributeField.name),
                                                        })}
                                                    >
                                                        {t('common.delete')}
                                                    </Button>
                                                </Space>
                                            ))}
                                        </Space>
                                    )}
                                </Form.List>
                            </Card>
                        ))}

                        <Button
                            icon={<FontIcon name="create" />}
                            onClick={() => add(createSkuRow([], fields.length))}
                            disabled={productType === 'simple' && fields.length >= 1}
                        >
                            {t('sku_variants.actions.add_sku')}
                        </Button>
                    </Space>
                )}
            </Form.List>
        </Space>
    );
}
