<?php
// src/Models/EstatePlanningScenario.php
declare(strict_types=1);

namespace Ksfraser\WP\EstatePlanning\Models;

use wpdb;

class EstatePlanningScenario
{
    private static string $table = 'ksf_estate_scenarios';
    private static string $beneficiariesTable = 'ksf_estate_beneficiaries';

    public static function getByUser(int $userId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
            $userId
        ), ARRAY_A) ?? [];
    }

    public static function getById(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        
        $scenario = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if ($scenario) {
            $scenario['beneficiaries'] = self::getBeneficiaries($id);
        }
        
        return $scenario ?? null;
    }

    public static function create(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        
        $defaults = [
            'user_id' => get_current_user_id(),
            'scenario_name' => 'New Scenario',
            'data' => null,
            'status' => 'draft',
        ];
        
        $data = array_merge($defaults, $data);
        
        $wpdb->insert($table, [
            'user_id' => $data['user_id'],
            'scenario_name' => $data['scenario_name'],
            'data' => json_encode($data['data']),
            'status' => $data['status'],
        ]);
        
        return (int) $wpdb->insert_id;
    }

    public static function update(int $id, array $data): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        
        $updateData = [];
        if (isset($data['scenario_name'])) {
            $updateData['scenario_name'] = $data['scenario_name'];
        }
        if (isset($data['data'])) {
            $updateData['data'] = json_encode($data['data']);
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        
        if (!empty($updateData)) {
            $wpdb->update($table, $updateData, ['id' => $id]);
        }
        
        return self::getById($id);
    }

    public static function delete(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        $beneficiariesTable = $wpdb->prefix . self::$beneficiariesTable;
        
        $wpdb->delete($beneficiariesTable, ['scenario_id' => $id]);
        return $wpdb->delete($table, ['id' => $id]) > 0;
    }

    private static function getBeneficiaries(int $scenarioId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$beneficiariesTable;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE scenario_id = %d",
            $scenarioId
        ), ARRAY_A) ?? [];
    }
}