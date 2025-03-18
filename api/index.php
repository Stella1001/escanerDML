<?php

$texto = "";
$lexical_table = array();
$symbol_ident = array();
$symbol_const = array();
$errors = array();


$ident_counter = 401;
$const_counter = 600;


$reserved = array(
    "SELECT"   => ["s", 10],
    "FROM"     => ["f", 11],
    "WHERE"    => ["w", 12],
    "IN"       => ["n", 13],
    "AND"      => ["y", 14],
    "OR"       => ["o", 15],
    "CREATE"   => ["c", 16],
    "TABLE"    => ["t", 17],
    "CHAR"     => ["h", 18],
    "NUMERIC"  => ["u", 19],
    "NOT"      => ["e", 20],
    "NULL"     => ["g", 21],
    "CONSTRAINT" => ["b", 22],
    "KEY"      => ["k", 23],
    "PRIMARY"  => ["p", 24],
    "FOREIGN"  => ["j", 25],
    "REFERENCES" => ["l", 26],
    "INSERT"   => ["m", 27],
    "INTO"     => ["q", 28],
    "VALUES"   => ["v", 29],

    "UPDATE"   => ["u", 30],
    "DELETE"   => ["d", 31],
    "SET"      => ["z", 32]
);


$delimiters = array(
    "," => 50,
    "." => 51,
    ")" => 52,
    "(" => 53,
    ";" => 55
);


$operators = array(
    "+" => 70,
    "-" => 71,
    "*" => 72,
    "/" => 73
);


$relational_operators = array(
    ">"  => 81,
    "<"  => 82,
    "="  => 83,
    ">=" => 84,
    "<=" => 85
);


$pattern = '/(\s+)|(?:"([^"]*)"|\'([^\']*)\'|[‘’](.*?)[‘’]|[“”](.*?)[“”])|(>=|<=|=|>|<|\*|\+|-|\/)|([,;()\.])|(\b\d+(?:\.\d+)?\b)|(\b[a-zA-Z_][a-zA-Z0-9_#]*(?:\.[a-zA-Z0-9_#]+)?\b#?)|(\S)/u';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["regexInput"])) {
    $texto = $_POST["regexInput"];
    $lines = explode("\n", $texto);
    foreach ($lines as $line_num => $line) {
        if (preg_match_all($pattern, $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {

                if (!empty($match[1])) continue;

                $token_value = "";
                $token_type = 0;
                $token_code = 0;


                if (!empty($match[2]) || !empty($match[3]) || !empty($match[4]) || !empty($match[5])) {
                    $token_value = "CONSTANTE";
                    $token_type = 6;
                    if (!empty($match[2])) {
                        $const_content = $match[2];
                    } elseif (!empty($match[3])) {
                        $const_content = $match[3];
                    } elseif (!empty($match[4])) {
                        $const_content = $match[4];
                    } else {
                        $const_content = $match[5];
                    }
                    if (!isset($symbol_const[$const_content])) {
                        $symbol_const[$const_content] = [
                            'constante' => $const_content,
                            'tipo' => 62,
                            'code' => $const_counter,
                            'lineas' => [$line_num + 1]
                        ];
                        $token_code = $const_counter++;
                    } else {
                        $token_code = $symbol_const[$const_content]['code'];
                        if (!in_array($line_num + 1, $symbol_const[$const_content]['lineas'])) {
                            $symbol_const[$const_content]['lineas'][] = $line_num + 1;
                        }
                    }
                } elseif (!empty($match[6])) {

                    $token_value = $match[6];
                    $token_type = 8;
                    if (isset($relational_operators[$token_value])) {
                        $token_code = $relational_operators[$token_value];
                    } elseif (isset($operators[$token_value])) {
                        $token_code = $operators[$token_value];
                    } else {
                        $token_code = 0;
                    }
                } elseif (!empty($match[7])) {

                    $token_value = $match[7];
                    $token_type = 5;
                    $token_code = isset($delimiters[$token_value]) ? $delimiters[$token_value] : 0;
                } elseif (!empty($match[8])) {

                    $token_value = "CONSTANTE";
                    $token_type = 6;
                    $num_const = trim($match[8]);
                    if (!isset($symbol_const[$num_const])) {
                        $symbol_const[$num_const] = [
                            'constante' => $num_const,
                            'tipo' => 61,
                            'code' => $const_counter,
                            'lineas' => [$line_num + 1]
                        ];
                        $token_code = $const_counter++;
                    } else {
                        $token_code = $symbol_const[$num_const]['code'];
                        if (!in_array($line_num + 1, $symbol_const[$num_const]['lineas'])) {
                            $symbol_const[$num_const]['lineas'][] = $line_num + 1;
                        }
                    }
                } elseif (!empty($match[9])) {

                    $token_value = $match[9];
                    if (isset($reserved[strtoupper($token_value)])) {
                        $token_type = 1;
                        list($symbol, $token_code) = $reserved[strtoupper($token_value)];
                    } else {
                        $token_type = 4;
                        if (!isset($symbol_ident[$token_value])) {
                            $symbol_ident[$token_value] = [
                                'identificador' => $token_value,
                                'code' => $ident_counter,
                                'lineas' => [$line_num + 1]
                            ];
                            $token_code = $ident_counter++;
                        } else {
                            $token_code = $symbol_ident[$token_value]['code'];
                            if (!in_array($line_num + 1, $symbol_ident[$token_value]['lineas'])) {
                                $symbol_ident[$token_value]['lineas'][] = $line_num + 1;
                            }
                        }
                    }
                } elseif (!empty($match[10])) {

                    $token_value = $match[10];
                    $token_type = 0;
                    $token_code = 0;
                    $errors[] = "error linea " . ($line_num + 1) . ". Simbolo desconocido: " . $token_value;
                }

                $lexical_table[] = [
                    'linea' => $line_num + 1,
                    'token' => $token_value,
                    'tipo' => $token_type,
                    'codigo' => $token_code
                ];
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
    <link rel="stylesheet" href="/css/style.css"/>
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
        <h2>Resultado del Escáner</h2>
        <?php if (!empty($errors)): ?>
            <?php
            foreach ($errors as $err) {
                echo "<p>" . htmlspecialchars($err) . "</p>";
            }
            ?>
        <?php else: ?>
            <p>sin errores</p>
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
                        <td><?= implode(", ", array_unique($id['lineas'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Tabla de Constantes</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>Constante</th>
                    <th>Valor</th>
                    <th>Línea</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($symbol_const as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['constante']) ?></td>
                        <td><?= $c['code'] ?></td>
                        <td><?= implode(", ", array_unique($c['lineas'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
