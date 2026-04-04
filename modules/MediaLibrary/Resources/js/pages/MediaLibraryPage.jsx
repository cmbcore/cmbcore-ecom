import React from 'react';
import { Card } from 'antd';
import MediaBrowser from '@admin/components/media/MediaBrowser';
import PageHeader from '@admin/components/ui/PageHeader';

export default function MediaLibraryPage() {
    return (
        <div>
            <PageHeader
                title="Media Library"
                description="Thu vien media dung Chonky de duyet folder, upload va chon file cho toan bo admin."
            />

            <Card bordered={false}>
                <MediaBrowser height={700} />
            </Card>
        </div>
    );
}
