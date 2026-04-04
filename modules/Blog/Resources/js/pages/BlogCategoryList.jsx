import React, { useCallback, useEffect, useState } from 'react';
import { Button, Card, Input, Popconfirm, Select, Space, Table, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import StatusBadge from '@admin/components/ui/StatusBadge';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

export default function BlogCategoryList() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [loading, setLoading] = useState(true);
    const [categories, setCategories] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });
    const [filters, setFilters] = useState({
        search: '',
        status: undefined,
        page: 1,
    });

    function updateFilter(name, value) {
        setFilters((current) => ({
            ...current,
            [name]: value,
            page: name === 'page' ? value : 1,
        }));
    }

    const fetchCategories = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/blog/categories', {
                params: {
                    search: filters.search || undefined,
                    status: filters.status,
                    page: filters.page,
                },
            });

            setCategories(response.data.data ?? []);
            setMeta(response.data.meta ?? {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blog_categories.messages.load_failed'));
        } finally {
            setLoading(false);
        }
    }, [filters.page, filters.search, filters.status, t]);

    async function handleDelete(categoryId) {
        try {
            await api.delete(`/blog/categories/${categoryId}`);
            message.success(t('blog_categories.messages.deleted'));
            await fetchCategories();
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blog_categories.messages.delete_failed'));
        }
    }

    useEffect(() => {
        fetchCategories();
    }, [fetchCategories]);

    return (
        <div>
            <PageHeader
                title={t('blog_categories.title')}
                description={t('blog_categories.description')}
                extra={[
                    { label: t('blog_categories.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchCategories },
                    { label: t('blog_categories.actions.create'), icon: <FontIcon name="create" />, type: 'primary', onClick: () => navigate('/admin/blog/categories/create') },
                ]}
            />

            <Card bordered={false}>
                <Space wrap size={16}>
                    <Input
                        allowClear
                        value={filters.search}
                        onChange={(event) => updateFilter('search', event.target.value)}
                        placeholder={t('blog_categories.placeholders.search')}
                        style={{ width: 260 }}
                    />

                    <Select
                        allowClear
                        value={filters.status}
                        onChange={(value) => updateFilter('status', value)}
                        placeholder={t('blog_categories.placeholders.status')}
                        options={[
                            { label: t('blog_categories.status_options.active'), value: 'active' },
                            { label: t('blog_categories.status_options.inactive'), value: 'inactive' },
                        ]}
                        style={{ width: 180 }}
                    />
                </Space>
            </Card>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={categories}
                columns={[
                    {
                        title: t('blog_categories.table.name'),
                        dataIndex: 'name',
                        render: (_, category) => (
                            <div>
                                <strong>{category.name}</strong>
                                <div>{category.slug}</div>
                            </div>
                        ),
                    },
                    {
                        title: t('blog_categories.table.status'),
                        dataIndex: 'status',
                        width: 140,
                        render: (value) => <StatusBadge value={value} />,
                    },
                    {
                        title: t('blog_categories.table.actions'),
                        key: 'actions',
                        width: 220,
                        render: (_, category) => (
                            <Space wrap>
                                <Button size="small" icon={<FontIcon name="edit" />} onClick={() => navigate(`/admin/blog/categories/${category.id}/edit`)}>
                                    {t('blog_categories.actions.edit')}
                                </Button>
                                <Popconfirm
                                    title={t('blog_categories.confirm_delete.title')}
                                    description={t('blog_categories.confirm_delete.description')}
                                    okText={t('blog_categories.confirm_delete.ok')}
                                    cancelText={t('blog_categories.confirm_delete.cancel')}
                                    onConfirm={() => handleDelete(category.id)}
                                >
                                    <Button size="small" danger icon={<FontIcon name="delete" />}>
                                        {t('blog_categories.actions.delete')}
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
