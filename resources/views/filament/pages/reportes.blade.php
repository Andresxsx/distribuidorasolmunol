<x-filament-panels::page>
    <style>
        .reportes-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .reportes-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            padding: 26px;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18);
        }

        .reportes-title {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .reportes-subtitle {
            color: #cbd5e1;
            margin: 4px 0;
            font-size: 14px;
        }

        .reportes-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .reporte-card {
            background: white;
            border-radius: 18px;
            padding: 22px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .reporte-card h3 {
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 8px 0;
            color: #0f172a;
        }

        .reporte-card p {
            color: #64748b;
            margin: 0 0 18px 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .reportes-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-reporte {
            text-decoration: none;
            padding: 11px 16px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 14px;
            display: inline-block;
        }

        .btn-excel {
            background: #16a34a;
            color: white;
        }

        .btn-pdf {
            background: #dc2626;
            color: white;
        }

        .btn-excel:hover {
            background: #15803d;
        }

        .btn-pdf:hover {
            background: #b91c1c;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 20px;
            color: #334155;
        }

        .info-box strong {
            color: #0f172a;
        }

        @media (max-width: 900px) {
            .reportes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="reportes-container">

        <div class="reportes-header">
            <h2 class="reportes-title">Centro de Reportes</h2>
            <p class="reportes-subtitle">
                Usuario: {{ $usuario->name ?? 'Administrador' }}
            </p>
            <p class="reportes-subtitle">
                Fecha y hora Ecuador: {{ $fechaHora }}
            </p>
            <p class="reportes-subtitle">
                Descarga reportes administrativos en Excel y PDF.
            </p>
        </div>

        <div class="reportes-grid">
            <div class="reporte-card">
                <h3>Reporte de Compras</h3>
                <p>
                    Permite consultar las compras registradas, proveedor, producto,
                    cantidad, precio unitario, total y usuario que realizó el registro.
                </p>

                <div class="reportes-actions">
                    <a class="btn-reporte btn-excel" href="{{ route('reportes.compras.excel') }}" target="_blank">
                        Descargar Excel
                    </a>

                    <a class="btn-reporte btn-pdf" href="{{ route('reportes.compras.pdf') }}" target="_blank">
                        Descargar PDF
                    </a>
                </div>
            </div>

            <div class="reporte-card">
                <h3>Reporte de Ventas</h3>
                <p>
                    Permite consultar las ventas registradas, cliente, producto,
                    cantidad, precio unitario, total y usuario que realizó el registro.
                </p>

                <div class="reportes-actions">
                    <a class="btn-reporte btn-excel" href="{{ route('reportes.ventas.excel') }}" target="_blank">
                        Descargar Excel
                    </a>

                    <a class="btn-reporte btn-pdf" href="{{ route('reportes.ventas.pdf') }}" target="_blank">
                        Descargar PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="info-box">
            <strong>Uso del módulo:</strong>
            esta página es de consulta y generación de reportes. No permite crear,
            modificar ni eliminar registros. Está pensada para la parte directiva y administrativa
            del ERP.
        </div>

    </div>
</x-filament-panels::page>