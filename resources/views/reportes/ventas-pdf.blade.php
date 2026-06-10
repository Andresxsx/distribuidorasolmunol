<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>

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
        <p class="title">Distribuidora Solmunol - Reporte de Ventas</p>
        <p class="subtitle">Fecha de generación: {{ $fechaHora }}</p>
        <p class="subtitle">Moneda: USD</p>
    </div>

    <div class="summary">
        <strong>Total de ventas:</strong> ${{ number_format($totalVentas, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Venta</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Total</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ventas as $venta)
                <tr>
                    <td>{{ $venta->numero_venta }}</td>
                    <td>{{ optional($venta->fecha)->format('d/m/Y') }}</td>
                    <td>{{ $venta->cliente->nombre ?? 'Cliente eliminado' }}</td>
                    <td>{{ $venta->producto->nombre ?? 'Producto eliminado' }}</td>
                    <td>{{ $venta->cantidad }}</td>
                    <td class="money">${{ number_format($venta->precio_unitario, 2) }}</td>
                    <td class="money">${{ number_format($venta->total, 2) }}</td>
                    <td>{{ $venta->user->name ?? 'Sistema' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No hay ventas registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Reporte generado automáticamente por Distribuidora Solmunol.
    </div>
</body>
</html>