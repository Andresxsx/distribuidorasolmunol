<?php

namespace App\Http\Controllers;

use App\Services\DecisionAiService;
use Illuminate\Http\Request;

class DecisionIaController extends Controller
{
    public function preguntar(Request $request, DecisionAiService $decisionAiService)
    {
        if (! auth()->check()) {
            abort(403);
        }

        if (! in_array(auth()->user()->rol, ['Administrador', 'Directivo'], true)) {
            return back()->with(
                'respuesta_ia',
                'Solo el Administrador o el Directivo pueden usar la IA para toma de decisiones.'
            );
        }

        $request->validate([
            'pregunta_ia' => ['required', 'string', 'min:5', 'max:300'],
        ], [
            'pregunta_ia.required' => 'Escriba una pregunta para la IA.',
            'pregunta_ia.min' => 'La pregunta debe tener al menos 5 caracteres.',
            'pregunta_ia.max' => 'La pregunta no debe superar los 300 caracteres.',
        ]);

        $pregunta = trim($request->input('pregunta_ia'));

        $respuesta = $decisionAiService->responder($pregunta);

        return back()
            ->with('pregunta_ia', $pregunta)
            ->with('respuesta_ia', $respuesta);
    }
}