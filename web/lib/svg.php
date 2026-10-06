<?php
declare(strict_types=1);

const SVG_NS = 'http://www.w3.org/2000/svg';

function extract_svg(string $text): ?string
{
    return preg_match('/<svg\b.*<\/svg>/si', $text, $m) === 1 ? $m[0] : null;
}

function sanitize_svg(string $svg, ?string $logoDataUri, int $width, int $height): string
{
    if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg) === 1) {
        throw new RuntimeException('SVG zawiera niedozwoloną deklarację.');
    }
    if (preg_match('/<svg\b[^>]*\sxmlns\s*=/i', $svg) !== 1) {
        $svg = preg_replace('/<svg\b/i', '<svg xmlns="' . SVG_NS . '"', $svg, 1);
    }
    $dom = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $ok = $dom->loadXML($svg, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $root = $dom->documentElement;
    if (!$ok || $root === null || $root->localName !== 'svg') {
        throw new RuntimeException('Niepoprawny dokument SVG.');
    }

    $xp = new DOMXPath($dom);
    $banned = '//*[local-name()="script" or local-name()="foreignObject" or local-name()="iframe" or local-name()="object" or local-name()="embed"]';
    foreach (iterator_to_array($xp->query($banned)) as $node) {
        $node->parentNode?->removeChild($node);
    }
    $externalUrl = '/url\(\s*[\'"]?\s*[^#\'"\s)]/i';
    foreach (iterator_to_array($xp->query('//*[local-name()="style"]')) as $style) {
        if (preg_match('/@import/i', $style->textContent) === 1 || preg_match($externalUrl, $style->textContent) === 1) {
            $style->parentNode?->removeChild($style);
        }
    }
    foreach (iterator_to_array($xp->query('//*')) as $el) {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $val = trim($attr->value);
            $remove = str_starts_with($name, 'on')
                || (($name === 'href' || $name === 'xlink:href') && !str_starts_with($val, '#'))
                || preg_match($externalUrl, $val) === 1
                || stripos($val, 'javascript:') !== false;
            if ($remove) {
                $el->removeAttributeNode($attr);
            }
        }
    }

    $root->setAttribute('width', (string)$width);
    $root->setAttribute('height', (string)$height);
    if (!$root->hasAttribute('viewBox')) {
        $root->setAttribute('viewBox', "0 0 $width $height");
    }

    $slot = $xp->query('//*[@id="logo-slot"]')->item(0);
    if ($slot instanceof DOMElement) {
        if ($logoDataUri !== null) {
            $img = $dom->createElementNS(SVG_NS, 'image');
            foreach (['x', 'y', 'width', 'height'] as $a) {
                $img->setAttribute($a, $slot->getAttribute($a) !== '' ? $slot->getAttribute($a) : '0');
            }
            $img->setAttribute('href', $logoDataUri);
            $img->setAttribute('preserveAspectRatio', 'xMidYMid meet');
            $slot->parentNode?->replaceChild($img, $slot);
        } else {
            $slot->parentNode?->removeChild($slot);
        }
    }
    return (string)$dom->saveXML($root);
}
