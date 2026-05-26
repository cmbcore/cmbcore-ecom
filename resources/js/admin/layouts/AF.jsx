import React from 'react';

// Footer attribution. Renders the original CMBCORE credit with correct
// Vietnamese characters (the previous build mangled the diacritics).
export default function AF() {
    return (
        <div className="admin-footer">
            © 2006{' '}
            <a href="https://cmbcore.com" target="_blank" rel="noopener noreferrer">
                CMB core
            </a>
            . All rights reserved - Liên hệ fix lỗi và phát triển
        </div>
    );
}
