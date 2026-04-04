import React, { useCallback, useEffect, useState } from 'react';
import { Button, Popconfirm, Space, Table, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import StatusBadge from '@admin/components/ui/StatusBadge';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

function CategoryNameCell({ category }) {
    return (
        <div className="category-tree-label">
            <span className="category-tree-label__depth">{category.level}</span>
            <span>{category.name}</span>
        </div>
    );
}

export default function CategoryList() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [loading, setLoading] = useState(true);
    const [categories, setCategories] = useState([]);

    const fetchTree = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/categories/tree');
            setCategories(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('categories.messages.load_failed'));
        } finally {
            setLoading(false);
        }
    }, [t]);

    async function handleDelete(categoryId) {
        try {
            await api.delete(`/categories/${categoryId}`);
            message.success(t('categories.messages.deleted'));
            await fetchTree();
        } catch (error) {
            message.error(error.response?.data?.message ?? t('categories.messages.delete_failed'));
        }
    }

    useEffect(() => {
        fetchTree();
    }, [fetchTree]);

    return (
        <div>
            <PageHeader
                title={t('categories.title')}
                description={t('categories.description')}
                extra={[
                    { label: t('categories.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchTree },
                    { label: t('categories.actions.create'), icon: <FontIcon name="create" />, type: 'primary', onClick: () => navigate('/admin/categories/create') },
                ]}
            />

            <Table
                rowKey="id"
                loading={loading}
                className="category-tree-table"
                columns={[
                    {
                        title: t('categories.table.name'),
                        dataIndex: 'name',
                        render: (_, category) => <CategoryNameCell category={category} />,
                    },
                    {
                        title: t('categories.table.slug'),
                        dataIndex: 'slug',
                    },
                    {
                        title: t('categories.table.status'),
                        dataIndex: 'status',
                        width: 140,
                        render: (value) => <StatusBadge value={value} />,
                    },
                    {
                        title: t('categories.table.position'),
                        dataIndex: 'position',
                        width: 100,
                    },
                    {
                        title: t('categories.table.product_count'),
                        dataIndex: 'product_count',
                        width: 120,
                    },
                    {
                        title: t('categories.table.actions'),
                        key: 'actions',
                        width: 220,
                        render: (_, category) => (
                            <Space wrap>
                                <Button size="small" icon={<FontIcon name="edit" />} onClick={() => navigate(`/admin/categories/${category.id}/edit`)}>
                                    {t('categories.actions.edit')}
                                </Button>
                                <Popconfirm
                                    title={t('categories.confirm_delete.title')}
                                    description={t('categories.confirm_delete.description')}
                                    okText={t('categories.confirm_delete.ok')}
                                    cancelText={t('categories.confirm_delete.cancel')}
                                    onConfirm={() => handleDelete(category.id)}
                                >
                                    <Button size="small" danger icon={<FontIcon name="delete" />}>
                                        {t('categories.actions.delete')}
                                    </Button>
                                </Popconfirm>
                            </Space>
                        ),
                    },
                ]}
                dataSource={categories}
                pagination={false}
                expandable={{ defaultExpandAllRows: true }}
            />
        </div>
    );
}
