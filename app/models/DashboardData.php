<?php

namespace SysInescolara\models;

use PDO;
use SysInescolara\core\Database;

class DashboardData extends Database
{
    private PDO $secDb;

    public function __construct()
    {
        parent::__construct();
        $this->secDb = $this->createSecurityConnection();
    }

    private function createSecurityConnection(): PDO
    {
        $host = getenv('DB_SEC_HOST') ?: getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_SEC_PORT') ?: getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_SEC_NAME') ?: 'SysInescolara-Seguridad';
        $username = getenv('DB_SEC_USER') ?: getenv('DB_USER') ?: 'root';
        $password = getenv('DB_SEC_PASSWORD') ?: getenv('DB_PASSWORD') ?: '';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $username, $password, $options);
    }

    public function getStats(): array
    {
        $stats = [];

        try { $stats['total_especies'] = (int) $this->db->query("SELECT COUNT(*) FROM especie")->fetchColumn(); } catch (\Throwable $e) { $stats['total_especies'] = 0; }
        try { $stats['total_plantas'] = (int) $this->db->query("SELECT COUNT(*) FROM plantas")->fetchColumn(); } catch (\Throwable $e) { $stats['total_plantas'] = 0; }
        try { $stats['total_clientes'] = (int) $this->db->query("SELECT COUNT(*) FROM cliente")->fetchColumn(); } catch (\Throwable $e) { $stats['total_clientes'] = 0; }
        try { $stats['total_proveedores'] = (int) $this->db->query("SELECT COUNT(*) FROM proveedores")->fetchColumn(); } catch (\Throwable $e) { $stats['total_proveedores'] = 0; }
        try { $stats['total_trabajadores'] = (int) $this->db->query("SELECT COUNT(*) FROM trabajadores")->fetchColumn(); } catch (\Throwable $e) { $stats['total_trabajadores'] = 0; }
        try { $stats['total_lotes'] = (int) $this->db->query("SELECT COUNT(*) FROM lote")->fetchColumn(); } catch (\Throwable $e) { $stats['total_lotes'] = 0; }
        try { $stats['total_insumos'] = (int) $this->db->query("SELECT COUNT(*) FROM insumo")->fetchColumn(); } catch (\Throwable $e) { $stats['total_insumos'] = 0; }
        try { $stats['total_herramientas'] = (int) $this->db->query("SELECT COUNT(*) FROM herramienta")->fetchColumn(); } catch (\Throwable $e) { $stats['total_herramientas'] = 0; }
        try { $stats['total_precios_vigentes'] = (int) $this->db->query("SELECT COUNT(*) FROM planta_precio_vigente")->fetchColumn(); } catch (\Throwable $e) { $stats['total_precios_vigentes'] = 0; }
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM movimiento_planta WHERE tipo_movimiento = 'Venta'");
            $stats['total_ventas'] = (int) $stmt->fetchColumn();
        } catch (\Throwable $e) { $stats['total_ventas'] = 0; }

        return $stats;
    }

    public function getRecentActivity(int $limit = 10): array
    {
        try {
            $stmt = $this->secDb->prepare("
                SELECT a.id_log, a.accion, a.tabla_afectada, a.id_registro_afectado, a.fecha_accion, a.valor_nuevo, u.nombre_usuario
                FROM auditoria_logs a
                LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
                ORDER BY a.fecha_accion DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error al obtener actividad reciente: ' . $e->getMessage());
            return [];
        }
    }

    public function getLowStockLots(int $threshold = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT l.id_lote, l.cantidad_actual, l.estado, p.nombre_comun AS planta_nombre
                FROM lote l
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                WHERE l.cantidad_actual < :threshold
                ORDER BY l.cantidad_actual ASC
                LIMIT 10
            ");
            $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error al obtener lotes con stock bajo: ' . $e->getMessage());
            return [];
        }
    }

    public function getLowStockSupplies(int $threshold = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT i.id_insumo, i.nombre_insumo, i.stock_actual, u.nombre_unidad_medida AS unidad_medida
                FROM insumo i
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE i.stock_actual < :threshold
                ORDER BY i.stock_actual ASC
                LIMIT 10
            ");
            $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error al obtener insumos con stock bajo: ' . $e->getMessage());
            return [];
        }
    }

    public function getReportData(string $reportType, array $params = []): array
    {
        switch ($reportType) {
            case 'plants_by_species':
                return $this->reportPlantsBySpecies();
            case 'lots_by_status':
                return $this->reportLotsByStatus();
            case 'inventory_summary':
                return $this->reportInventorySummary();
            case 'supply_stock':
                return $this->reportSupplyStock();
            case 'recent_sales':
                return $this->reportRecentSales();
            default:
                return [];
        }
    }

    private function reportPlantsBySpecies(): array
    {
        try {
            return $this->db->query("
                SELECT e.nombre_especie AS especie, COUNT(p.id_planta) AS total_plantas
                FROM especie e
                LEFT JOIN plantas p ON p.id_especie = e.id_especie
                GROUP BY e.id_especie, e.nombre_especie
                ORDER BY total_plantas DESC
            ")->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error en reportPlantsBySpecies: ' . $e->getMessage());
            return [];
        }
    }

    private function reportLotsByStatus(): array
    {
        try {
            return $this->db->query("
                SELECT l.estado, COUNT(*) AS total_lotes, SUM(l.cantidad_actual) AS total_plantas
                FROM lote l
                GROUP BY l.estado
                ORDER BY total_lotes DESC
            ")->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error en reportLotsByStatus: ' . $e->getMessage());
            return [];
        }
    }

    private function reportInventorySummary(): array
    {
        try {
            return $this->db->query("
                SELECT 
                    CASE 
                        WHEN l.cantidad_actual <= 0 THEN 'Sin stock'
                        WHEN l.cantidad_actual < 20 THEN 'Bajo'
                        WHEN l.cantidad_actual < 50 THEN 'Medio'
                        ELSE 'Alto'
                    END AS nivel_stock,
                    COUNT(*) AS total_lotes,
                    SUM(l.cantidad_actual) AS total_plantas
                FROM lote l
                GROUP BY nivel_stock
                ORDER BY FIELD(nivel_stock, 'Alto', 'Medio', 'Bajo', 'Sin stock')
            ")->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error en reportInventorySummary: ' . $e->getMessage());
            return [];
        }
    }

    private function reportSupplyStock(): array
    {
        try {
            return $this->db->query("
                SELECT i.id_insumo, i.nombre_insumo, i.stock_actual, u.nombre_unidad_medida AS unidad_medida, i.costo_unitario_actual
                FROM insumo i
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                ORDER BY i.stock_actual ASC
            ")->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error en reportSupplyStock: ' . $e->getMessage());
            return [];
        }
    }

    private function reportRecentSales(): array
    {
        try {
            return $this->db->query("
                SELECT mp.id_movimiento_planta, c.nombre_cliente, mpd.sub_total AS monto_total, mp.fecha_movimiento AS fecha_venta
                FROM movimiento_planta mp
                LEFT JOIN cliente c ON mp.id_cliente = c.id_cliente
                LEFT JOIN movimiento_planta_detalle mpd ON mp.id_movimiento_planta = mpd.id_movimiento_planta
                WHERE mp.tipo_movimiento = 'Venta'
                GROUP BY mp.id_movimiento_planta
                ORDER BY mp.fecha_movimiento DESC
                LIMIT 100
            ")->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error en reportRecentSales: ' . $e->getMessage());
            return [];
        }
    }
}
