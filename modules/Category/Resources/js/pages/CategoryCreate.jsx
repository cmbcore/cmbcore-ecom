import React, { useCallback, useEffect, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate } from 'react-router-dom';
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

export default function CategoryCreate() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [parentOptions, setParentOptions] = useState([]);

    const fetchParentOptions = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/categories/tree');
            setParentOptions(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('categories.messages.load_parents_failed'));
        } finally {
            setLoading(false);
        }
    }, [t]);

    async function handleSubmit(values) {
        setSubmitting(true);

        try {
            await api.post('/categories', values);
            message.success(t('categories.messages.created'));
            navigate('/admin/categories');
        } catch (error) {
            applyFormErrors(form, error);
            message.error(error.response?.data?.message ?? t('categories.messages.create_failed'));
        } finally {
            setSubmitting(false);
        }
    }

    useEffect(() => {
        fetchParentOptions();
    }, [fetchParentOptions]);

    return (
        <div>
            <PageHeader
                title={t('categories.create_title')}
                description={t('categories.create_description')}
            />
            <CategoryForm
                form={form}
                initialValues={normalizeCategoryFormValues(null)}
                parentOptions={parentOptions}
                loading={loading || submitting}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/categories')}
                submitLabel={t('categories.actions.save')}
            />
        </div>
    );
}
