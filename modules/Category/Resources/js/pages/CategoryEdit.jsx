import React, { useEffect, useRef, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import CategoryForm, { normalizeCategoryFormValues } from '../components/CategoryForm';

function applyFormErrors(form, error) {
    const errors = error.response?.data?.errors ?? {};
    const fields = Object.entries(errors).map(([name, messages]) => ({
        name,
        errors: Array.isArray(messages) ? messages : [String(messages)],
    }));

    if (fields.length > 0) {
        form.setFields(fields);
    }
}

export default function CategoryEdit() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const { id } = useParams();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [category, setCategory] = useState(null);
    const [parentOptions, setParentOptions] = useState([]);

    const tRef = useRef(t);
    tRef.current = t;

    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        async function bootstrap() {
            try {
                const [categoryResponse, treeResponse] = await Promise.all([
                    api.get(`/categories/${id}`),
                    api.get('/categories/tree', { params: { exclude_id: id } }),
                ]);

                if (cancelled) return;

                const nextCategory = categoryResponse.data.data;
                setCategory(nextCategory);
                setParentOptions(treeResponse.data.data ?? []);
                form.setFieldsValue(normalizeCategoryFormValues(nextCategory));
            } catch (error) {
                if (cancelled) return;
                message.error(error.response?.data?.message ?? tRef.current('categories.messages.load_failed'));
                if (error.response?.status === 404) {
                    navigate('/admin/categories');
                }
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        bootstrap();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id]);

    async function handleSubmit(values) {
        setSubmitting(true);

        try {
            await api.post(`/categories/${id}`, values);
            message.success(t('categories.messages.updated'));
            navigate('/admin/categories');
        } catch (error) {
            applyFormErrors(form, error);
            message.error(error.response?.data?.message ?? t('categories.messages.update_failed'));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div>
            <PageHeader
                title={category ? t('categories.edit_title', { name: category.name }) : t('categories.edit_fallback')}
                description={t('categories.edit_description')}
            />
            <CategoryForm
                form={form}
                initialValues={normalizeCategoryFormValues(category)}
                parentOptions={parentOptions}
                loading={loading || submitting}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/categories')}
                submitLabel={t('categories.actions.update')}
            />
        </div>
    );
}
