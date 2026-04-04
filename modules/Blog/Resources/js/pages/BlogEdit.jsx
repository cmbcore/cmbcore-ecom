import React, { useEffect, useRef, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import { toDateTimeLocalValue } from '@admin/utils/dateTime';
import BlogForm from '../components/BlogForm';

function normalizeFormValues(post) {
    return {
        ...post,
        published_at: toDateTimeLocalValue(post.published_at),
    };
}

export default function BlogEdit() {
    const navigate = useNavigate();
    const { id } = useParams();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [post, setPost] = useState(null);
    const [categories, setCategories] = useState([]);

    const tRef = useRef(t);
    tRef.current = t;

    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        async function fetchAll() {
            try {
                const [postResponse, categoriesResponse] = await Promise.all([
                    api.get(`/blog/posts/${id}`),
                    api.get('/blog/categories', { params: { per_page: 100 } }),
                ]);

                if (cancelled) return;

                const nextPost = postResponse.data.data;
                setPost(nextPost);
                form.setFieldsValue(normalizeFormValues(nextPost));
                setCategories(categoriesResponse.data.data ?? []);
            } catch (error) {
                if (cancelled) return;
                message.error(error.response?.data?.message ?? tRef.current('blogs.messages.load_failed'));
                if (error.response?.status === 404) {
                    navigate('/admin/blog/posts');
                }
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        fetchAll();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            await api.post(`/blog/posts/${id}`, values);
            message.success(t('blogs.messages.updated'));
            navigate('/admin/blog/posts');
        } catch (error) {
            message.error(error.response?.data?.message ?? t('blogs.messages.update_failed'));
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
                title={t('blogs.edit_title', { title: post?.title ?? t('blogs.edit_fallback') })}
                description={t('blogs.edit_description')}
            />
            <BlogForm
                form={form}
                initialValues={normalizeFormValues(post ?? {})}
                categories={categories}
                loading={saving}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/blog/posts')}
                submitLabel={t('blogs.actions.update')}
            />
        </div>
    );
}
