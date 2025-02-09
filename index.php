<?php
// Conexión a la base de datos
function connectDb() {
    $host = "localhost";
    $dbname = "bruc_sustratos_aridos"; // Nombre de tu base de datos
    $user = "root";                   // Usuario por defecto en XAMPP
    $password = "";                   // Contraseña por defecto en XAMPP

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        return $pdo;
    } catch (PDOException $e) {
        echo "Error al conectar a la base de datos: " . $e->getMessage();
        return null;
    }
}

// Obtener los productos
function getProducts($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, nombre FROM articulos;");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al obtener los artículos: " . $e->getMessage();
        return [];
    }
}

// Obtener detalles de presentaciones para un producto
function getProductPresentations($pdo, $productId) {
    try {
        $stmt = $pdo->prepare("
            SELECT pp.saco_litros, pp.peso_saco_litros, pp.volumen_saca_big, 
                   pp.peso_saca_big, pp.saco_kg, pp.rendimiento_kg_m3, 
                   pp.peso_mediasaca_big, a.nombre
            FROM producto_presentaciones pp
            INNER JOIN articulos a ON pp.producto_id = a.id
            WHERE pp.producto_id = :producto_id;
        ");
        $stmt->execute(['producto_id' => $productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al obtener las presentaciones del producto: " . $e->getMessage();
        return null;
    }
}

// Calcular los requisitos
function calculateRequirements($presentations, $areaM2, $grosorCm) {
    $grosorM = $grosorCm / 100; // Convertir cm a metros
    $volumeM3 = $areaM2 * $grosorM; // Volumen total en m³

    // Inicializar resultados
    $sacosLitros = 0;
    $sacosKg = 0;
    $sacasBig = 0;
    $mediasSacas = 0;
    $kgSinCubrir = 0;

    $results = [
        'sacos_litros' => 0,
        'sacos_kg' => 0,
        'sacas_big' => 0,
        'medias_sacas' => 0,
        'kg_sin_cubrir' => 0 // Se inicializa en 0
    ];


    // Calcular sacos en litros si existen
    if (!empty($presentations['saco_litros']) && !empty($presentations['peso_saco_litros'])) {
        $sacosLitros = ceil(($volumeM3 * 1000) / $presentations['saco_litros']);
    }

    //calcular el peso total en funcion del rendimiento pot m3 si está definido
    $pesoTotalKg = !empty($presentations['rendimiento_kg_m3']) ? ($volumeM3 * $presentations['rendimiento_kg_m3']) : 0;

    // Calcular sacas grandes si existen
    if (!empty($presentations['peso_saca_big'])) {
        $sacasBig = floor($pesoTotalKg / $presentations['peso_saca_big']); //se usa floor para enteros
        $pesoRestante = $pesoTotalKg - ($sacasBig * $presentations['peso_saca_big']); //resto de peso no cubierto por sacas grandes
    } else {
        $pesoRestante = $pesoTotalKg;
    }
    //si hay peso restante y existe la opcion de media saca, calcular cuántas se necesitan
    if (!empty($presentations['peso_mediasaca_big']) && $pesoRestante > 0) {
        $mediasSacas = floor($pesoRestante / $presentations['peso_mediasaca_big']);
        $pesoRestante -= $mediasSacas * $presentations['peso_mediasaca_big'];
    }

    // Calcular sacos en kg si existen
    if (!empty($presentations['saco_kg']) && $presentations['saco_kg'] >= 20) {
        $sacosKg = ceil($pesoTotalKg / $presentations['saco_kg']);
    }

    // Si sigue quedando peso sin cubrir, guardarlo
    if ($pesoRestante > 0) {
    $kgSinCubrir = $pesoRestante;
    }


    // Construir resultados dinámicamente
    $result = [
        'Artículo' => $presentations['nombre'],
        'volumen_m3' => $volumeM3,
        'peso_total_kg' => $pesoTotalKg
    ];

    // Añadir solo los resultados que aplican
    if ($sacosLitros > 0) {
        $result['sacos_litros'] = $sacosLitros;
    }
    if ($sacosKg > 0) {
        $result['sacos_kg'] = $sacosKg;
    }

    if ($sacasBig > 0) {
        $result['sacas_big'] = $sacasBig;
    }

    if ($mediasSacas >0) { //solo si hay medias sacas
        $result['medias_sacas'] = $mediasSacas;
    }

    if ($kgSinCubrir > 0) {
        $result['kg_sin_cubrir'] = $kgSinCubrir;
    }

    return $result;
}

// Programa principal
function main() {
    $pdo = connectDb();
    if (!$pdo) return;

    // Obtener los productos
    $products = getProducts($pdo);
    if (empty($products)) {
        echo "No hay productos disponibles.\n";
        return;
    }
    // Capturar el producto seleccionado (si se envió el formulario)
    $productIdSeleccionado = $_POST['product'] ?? null;

    // Mostrar formulario
    echo '<form method="POST">';
    echo '<label for="product">Selecciona un producto:</label>';
    echo '<select name="product" id="product">';
    foreach ($products as $product) {
        $selected = ($productIdSeleccionado == $product['id']) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($product['id']) . '" ' . $selected . '>' . htmlspecialchars($product['nombre']) . '</option>';
    }
    echo '</select><br>';

    echo '<label for="areaM2">Área en m²:</label>';
    echo '<input type="number" step="0.01" name="areaM2" id="areaM2" required value="' . ($_POST['areaM2'] ?? '') . '"><br>';

    echo '<label for="grosorCm">Espesor en cm:</label>';
    echo '<input type="number" step="0.01" name="grosorCm" id="grosorCm" required value="' . ($_POST['grosorCm'] ?? '') . '"><br>';

    echo '<button type="submit">Calcular</button>';
    echo '</form>';

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $areaM2 = (float)$_POST['areaM2'];
        $grosorCm = (float)$_POST['grosorCm'];

        $presentations = getProductPresentations($pdo, $productIdSeleccionado);
        if (!$presentations) {
            echo "Error al obtener las presentaciones del producto.\n";
            return;
        }

        $results = calculateRequirements($presentations, $areaM2, $grosorCm);

        // Mostrar resultados
        echo "<h2>Resultados para: " . htmlspecialchars($results['Artículo']) . "</h2>";
        echo "<p>✅ Volumen total requerido: " . number_format($results['volumen_m3'], 2) . " m³</p>";
        echo "<p>✅ Peso total requerido: " . number_format($results['peso_total_kg'], 2) . " kg</p>";

        if (isset($results['sacos_litros'])) {
            echo "<p>✅ Número de sacos de " . $presentations['saco_litros'] . " litros " . $results['sacos_litros'] . "</p>";
        }
        if (isset($results['sacas_big'])) {
            echo "<p>✅ Número de Bigs grandes (peso " . $presentations['peso_saca_big'] . " kg): " . $results['sacas_big'] . "</p>";
        }
        if (isset($results['medias_sacas'])) {
            echo "<p>✅ Número de medios Bigs (peso " . $presentations['peso_mediasaca_big'] . " kg): " . $results['medias_sacas'] . "</p>";
        }
        if (isset($results['sacos_kg'])) {
            echo "<p>✅ Número de sacos de " . $presentations['saco_kg'] . " kg: " . $results['sacos_kg'] . "</p>";
        }
        if(!isset($results['kg_sin_cubrir']) || $results['kg_sin_cubrir'] <= 0) {
            echo "<p>✅ Todo el peso está cubierto por los envases.</p>";
        } else {
            $pesoReferencia = !empty($presentations['peso_mediasaca_big']) ? $presentations['peso_mediasaca_big'] : 
                              (!empty($presentations['peso_saca_big']) ? $presentations['peso_saca_big'] : "N/A");
            
            if ($pesoReferencia === "N/A") {
                echo "<p>⚠️ Kg sin cubrir por BIGS: " . $results['kg_sin_cubrir'] . " kg (No existen sacas grandes ni medias sacas para este producto)</p>";
            } else {
                echo "<p>⚠️ Kg sin cubrir por sacas grandes o medias sacas (peso " . $pesoReferencia . " kg): " . $results['kg_sin_cubrir'] . " kg</p>";
            }
        } 
       
    }
}

// Llamar a la función principal
main();
?>