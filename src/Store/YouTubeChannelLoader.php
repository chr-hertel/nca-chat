<?php

declare(strict_types=1);

namespace App\Store;

use App\YouTube\TranscriptFetcher;
use App\YouTube\VideoListing;
use Psr\Log\LoggerInterface;
use Symfony\AI\Store\Document\LoaderInterface;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\Component\Uid\Uuid;

final readonly class YouTubeChannelLoader implements LoaderInterface
{
    public function __construct(
        private VideoListing $videoList,
        private TranscriptFetcher $transcriptFetcher,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string            $source  YouTube Channel Handle
     * @param array{limit: int} $options
     *
     * @return iterable<TextDocument>
     */
    public function __invoke(string $source, array $options = []): iterable
    {
        $limit = $options['limit'] ?? 100;

        foreach ($this->videoList->getVideos($source, $limit) as $video) {
            $this->logger->info(\sprintf('Loading video: %s', $video['title']));

            $metadata = new Metadata([
                'id' => $video['id'],
                'title' => $video['title'],
                'description' => $video['description'],
                'publishedAt' => $video['publishedAt']->format('Y-m-d H:i:s'),
            ]);

            // First yield the title as document
            yield new TextDocument(Uuid::v4(), $video['title'], $metadata);

            // Then yield the description as document
            yield new TextDocument(Uuid::v4(), $video['description'], $metadata);

            // Finally, yield the transcript as split documents
            $transcript = $this->transcriptFetcher->fetchTranscript($video['id']);

            if (empty($transcript)) {
                $this->logger->warning(sprintf('Transcript is empty for video: %s', $video['id']));
                continue;
            }

            yield new TextDocument(Uuid::v4(), $transcript, $metadata);
        }
    }
}
