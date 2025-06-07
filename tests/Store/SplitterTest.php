<?php

declare(strict_types=1);

namespace App\Tests\Store;

use App\Store\Splitter;
use PhpLlm\LlmChain\Store\Document\Metadata;
use PhpLlm\LlmChain\Store\Document\TextDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(Splitter::class)]
class SplitterTest extends TestCase
{
    private Splitter $splitter;

    protected function setUp(): void
    {
        $this->splitter = new Splitter();
    }

    public function testSplitReturnsSingleChunkForShortText(): void
    {
        $document = new TextDocument(Uuid::v4(), 'short text');

        $chunks = iterator_to_array($this->splitter->split($document));

        self::assertCount(1, $chunks);
        self::assertSame('short text', $chunks[0]->content);
    }

    public function testTextLength(): void
    {
        self::assertSame(1500, mb_strlen($this->getLongText()));
    }

    public function testSplitSplitsLongTextWithOverlap(): void
    {
        $document = new TextDocument(Uuid::v4(), $this->getLongText());

        $chunks = iterator_to_array($this->splitter->split($document));

        self::assertCount(2, $chunks);

        self::assertSame(1000, mb_strlen($chunks[0]->content));
        self::assertSame(substr($this->getLongText(), 0, 1000), $chunks[0]->content);

        self::assertSame(700, mb_strlen($chunks[1]->content));
        self::assertSame(substr($this->getLongText(), 800, 700), $chunks[1]->content);
    }

    public function testSplitWithCustomChunkSizeAndOverlap(): void
    {
        $doc = new TextDocument(Uuid::v4(), $this->getLongText());

        $chunks = iterator_to_array($this->splitter->split($doc, 150, 25));

        self::assertCount(12, $chunks);

        self::assertSame(150, mb_strlen($chunks[0]->content));
        self::assertSame(substr($this->getLongText(), 0, 150), $chunks[0]->content);

        self::assertSame(150, mb_strlen($chunks[1]->content));
        self::assertSame(substr($this->getLongText(), 125, 150), $chunks[1]->content);

        self::assertSame(150, mb_strlen($chunks[2]->content));
        self::assertSame(substr($this->getLongText(), 250, 150), $chunks[2]->content);

        self::assertSame(150, mb_strlen($chunks[3]->content));
        self::assertSame(substr($this->getLongText(), 375, 150), $chunks[3]->content);

        self::assertSame(150, mb_strlen($chunks[4]->content));
        self::assertSame(substr($this->getLongText(), 500, 150), $chunks[4]->content);

        self::assertSame(150, mb_strlen($chunks[5]->content));
        self::assertSame(substr($this->getLongText(), 625, 150), $chunks[5]->content);

        self::assertSame(150, mb_strlen($chunks[6]->content));
        self::assertSame(substr($this->getLongText(), 750, 150), $chunks[6]->content);

        self::assertSame(150, mb_strlen($chunks[7]->content));
        self::assertSame(substr($this->getLongText(), 875, 150), $chunks[7]->content);

        self::assertSame(150, mb_strlen($chunks[8]->content));
        self::assertSame(substr($this->getLongText(), 1000, 150), $chunks[8]->content);

        self::assertSame(150, mb_strlen($chunks[9]->content));
        self::assertSame(substr($this->getLongText(), 1125, 150), $chunks[9]->content);

        self::assertSame(150, mb_strlen($chunks[10]->content));
        self::assertSame(substr($this->getLongText(), 1250, 150), $chunks[10]->content);

        self::assertSame(125, mb_strlen($chunks[11]->content));
        self::assertSame(substr($this->getLongText(), 1375, 150), $chunks[11]->content);
    }

    public function testSplitWithZeroOverlap(): void
    {
        $doc = new TextDocument(Uuid::v4(), $this->getLongText());

        $chunks = iterator_to_array($this->splitter->split($doc, overlap: 0));

        self::assertCount(2, $chunks);
        self::assertSame(substr($this->getLongText(), 0, 1000), $chunks[0]->content);
        self::assertSame(substr($this->getLongText(), 1000, 500), $chunks[1]->content);
    }

    public function testParentIdIsSetInMetadata(): void
    {
        $document = new TextDocument(Uuid::v4(), $this->getLongText());

        $chunks = iterator_to_array($this->splitter->split($document, 1000, 200));

        self::assertCount(2, $chunks);
        self::assertSame($document->id, $chunks[0]->metadata['parent_id']);
        self::assertSame($document->id, $chunks[1]->metadata['parent_id']);
    }

    public function testMetadataIsInherited(): void
    {
        $document = new TextDocument(Uuid::v4(), $this->getLongText(), new Metadata([
            'key' => 'value',
            'foo' => 'bar',
        ]));

        $chunks = iterator_to_array($this->splitter->split($document));

        self::assertCount(2, $chunks);
        self::assertSame('value', $chunks[0]->metadata['key']);
        self::assertSame('bar', $chunks[0]->metadata['foo']);
        self::assertSame('value', $chunks[1]->metadata['key']);
        self::assertSame('bar', $chunks[1]->metadata['foo']);
    }

    public function testSplitWithChunkSizeLargerThanText(): void
    {
        $document = new TextDocument(Uuid::v4(), 'tiny');

        $chunks = iterator_to_array($this->splitter->split($document));

        self::assertCount(1, $chunks);
        self::assertSame('tiny', $chunks[0]->content);
    }

    public function testSplitWithOverlapGreaterThanChunkSize(): void
    {
        $document = new TextDocument(Uuid::v4(), 'Abcdefg', new Metadata([]));
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Overlap must be non-negative and less than chunk size.');

        $this->splitter->split($document, 10, 20);
    }

    public function testSplitWithNegativeOverlap(): void
    {
        $document = new TextDocument(Uuid::v4(), 'Abcdefg', new Metadata([]));
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Overlap must be non-negative and less than chunk size.');

        $this->splitter->split($document, 10, -1);
    }

    /**
     * Returns a text with 1500 characters.
     */
    private function getLongText(): string
    {
        return <<<TEXT
            Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa.
            Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis,
            ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo,
            fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae,
            justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper
            nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.
            Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius
            laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies
            nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero,
            sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem.
            Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis
            ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec
            sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida
            magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed,
            nonummy id, met
            TEXT;
    }
}
