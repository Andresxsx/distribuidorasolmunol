<?php

namespace App\Http\Controllers;

use App\Services\DecisionAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AsistenteIaController extends Controller
{
    public function preguntar(Request $request, DecisionAiService $decisionAiService)
    {
        try {
            if (! auth()->check()) {
                return response()->json([
                    'ok' => false,
                    'respuesta' => 'No tiene sesión activa. Inicie sesión nuevamente.',
                ], 403);
            }

            $usuario = auth()->user();
            $rolUsuario = strtolower(trim((string) $usuario->rol));

            if (! in_array($rolUsuario, ['administrador', 'directivo'], true)) {
                return response()->json([
                    'ok' => false,
                    'respuesta' => 'Solo el Administrador o el Directivo pueden usar el asistente IA para toma de decisiones.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'pregunta_ia' => ['required', 'string', 'min:5', 'max:1200'],
            ], [
                'pregunta_ia.required' => 'Escriba una pregunta para la IA.',
                'pregunta_ia.string' => 'La pregunta debe ser texto.',
                'pregunta_ia.min' => 'La pregunta debe tener al menos 5 caracteres.',
                'pregunta_ia.max' => 'La pregunta no debe superar los 1200 caracteres.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'ok' => false,
                    'respuesta' => $validator->errors()->first('pregunta_ia'),
                ], 422);
            }

            $pregunta = trim((string) $request->input('pregunta_ia'));
            $respuesta = $decisionAiService->responder($pregunta);

            return response()->json([
                'ok' => true,
                'pregunta' => $pregunta,
                'respuesta' => $respuesta,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'respuesta' => 'Error interno del asistente IA. Revise los logs del sistema si el problema continúa.',
            ], 500);
        }
    }
}
