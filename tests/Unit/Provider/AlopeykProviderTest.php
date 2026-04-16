<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Provider;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\HttpResponse;
use Eram\Ersal\Provider\Alopeyk\AlopeykConfig;
use Eram\Ersal\Provider\Alopeyk\AlopeykProvider;
use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Shipment\ShipmentStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AlopeykProviderTest extends TestCase
{
    #[Test]
    public function provider_name(): void
    {
        $p = new AlopeykProvider(new AlopeykConfig('t'), $this->createMock(HttpClient::class));
        $this->assertSame('alopeyk', $p->getName());
    }

    #[Test]
    public function create_shipment(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('postJson')->willReturn(new HttpResponse(200, (string) json_encode([
            'data' => ['order_id' => 'AL-1', 'tracking_code' => 'T-1', 'status' => 'accepted'],
        ])));

        $p = new AlopeykProvider(new AlopeykConfig('t'), $client);

        $s = $p->createShipment(new BookingRequest(
            origin: $this->addr(),
            destination: $this->addr(),
            parcel: new Parcel(weightGrams: 300),
            orderId: 'O-1',
        ));

        $this->assertSame('AL-1', $s->getId()->value());
        $this->assertSame(ShipmentStatus::Booked, $s->getStatus());
    }

    private function addr(string $city = 'تهران'): Address
    {
        return new Address(
            firstName: 'A',
            lastName: 'R',
            phone: '09123456789',
            province: $city,
            city: $city,
            addressLine: 'خ 1',
        );
    }
}
