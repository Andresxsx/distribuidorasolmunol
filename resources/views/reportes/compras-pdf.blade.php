<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .header {
            margin-bottom: 18px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            color: #0f172a;
        }

        .subtitle {
            margin: 4px 0;
            color: #475569;
        }

        .summary {
            margin: 12px 0;
            padding: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #0f172a;
            color: white;
            padding: 7px;
            border: 1px solid #0f172a;
            text-align: left;
        }

        td {
            padding: 6px;
            border: 1px solid #cbd5e1;
        }

        .money {
            text-align: right;
        }

        .footer {
            margin-top: 16px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Distribuidora Solmunol - Reporte de Compras</p>
        <p class="subtitle">Fecha de generación: {{ $fechaHora }}</p>
        <p class="subtitle">Moneda: USD</p>
    </div>

    <div class="summary">
        <strong>Total de compras:</strong> ${{ number_format($totalCompras, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Compra</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Total</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($compras as $compra)
                <tr>
                    <td>{{ $compra->numero_compra }}</td>
                    <td>{{ optional($compra->fecha)->format('d/m/Y') }}</td>
                    <td>{{ $compra->proveedor->nombre ?? 'Proveedor eliminado' }}</td>
                    <td>{{ $compra->producto->nombre ?? 'Producto eliminado' }}</td>
                    <td>{{ $compra->cantidad }}</td>
                    <td class="money">${{ number_format($compra->precio_unitario, 2) }}</td>
                    <td class="money">${{ number_format($compra->total, 2) }}</td>
                    <td>{{ $compra->user->name ?? 'Sistema' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No hay compras registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Reporte generado automáticamente por ERP Solis.
    </div>
</body>
</html>