<?php




// Inicializamos variables de texto, tablas léxica y de símbolos, y arreglo de errores.
$texto = "";
$lexical_table = array();
$symbol_ident = array();
$symbol_const = array();
$errors = array();

// Contadores para asignación dinámica
$ident_counter = 401;  // Identificadores
$const_counter = 600;  // Constantes

// Mapeo de palabras reservadas (token tipo 1) con sus códigos predefinidos.
$reserved = array(
    "SELECT" => 10,
    "FROM"   => 11,
    "WHERE"  => 12,
    "AND"    => 14,
    "OR"     => 15,
    "CREATE" => 16,
    "TABLE"  => 17,
    "CHAR"   => 18,
    "NUMERIC"=> 19,
    "NOT"    => 20,
    "NULL"   => 21
);

// Actualizamos el patrón general (agregamos /u para Unicode)
// Grupos:
//   1. Operadores relacionales (incluyendo '*'): >=, <=, <>, =, >, <, *
//   2. Delimitadores: coma, punto y coma, paréntesis
//   3. Constantes de cadena: delimitadas por cualquiera de las comillas: ’, ‘ o '
//   4. Constantes numéricas: enteros o decimales
//   5. Identificadores: palabras que inician con letra o _
//   6. Cualquier otro carácter no blanco (para marcar error)
$pattern = '/(>=|<=|<>|=|>|<|\*)|([,;()])|([‘’\'][^‘’\']+[‘’\'])|(\b\d+(?:\.\d+)?\b)|(\b[a-zA-Z_][a-zA-Z0-9_]*\b)|(\S)/u';



// Patrón para constantes de cadena (para llenar la tabla de símbolos de constantes).
// Se capturará el contenido interno sin las comillas.
$regex_const = "/[‘’']([^‘’']+)[‘’']/u";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["regexInput"])) {
    $texto = $_POST["regexInput"];
    $lines = explode("\n", $texto);
    foreach ($lines as $line_num => $line) {
        if (preg_match_all($pattern, $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $token_value = "";
                $token_type = 0;
                $token_code = 0;

                // 1. Operadores (grupo 1)
                if (!empty($match[1])) {
                    $token_value = $match[1];
                    $token_type = 8;
                    if ($token_value == "=") {
                        $token_code = 83;
                    } elseif ($token_value == ">=") {
                        $token_code = 84;
                    } elseif ($token_value == "<=") {
                        $token_code = 85;
                    } elseif ($token_value == ">") {
                        $token_code = 87;
                    } elseif ($token_value == "<") {
                        $token_code = 88;
                    } elseif ($token_value == "<>") {
                        $token_code = 86;
                    } elseif ($token_value == "*") {
                        $token_code = 9;
                    } else {
                        $token_code = 0;
                        $errors[] = "Operador desconocido '$token_value' en línea " . ($line_num+1);
                    }
                }
                // 2. Delimitadores (grupo 2)
                elseif (!empty($match[2])) {
                    $token_value = $match[2];
                    $token_type = 5;
                    if ($token_value == ",") {
                        $token_code = 50;
                    } elseif ($token_value == ";") {
                        $token_code = 52;
                    } else {
                        $token_code = 0;
                    }
                }
                // 3. Constantes de cadena (grupo 3)
                elseif (!empty($match[3])) {
                    $full_const = $match[3]; // Incluye las comillas
                    $token_value = "CONSTANTE";
                    $token_type = 6;
                    $const_content = mb_substr($full_const, 1, mb_strlen($full_const, 'UTF-8') - 2, 'UTF-8');

                    // Para constantes alfanuméricas se asigna tipo 62.
                    $const_symbol_type = 62;
                    if (!isset($symbol_const[$const_content])) {
                        $symbol_const[$const_content] = array(
                            'constante' => $const_content,
                            'tipo' => $const_symbol_type,
                            'code' => $const_counter,
                            'lineas' => array($line_num+1)
                        );
                        $token_code = $const_counter;
                        $const_counter++;
                    } else {
                        if (!in_array($line_num+1, $symbol_const[$const_content]['lineas'])) {
                            $symbol_const[$const_content]['lineas'][] = $line_num+1;
                        }
                        $token_code = $symbol_const[$const_content]['code'];
                    }
                }
                // 4. Constantes numéricas (grupo 4)
                elseif (!empty($match[4])) {
                    $token_value = "CONSTANTE";
                    $token_type = 6;
                    $num_const = $match[4];
                    // Para constantes numéricas, el tipo de símbolo será 61.
                    if (!isset($symbol_const[$num_const])) {
                        $symbol_const[$num_const] = array(
                            'constante' => $num_const,
                            'tipo' => 61,
                            'code' => $const_counter,
                            'lineas' => array($line_num+1)
                        );
                        $token_code = $const_counter;
                        $const_counter++;
                    } else {
                        if (!in_array($line_num+1, $symbol_const[$num_const]['lineas'])) {
                            $symbol_const[$num_const]['lineas'][] = $line_num+1;
                        }
                        $token_code = $symbol_const[$num_const]['code'];
                    }
                }
                // 5. Identificadores (grupo 5)
                elseif (!empty($match[5])) {
                    $token_value = $match[5];
                    if (isset($reserved[strtoupper($token_value)])) {
                        $token_type = 1;
                        $token_code = $reserved[strtoupper($token_value)];
                    } else {
                        $token_type = 4;
                        if (!isset($symbol_ident[$token_value])) {
                            $symbol_ident[$token_value] = array(
                                'identificador' => $token_value,
                                'code' => $ident_counter,
                                'lineas' => array($line_num+1)
                            );
                            $token_code = $ident_counter;
                            $ident_counter++;
                        } else {
                            if (!in_array($line_num+1, $symbol_ident[$token_value]['lineas'])) {
                                $symbol_ident[$token_value]['lineas'][] = $line_num+1;
                            }
                            $token_code = $symbol_ident[$token_value]['code'];
                        }
                    }
                }
                // 6. Otros (grupo 6): token desconocido
                elseif (!empty($match[6])) {
                    $token_value = $match[6];
                    $token_type = 0;
                    $token_code = 0;
                    $errors[] = "Token desconocido '$token_value' en línea " . ($line_num+1);
                }

                $lexical_table[] = array(
                    'linea' => $line_num+1,
                    'token' => $token_value,
                    'tipo' => $token_type,
                    'codigo' => $token_code
                );
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner DML</title>
    <link rel="stylesheet" href="<?php echo '/styles.css'; ?>">

    <script>
        function resetForm() {
            document.getElementById("regexInput").value = "";
        }
    </script>
</head>
<body>
<div class="container">
    <h1 class="titulo">Escáner DML</h1>
    <form method="post">
        <label for="regexInput">Ingrese una sentencia SQL:</label>
        <div class="textarea-container">
            <textarea id="regexInput" name="regexInput" required><?= htmlspecialchars($texto) ?></textarea>
            <button class="boton" type="submit">Enviar</button>
            <button class="boton" type="button" onclick="resetForm()">Reset</button>
        </div>
    </form>
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <?php if (!empty($errors)): ?>
            <h2>Módulo de Errores</h2>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <h2>Módulo de Resultados</h2>
            <h3>Tabla Léxica</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>No.</th>
                    <th>Línea</th>
                    <th>TOKEN</th>
                    <th>Tipo</th>
                    <th>Código</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php foreach ($lexical_table as $t): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $t['linea'] ?></td>
                        <td><?= htmlspecialchars($t['token']) ?></td>
                        <td><?= $t['tipo'] ?></td>
                        <td><?= $t['codigo'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Tabla de Identificadores</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>Identificador</th>
                    <th>Valor</th>
                    <th>Línea</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($symbol_ident as $id): ?>
                    <tr>
                        <td><?= htmlspecialchars($id['identificador']) ?></td>
                        <td><?= $id['code'] ?></td>
                        <td><?= implode(", ", $id['lineas']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Tabla de Constantes</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>Constante</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Línea</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($symbol_const as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['constante']) ?></td>
                        <td><?= $c['tipo'] ?></td>
                        <td><?= $c['code'] ?></td>
                        <td><?= implode(", ", $c['lineas']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
