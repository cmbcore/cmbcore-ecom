import React from 'react';
import { Button, Result } from 'antd';
import { Link } from 'react-router-dom';
import { useLocale } from '@admin/hooks/useLocale';

export default function NotFound() {
    const { t } = useLocale();

    return (
        <Result
            status="404"
            title={t('not_found.title')}
            subTitle={t('not_found.description')}
            extra={<Button type="primary"><Link to="/admin">{t('common.back_to_dashboard')}</Link></Button>}
        />
    );
}
