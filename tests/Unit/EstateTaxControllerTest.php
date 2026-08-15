<?php
// tests/Unit/EstateTaxControllerTest.php
declare(strict_types=1);

namespace Tests\Unit;

use Ksfraser\WP\EstatePlanning\Endpoints\EstateTaxController;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Server;

class EstateTaxControllerTest extends TestCase
{
    private EstateTaxController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new EstateTaxController();
    }

    public function testCalculateTaxSuccess(): void
    {
        $request = new WP_REST_Request('POST');
        $request->set_param('estate_value', 1000000);
        $request->set_param('province', 'ON');
        $request->set_param('tax_year', 2026);

        $response = $this->controller->calculateTax($request);
        
        $this->assertEquals(WP_REST_Server::CREATED, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('total_tax', $data['data']);
    }

    public function testCalculateTaxWithExemptions(): void
    {
        $request = new WP_REST_Request('POST');
        $request->set_param('estate_value', 2000000);
        $request->set_param('province', 'BC');
        $request->set_param('exemptions', [100000]);
        $request->set_param('credits', [5000]);

        $response = $this->controller->calculateTax($request);
        
        $this->assertEquals(WP_REST_Server::CREATED, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    public function testCalculateTaxInvalidValue(): void
    {
        $request = new WP_REST_Request('POST');
        $request->set_param('estate_value', -1000);
        $request->set_param('province', 'ON');

        $response = $this->controller->calculateTax($request);
        
        $this->assertEquals(WP_REST_Server::BAD_REQUEST, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
    }
}