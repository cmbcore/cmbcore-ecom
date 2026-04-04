import React, { useCallback, useEffect, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import BlogForm from '../components/BlogForm';

export default function BlogCreate() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [saving, setSaving] = useState(false);
    const [categories, setCategories] = useState([]);

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

    useEffect(() => {
        fetchCategories();
    }, [fetchCategories]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            await api.post('/blog/posts', values);
            message.success(t('blogs.messages.created'));
            navigate('/admin/blog/posts');
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blogs.messages.create_failed'));
        } finally {
            setSaving(false);
        }
    }

    return (
        <div>
            <PageHeader title={t('blogs.create_title')} description={t('blogs.create_description')} />
            <BlogForm
                form={form}
                initialValues={{
                    status: 'draft',
                    is_featured: false,
                }}
                categories={categories}
                loading={saving}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/blog/posts')}
                submitLabel={t('blogs.actions.save')}
            />
        </div>
    );
}
