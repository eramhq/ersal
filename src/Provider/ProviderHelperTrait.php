<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider;

use Eram\Ersal\Event\ShipmentFailed;
use Eram\Ersal\Exception\BookingException;
use Eram\Ersal\Exception\CancellationException;
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Exception\TrackingException;
use Eram\Ersal\Http\EventDispatcher;

trait ProviderHelperTrait
{
    protected ?EventDispatcher $eventDispatcher = null;

    protected function dispatch(object $event): void
    {
        $this->eventDispatcher?->dispatch($event);
    }

    protected function failQuote(string $message, int|string $code = 0): never
    {
        $this->dispatch(new ShipmentFailed($this->getName(), 'quote', $message, $code));

        throw new ProviderException($message, $this->getName(), $code);
    }

    protected function failBooking(string $message, int|string $code = 0): never
    {
        $this->dispatch(new ShipmentFailed($this->getName(), 'book', $message, $code));

        throw new BookingException($message, $this->getName(), $code);
    }

    protected function failTracking(string $message, int|string $code = 0): never
    {
        $this->dispatch(new ShipmentFailed($this->getName(), 'track', $message, $code));

        throw new TrackingException($message, $this->getName(), $code);
    }

    protected function failCancellation(string $message, int|string $code = 0): never
    {
        $this->dispatch(new ShipmentFailed($this->getName(), 'cancel', $message, $code));

        throw new CancellationException($message, $this->getName(), $code);
    }

    protected function nullIfEmpty(string $value): ?string
    {
        return $value !== '' ? $value : null;
    }

    /**
     * Name of the provider (implemented by the concrete provider class).
     */
    abstract public function getName(): string;
}
