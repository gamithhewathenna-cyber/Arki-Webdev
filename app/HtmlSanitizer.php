<?php
declare(strict_types=1);

/**
 * Whitelist HTML cleaner for rich-text fields (product descriptions).
 * Keeps basic formatting and safe links; drops scripts, styles, event
 * attributes and anything else.
 */
final class HtmlSanitizer
{
    private const TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h3', 'h4', 'blockquote', 'a'];
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math', 'noscript', 'head', 'title'];

    public static function clean(?string $html): string
    {
        $html = trim((string)$html);
        if ($html === '') {
            return '';
        }
        if (!class_exists('DOMDocument')) {
            // Fallback: plain text paragraphs.
            return paragraphs(strip_tags($html));
        }
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('__root');
        if (!$root) {
            return '';
        }
        self::walk($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        // Collapse empty paragraphs left behind by editors.
        $out = preg_replace('#<p>(\s|&nbsp;|<br>)*</p>#i', '', $out);
        return trim((string)$out);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                    $node->removeChild($child);
                    continue;
                }
                self::walk($child);
                if ($tag === 'div') {
                    // Editors wrap lines in <div>: turn them into paragraphs.
                    $p = $child->ownerDocument->createElement('p');
                    while ($child->firstChild) $p->appendChild($child->firstChild);
                    $node->replaceChild($p, $child);
                    continue;
                }
                if (!in_array($tag, self::TAGS, true)) {
                    // Unknown tag: keep its text/children, drop the tag itself.
                    while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                    $node->removeChild($child);
                    continue;
                }
                // Strip every attribute except a safe href on links.
                $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $child->removeAttribute($attr->nodeName);
                }
                if ($tag === 'a') {
                    if ($href !== '' && safe_url($href) !== '#') {
                        $child->setAttribute('href', $href);
                        if (preg_match('#^https?://#i', $href)) {
                            $child->setAttribute('target', '_blank');
                            $child->setAttribute('rel', 'noopener noreferrer');
                        }
                    }
                }
            } elseif ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $node->removeChild($child);
            }
        }
    }
}
