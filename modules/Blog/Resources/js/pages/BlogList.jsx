import React, { useCallback, useDeferredValue, useEffect, useState } from 'react';
import { Button, Card, Input, Popconfirm, Select, Space, Table, Tag, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import StatusBadge from '@admin/components/ui/StatusBadge';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import { formatDateTime } from '@admin/utils/dateTime';

function BlogTitleCell({ post }) {
    return (
        <div>
            <strong>{post.title}</strong>
            <div>{post.slug}</div>
        </div>
    );
}

export default function BlogList() {
    const navigate = useNavigate();
    const { currentLocale, t } = useLocale();
    const [loading, setLoading] = useState(true);
    const [posts, setPosts] = useState([]);
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
        blog_category_id: undefined,
        featured: undefined,
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

    const fetchPosts = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/blog/posts', {
                params: {
                    search: deferredSearch || undefined,
                    status: filters.status,
                    blog_category_id: filters.blog_category_id,
                    featured: filters.featured,
                    page: filters.page,
                },
            });

            setPosts(response.data.data ?? []);
            setMeta(response.data.meta ?? {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blogs.messages.load_failed'));
        } finally {
            setLoading(false);
        }
    }, [deferredSearch, filters.blog_category_id, filters.featured, filters.page, filters.status, t]);

    const fetchCategories = useCallback(async () => {
        try {
            const response = await api.get('/blog/categories', {
                params: { per_page: 100 },
            });

            setCategories(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blogs.messages.load_categories_failed'));
        }
    }, [t]);

    async function handleDelete(postId) {
        try {
            await api.delete(`/blog/posts/${postId}`);
            message.success(t('blogs.messages.deleted'));
            await fetchPosts();
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blogs.messages.delete_failed'));
        }
    }

    useEffect(() => {
        fetchPosts();
        fetchCategories();
    }, [fetchCategories, fetchPosts]);

    return (
        <div>
            <PageHeader
                title={t('blogs.title')}
                description={t('blogs.description')}
                extra={[
                    { label: t('blogs.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchPosts },
                    { label: t('blogs.actions.create'), icon: <FontIcon name="create" />, type: 'primary', onClick: () => navigate('/admin/blog/posts/create') },
                ]}
            />

            <Card bordered={false}>
                <Space wrap size={16}>
                    <Input
                        allowClear
                        value={filters.search}
                        onChange={(event) => updateFilter('search', event.target.value)}
                        placeholder={t('blogs.placeholders.search')}
                        style={{ width: 260 }}
                    />

                    <Select
                        allowClear
                        value={filters.status}
                        onChange={(value) => updateFilter('status', value)}
                        placeholder={t('blogs.placeholders.status')}
                        options={[
                            { label: t('blogs.status_options.draft'), value: 'draft' },
                            { label: t('blogs.status_options.published'), value: 'published' },
                            { label: t('blogs.status_options.archived'), value: 'archived' },
                        ]}
                        style={{ width: 180 }}
                    />

                    <Select
                        allowClear
                        value={filters.blog_category_id}
                        onChange={(value) => updateFilter('blog_category_id', value)}
                        placeholder={t('blogs.placeholders.blog_category_id')}
                        options={categories.map((category) => ({
                            label: category.name,
                            value: category.id,
                        }))}
                        style={{ width: 220 }}
                    />

                    <Select
                        allowClear
                        value={filters.featured}
                        onChange={(value) => updateFilter('featured', value)}
                        placeholder={t('blogs.placeholders.featured')}
                        options={[
                            { label: t('blogs.featured_options.featured'), value: true },
                            { label: t('blogs.featured_options.normal'), value: false },
                        ]}
                        style={{ width: 180 }}
                    />
                </Space>
            </Card>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={posts}
                scroll={{ x: 1000 }}
                columns={[
                    {
                        title: t('blogs.table.title'),
                        dataIndex: 'title',
                        width: 260,
                        ellipsis: true,
                        render: (_, post) => <BlogTitleCell post={post} />,
                    },
                    {
                        title: t('blogs.table.category'),
                        dataIndex: ['category', 'name'],
                        width: 140,
                        ellipsis: true,
                        render: (value) => value ?? '-',
                    },
                    {
                        title: t('blogs.table.author_name'),
                        dataIndex: 'author_name',
                        width: 140,
                        ellipsis: true,
                        render: (value) => value ?? '-',
                    },
                    {
                        title: t('blogs.table.status'),
                        dataIndex: 'status',
                        width: 130,
                        render: (value) => <StatusBadge value={value} />,
                    },
                    {
                        title: t('blogs.table.featured'),
                        dataIndex: 'is_featured',
                        width: 90,
                        render: (value) => (
                            <Tag color={value ? 'gold' : 'default'}>
                                {value ? t('common.yes') : t('common.no')}
                            </Tag>
                        ),
                    },
                    {
                        title: t('blogs.table.published_at'),
                        dataIndex: 'published_at',
                        width: 160,
                        render: (value) => formatDateTime(value, currentLocale),
                    },
                    {
                        title: t('blogs.table.view_count'),
                        dataIndex: 'view_count',
                        width: 80,
                    },
                    {
                        title: t('blogs.table.actions'),
                        key: 'actions',
                        width: 160,
                        fixed: 'right',
                        render: (_, post) => (
                            <Space size={4}>
                                <Button size="small" icon={<FontIcon name="edit" />} onClick={() => navigate(`/admin/blog/posts/${post.id}/edit`)}>
                                    {t('blogs.actions.edit')}
                                </Button>
                                <Popconfirm
                                    title={t('blogs.confirm_delete.title')}
                                    description={t('blogs.confirm_delete.description')}
                                    okText={t('blogs.confirm_delete.ok')}
                                    cancelText={t('blogs.confirm_delete.cancel')}
                                    onConfirm={() => handleDelete(post.id)}
                                >
                                    <Button size="small" danger icon={<FontIcon name="delete" />}>
                                        {t('blogs.actions.delete')}
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
