<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Provider;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Exception\BookingException;
use Eram\Ersal\Exception\TrackingException;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\HttpResponse;
use Eram\Ersal\Provider\Tipax\TipaxConfig;
use Eram\Ersal\Provider\Tipax\TipaxProvider;
use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Request\QuoteRequest;
use Eram\Ersal\Shipment\ShipmentId;
use Eram\Ersal\Shipment\ShipmentStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TipaxProviderTest extends TestCase
{
    #[Test]
    public function gateway_name(): void
    {
        $provider = new TipaxProvider(
            new TipaxConfig('token'),
            $this->createMock(HttpClient::class),
        );

        $this->assertSame('tipax', $provider->getName());
    }

    #[Test]
    public function quote_returns_list_of_quotes(): void
    {
        $httpClient = $this->createMockHttpClient(200, (string) json_encode([
            'data' => [
                'quotes' => [
                    ['service_level' => 'standard', 'cost' => 800_000, 'eta_days' => 3, 'quote_id' => 'q1'],
                    ['service_level' => 'express', 'cost' => 1_500_000, 'eta_days' => 1, 'quote_id' => 'q2'],
                ],
            ],
        ]));

        $provider = new TipaxProvider(new TipaxConfig('token'), $httpClient);

        $quotes = $provider->quote(new QuoteRequest(
            origin: $this->makeAddress(),
            destination: $this->makeAddress(city: 'مشهد'),
            parcel: new Parcel(weightGrams: 2000),
        ));

        $this->assertCount(2, $quotes);
        $this->assertSame('standard', $quotes[0]->serviceLevel);
        $this->assertSame(800_000, $quotes[0]->cost->inRials());
        $this->assertSame(3, $quotes[0]->etaDays);
        $this->assertSame('express', $quotes[1]->serviceLevel);
    }

    #[Test]
    public function create_shipment_returns_shipment_with_tracking(): void
    {
        $httpClient = $this->createMockHttpClient(200, (string) json_encode([
            'data' => [
                'shipment_id' => 'SHP-001',
                'tracking_code' => 'TRK-ABC-123',
                'status' => 'booked',
                'cost' => 900_000,
            ],
        ]));

        $provider = new TipaxProvider(new TipaxConfig('token'), $httpClient);

        $shipment = $provider->createShipment(new BookingRequest(
            origin: $this->makeAddress(),
            destination: $this->makeAddress(city: 'اصفهان'),
            parcel: new Parcel(weightGrams: 1500),
            orderId: 'ORDER-42',
        ));

        $this->assertSame('SHP-001', $shipment->getId()->value());
        $this->assertSame('TRK-ABC-123', $shipment->getTrackingCode());
        $this->assertSame(ShipmentStatus::Booked, $shipment->getStatus());
        $this->assertSame(900_000, $shipment->getCost()?->inRials());
        $this->assertSame('tipax', $shipment->getProviderName());
    }

    #[Test]
    public function create_shipment_throws_booking_exception_on_error(): void
    {
        $httpClient = $this->createMockHttpClient(200, (string) json_encode([
            'error' => ['code' => 1003, 'message' => 'Parcel too heavy'],
        ]));

        $provider = new TipaxProvider(new TipaxConfig('token'), $httpClient);

        $this->expectException(BookingException::class);

        $provider->createShipment(new BookingRequest(
            origin: $this->makeAddress(),
            destination: $this->makeAddress(city: 'شیراز'),
            parcel: new Parcel(weightGrams: 50_000),
            orderId: 'ORDER-99',
        ));
    }

    #[Test]
    public function track_returns_shipment_with_history(): void
    {
        $httpClient = $this->createMockHttpClient(200, (string) json_encode([
            'data' => [
                'shipment_id' => 'SHP-001',
                'tracking_code' => 'TRK-ABC-123',
                'status' => 'in_transit',
                'cost' => 900_000,
                'history' => [
                    [
                        'at' => '2026-04-15T08:00:00+03:30',
                        'status' => 'booked',
                        'description' => 'Shipment booked',
                        'location' => 'تهران',
                    ],
                    [
                        'at' => '2026-04-15T14:00:00+03:30',
                        'status' => 'picked_up',
                        'description' => 'Picked up by courier',
                        'location' => 'تهران',
                    ],
                    [
                        'at' => '2026-04-16T09:00:00+03:30',
                        'status' => 'in_transit',
                        'description' => 'Arrived at hub',
                        'location' => 'قم',
                    ],
                ],
            ],
        ]));

        $provider = new TipaxProvider(new TipaxConfig('token'), $httpClient);

        $shipment = $provider->track(new ShipmentId('SHP-001'));

        $this->assertSame(ShipmentStatus::InTransit, $shipment->getStatus());
        $history = $shipment->getHistory();
        $this->assertCount(3, $history);
        $this->assertSame(ShipmentStatus::Booked, $history[0]->status);
        $this->assertSame(ShipmentStatus::PickedUp, $history[1]->status);
        $this->assertSame(ShipmentStatus::InTransit, $history[2]->status);
        $this->assertSame('قم', $history[2]->location);
    }

    #[Test]
    public function track_throws_on_not_found(): void
    {
        $httpClient = $this->createMockHttpClient(200, (string) json_encode([
            'error' => ['code' => 404, 'message' => 'Not found'],
        ]));

        $provider = new TipaxProvider(new TipaxConfig('token'), $httpClient);

        $this->expectException(TrackingException::class);

        $provider->track(new ShipmentId('missing'));
    }

    #[Test]
    public function cancel_marks_shipment_cancelled(): void
    {
        $httpClient = $this->createMockHttpClient(200, (string) json_encode([
            'data' => [
                'shipment_id' => 'SHP-001',
                'tracking_code' => 'TRK-ABC-123',
                'status' => 'cancelled',
            ],
        ]));

        $provider = new TipaxProvider(new TipaxConfig('token'), $httpClient);

        $shipment = $provider->cancel(new ShipmentId('SHP-001'));

        $this->assertSame(ShipmentStatus::Cancelled, $shipment->getStatus());
    }

    #[Test]
    public function get_label_decodes_base64_bytes(): void
    {
        $rawPdf = "%PDF-1.4 fake label\n";
        $httpClient = $this->createMockHttpClient(200, (string) json_encode([
            'data' => [
                'format' => 'pdf',
                'bytes_base64' => base64_encode($rawPdf),
            ],
        ]));

        $provider = new TipaxProvider(new TipaxConfig('token'), $httpClient);

        $label = $provider->getLabel(new ShipmentId('SHP-001'));

        $this->assertSame('pdf', $label->format);
        $this->assertSame($rawPdf, $label->bytes);
    }

    #[Test]
    public function list_branches_parses_results(): void
    {
        $httpClient = $this->createMockHttpClient(200, (string) json_encode([
            'data' => [
                'branches' => [
                    ['id' => 'B1', 'name' => 'Branch 1', 'city' => 'تهران', 'address' => 'آدرس 1'],
                    ['id' => 'B2', 'name' => 'Branch 2', 'city' => 'مشهد', 'address' => 'آدرس 2', 'phone' => '09121111111'],
                ],
            ],
        ]));

        $provider = new TipaxProvider(new TipaxConfig('token'), $httpClient);

        $branches = $provider->listBranches();

        $this->assertCount(2, $branches);
        $this->assertSame('B1', $branches[0]->id);
        $this->assertSame('09121111111', $branches[1]->phone);
    }

    private function makeAddress(string $city = 'تهران'): Address
    {
        return new Address(
            firstName: 'Ali',
            lastName: 'Rezaei',
            phone: '09123456789',
            province: $city,
            city: $city,
            addressLine: 'خیابان ولیعصر',
            postalCode: '1234567890',
        );
    }

    private function createMockHttpClient(int $statusCode, string $body): HttpClient
    {
        $client = $this->createMock(HttpClient::class);
        $response = new HttpResponse($statusCode, $body);

        $client->method('postJson')->willReturn($response);
        $client->method('getJson')->willReturn($response);
        $client->method('deleteJson')->willReturn($response);

        return $client;
    }
}
