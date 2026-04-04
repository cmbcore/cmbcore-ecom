import React, { useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import BlogCategoryForm, { normalizeBlogCategoryFormValues } from '../components/BlogCategoryForm';

export default function BlogCategoryCreate() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [saving, setSaving] = useState(false);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            await api.post('/blog/categories', values);
            message.success(t('blog_categories.messages.created'));
            navigate('/admin/blog/categories');
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blog_categories.messages.create_failed'));
        } finally {
            setSaving(false);
        }
    }

    return (
        <div>
            <PageHeader title={t('blog_categories.create_title')} description={t('blog_categories.create_description')} />
            <BlogCategoryForm
                form={form}
                initialValues={normalizeBlogCategoryFormValues(null)}
                loading={saving}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/blog/categories')}
                submitLabel={t('blog_categories.actions.save')}
            />
        </div>
    );
}
