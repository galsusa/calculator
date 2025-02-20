<?php
/**
 * Plugin Name: Calculadora de Áridos
 * Plugin URI: https://jardineriadelvalles.com/calculo-volumetrico/
 * Description: Calculadora de áridos basada en área y grosor.
 * Version: 1.0
 * Author: Susana Galvez
 * Author URI: https://tusitio.com
 */

if (!defined('ABSPATH')) {
    exit; // Seguridad
}

// Función para obtener productos
function ca_get_products() {
    global $wpdb;
    $results = $wpdb->get_results("SELECT id, nombre FROM articulos", ARRAY_A);
    return $results ?: [];
}

// Función para obtener presentaciones de un producto
function ca_get_product_presentations($productIdSeleccionado) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT pp.saco_litros, pp.peso_saco_litros, pp.volumen_saca_big, 
               pp.peso_saca_big, pp.saco_kg, pp.rendimiento_kg_m3, 
               pp.peso_mediasaca_big, a.nombre
        FROM producto_presentaciones pp
        INNER JOIN articulos a ON pp.producto_id = a.id
        WHERE pp.producto_id = %d",
        $productIdSeleccionado
    ), ARRAY_A);
}

// Función de cálculo
function ca_calculate_requirements($presentations, $areaM2, $grosorCm) {
    $grosorM = $grosorCm / 100;
    $volumeM3 = $areaM2 * $grosorM;
    $pesoTotalKg = !empty($presentations['rendimiento_kg_m3']) ? ($volumeM3 * $presentations['rendimiento_kg_m3']) : 0;

    $sacosLitros = !empty($presentations['saco_litros']) && !empty($presentations['peso_saco_litros']) ?
                   ceil(($volumeM3 * 1000) / $presentations['saco_litros']) : 0;

    $sacasBig = !empty($presentations['peso_saca_big']) ? floor($pesoTotalKg / $presentations['peso_saca_big']) : 0;
    $pesoRestante = $pesoTotalKg - ($sacasBig * $presentations['peso_saca_big']);

    $mediasSacas = (!empty($presentations['peso_mediasaca_big']) && $pesoRestante > 0) ?
                   floor($pesoRestante / $presentations['peso_mediasaca_big']) : 0;

    $pesoRestante -= $mediasSacas * $presentations['peso_mediasaca_big'];

    $sacosKg = (!empty($presentations['saco_kg']) && $presentations['saco_kg'] >= 20) ?
               ceil($pesoTotalKg / $presentations['saco_kg']) : 0;

    return [
        'Artículo' => $presentations['nombre'],
        'volumen_m3' => $volumeM3,
        'peso_total_kg' => $pesoTotalKg,
        'sacos_litros' => $sacosLitros,
        'sacos_kg' => $sacosKg,
        'sacas_big' => $sacasBig,
        'medias_sacas' => $mediasSacas,
        'kg_sin_cubrir' => $pesoRestante > 0 ? $pesoRestante : 0
    ];
}

// Función principal del formulario y cálculo
function ca_render_calculator() {
    ob_start();
    
    $results = null; // Inicializar variable para almacenar resultados

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ca_calcular'])) {
        $productIdSeleccionado = (int)$_POST['product'];
        $presentations = ca_get_product_presentations($productIdSeleccionado);

        if ($presentations) {
            $areaM2 = (float)$_POST['areaM2'];
            $grosorCm = (float)$_POST['grosorCm'];
            $results = ca_calculate_requirements($presentations, $areaM2, $grosorCm);
        }
    }

	// Mostrar formulario
	$products = ca_get_products();
	$selected_product = isset($_POST['product']) ? $_POST['product'] : ''; // Guardar el producto seleccionado
	?>
	<form method="POST">
		<label for="product">Selecciona un producto:</label>
		<select name="product" id="product">
			<?php foreach ($products as $product) : ?>
				<option value="<?= esc_attr($product['id']); ?>" <?= ($product['id'] == $selected_product) ? 'selected' : ''; ?>>
					<?= esc_html($product['nombre']); ?>
				</option>
			<?php endforeach; ?>
		</select><br>

		<label for="areaM2">Área en m²:</label>
		<input type="number" step="0.01" name="areaM2" id="areaM2" value="<?= isset($_POST['areaM2']) ? esc_attr($_POST['areaM2']) : ''; ?>" 	required><br>

		<label for="grosorCm">Espesor en cm:</label>
		<input type="number" step="0.01" name="grosorCm" id="grosorCm" value="<?= isset($_POST['grosorCm']) ? esc_attr($_POST['grosorCm']) : ''; ?>" required><br>

		<button type="submit" name="ca_calcular">Calcular</button>
	</form>
    
    <?php
    // Mostrar resultados después del formulario
    if ($results) {
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
                echo "<p>⚠️ Kg sin cubrir por BIGS grandes o medios BIGS : " . $results['kg_sin_cubrir'] . " kg</p>";
            }
        } 

    }

    return ob_get_clean();
}

// Registrar el shortcode
add_shortcode('calculadora_aridos', 'ca_render_calculator');