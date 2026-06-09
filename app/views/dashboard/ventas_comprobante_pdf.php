<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante - <?= htmlspecialchars($venta['referencia']) ?></title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #2c3e50; }
        .header h1 { font-size: 16pt; margin: 5px 0; color: #2c3e50; }
        .header h2 { font-size: 13pt; margin: 3px 0; color: #27ae60; }
        .header p { font-size: 8pt; color: #666; margin: 2px 0; }
        .datos { margin-bottom: 20px; }
        .datos table { width: 100%; border-collapse: collapse; }
        .datos td { padding: 3px 8px; vertical-align: top; font-size: 9pt; }
        .datos .label { font-weight: bold; width: 120px; color: #555; }
        table.productos { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9pt; }
        table.productos th { background: #2c3e50; color: white; padding: 7px 8px; text-align: left; font-size: 9pt; }
        table.productos td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        table.productos tr:nth-child(even) { background: #f9f9f9; }
        table.productos .text-right { text-align: right; }
        table.productos .text-center { text-align: center; }
        .totales { float: right; width: 300px; margin-top: 10px; }
        .totales table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        .totales td { padding: 4px 10px; }
        .totales .label-cell { text-align: right; font-weight: bold; color: #555; }
        .totales .value-cell { text-align: right; }
        .totales .total-row td { font-size: 12pt; font-weight: bold; color: #27ae60; border-top: 2px solid #27ae60; padding-top: 8px; }
        .pagos { margin-top: 10px; clear: both; }
        .pagos table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        .pagos th { background: #2c3e50; color: white; padding: 7px 8px; text-align: left; }
        .pagos td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; color: #999; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="header">
        <h1>VIVERO INSTITUCIONAL INECOLARA</h1>
        <h2>Comprobante de Venta</h2>
        <p>RIF: J-XXXXXXXX-X | Av. Principal, Barquisimeto, Estado Lara</p>
        <p>Tel: (0251) XXX-XXXX | Email: vivero@inecolara.gob.ve</p>
    </div>

    <div class="datos">
        <table>
            <tr>
                <td class="label">N° Referencia:</td>
                <td><?= htmlspecialchars($venta['referencia']) ?></td>
                <td class="label">Fecha:</td>
                <td><?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></td>
            </tr>
            <tr>
                <td class="label">Cliente:</td>
                <td><?= htmlspecialchars($venta['nombre_cliente'] ?? '') ?></td>
                <td class="label">Vendedor:</td>
                <td><?= htmlspecialchars(($venta['nombre_trabajador'] ?? '') . ' ' . ($venta['apellido_trabajador'] ?? '')) ?></td>
            </tr>
            <tr>
                <td class="label">Tipo Venta:</td>
                <td><?= ucfirst(htmlspecialchars($venta['tipo_venta'] ?? '')) ?></td>
                <td class="label">Estado:</td>
                <td><?= ucfirst(htmlspecialchars($venta['estado'] ?? '')) ?></td>
            </tr>
            <?php if (!empty($venta['observaciones'])): ?>
            <tr>
                <td class="label">Observaciones:</td>
                <td colspan="3"><?= htmlspecialchars($venta['observaciones']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <table class="productos">
        <thead>
            <tr>
                <th style="width:40px;text-align:center;">#</th>
                <th>Planta</th>
                <th style="width:80px;text-align:center;">Especie</th>
                <th style="width:60px;text-align:center;">Cant.</th>
                <th style="width:90px;text-align:right;">Precio Unit.</th>
                <th style="width:90px;text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php $cont = 1; ?>
            <?php foreach ($venta['detalles'] as $det): ?>
            <?php $subtotal = (float)$det['cantidad'] * (float)$det['precio_unitario']; ?>
            <tr>
                <td class="text-center"><?= $cont++ ?></td>
                <td><?= htmlspecialchars($det['planta_nombre'] ?? '') ?></td>
                <td class="text-center"><?= htmlspecialchars($det['especie_nombre'] ?? '') ?></td>
                <td class="text-center"><?= (int)$det['cantidad'] ?></td>
                <td class="text-right">Bs. <?= number_format((float)$det['precio_unitario'], 2, ',', '.') ?></td>
                <td class="text-right">Bs. <?= number_format($subtotal, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totales">
        <table>
            <tr>
                <td class="label-cell">Subtotal (sin IVA):</td>
                <td class="value-cell">Bs. <?= number_format($montoSinIva, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td class="label-cell">IVA (<?= number_format($venta['iva_porcentaje'], 0) ?>%):</td>
                <td class="value-cell">Bs. <?= number_format($montoIva, 2, ',', '.') ?></td>
            </tr>
            <tr class="total-row">
                <td>TOTAL A PAGAR:</td>
                <td>Bs. <?= number_format($montoTotal, 2, ',', '.') ?></td>
            </tr>
        </table>
    </div>

    <?php if (!empty($venta['pagos'])): ?>
    <div class="pagos">
        <h3 style="font-size:10pt;color:#2c3e50;margin-bottom:5px;">Detalle de Pagos</h3>
        <table>
            <thead>
                <tr>
                    <th>Método</th>
                    <th style="text-align:right;">Monto</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($venta['pagos'] as $pago): ?>
                <tr>
                    <td><?= ucfirst(htmlspecialchars($pago['metodo'] ?? '')) ?></td>
                    <td style="text-align:right;">Bs. <?= number_format((float)$pago['monto'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($pago['referencia'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Gracias por su compra. ¡Vivero INECOLARA, cultivando el futuro!</p>
        <p>Documento generado electrónicamente el <?= date('d/m/Y H:i:s') ?></p>
        <p>Este comprobante no es una factura fiscal.</p>
    </div>
</body>
</html>
