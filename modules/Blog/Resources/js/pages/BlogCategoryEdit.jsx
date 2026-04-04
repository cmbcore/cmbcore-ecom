import React, { useEffect, useRef, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import BlogCategoryForm, { normalizeBlogCategoryFormValues } from '../components/BlogCategoryForm';

export default function BlogCategoryEdit() {
    const navigate = useNavigate();
    const { id } = useParams();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [category, setCategory] = useState(null);

    const tRef = useRef(t);
    tRef.current = t;

    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        async function fetchCategory() {
            try {
                const response = await api.get(`/blog/categories/${id}`);
                if (cancelled) return;

                const nextCategory = response.data.data;
                setCategory(nextCategory);
                form.setFieldsValue(normalizeBlogCategoryFormValues(nextCategory));
            } catch (error) {
                if (cancelled) return;
                message.error(error.response?.data?.message ?? tRef.current('blog_categories.messages.load_failed'));
                if (error.response?.status === 404) {
                    navigate('/admin/blog/categories');
                }
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        fetchCategory();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            await api.post(`/blog/categories/${id}`, values);
            message.success(t('blog_categories.messages.updated'));
            navigate('/admin/blog/categories');
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blog_categories.messages.update_failed'));
        } finally {
            setSaving(false);
        }
    }

    if (loading) {
        return null;
    }

    return (
        <div>
            <PageHeader
                title={t('blog_categories.edit_title', { name: category?.name ?? t('blog_categories.edit_fallback') })}
                description={t('blog_categories.edit_description')}
            />
            <BlogCategoryForm
                form={form}
                initialValues={normalizeBlogCategoryFormValues(category)}
                loading={saving}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/blog/categories')}
                submitLabel={t('blog_categories.actions.update')}
            />
        </div>
    );
}
