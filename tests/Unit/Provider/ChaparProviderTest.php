<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Provider;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Exception\BookingException;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\HttpResponse;
use Eram\Ersal\Provider\Chapar\ChaparConfig;
use Eram\Ersal\Provider\Chapar\ChaparProvider;
use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Request\QuoteRequest;
use Eram\Ersal\Shipment\ShipmentId;
use Eram\Ersal\Shipment\ShipmentStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ChaparProviderTest extends TestCase
{
    #[Test]
    public function provider_name(): void
    {
        $p = new ChaparProvider(new ChaparConfig('key'), $this->createMock(HttpClient::class));

        $this->assertSame('chapar', $p->getName());
    }

    #[Test]
    public function quote_parses_rates(): void
    {
        $client = $this->mockClient((string) json_encode([
            'data' => [
                'rates' => [
                    ['service_level' => 'standard', 'cost' => 700_000, 'eta_days' => 2, 'rate_id' => 'r1'],
                ],
            ],
        ]));

        $p = new ChaparProvider(new ChaparConfig('key'), $client);

        $quotes = $p->quote(new QuoteRequest(
            origin: $this->address(),
            destination: $this->address(city: 'اصفهان'),
            parcel: new Parcel(weightGrams: 1000),
        ));

        $this->assertCount(1, $quotes);
        $this->assertSame(700_000, $quotes[0]->cost->inRials());
    }

    #[Test]
    public function create_shipment_returns_booked(): void
    {
        $client = $this->mockClient((string) json_encode([
            'data' => ['id' => 'CHP-1', 'tracking_code' => 'T-1', 'status' => 'booked', 'cost' => 700_000],
        ]));

        $p = new ChaparProvider(new ChaparConfig('key'), $client);

        $shipment = $p->createShipment(new BookingRequest(
            origin: $this->address(),
            destination: $this->address(city: 'تبریز'),
            parcel: new Parcel(weightGrams: 1000),
            orderId: 'O-1',
        ));

        $this->assertSame('CHP-1', $shipment->getId()->value());
        $this->assertSame(ShipmentStatus::Booked, $shipment->getStatus());
    }

    #[Test]
    public function duplicate_order_id_throws_booking_exception(): void
    {
        $client = $this->mockClient((string) json_encode([
            'error' => ['code' => 2101, 'message' => 'Duplicate order ID'],
        ]));

        $p = new ChaparProvider(new ChaparConfig('key'), $client);

        $this->expectException(BookingException::class);

        $p->createShipment(new BookingRequest(
            origin: $this->address(),
            destination: $this->address(city: 'شیراز'),
            parcel: new Parcel(weightGrams: 1000),
            orderId: 'O-1',
        ));
    }

    #[Test]
    public function cancel_marks_cancelled(): void
    {
        $client = $this->mockClient((string) json_encode([
            'data' => ['id' => 'CHP-1', 'tracking_code' => 'T-1', 'status' => 'cancelled'],
        ]));

        $p = new ChaparProvider(new ChaparConfig('key'), $client);

        $shipment = $p->cancel(new ShipmentId('CHP-1'));

        $this->assertSame(ShipmentStatus::Cancelled, $shipment->getStatus());
    }

    private function address(string $city = 'تهران'): Address
    {
        return new Address(
            firstName: 'Ali',
            lastName: 'Rezaei',
            phone: '09123456789',
            province: $city,
            city: $city,
            addressLine: 'خیابان 1',
            postalCode: '1234567890',
        );
    }

    private function mockClient(string $body): HttpClient
    {
        $client = $this->createMock(HttpClient::class);
        $response = new HttpResponse(200, $body);
        $client->method('postJson')->willReturn($response);
        $client->method('getJson')->willReturn($response);
        $client->method('deleteJson')->willReturn($response);

        return $client;
    }
}
