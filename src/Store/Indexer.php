<?php

declare(strict_types=1);

namespace App\Store;

use Symfony\AI\Store\Indexer as AiIndexer;

final readonly class Indexer
{
    public function __construct(
        private Loader $loader,
        private Splitter $splitter,
        private AiIndexer $indexer,
    ) {
    }

    public function index(int $limit): void
    {
        $documents = [];
        foreach ($this->loader->load($limit) as $document) {
            array_push($documents, ...$this->splitter->split($document));
        }

        $this->indexer->index($documents);
    }
}
