<?php

declare(strict_types=1);

namespace App\Store;

use Symfony\AI\Store\Document\Transformer\TextSplitTransformer;
use Symfony\AI\Store\IndexerInterface as AiIndexer;

final readonly class Indexer
{
    public function __construct(
        private YouTubeChannelLoader $loader,
        private TextSplitTransformer $transformer,
        private AiIndexer $indexer,
    ) {
    }

    public function index(string $channelHandle, int $limit): void
    {
        $documents = [];
        foreach ($this->transformer->transform($this->loader->load($channelHandle, ['limit' => $limit])) as $document) {
            $documents[] = $document;
        }

        $this->indexer->index($documents);
    }
}
