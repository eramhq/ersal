<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Provider;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\HttpResponse;
use Eram\Ersal\Provider\Amadast\AmadastConfig;
use Eram\Ersal\Provider\Amadast\AmadastProvider;
use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Shipment\ShipmentStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AmadastProviderTest extends TestCase
{
    #[Test]
    public function provider_name(): void
    {
        $p = new AmadastProvider(new AmadastConfig('k'), $this->createMock(HttpClient::class));
        $this->assertSame('amadast', $p->getName());
    }

    #[Test]
    public function create_shipment(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('postJson')->willReturn(new HttpResponse(200, (string) json_encode([
            'data' => ['id' => 'AMD-1', 'tracking_code' => 'T-1', 'status' => 'booked'],
        ])));

        $p = new AmadastProvider(new AmadastConfig('k'), $client);

        $s = $p->createShipment(new BookingRequest(
            origin: $this->addr(),
            destination: $this->addr('اصفهان'),
            parcel: new Parcel(weightGrams: 1200),
            orderId: 'O-1',
        ));

        $this->assertSame('AMD-1', $s->getId()->value());
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
            postalCode: '1234567890',
        );
    }
}
