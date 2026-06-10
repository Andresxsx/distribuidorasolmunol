@if (auth()->check() && in_array(auth()->user()->rol, ['Administrador', 'Directivo'], true))
    <style>
        .erp-ai-launcher {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 9999;
            width: 58px;
            height: 58px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #0f172a, #2563eb);
            color: white;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.35);
        }

        .erp-ai-panel {
            position: fixed;
            right: 22px;
            bottom: 92px;
            z-index: 9999;
            width: 390px;
            max-width: calc(100vw - 34px);
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.28);
            overflow: hidden;
            display: none;
        }

        .erp-ai-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .erp-ai-title {
            font-weight: 900;
            margin: 0;
            font-size: 15px;
        }

        .erp-ai-subtitle {
            margin: 2px 0 0 0;
            color: #cbd5e1;
            font-size: 12px;
        }

        .erp-ai-close {
            background: rgba(255, 255, 255, 0.12);
            border: none;
            color: white;
            border-radius: 10px;
            padding: 6px 9px;
            cursor: pointer;
            font-weight: 900;
        }

        .erp-ai-body {
            padding: 14px;
        }

        .erp-ai-messages {
            height: 280px;
            overflow-y: auto;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px;
            font-size: 13px;
            color: #334155;
            margin-bottom: 12px;
        }

        .erp-ai-message {
            margin-bottom: 12px;
            line-height: 1.5;
            white-space: pre-line;
            word-break: break-word;
        }

        .erp-ai-user {
            background: #dbeafe;
            color: #1e3a8a;
            padding: 10px;
            border-radius: 12px;
        }

        .erp-ai-bot {
            background: white;
            color: #334155;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .erp-ai-textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 10px;
            font-size: 13px;
            color: #0f172a;
            resize: vertical;
            min-height: 70px;
            background: white;
        }

        .erp-ai-actions {
            margin-top: 10px;
            display: flex;
            gap: 8px;
        }

        .erp-ai-send {
            background: #0f172a;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 800;
            cursor: pointer;
            flex: 1;
        }

        .erp-ai-send:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .erp-ai-clear {
            background: #e2e8f0;
            color: #0f172a;
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .erp-ai-help {
            margin: 8px 0 0 0;
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
        }

        @media (max-width: 600px) {
            .erp-ai-panel {
                right: 12px;
                bottom: 82px;
                width: calc(100vw - 24px);
            }

            .erp-ai-launcher {
                right: 14px;
                bottom: 14px;
            }
        }
    </style>

    <button type="button" class="erp-ai-launcher" id="erpAiLauncher">
        IA
    </button>

    <div class="erp-ai-panel" id="erpAiPanel" data-url="{{ route('ia.asistente') }}">
        <div class="erp-ai-header">
            <div>
                <p class="erp-ai-title">Asistente IA Distribuidora Solmunol</p>
                <p class="erp-ai-subtitle">Predicciones, stock, ventas y decisiones</p>
            </div>

            <button type="button" class="erp-ai-close" id="erpAiClose">
                X
            </button>
        </div>

        <div class="erp-ai-body">
            <div class="erp-ai-messages" id="erpAiMessages">
                <div class="erp-ai-message erp-ai-bot">
                   Hola. Soy el asistente IA de Distribuidora Solmunol. Puedo analizar compras, ventas, stock, historial, productos, bodega y reportes.
                </div>
            </div>

            <textarea
                class="erp-ai-textarea"
                id="erpAiQuestion"
                maxlength="350"
                placeholder="Ejemplo: ¿Qué decisión debo tomar hoy para mejorar la empresa?"
            ></textarea>

            <input type="hidden" id="erpAiToken" value="{{ csrf_token() }}">

            <div class="erp-ai-actions">
                <button type="button" class="erp-ai-send" id="erpAiSend">
                    Preguntar
                </button>

                <button type="button" class="erp-ai-clear" id="erpAiClear">
                    Limpiar
                </button>
            </div>

            <p class="erp-ai-help">
                Solo responde sobre la lógica del ERP. No responde preguntas externas al sistema.
                También puedes presionar Ctrl + Enter para enviar.
            </p>
        </div>
    </div>

    <script>
        (function () {
            if (window.erpAiAssistantLoaded) {
                return;
            }

            window.erpAiAssistantLoaded = true;

            function ready(fn) {
                if (document.readyState !== 'loading') {
                    fn();
                } else {
                    document.addEventListener('DOMContentLoaded', fn);
                }
            }

            ready(function () {
                const launcher = document.getElementById('erpAiLauncher');
                const panel = document.getElementById('erpAiPanel');
                const close = document.getElementById('erpAiClose');
                const send = document.getElementById('erpAiSend');
                const clear = document.getElementById('erpAiClear');
                const question = document.getElementById('erpAiQuestion');
                const messages = document.getElementById('erpAiMessages');
                const token = document.getElementById('erpAiToken');

                if (!launcher || !panel || !close || !send || !clear || !question || !messages || !token) {
                    return;
                }

                function addMessage(text, type) {
                    const div = document.createElement('div');
                    div.className = 'erp-ai-message ' + (type === 'user' ? 'erp-ai-user' : 'erp-ai-bot');
                    div.textContent = text;
                    messages.appendChild(div);
                    messages.scrollTop = messages.scrollHeight;
                }

                launcher.addEventListener('click', function () {
                    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
                });

                close.addEventListener('click', function () {
                    panel.style.display = 'none';
                });

                clear.addEventListener('click', function () {
                    messages.innerHTML = '';
                    addMessage('Conversación limpiada. Puedes hacer otra pregunta sobre el ERP.', 'bot');
                });

                send.addEventListener('click', async function () {
                    const pregunta = question.value.trim();

                    if (pregunta.length < 5) {
                        addMessage('Escribe una pregunta de al menos 5 caracteres.', 'bot');
                        return;
                    }

                    addMessage(pregunta, 'user');
                    question.value = '';

                    send.disabled = true;
                    send.textContent = 'Analizando...';

                    try {
                        const response = await fetch(panel.dataset.url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token.value,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                pregunta_ia: pregunta,
                            }),
                        });

                        const rawText = await response.text();
                        let data = null;

                        try {
                            data = JSON.parse(rawText);
                        } catch (error) {
                            let textoError = rawText
                                .replace(/<style[\s\S]*?<\/style>/gi, '')
                                .replace(/<script[\s\S]*?<\/script>/gi, '')
                                .replace(/<[^>]*>/g, ' ')
                                .replace(/\s+/g, ' ')
                                .trim()
                                .substring(0, 900);

                            addMessage(
                                'Error real del servidor:\n' + textoError,
                                'bot'
                            );

                            return;
                        }

                        if (!response.ok) {
                            addMessage(data.respuesta || data.message || 'No se pudo procesar la pregunta.', 'bot');
                            return;
                        }

                        addMessage(data.respuesta || 'La IA no generó una respuesta.', 'bot');
                    } catch (error) {
                        addMessage(
                            'No se pudo conectar con el asistente IA. Verifica que el servidor Laravel esté encendido.',
                            'bot'
                        );
                    } finally {
                        send.disabled = false;
                        send.textContent = 'Preguntar';
                    }
                });

                question.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' && event.ctrlKey) {
                        send.click();
                    }
                });
            });
        })();
    </script>
@endif