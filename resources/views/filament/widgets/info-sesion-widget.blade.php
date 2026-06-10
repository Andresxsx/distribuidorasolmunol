<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .session-card {
                background: linear-gradient(135deg, #0f172a, #1e293b);
                color: white;
                padding: 22px;
                border-radius: 18px;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18);
            }

            .session-title {
                font-size: 22px;
                font-weight: 800;
                margin: 0 0 6px 0;
            }

            .session-subtitle {
                color: #cbd5e1;
                margin: 0 0 18px 0;
                font-size: 14px;
            }

            .session-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
            }

            .session-item {
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.12);
                padding: 14px;
                border-radius: 14px;
            }

            .session-label {
                color: #cbd5e1;
                font-size: 12px;
                margin: 0;
            }

            .session-value {
                font-size: 17px;
                font-weight: 800;
                margin: 5px 0 0 0;
            }

            @media (max-width: 900px) {
                .session-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 600px) {
                .session-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="session-card">
            <h2 class="session-title">Bienvenido al Sistema Distribuidora Solmunol</h2>
            <p class="session-subtitle">
                Información de acceso al sistema empresarial.
            </p>

            <div class="session-grid">
                <div class="session-item">
                    <p class="session-label">Usuario conectado</p>
                    <p class="session-value">{{ $usuario->name ?? 'Usuario' }}</p>
                </div>

                <div class="session-item">
                    <p class="session-label">Rol del usuario</p>
                    <p class="session-value">{{ $usuario->rol ?? 'Sin rol' }}</p>
                </div>

                <div class="session-item">
                    <p class="session-label">Fecha Ecuador</p>
                    <p class="session-value">{{ $fecha }}</p>
                </div>

                <div class="session-item">
                    <p class="session-label">Hora Ecuador</p>
                    <p class="session-value">{{ $hora }}</p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>