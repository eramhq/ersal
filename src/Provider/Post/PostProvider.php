<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Post;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Catalog\Branch;
use Eram\Ersal\Contracts\ShipmentInterface;
use Eram\Ersal\Contracts\ShippingInterface;
use Eram\Ersal\Contracts\SupportsBranches;
use Eram\Ersal\Contracts\SupportsLabel;
use Eram\Ersal\Event\ShipmentCancelled;
use Eram\Ersal\Event\ShipmentCreated;
use Eram\Ersal\Event\ShipmentQuoted;
use Eram\Ersal\Event\ShipmentTracked;
use Eram\Ersal\Http\EventDispatcher;
use Eram\Ersal\Http\Logger;
use Eram\Ersal\Http\SoapClientFactory;
use Eram\Abzar\Money\Amount;
use Eram\Ersal\Provider\AbstractSoapProvider;
use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Request\LabelResponse;
use Eram\Ersal\Request\Quote;
use Eram\Ersal\Request\QuoteRequest;
use Eram\Ersal\Shipment\Shipment;
use Eram\Ersal\Shipment\ShipmentId;
use Eram\Ersal\Shipment\ShipmentStatus;
use Eram\Ersal\Tracking\TrackingEvent;

/**
 * Iran Post (شرکت پست) shipping provider — SOAP API.
 *
 * Flow mirrors Iran Post's standard SOAP surface:
 *   calculateFee → createShipment → getShipmentStatus → cancelShipment.
 *
 * Capabilities: quote, book, track, cancel, label, branches.
 *
 * WSDL endpoints and method names reflect Iran Post's public service
 * catalog — verify against your contracted endpoint, which can vary by
 * service tier (pishtaz, sefareshi, special, international).
 */
final class PostProvider extends AbstractSoapProvider implements
    ShippingInterface,
    SupportsLabel,
    SupportsBranches
{
    private const WSDL_URL = 'https://api.post.ir/services/shipping?wsdl';
    private const SANDBOX_WSDL_URL = 'https://sandbox.post.ir/services/shipping?wsdl';

    public function __construct(
        private readonly PostConfig $config,
        ?SoapClientFactory $soapFactory = null,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($soapFactory, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'post';
    }

    protected function getWsdlUrl(): string
    {
        return $this->config->wsdlUrl
            ?? ($this->config->sandbox ? self::SANDBOX_WSDL_URL : self::WSDL_URL);
    }

    public function quote(QuoteRequest $request): array
    {
        /** @var object $result */
        $result = $this->callSoap('calculateFee', array_merge($this->authParams(), [
            'originPostalCode' => $request->origin->postalCode,
            'destinationPostalCode' => $request->destination->postalCode,
            'weightGrams' => $request->parcel->chargeableWeightGrams(),
            'declaredValue' => $request->parcel->declaredValue?->inRials(),
            'serviceLevel' => $request->serviceLevel ?? 'pishtaz',
        ]));

        $this->assertReturnOk($result, 'quote');

        /** @var array<int, object> $rows */
        $rows = (array) ($result->rates ?? []);

        $quotes = [];
        foreach ($rows as $row) {
            $quotes[] = new Quote(
                providerName: $this->getName(),
                serviceLevel: (string) ($row->serviceLevel ?? 'pishtaz'),
                cost: Amount::fromRials((int) ($row->cost ?? 0)),
                etaDays: isset($row->etaDays) ? (int) $row->etaDays : null,
                quoteId: null,
                extra: (array) $row,
            );
        }

        $this->dispatch(new ShipmentQuoted($this->getName(), $request, $quotes));

        return $quotes;
    }

    public function createShipment(BookingRequest $request): ShipmentInterface
    {
        /** @var object $result */
        $result = $this->callSoap('createShipment', array_merge($this->authParams(), [
            'orderId' => $request->orderId,
            'serviceLevel' => $request->serviceLevel ?? 'pishtaz',
            'origin' => $this->soapAddress($request->origin),
            'destination' => $this->soapAddress($request->destination),
            'parcel' => $this->soapParcel($request->parcel),
            'description' => $request->description ?? '',
        ]));

        $this->assertReturnOk($result, 'book');

        $shipmentId = (string) ($result->shipmentId ?? '');
        $trackingCode = (string) ($result->trackingCode ?? '');

        $shipment = new Shipment(
            id: new ShipmentId($shipmentId),
            providerName: $this->getName(),
            trackingCode: $trackingCode,
            status: ShipmentStatus::Booked,
            origin: $request->origin,
            destination: $request->destination,
            parcel: $request->parcel,
            cost: isset($result->cost) ? Amount::fromRials((int) $result->cost) : null,
            extra: (array) $result,
        );

        $this->dispatch(new ShipmentCreated($this->getName(), $shipment));

        return $shipment;
    }

    public function track(ShipmentId $id): ShipmentInterface
    {
        /** @var object $result */
        $result = $this->callSoap('getShipmentStatus', array_merge($this->authParams(), [
            'shipmentId' => $id->value(),
        ]));

        $this->assertReturnOk($result, 'track');

        $history = [];
        /** @var array<int, object> $events */
        $events = (array) ($result->history ?? []);
        foreach ($events as $event) {
            $history[] = new TrackingEvent(
                at: new \DateTimeImmutable((string) ($event->at ?? 'now')),
                status: $this->mapStatus((string) ($event->status ?? 'in_transit')),
                description: (string) ($event->description ?? ''),
                location: $this->nullIfEmpty((string) ($event->location ?? '')),
                raw: (array) $event,
            );
        }

        $shipment = new Shipment(
            id: $id,
            providerName: $this->getName(),
            trackingCode: (string) ($result->trackingCode ?? ''),
            status: $this->mapStatus((string) ($result->status ?? 'booked')),
            origin: $this->placeholderAddress(),
            destination: $this->placeholderAddress(),
            parcel: new Parcel(weightGrams: 1),
            cost: isset($result->cost) ? Amount::fromRials((int) $result->cost) : null,
            history: $history,
            extra: (array) $result,
        );

        $this->dispatch(new ShipmentTracked($this->getName(), $shipment));

        return $shipment;
    }

    public function cancel(ShipmentId $id): ShipmentInterface
    {
        /** @var object $result */
        $result = $this->callSoap('cancelShipment', array_merge($this->authParams(), [
            'shipmentId' => $id->value(),
        ]));

        $this->assertReturnOk($result, 'cancel');

        $shipment = new Shipment(
            id: $id,
            providerName: $this->getName(),
            trackingCode: (string) ($result->trackingCode ?? ''),
            status: ShipmentStatus::Cancelled,
            origin: $this->placeholderAddress(),
            destination: $this->placeholderAddress(),
            parcel: new Parcel(weightGrams: 1),
            extra: (array) $result,
        );

        $this->dispatch(new ShipmentCancelled($this->getName(), $shipment));

        return $shipment;
    }

    public function getLabel(ShipmentId $id): LabelResponse
    {
        /** @var object $result */
        $result = $this->callSoap('getShipmentLabel', array_merge($this->authParams(), [
            'shipmentId' => $id->value(),
        ]));

        $this->assertReturnOk($result, 'label');

        $base64 = (string) ($result->bytesBase64 ?? '');
        $bytes = $base64 !== '' ? (string) base64_decode($base64, true) : '';

        return new LabelResponse(
            format: (string) ($result->format ?? 'pdf'),
            bytes: $bytes,
            url: $this->nullIfEmpty((string) ($result->url ?? '')),
        );
    }

    public function listBranches(?string $city = null): array
    {
        /** @var object $result */
        $result = $this->callSoap('listBranches', array_merge($this->authParams(), [
            'city' => $city,
        ]));

        $this->assertReturnOk($result, 'quote');

        /** @var array<int, object> $rows */
        $rows = (array) ($result->branches ?? []);

        $branches = [];
        foreach ($rows as $row) {
            $branches[] = new Branch(
                id: (string) ($row->id ?? ''),
                name: (string) ($row->name ?? ''),
                city: (string) ($row->city ?? ''),
                address: (string) ($row->address ?? ''),
                phone: $this->nullIfEmpty((string) ($row->phone ?? '')),
                lat: isset($row->lat) ? (float) $row->lat : null,
                lng: isset($row->lng) ? (float) $row->lng : null,
                openingHours: $this->nullIfEmpty((string) ($row->openingHours ?? '')),
            );
        }

        return $branches;
    }

    /**
     * @return array<string, string>
     */
    private function authParams(): array
    {
        return [
            'username' => $this->config->username,
            'password' => $this->config->password,
            'contractCode' => $this->config->contractCode,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function soapAddress(Address $a): array
    {
        return [
            'firstName' => $a->firstName,
            'lastName' => $a->lastName,
            'phone' => $a->phone,
            'province' => $a->province,
            'city' => $a->city,
            'addressLine' => $a->addressLine,
            'postalCode' => $a->postalCode,
            'plate' => $a->plate,
            'unit' => $a->unit,
            'nationalId' => $a->nationalId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function soapParcel(Parcel $p): array
    {
        return [
            'weightGrams' => $p->weightGrams,
            'lengthMm' => $p->lengthMm,
            'widthMm' => $p->widthMm,
            'heightMm' => $p->heightMm,
            'declaredValue' => $p->declaredValue?->inRials(),
            'contents' => $p->contentsDescription,
            'fragile' => $p->fragile,
        ];
    }

    private function assertReturnOk(object $result, string $operation): void
    {
        $code = (int) ($result->code ?? 0);

        if ($code === 0) {
            return;
        }

        $errorEnum = PostErrorCode::tryFrom($code);
        $message = $errorEnum?->message()
            ?? (string) ($result->message ?? "Unknown error code: {$code}");

        match ($operation) {
            'book' => $this->failBooking($message, $code),
            'track' => $this->failTracking($message, $code),
            'cancel' => $this->failCancellation($message, $code),
            default => $this->failQuote($message, $code),
        };
    }

    private function placeholderAddress(): Address
    {
        return new Address(
            firstName: '-',
            lastName: '-',
            phone: '09000000000',
            province: '-',
            city: '-',
            addressLine: '-',
        );
    }

    private function mapStatus(string $raw): ShipmentStatus
    {
        return ShipmentStatus::fromCanonical($raw);
    }
}
