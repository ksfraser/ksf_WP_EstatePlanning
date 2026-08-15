<?php
// src/Endpoints/EstateTaxController.php
declare(strict_types=1);

namespace Ksfraser\WP\EstatePlanning\Endpoints;

use Ksfraser\EstatePlanning\Services\EstateTaxCalculatorService;
use Ksfraser\EstatePlanning\ValueObjects\EstateValue;
use Ksfraser\EstatePlanning\ValueObjects\Jurisdiction;
use Ksfraser\WP\EstatePlanning\Models\EstatePlanningScenario;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class EstateTaxController
{
    private EstateTaxCalculatorService $calculator;

    public function __construct()
    {
        $this->calculator = new EstateTaxCalculatorService();
    }

    public function calculateTax(WP_REST_Request $request): WP_REST_Response
    {
        $value = (float) $request->get_param('estate_value');
        $province = sanitize_text_field($request->get_param('province'));
        $taxYear = (int) $request->get_param('tax_year', date('Y'));
        $exemptions = $request->get_param('exemptions', []);
        $credits = $request->get_param('credits', []);

        try {
            if ($value <= 0) {
                return new WP_REST_Response([
                    'success' => false,
                    'error' => 'Estate value must be positive',
                ], WP_REST_Server::BAD_REQUEST);
            }

            $jurisdiction = new Jurisdiction("CA-{$province}");
            $estateValue = new EstateValue($value, 'CAD');
            
            if (!empty($exemptions) || !empty($credits)) {
                $result = $this->calculator->calculateWithExemptions($estateValue, $exemptions, $credits, $jurisdiction);
            } else {
                $result = $this->calculator->calculateTotalTax($estateValue, $jurisdiction);
            }
            
            $federal = $this->calculator->calculateFederalTax($estateValue, $jurisdiction);
            $provincial = $this->calculator->calculateProvincialTax($estateValue, $jurisdiction);
            
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'gross_estate' => $value,
                    'jurisdiction' => $province,
                    'tax_year' => $taxYear,
                    'federal_tax' => $federal->getAmount(),
                    'provincial_tax' => $provincial->getAmount(),
                    'total_tax' => $result->getAmount(),
                    'effective_rate' => $value > 0 ? round(($result->getAmount() / $value) * 100, 2) : 0,
                ]
            ], WP_REST_Server::CREATED);
        } catch (\Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $e->getMessage(),
            ], WP_REST_Server::BAD_REQUEST);
        }
    }

    public function getScenarios(WP_REST_Request $request): WP_REST_Response
    {
        $userId = get_current_user_id();
        $scenarios = EstatePlanningScenario::getByUser($userId);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $scenarios,
        ]);
    }

    public function handleScenario(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $method = $request->get_method();
        
        try {
            switch ($method) {
                case 'GET':
                    $scenario = EstatePlanningScenario::getById($id);
                    if (!$scenario) {
                        return new WP_REST_Response([
                            'success' => false,
                            'error' => 'Scenario not found',
                        ], WP_REST_Server::NOT_FOUND);
                    }
                    return new WP_REST_Response(['success' => true, 'data' => $scenario]);
                    
                case 'PUT':
                    $data = $request->get_json_params();
                    $scenario = EstatePlanningScenario::update($id, $data);
                    return new WP_REST_Response(['success' => true, 'data' => $scenario]);
                    
                case 'DELETE':
                    EstatePlanningScenario::delete($id);
                    return new WP_REST_Response(['success' => true, 'message' => 'Deleted']);
            }
        } catch (\Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $e->getMessage(),
            ], WP_REST_Server::BAD_REQUEST);
        }
    }
}