<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte - <?= htmlspecialchars($moduleName) ?></title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #2c3e50; }
        .header h1 { font-size: 14pt; margin: 3px 0; color: #2c3e50; }
        .header h2 { font-size: 11pt; margin: 2px 0; color: #27ae60; }
        .header p { font-size: 7pt; color: #666; margin: 1px 0; }
        .meta { margin-bottom: 15px; font-size: 8pt; color: #555; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 1px 5px; }
        .meta .label { font-weight: bold; width: 100px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 8pt; }
        table.data th { background: #2c3e50; color: white; padding: 5px 6px; text-align: left; font-size: 8pt; }
        table.data td { padding: 3px 6px; border-bottom: 1px solid #ddd; }
        table.data tr:nth-child(even) { background: #f9f9f9; }
        table.data .text-right { text-align: right; }
        table.data .text-center { text-align: center; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; color: #999; font-size: 7pt; }
    </style>
</head>
<body>
    <div class="header">
        <h1>VIVERO INSTITUCIONAL INECOLARA</h1>
        <h2>Reporte de <?= htmlspecialchars($moduleName) ?></h2>
        <p>RIF: J-XXXXXXXX-X | Av. Principal, Barquisimeto, Estado Lara</p>
        <p>Tel: (0251) XXX-XXXX | Email: vivero@inecolara.gob.ve</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">Generado por:</td>
                <td><?= htmlspecialchars($usuario) ?></td>
                <td class="label">Fecha:</td>
                <td><?= htmlspecialchars($fechaGeneracion) ?></td>
            </tr>
            <?php if (!empty($filterLabels)): ?>
            <tr>
                <td class="label">Filtros:</td>
                <td colspan="3"><?= implode(' | ', $filterLabels) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <?php if (!empty($data['rows'])): ?>
    <table class="data">
        <thead>
            <tr>
                <?php foreach ($data['columns'] as $col): ?>
                <th><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['rows'] as $row): ?>
            <tr>
                <?php foreach ($data['columns'] as $i => $col): ?>
                <?php
                $keys = array_keys($row);
                $key = $keys[$i] ?? '';
                $val = $row[$key] ?? '-';
                $isCurrency = in_array($key, ['total','subtotal','iva','costo_unitario_actual','costo_unitario','monto_total','precio_unitario']);
                $class = $isCurrency ? ' class="text-right"' : '';
                ?>
                <td<?= $class ?>><?= htmlspecialchars((string)$val) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="text-align:center;color:#999;">No se encontraron datos para este reporte.</p>
    <?php endif; ?>

    <div class="footer">
        <p>Documento generado electrónicamente el <?= date('d/m/Y h:i:s A') ?> por SYSINECOLARA</p>
        <p>Vivero INECOLARA — Cultivando el futuro</p>
    </div>
</body>
</html>
