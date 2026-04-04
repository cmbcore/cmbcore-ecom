import React, { useCallback, useDeferredValue, useEffect, useState } from 'react';
import { Button, Card, Input, Popconfirm, Select, Space, Table, Tag, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import CategoryTreeSelect from '@admin/components/CategoryTreeSelect';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import StatusBadge from '@admin/components/ui/StatusBadge';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

function ProductNameCell({ product }) {
    return (
        <div>
            <strong>{product.name}</strong>
            <div>{product.slug}</div>
        </div>
    );
}

function formatPriceRange(product, locale) {
    if (product.min_price === null || product.min_price === undefined) {
        return '0';
    }

    const formatter = new Intl.NumberFormat(locale === 'vi' ? 'vi-VN' : 'en-US', {
        maximumFractionDigits: 2,
    });

    if (product.min_price === product.max_price || product.max_price === null || product.max_price === undefined) {
        return formatter.format(product.min_price);
    }

    return `${formatter.format(product.min_price)} - ${formatter.format(product.max_price)}`;
}

export default function ProductList() {
    const navigate = useNavigate();
    const { currentLocale, t } = useLocale();
    const [loading, setLoading] = useState(true);
    const [categories, setCategories] = useState([]);
    const [products, setProducts] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });
    const [filters, setFilters] = useState({
        search: '',
        status: undefined,
        type: undefined,
        category_id: undefined,
        page: 1,
    });
    const deferredSearch = useDeferredValue(filters.search);

    function updateFilter(name, value) {
        setFilters((current) => ({
            ...current,
            [name]: value,
            page: name === 'page' ? value : 1,
        }));
    }

    const fetchCategories = useCallback(async () => {
        try {
            const response = await api.get('/categories/tree');
            setCategories(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('products.messages.load_categories_failed'));
        }
    }, [t]);

    const fetchProducts = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/products', {
                params: {
                    search: deferredSearch || undefined,
                    status: filters.status,
                    type: filters.type,
                    category_id: filters.category_id,
                    page: filters.page,
                },
            });

            setProducts(response.data.data ?? []);
            setMeta(response.data.meta ?? {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('products.messages.load_failed'));
        } finally {
            setLoading(false);
        }
    }, [deferredSearch, filters.category_id, filters.page, filters.status, filters.type, t]);

    async function handleDelete(productId) {
        try {
            await api.delete(`/products/${productId}`);
            message.success(t('products.messages.deleted'));
            await fetchProducts();
        } catch (error) {
            message.error(error.response?.data?.message ?? t('products.messages.delete_failed'));
        }
    }

    useEffect(() => {
        fetchCategories();
    }, [fetchCategories]);

    useEffect(() => {
        fetchProducts();
    }, [fetchProducts]);

    return (
        <div>
            <PageHeader
                title={t('products.title')}
                description={t('products.description')}
                extra={[
                    { label: t('products.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchProducts },
                    { label: t('products.actions.create'), icon: <FontIcon name="create" />, type: 'primary', onClick: () => navigate('/admin/products/create') },
                ]}
            />

            <Card className="product-filters" bordered={false}>
                <Space wrap size={16}>
                    <Input
                        allowClear
                        value={filters.search}
                        onChange={(event) => updateFilter('search', event.target.value)}
                        placeholder={t('products.placeholders.search')}
                        style={{ width: 240 }}
                    />

                    <Select
                        allowClear
                        value={filters.status}
                        onChange={(value) => updateFilter('status', value)}
                        placeholder={t('products.placeholders.status')}
                        options={[
                            { label: t('products.status_options.draft'), value: 'draft' },
                            { label: t('products.status_options.active'), value: 'active' },
                            { label: t('products.status_options.archived'), value: 'archived' },
                        ]}
                        style={{ width: 180 }}
                    />

                    <Select
                        allowClear
                        value={filters.type}
                        onChange={(value) => updateFilter('type', value)}
                        placeholder={t('products.placeholders.type')}
                        options={[
                            { label: t('products.type_options.simple'), value: 'simple' },
                            { label: t('products.type_options.variable'), value: 'variable' },
                        ]}
                        style={{ width: 180 }}
                    />

                    <CategoryTreeSelect
                        categories={categories}
                        value={filters.category_id}
                        onChange={(value) => updateFilter('category_id', value)}
                        placeholder={t('products.placeholders.category_id')}
                        style={{ width: 280 }}
                    />
                </Space>
            </Card>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={products}
                scroll={{ x: 1100 }}
                columns={[
                    {
                        title: t('products.table.name'),
                        dataIndex: 'name',
                        width: 220,
                        ellipsis: true,
                        render: (_, product) => <ProductNameCell product={product} />,
                    },
                    {
                        title: t('products.table.category'),
                        dataIndex: ['category', 'name'],
                        width: 150,
                        ellipsis: true,
                        render: (_, product) => product.category?.name ?? '-',
                    },
                    {
                        title: t('products.table.type'),
                        dataIndex: 'type',
                        width: 120,
                        render: (value) => t(`products.type_options.${value}`),
                    },
                    {
                        title: t('products.table.status'),
                        dataIndex: 'status',
                        width: 130,
                        render: (value) => <StatusBadge value={value} />,
                    },
                    {
                        title: t('products.table.featured'),
                        dataIndex: 'is_featured',
                        width: 90,
                        render: (value) => (
                            <Tag color={value ? 'gold' : 'default'}>
                                {value ? t('common.yes') : t('common.no')}
                            </Tag>
                        ),
                    },
                    {
                        title: t('products.table.price'),
                        key: 'price',
                        width: 140,
                        render: (_, product) => formatPriceRange(product, currentLocale),
                    },
                    {
                        title: t('products.table.stock'),
                        dataIndex: 'total_stock',
                        width: 80,
                    },
                    {
                        title: t('products.table.skus'),
                        dataIndex: 'sku_count',
                        width: 70,
                    },
                    {
                        title: t('products.table.media'),
                        dataIndex: 'media_count',
                        width: 70,
                    },
                    {
                        title: t('products.table.actions'),
                        key: 'actions',
                        width: 160,
                        fixed: 'right',
                        render: (_, product) => (
                            <Space size={4}>
                                <Button size="small" icon={<FontIcon name="edit" />} onClick={() => navigate(`/admin/products/${product.id}/edit`)}>
                                    {t('products.actions.edit')}
                                </Button>
                                <Popconfirm
                                    title={t('products.confirm_delete.title')}
                                    description={t('products.confirm_delete.description')}
                                    okText={t('products.confirm_delete.ok')}
                                    cancelText={t('products.confirm_delete.cancel')}
                                    onConfirm={() => handleDelete(product.id)}
                                >
                                    <Button size="small" danger icon={<FontIcon name="delete" />}>
                                        {t('products.actions.delete')}
                                    </Button>
                                </Popconfirm>
                            </Space>
                        ),
                    },
                ]}
                pagination={{
                    current: meta.current_page,
                    pageSize: meta.per_page,
                    total: meta.total,
                    onChange: (page) => updateFilter('page', page),
                }}
            />
        </div>
    );
}
