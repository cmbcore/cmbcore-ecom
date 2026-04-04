import React, { useCallback, useEffect, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import PageForm from '../components/PageForm';
import { buildPagePayload } from '../components/pageBlocksPayload';

export default function PageCreate() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [templates, setTemplates] = useState([]);
    const [blocks, setBlocks] = useState([]);
    const [puckData, setPuckData] = useState(null);

    const fetchTemplates = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/pages/templates');
            const nextTemplates = response.data.data?.templates ?? [];
            const nextBlocks = response.data.data?.blocks ?? [];

            setTemplates(nextTemplates);
            setBlocks(nextBlocks);
            form.setFieldsValue({
                template: 'builder',
                status: 'draft',
                content_blocks: [],
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('pages.messages.load_templates_failed'));
            navigate('/admin/pages');
        } finally {
            setLoading(false);
        }
    }, [form, navigate, t]);

    useEffect(() => {
        fetchTemplates();
    }, [fetchTemplates]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const payload = buildPagePayload(values);
            const formData = new FormData();

            Object.entries(payload.values).forEach(([key, value]) => {
                if (value !== undefined) {
                    formData.append(key, typeof value === 'string' ? value : JSON.stringify(value));
                }
            });

            Object.entries(payload.directFiles ?? {}).forEach(([key, file]) => {
                formData.append(key, file);
            });

            payload.uploads.forEach(({ token, file }) => {
                formData.append(`uploads[${token}]`, file);
            });

            await api.post('/pages', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            message.success(t('pages.messages.created'));
            navigate('/admin/pages');
        } catch (error) {
            message.error(error.response?.data?.message ?? t('pages.messages.create_failed'));
        } finally {
            setSaving(false);
        }
    }

    if (loading) {
        return null;
    }

    return (
        <div>
            <PageHeader title={t('pages.create_title')} description={t('pages.create_description')} />
            <PageForm
                form={form}
                initialValues={{
                    template: 'builder',
                    status: 'draft',
                }}
                loading={saving}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/pages')}
                submitLabel={t('pages.actions.save')}
                templates={templates}
                blocks={blocks}
                puckData={puckData}
                onPuckDataChange={setPuckData}
            />
        </div>
    );
}
