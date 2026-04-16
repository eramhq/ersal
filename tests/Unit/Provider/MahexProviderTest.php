<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Provider;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\HttpResponse;
use Eram\Ersal\Provider\Mahex\MahexConfig;
use Eram\Ersal\Provider\Mahex\MahexProvider;
use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Shipment\ShipmentStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MahexProviderTest extends TestCase
{
    #[Test]
    public function provider_name(): void
    {
        $p = new MahexProvider(new MahexConfig('t'), $this->createMock(HttpClient::class));

        $this->assertSame('mahex', $p->getName());
    }

    #[Test]
    public function create_shipment(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('postJson')->willReturn(new HttpResponse(200, (string) json_encode([
            'data' => ['id' => 'MX-1', 'tracking_code' => 'T-1', 'status' => 'booked', 'cost' => 500_000],
        ])));

        $p = new MahexProvider(new MahexConfig('t'), $client);

        $s = $p->createShipment(new BookingRequest(
            origin: $this->addr(),
            destination: $this->addr('اصفهان'),
            parcel: new Parcel(weightGrams: 800),
            orderId: 'O-1',
        ));

        $this->assertSame('MX-1', $s->getId()->value());
        $this->assertSame(ShipmentStatus::Booked, $s->getStatus());
    }

    private function addr(string $city = 'تهران'): Address
    {
        return new Address(
            firstName: 'Ali',
            lastName: 'R',
            phone: '09123456789',
            province: $city,
            city: $city,
            addressLine: 'خ 1',
            postalCode: '1234567890',
        );
    }
}
