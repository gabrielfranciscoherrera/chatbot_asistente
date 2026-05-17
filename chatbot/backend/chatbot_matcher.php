<?php
declare(strict_types=1);

function normalizarTexto(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = transliterator_transliterate('Any-Latin; Latin-ASCII', $texto);
    $texto = preg_replace('/[^a-z0-9 ]/', ' ', $texto);
    return trim(preg_replace('/\s+/', ' ', $texto));
}

/**
 * ¿Un token de la clave coincide con algún token del texto (con tolerancia a typos)?
 */
function tokenCoincide(string $tokenClave, array $palabrasTexto): bool
{
    $lenClave = strlen($tokenClave);
    if ($lenClave < 2) return false;

    foreach ($palabrasTexto as $tokenTexto) {
        // Exacto
        if ($tokenTexto === $tokenClave) return true;

        $lenTexto = strlen($tokenTexto);

        // Parcial (mínimo 5 chars para evitar "tal" en "hospital")
        if ($lenClave >= 5 && $lenTexto >= 5) {
            if (str_contains($tokenTexto, $tokenClave) || str_contains($tokenClave, $tokenTexto)) {
                return true;
            }
        }

        // Levenshtein: umbral 1 para ≤5 chars, 2 para ≥6 chars
        $umbral = $lenClave <= 5 ? 1 : 2;
        if (abs($lenTexto - $lenClave) > $umbral) continue;

        if (levenshtein($tokenTexto, $tokenClave) <= $umbral) return true;

        // Transposición de 2 letras (hoas↔hola): misma longitud, mismo multiconjunto de chars
        if ($lenTexto === $lenClave && $lenClave >= 4) {
            $diffs = 0;
            for ($i = 0; $i < $lenClave; $i++) {
                if ($tokenTexto[$i] !== $tokenClave[$i]) $diffs++;
                if ($diffs > 2) break;
            }
            if ($diffs === 2) {
                $s1 = str_split($tokenTexto); sort($s1);
                $s2 = str_split($tokenClave);  sort($s2);
                if ($s1 === $s2) return true;
            }
        }
    }

    return false;
}

/**
 * ¿La frase-clave coincide con el texto del usuario?
 *
 * Para claves de 1 token: basta que ese token coincida en el texto.
 * Para claves de 2+ tokens: TODOS los tokens deben coincidir en el texto
 * (evita que "que" de "que es" active "que tal").
 */
function palabraCoincide(string $clave, string $texto): bool
{
    // 1. Coincidencia exacta de frase completa (word boundary)
    if (preg_match('/\b' . preg_quote($clave, '/') . '\b/', $texto)) {
        return true;
    }

    $palabrasTexto = preg_split('/\s+/', $texto, -1, PREG_SPLIT_NO_EMPTY);
    $palabrasClave = preg_split('/\s+/', $clave, -1, PREG_SPLIT_NO_EMPTY);

    // 2. Para claves multi-token: TODOS deben coincidir (lógica AND)
    if (count($palabrasClave) > 1) {
        foreach ($palabrasClave as $tc) {
            if (!tokenCoincide($tc, $palabrasTexto)) return false;
        }
        return true;
    }

    // 3. Para claves de 1 token: basta una coincidencia
    return tokenCoincide($palabrasClave[0], $palabrasTexto);
}

function buscarRespuesta(PDO $pdo, string $mensajeUsuario): ?array
{
    $texto = normalizarTexto($mensajeUsuario);

    $stmt = $pdo->query(
        "SELECT * FROM chatbot_entradas WHERE activo = 1 ORDER BY prioridad ASC, id ASC"
    );
    $entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($entradas as $entrada) {
        $claves = array_map('normalizarTexto', explode(',', $entrada['palabras_clave']));
        foreach ($claves as $clave) {
            if ($clave === '') continue;
            if (palabraCoincide($clave, $texto)) {
                return $entrada;
            }
        }
    }

    return null;
}
