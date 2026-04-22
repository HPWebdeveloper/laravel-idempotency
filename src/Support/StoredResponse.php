<?php

declare(strict_types=1);

namespace WendellAdriel\Idempotency\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class StoredResponse
{
    /**
     * @param  array<string, list<string>>  $headers
     */
    public function __construct(
        public string $fingerprint,
        public int $status,
        public array $headers,
        public ?string $content,
        public bool $isRedirect,
        public ?string $targetUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $stored
     * @param  array<string, list<string>>  $headers
     */
    public static function fromStored(array $stored, array $headers): self
    {
        $status = Response::HTTP_OK;

        if (is_int($stored['status'] ?? null)) {
            $status = $stored['status'];
        } elseif (is_numeric($stored['status'] ?? null)) {
            $status = (int) $stored['status'];
        }

        return new self(
            fingerprint: is_string($stored['fingerprint'] ?? null) ? $stored['fingerprint'] : '',
            status: $status,
            headers: $headers,
            content: is_string($stored['content'] ?? null) ? $stored['content'] : null,
            isRedirect: (bool) ($stored['is_redirect'] ?? false),
            targetUrl: is_string($stored['target_url'] ?? null) ? $stored['target_url'] : null,
        );
    }

    /**
     * @param  array<string, list<string>>  $headers
     */
    public static function fromResponse(Response $response, string $fingerprint, array $headers): self
    {
        return new self(
            fingerprint: $fingerprint,
            status: $response->getStatusCode(),
            headers: $headers,
            content: $response->getContent() === false ? null : $response->getContent(),
            isRedirect: $response instanceof RedirectResponse,
            targetUrl: $response instanceof RedirectResponse ? $response->getTargetUrl() : null,
        );
    }

    /**
     * @return array{fingerprint: string, status: int, headers: array<string, list<string>>, content: string|null, is_redirect: bool, target_url: string|null}
     */
    public function toArray(): array
    {
        return [
            'fingerprint' => $this->fingerprint,
            'status' => $this->status,
            'headers' => $this->headers,
            'content' => $this->content,
            'is_redirect' => $this->isRedirect,
            'target_url' => $this->targetUrl,
        ];
    }

    public function toResponse(): Response
    {
        $response = $this->isRedirect
            ? new RedirectResponse($this->targetUrl ?? '/', $this->status, $this->headers)
            : new IlluminateResponse($this->content, $this->status, $this->headers);

        $response->headers->set('Idempotency-Replayed', 'true');

        return $response;
    }
}
