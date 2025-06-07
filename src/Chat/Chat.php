<?php

declare(strict_types=1);

namespace App\Chat;

use Symfony\Component\HttpFoundation\RequestStack;

final class Chat
{
    private const SESSION_KEY = 'nca-chat';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return MessageList
     */
    public function loadMessages(): array
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY, []);
    }

    public function submitMessage(string $message): void
    {
        $messages = $this->loadMessages();

        $messages[] = ['role' => ['value' => 'user'], 'content' => [['text' => $message]]];
        sleep(1); // Simulate GPT API call
        $response = 'Das ist keine clevere Antwort.'; // TODO: Replace with Agent->call
        $messages[] = ['role' => ['value' => 'assistant'], 'content' => $response];

        $this->saveMessages($messages);
    }

    public function reset(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }

    /**
     * @param MessageList $messages
     */
    private function saveMessages(array $messages): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $messages);
    }
}
