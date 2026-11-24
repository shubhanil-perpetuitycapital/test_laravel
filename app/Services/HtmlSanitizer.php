<?php

namespace App\Services;

/**
 * Cleans the HTML that admin users type into page content, so a staff
 * account can't inject scripts into public pages. Allows basic formatting,
 * links, lists, headings, tables and FAQ <details> blocks.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><a><ul><ol><li><h2><h3><h4><blockquote><details><summary><table><thead><tbody><tr><th><td><hr><span>';

    public function clean(?string $html): string
    {
        $html = (string) $html;

        // Remove script/style blocks entirely (content included).
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? '';

        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Drop event handlers and inline styles.
        $html = preg_replace('/\s+(on[a-z]+|style)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';

        // Neutralise javascript:/data: URLs in links.
        $html = preg_replace('/href\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'\s>]*\1/i', 'href="#"', $html) ?? '';

        return trim($html);
    }
}
