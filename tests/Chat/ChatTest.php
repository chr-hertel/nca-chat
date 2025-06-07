<?php

declare(strict_types=1);

namespace App\Tests\Chat;

use App\Chat\Chat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(Chat::class)]
final class ChatTest extends TestCase
{
    private Chat $chat;

    protected function setUp(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->chat = new Chat(new RequestStack([$request]));
    }

    public function testLoadMessagesReturnsEmptyArrayByDefault(): void
    {
        $messages = $this->chat->loadMessages();

        self::assertEmpty($messages);
    }

    public function testSubmitMessageAddsUserAndAssistantMessages(): void
    {
        self::assertCount(0, $this->chat->loadMessages());

        $this->chat->submitMessage('Hello!');

        $messages = $this->chat->loadMessages();

        self::assertCount(2, $messages);
        self::assertSame('Hello!', $messages[0]['content'][0]['text'] ?? '');
        self::assertSame('Das ist keine clevere Antwort.', $messages[1]['content']);
    }
}
