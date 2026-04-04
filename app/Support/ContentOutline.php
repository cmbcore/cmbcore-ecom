<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

final class ContentOutline
{
    /**
     * @param  array<int, string>  $allowedTags
     * @return array{html:string,toc:array<int, array{id:string,label:string,level:int}>}
     */
    public function decorate(?string $html, array $allowedTags = ['h2', 'h3']): array
    {
        $html = trim((string) $html);

        if ($html === '') {
            return [
                'html' => '',
                'toc' => [],
            ];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<?xml encoding="utf-8" ?><div id="content-outline-root">' . $html . '</div>';
        $usedIds = [];
        $toc = [];

        $previousState = libxml_use_internal_errors(true);

        try {
            $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }

        $xpath = new DOMXPath($document);
        $tagSelector = implode(' or ', array_map(
            static fn (string $tag): string => 'self::' . strtolower($tag),
            $allowedTags,
        ));

        foreach ($xpath->query('//*[@id="content-outline-root"]/* | //*[' . $tagSelector . ']') ?: [] as $node) {
            if (! in_array(strtolower($node->nodeName), $allowedTags, true)) {
                continue;
            }

            $label = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');

            if ($label === '') {
                continue;
            }

            $baseId = trim((string) $node->attributes?->getNamedItem('id')?->nodeValue);
            $baseId = $baseId !== '' ? $baseId : (Str::slug($label) ?: 'section');
            $id = $this->uniqueId($baseId, $usedIds);

            $node->setAttribute('id', $id);
            $toc[] = [
                'id' => $id,
                'label' => $label,
                'level' => (int) ltrim($node->nodeName, 'h'),
            ];
        }

        $root = $document->getElementById('content-outline-root');
        $renderedHtml = '';

        if ($root !== null) {
            foreach ($root->childNodes as $childNode) {
                $renderedHtml .= $document->saveHTML($childNode);
            }
        }

        return [
            'html' => $renderedHtml,
            'toc' => $toc,
        ];
    }

    /**
     * @param  array<int, string>  $usedIds
     */
    private function uniqueId(string $baseId, array &$usedIds): string
    {
        $candidate = $baseId;
        $counter = 2;

        while (in_array($candidate, $usedIds, true)) {
            $candidate = $baseId . '-' . $counter;
            $counter++;
        }

        $usedIds[] = $candidate;

        return $candidate;
    }
}
