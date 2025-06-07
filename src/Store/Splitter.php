<?php

declare(strict_types=1);

namespace App\Store;

use Symfony\AI\Store\Document\TextDocument;
use Symfony\Component\Uid\Uuid;

final readonly class Splitter
{
    /**
     * Splits a TextDocument into smaller chunks of specified size with optional overlap.
     * If the document's content is shorter than the specified chunk size, it returns the original document as a single chunk.
     * Overlap cannot be negative and must be less than the chunk size.
     *
     * @return \Generator<TextDocument>
     */
    public function split(TextDocument $document, int $chunkSize = 1000, int $overlap = 200): \Generator
    {
        if ($overlap < 0 || $overlap >= $chunkSize) {
            throw new \InvalidArgumentException('Overlap must be non-negative and less than chunk size.');
        }

        if (mb_strlen($document->content) <= $chunkSize) {
            return [$document];
        }

        $text = $document->content;
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $chunkSize, $length);
            $chunkText = mb_substr($text, $start, $end - $start);

            yield new TextDocument(Uuid::v4(), $chunkText, new Metadata([
                'parent_id' => $document->id,
                'text' => $chunkText,
                ...$document->metadata,
            ]));

            $start += ($chunkSize - $overlap);
        }
    }
}
