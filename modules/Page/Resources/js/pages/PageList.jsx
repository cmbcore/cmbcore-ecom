import React, { useCallback, useDeferredValue, useEffect, useState } from 'react';
import { Button, Card, Input, Popconfirm, Select, Space, Table, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import StatusBadge from '@admin/components/ui/StatusBadge';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import { formatDateTime } from '@admin/utils/dateTime';

function PageTitleCell({ page }) {
    return (
        <div>
            <strong>{page.title}</strong>
            <div>{page.slug}</div>
        </div>
    );
}

export default function PageList() {
    const navigate = useNavigate();
    const { currentLocale, t } = useLocale();
    const [loading, setLoading] = useState(true);
    const [pages, setPages] = useState([]);
    const [templates, setTemplates] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });
    const [filters, setFilters] = useState({
        search: '',
        status: undefined,
        template: undefined,
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

    const fetchTemplates = useCallback(async () => {
        try {
            const response = await api.get('/pages/templates');
            setTemplates(response.data.data?.templates ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('pages.messages.load_templates_failed'));
        }
    }, [t]);

    const fetchPages = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/pages', {
                params: {
                    search: deferredSearch || undefined,
                    status: filters.status,
                    template: filters.template,
                    page: filters.page,
                },
            });

            setPages(response.data.data ?? []);
            setMeta(response.data.meta ?? {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('pages.messages.load_failed'));
        } finally {
            setLoading(false);
        }
    }, [deferredSearch, filters.page, filters.status, filters.template, t]);

    async function handleDelete(pageId) {
        try {
            await api.delete(`/pages/${pageId}`);
            message.success(t('pages.messages.deleted'));
            await fetchPages();
        } catch (error) {
            message.error(error.response?.data?.message ?? t('pages.messages.delete_failed'));
        }
    }

    useEffect(() => {
        fetchTemplates();
    }, [fetchTemplates]);

    useEffect(() => {
        fetchPages();
    }, [fetchPages]);

    return (
        <div>
            <PageHeader
                title={t('pages.title')}
                description={t('pages.description')}
                extra={[
                    { label: t('pages.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchPages },
                    { label: t('pages.actions.create'), icon: <FontIcon name="create" />, type: 'primary', onClick: () => navigate('/admin/pages/create') },
                ]}
            />

            <Card bordered={false}>
                <Space wrap size={16}>
                    <Input
                        allowClear
                        value={filters.search}
                        onChange={(event) => updateFilter('search', event.target.value)}
                        placeholder={t('pages.placeholders.search')}
                        style={{ width: 260 }}
                    />

                    <Select
                        allowClear
                        value={filters.status}
                        onChange={(value) => updateFilter('status', value)}
                        placeholder={t('pages.placeholders.status')}
                        options={[
                            { label: t('pages.status_options.draft'), value: 'draft' },
                            { label: t('pages.status_options.published'), value: 'published' },
                            { label: t('pages.status_options.archived'), value: 'archived' },
                        ]}
                        style={{ width: 180 }}
                    />

                    <Select
                        allowClear
                        value={filters.template}
                        onChange={(value) => updateFilter('template', value)}
                        placeholder={t('pages.placeholders.template')}
                        options={templates.map((template) => ({
                            label: template.label,
                            value: template.name,
                        }))}
                        style={{ width: 180 }}
                    />
                </Space>
            </Card>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={pages}
                columns={[
                    {
                        title: t('pages.table.title'),
                        dataIndex: 'title',
                        render: (_, page) => <PageTitleCell page={page} />,
                    },
                    {
                        title: t('pages.table.template'),
                        dataIndex: 'template',
                        width: 140,
                    },
                    {
                        title: t('pages.table.status'),
                        dataIndex: 'status',
                        width: 150,
                        render: (value) => <StatusBadge value={value} />,
                    },
                    {
                        title: t('pages.table.published_at'),
                        dataIndex: 'published_at',
                        width: 190,
                        render: (value) => formatDateTime(value, currentLocale),
                    },
                    {
                        title: t('pages.table.view_count'),
                        dataIndex: 'view_count',
                        width: 120,
                    },
                    {
                        title: t('pages.table.actions'),
                        key: 'actions',
                        width: 220,
                        render: (_, page) => (
                            <Space wrap>
                                <Button size="small" icon={<FontIcon name="edit" />} onClick={() => navigate(`/admin/pages/${page.id}/edit`)}>
                                    {t('pages.actions.edit')}
                                </Button>
                                <Popconfirm
                                    title={t('pages.confirm_delete.title')}
                                    description={t('pages.confirm_delete.description')}
                                    okText={t('pages.confirm_delete.ok')}
                                    cancelText={t('pages.confirm_delete.cancel')}
                                    onConfirm={() => handleDelete(page.id)}
                                >
                                    <Button size="small" danger icon={<FontIcon name="delete" />}>
                                        {t('pages.actions.delete')}
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
