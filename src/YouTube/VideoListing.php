<?php

declare(strict_types=1);

namespace App\YouTube;

use Google\Service\YouTube;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\DatePoint;

/**
 * @phpstan-type VideoData array{id: string, title: string, description: string, publishedAt: DatePoint}
 */
final readonly class VideoListing
{
    private const string CHANNEL_HANDLE = '@NeverCodeAlone';

    public function __construct(
        private YouTube $youtubeClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return \Generator<VideoData>
     */
    public function getVideos(int $maxResults): \Generator
    {
        $playlistId = $this->getUploadsPlaylistId();

        $this->logger->info(
            sprintf('Uploads playlist ID for channel %s: %s', self::CHANNEL_HANDLE, $playlistId)
        );

        yield from $this->loadVideos($playlistId, $maxResults);
    }

    private function getUploadsPlaylistId(): string
    {
        $response = $this->youtubeClient->channels->listChannels('contentDetails', [
            'forHandle' => self::CHANNEL_HANDLE,
        ]);

        return $response->getItems()[0]->getContentDetails()->getRelatedPlaylists()->getUploads();
    }

    /**
     * @return \Generator<VideoData>
     */
    private function loadVideos(string $playlistId, int $maxResults): \Generator
    {
        $loaded = 0;
        $response = null;

        do {
            $response = $this->youtubeClient->playlistItems->listPlaylistItems('snippet', [
                'playlistId' => $playlistId,
                'maxResults' => $maxResults - $loaded,
                'pageToken' => $response?->getNextPageToken(),
            ]);

            $this->logger->info(sprintf('Loaded %d videos out of %d requested', $loaded, $maxResults));

            foreach ($response->getItems() as $item) {
                yield [
                    'id' => $item->getSnippet()->getResourceId()->getVideoId(),
                    'title' => $item->getSnippet()->getTitle(),
                    'description' => $item->getSnippet()->getDescription(),
                    'publishedAt' => DatePoint::createFromFormat(DATE_ATOM, $item->getSnippet()->getPublishedAt()),
                ];
                ++$loaded;
            }
        } while ($response->getNextPageToken() && $loaded < $maxResults);

        $this->logger->info(sprintf('Total videos loaded: %d', $loaded));
    }
}
