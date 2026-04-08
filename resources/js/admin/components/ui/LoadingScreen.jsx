import React from 'react';
import { DotLottieReact } from '@lottiefiles/dotlottie-react';
/**
 * Full-page loading screen dùng loading.gif từ public/ Laravel.
 * Dùng window.location.origin để đảm bảo URL luôn trỏ đúng server
 * (tránh lỗi Vite dev proxy resolve sai khi dùng CSS url('/loading.gif')).
 */
export default function LoadingScreen({ panel = false }) {
    const gifSrc = `${window.location.origin}/loading.gif`;

    return (
        <div className={['admin-loading-screen', panel ? 'admin-loading-screen--panel' : ''].filter(Boolean).join(' ')}>
            <DotLottieReact
                src="https://lottie.host/76756585-7cb0-43fd-b42c-db1739b290fb/1bre3mUMz7.lottie"
                style={{ width: '100px', height: '100px' }}
                loop
                autoplay
            />
        </div>
    );
}
