<x-filament-panels::page>
    <style>
        .dashboard-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            padding: 26px;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18);
        }

        .dashboard-title {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .dashboard-subtitle {
            margin: 4px 0;
            color: #cbd5e1;
            font-size: 14px;
        }

        .cards-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .cards-grid-5 {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .dashboard-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
        }

        .card-label {
            font-size: 14px;
            color: #64748b;
            margin: 0;
            font-weight: 600;
        }

        .card-value {
            font-size: 30px;
            font-weight: 800;
            margin: 8px 0;
        }

        .card-help {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
        }

        .text-red {
            color: #dc2626;
        }

        .text-green {
            color: #16a34a;
        }

        .text-orange {
            color: #ea580c;
        }

        .text-blue {
            color: #2563eb;
        }

        .text-indigo {
            color: #4f46e5;
        }

        .text-purple {
            color: #9333ea;
        }

        .text-slate {
            color: #0f172a;
        }

        .section-title {
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 16px 0;
            color: #111827;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .dashboard-table th {
            background: #f8fafc;
            color: #334155;
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
        }

        .dashboard-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #334155;
        }

        .dashboard-table tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-red {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-orange {
            background: #ffedd5;
            color: #c2410c;
        }

        .badge-green {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .empty-text {
            color: #64748b;
            font-size: 14px;
            padding: 12px 0;
        }

        .chart-box {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .bar-row {
            display: grid;
            grid-template-columns: 130px 1fr 90px;
            gap: 12px;
            align-items: center;
            font-size: 14px;
        }

        .bar-label {
            color: #334155;
            font-weight: 700;
        }

        .bar-track {
            height: 14px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 999px;
        }

        .bar-red {
            background: #dc2626;
        }

        .bar-green {
            background: #16a34a;
        }

        .bar-orange {
            background: #ea580c;
        }

        .bar-blue {
            background: #2563eb;
        }

        .bar-purple {
            background: #9333ea;
        }

        .bar-value {
            text-align: right;
            color: #475569;
            font-weight: 700;
        }

        @media (max-width: 1200px) {
            .cards-grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cards-grid-5 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tables-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 800px) {
            .cards-grid-4,
            .cards-grid-5 {
                grid-template-columns: 1fr;
            }

            .bar-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .bar-value {
                text-align: left;
            }
        }
    </style>

    @php
        $mayorFinanciero = max($totalCompras, $totalVentas, 1);
        $porcentajeCompras = ($totalCompras / $mayorFinanciero) * 100;
        $porcentajeVentas = ($totalVentas / $mayorFinanciero) * 100;

        $mayorMovimiento = max($entradasBodega, $salidasBodega, $ajustesBodega, 1);
        $porcentajeEntradas = ($entradasBodega / $mayorMovimiento) * 100;
        $porcentajeSalidas = ($salidasBodega / $mayorMovimiento) * 100;
        $porcentajeAjustes = ($ajustesBodega / $mayorMovimiento) * 100;

        $mayorVendido = max($productosMasVendidos->max('total_vendido') ?? 0, 1);
    @endphp

    <div class="dashboard-container">

        <div class="dashboard-header">
            <h2 class="dashboard-title">Panel Directivo</h2>
            <p class="dashboard-subtitle">
                Usuario: {{ $usuario->name ?? 'Administrador' }}
            </p>
            <p class="dashboard-subtitle">
                Fecha y hora Ecuador: {{ $fechaHora }}
            </p>
            <p class="dashboard-subtitle">
                Módulo de consulta gerencial, indicadores y toma de decisiones.
            </p>
        </div>

        <div class="cards-grid-4">
            <div class="dashboard-card">
                <p class="card-label">Total de compras</p>
                <p class="card-value text-red">${{ number_format($totalCompras, 2) }}</p>
                <p class="card-help">Egresos registrados por compras</p>
            </div>

            <div class="dashboard-card">
                <p class="card-label">Total de ventas</p>
                <p class="card-value text-green">${{ number_format($totalVentas, 2) }}</p>
                <p class="card-help">Ingresos registrados por ventas</p>
            </div>

            <div class="dashboard-card">
                <p class="card-label">Resultado estimado</p>
                <p class="card-value {{ $gananciaEstimada >= 0 ? 'text-green' : 'text-red' }}">
                    ${{ number_format($gananciaEstimada, 2) }}
                </p>
                <p class="card-help">Ventas menos compras registradas</p>
            </div>

            <div class="dashboard-card">
                <p class="card-label">Valor de inventario</p>
                <p class="card-value text-blue">${{ number_format($valorInventario, 2) }}</p>
                <p class="card-help">Stock actual por precio de compra</p>
            </div>
        </div>

        <div class="cards-grid-5">
            <div class="dashboard-card">
                <p class="card-label">Bajo stock</p>
                <p class="card-value text-orange">{{ $productosBajoStock }}</p>
                <p class="card-help">Productos cerca del mínimo</p>
            </div>

            <div class="dashboard-card">
                <p class="card-label">Sin stock</p>
                <p class="card-value text-red">{{ $productosSinStock }}</p>
                <p class="card-help">Productos agotados</p>
            </div>

            <div class="dashboard-card">
                <p class="card-label">Empleados activos</p>
                <p class="card-value text-blue">{{ $empleadosActivos }}</p>
                <p class="card-help">Talento humano disponible</p>
            </div>

            <div class="dashboard-card">
                <p class="card-label">Clientes activos</p>
                <p class="card-value text-indigo">{{ $clientesActivos }}</p>
                <p class="card-help">Clientes habilitados</p>
            </div>

            <div class="dashboard-card">
                <p class="card-label">Proveedores activos</p>
                <p class="card-value text-purple">{{ $proveedoresActivos }}</p>
                <p class="card-help">Proveedores habilitados</p>
            </div>
        </div>

        <div class="tables-grid">
            <div class="dashboard-card">
                <h3 class="section-title">Gráfico: Compras vs Ventas</h3>

                <div class="chart-box">
                    <div class="bar-row">
                        <div class="bar-label">Compras</div>
                        <div class="bar-track">
                            <div class="bar-fill bar-red" style="width: {{ $porcentajeCompras }}%;"></div>
                        </div>
                        <div class="bar-value">${{ number_format($totalCompras, 2) }}</div>
                    </div>

                    <div class="bar-row">
                        <div class="bar-label">Ventas</div>
                        <div class="bar-track">
                            <div class="bar-fill bar-green" style="width: {{ $porcentajeVentas }}%;"></div>
                        </div>
                        <div class="bar-value">${{ number_format($totalVentas, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3 class="section-title">Gráfico: Movimientos de bodega</h3>

                <div class="chart-box">
                    <div class="bar-row">
                        <div class="bar-label">Entradas</div>
                        <div class="bar-track">
                            <div class="bar-fill bar-green" style="width: {{ $porcentajeEntradas }}%;"></div>
                        </div>
                        <div class="bar-value">{{ $entradasBodega }}</div>
                    </div>

                    <div class="bar-row">
                        <div class="bar-label">Salidas</div>
                        <div class="bar-track">
                            <div class="bar-fill bar-red" style="width: {{ $porcentajeSalidas }}%;"></div>
                        </div>
                        <div class="bar-value">{{ $salidasBodega }}</div>
                    </div>

                    <div class="bar-row">
                        <div class="bar-label">Ajustes</div>
                        <div class="bar-track">
                            <div class="bar-fill bar-orange" style="width: {{ $porcentajeAjustes }}%;"></div>
                        </div>
                        <div class="bar-value">{{ $ajustesBodega }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tables-grid">
            <div class="dashboard-card">
                <h3 class="section-title">Últimas compras</h3>

                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>N° Compra</th>
                            <th>Producto</th>
                            <th>Proveedor</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ultimasCompras as $compra)
                            <tr>
                                <td><strong>{{ $compra->numero_compra }}</strong></td>
                                <td>{{ $compra->producto->nombre ?? 'Producto eliminado' }}</td>
                                <td>{{ $compra->proveedor->nombre ?? 'Proveedor eliminado' }}</td>
                                <td class="text-red">
                                    <strong>${{ number_format($compra->total, 2) }}</strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-text">
                                    No hay compras registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="dashboard-card">
                <h3 class="section-title">Últimas ventas</h3>

                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>N° Venta</th>
                            <th>Producto</th>
                            <th>Cliente</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ultimasVentas as $venta)
                            <tr>
                                <td><strong>{{ $venta->numero_venta }}</strong></td>
                                <td>{{ $venta->producto->nombre ?? 'Producto eliminado' }}</td>
                                <td>{{ $venta->cliente->nombre ?? 'Cliente eliminado' }}</td>
                                <td class="text-green">
                                    <strong>${{ number_format($venta->total, 2) }}</strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-text">
                                    No hay ventas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tables-grid">
            <div class="dashboard-card">
                <h3 class="section-title">Productos más vendidos</h3>

                @forelse ($productosMasVendidos as $item)
                    @php
                        $porcentajeProducto = (($item->total_vendido ?? 0) / $mayorVendido) * 100;
                    @endphp

                    <div class="bar-row" style="margin-bottom: 12px;">
                        <div class="bar-label">
                            {{ $item->producto->nombre ?? 'Producto eliminado' }}
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-blue" style="width: {{ $porcentajeProducto }}%;"></div>
                        </div>
                        <div class="bar-value">
                            {{ $item->total_vendido }} und.
                        </div>
                    </div>
                @empty
                    <p class="empty-text">
                        No hay ventas suficientes para generar productos más vendidos.
                    </p>
                @endforelse
            </div>

            <div class="dashboard-card">
                <h3 class="section-title">Productos críticos</h3>

                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Stock</th>
                            <th>Mínimo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($productosCriticos as $producto)
                            <tr>
                                <td><strong>{{ $producto->nombre }}</strong></td>
                                <td>{{ $producto->stock_actual }}</td>
                                <td>{{ $producto->stock_minimo }}</td>
                                <td>
                                    @if ($producto->stock_actual <= 0)
                                        <span class="badge badge-red">Sin stock</span>
                                    @else
                                        <span class="badge badge-orange">Bajo stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-text">
                                    No hay productos críticos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>