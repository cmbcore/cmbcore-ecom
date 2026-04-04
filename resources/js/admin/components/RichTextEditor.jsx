import React, { useRef } from 'react';
import { Editor } from '@tinymce/tinymce-react';

// Self-hosted TinyMCE - import core first so `tinymce` global is available
// before any plugin/skin imports (required for Vite bundling)
import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/media';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/wordcount';
import 'tinymce/skins/content/default/content.min.css';
import 'tinymce/skins/ui/oxide/content.min.css';
import 'tinymce/skins/ui/oxide/skin.min.css';

// Suppress the unused variable warning - tinymce must be imported to register
void tinymce;

const DEFAULT_TOOLBAR =
    'undo redo | blocks | bold italic underline | forecolor backcolor | ' +
    'bullist numlist blockquote | link table image media | ' +
    'alignleft aligncenter alignright | removeformat code preview';

const INIT_CONFIG = {
    menubar: false,
    branding: false,
    promotion: false,
    resize: 'vertical',
    // Required for self-hosted/GPL bundled mode - no CDN loading
    licenseKey: 'gpl',
    // Prevent TinyMCE from trying to load skins from a CDN
    base_url: '',
    suffix: '.min',
    plugins: [
        'autolink',
        'charmap',
        'code',
        'image',
        'link',
        'lists',
        'media',
        'preview',
        'searchreplace',
        'table',
        'visualblocks',
        'wordcount',
    ],
    toolbar: DEFAULT_TOOLBAR,
    block_formats: 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4',
    contextmenu: false,
    browser_spellcheck: true,
    content_style: `
        body {
            font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 15px;
            line-height: 1.7;
            color: #0f172a;
            padding: 16px;
        }
        p { margin: 0 0 1em; }
        h2, h3, h4 { margin: 1.25em 0 0.65em; line-height: 1.3; }
        ul, ol { padding-left: 1.25rem; }
        img, video { max-width: 100%; height: auto; border-radius: 12px; }
        a { color: #0f766e; }
        blockquote {
            margin: 1.25em 0;
            padding-left: 1rem;
            border-left: 3px solid #99f6e4;
            color: #334155;
        }
    `,
};

export default function RichTextEditor({
    value,
    onChange,
    placeholder,
    minHeight = 280,
}) {
    const editorRef = useRef(null);

    return (
        <div className="rich-text-editor">
            <Editor
                ref={editorRef}
                value={value ?? ''}
                onEditorChange={(nextValue) => onChange?.(nextValue)}
                init={{
                    ...INIT_CONFIG,
                    height: minHeight,
                    placeholder,
                }}
            />
        </div>
    );
}
