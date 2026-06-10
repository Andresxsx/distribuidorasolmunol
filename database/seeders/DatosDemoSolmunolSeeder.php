<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatosDemoSolmunolSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $admin = User::where('email', 'admin@erpsolis.com')->first() ?? User::first();
        $userId = $admin?->id;

        $this->command?->info('Creando empleados...');
        $this->crearEmpleados();

        $this->command?->info('Creando proveedores...');
        $this->crearProveedores();

        $this->command?->info('Creando clientes...');
        $this->crearClientes();

        $this->command?->info('Creando productos...');
        $this->crearProductos();

        $this->command?->info('Creando compras...');
        $this->crearCompras($userId);

        $this->command?->info('Creando ventas...');
        $this->crearVentas($userId);

        $this->command?->info('Creando movimientos de bodega...');
        $this->crearMovimientosBodega($userId);

        $this->command?->info('Datos demo cargados correctamente.');
    }

    private function crearEmpleados(): void
    {
        $nombres = [
            ['Carlos Andrés', 'Mendoza Vera'], ['María Fernanda', 'Zambrano Moreira'], ['Luis Alberto', 'Cedeño Palma'],
            ['Karla Vanessa', 'Loor Andrade'], ['Jorge Luis', 'Macias Delgado'], ['Diana Carolina', 'Quiroz León'],
            ['Kevin Alexander', 'Paredes Cevallos'], ['Gabriela Estefanía', 'Vera Solórzano'], ['Miguel Ángel', 'Anchundia García'],
            ['Ana Belén', 'Chávez Molina'], ['Bryan David', 'Ortega Zamora'], ['Valeria Nicole', 'Reyes Cedeño'],
            ['José Daniel', 'Mera Ponce'], ['Rosa Elena', 'Alcívar Vélez'], ['Fernando Javier', 'Castro Hidalgo'],
            ['Paola Cristina', 'Bermúdez Rivas'], ['Andrés Felipe', 'Cantos Intriago'], ['Mónica Patricia', 'Palacios Mendoza'],
            ['Edwin Geovanny', 'Mora España'], ['Mayra Alejandra', 'Cruz Loor'], ['Víctor Manuel', 'Muñoz Litardo'],
            ['Nathaly Abigail', 'Santos Bravo'], ['Cristian David', 'Mora España'], ['Elvis Aldair', 'Ortega Estrada'],
            ['Sofía Anahí', 'Villacís Torres'], ['Ricardo Antonio', 'Barcia Peña'], ['Andrea Lizbeth', 'Delgado Vera'],
            ['Juan Carlos', 'Pincay Cedeño'], ['Melanie Dayana', 'Mendoza Chica'], ['Ángel Gabriel', 'Gómez Reyes'],
            ['Camila Doménica', 'Zamora Loor'], ['Henry Fabricio', 'Vera Mero'], ['Nicole Tatiana', 'Parrales Castro'],
            ['Santiago David', 'Cevallos Andrade'], ['Priscila Noemí', 'Moreira Macías'], ['Anthony Josué', 'Chávez Palma'],
            ['Jennifer Katherine', 'Solórzano Mera'], ['Pedro Alejandro', 'Ponce Quiroz'], ['Daniela Isabel', 'Rivas Zambrano'],
            ['Mario Enrique', 'Alvarado García'], ['Verónica Gabriela', 'León Delgado'], ['Jonathan Steven', 'Cruz Hidalgo'],
            ['María José', 'Palma Vélez'], ['Joel Sebastián', 'Reyes Anchundia'], ['Erika Valentina', 'Molina Cedeño'],
            ['Roberto Xavier', 'Bravo Macías'], ['Cinthya Lissette', 'Vélez Paredes'], ['Alex Eduardo', 'Intriago Mera'],
            ['Tatiana Elizabeth', 'Castro Moreira'], ['David Alejandro', 'Macias Loor'],
        ];

        $cargos = ['Gerente', 'Administrador', 'Vendedor', 'Bodeguero', 'Comprador', 'Contador', 'Asistente administrativo', 'Jefe de talento humano', 'Analista de sistemas'];
        $departamentos = ['Dirección', 'Administración', 'Talento Humano', 'Bodega', 'Compras', 'Ventas', 'Contabilidad', 'Sistemas'];
        $now = now();

        foreach ($nombres as $i => [$nombre, $apellido]) {
            DB::table('empleados')->updateOrInsert(
                ['cedula' => $this->cedulaValida($i + 1)],
                [
                    'codigo_empleado' => 'EMP-DEMO-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'nombres' => $nombre,
                    'apellidos' => $apellido,
                    'cargo' => $cargos[$i % count($cargos)],
                    'departamento' => $departamentos[$i % count($departamentos)],
                    'telefono' => '09' . str_pad((string) (13000000 + $i), 8, '0', STR_PAD_LEFT),
                    'correo' => 'empleado' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '@solmunol.com',
                    'sueldo' => 470 + (($i % 12) * 55),
                    'fecha_ingreso' => Carbon::now()->subMonths(3 + $i)->toDateString(),
                    'estado' => $i % 17 === 0 ? 'Suspendido' : 'Activo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function crearProveedores(): void
    {
        $proveedores = [
            'Comercial Manabí Distribuciones S.A.', 'Importadora Costa Azul Cía. Ltda.', 'Abarrotes El Pacífico S.A.',
            'Distribuidora Los Andes Cía. Ltda.', 'Proveedora San Gregorio S.A.', 'Comercial La Favorita Del Sur S.A.',
            'Industria Alimenticia Río Verde Cía. Ltda.', 'Bebidas Tropicales Ecuador S.A.', 'Lácteos Santa Clara Cía. Ltda.',
            'Molinos Del Litoral S.A.', 'Empaques Y Plásticos Del Ecuador S.A.', 'Productos De Limpieza Brillante S.A.',
            'Ferretería Central Mayorista Cía. Ltda.', 'Tecnología Y Suministros Globales S.A.', 'Papelería Nacional Del Ecuador Cía. Ltda.',
            'Agroinsumos Los Ríos S.A.', 'Alimentos La Campiña Cía. Ltda.', 'Distribuidora El Sol De Manta S.A.',
            'Comercial El Buen Precio Cía. Ltda.', 'Logística Y Abastecimiento Andino S.A.', 'Productos Del Valle Cía. Ltda.',
            'Supermayorista Guayas S.A.', 'Mercantil Quito Norte Cía. Ltda.', 'Insumos Comerciales Portoviejo S.A.',
            'Almacenes Del Carmen Cía. Ltda.', 'Distribuciones Bahía S.A.', 'Comercial Santa Ana Cía. Ltda.',
            'Proveedor Integral Ecuador S.A.', 'Alimentos Y Bebidas Primavera Cía. Ltda.', 'Corporación De Víveres Nacionales S.A.',
            'Productos Masivos Del Ecuador Cía. Ltda.', 'Distribuidora Buena Fe S.A.', 'Abarrotes Y Cereales San Pedro Cía. Ltda.',
            'Comercial Litoral Norte S.A.', 'Suministros Empresariales Quito Cía. Ltda.', 'Higiene Y Limpieza Total S.A.',
            'Electrodomésticos Y Tecnología Ruiz Cía. Ltda.', 'Materiales Y Ferretería Universal S.A.', 'Farmaproductos Ecuador Cía. Ltda.',
            'Bodega Mayorista El Ahorro S.A.', 'Comercializadora Mega Stock Cía. Ltda.', 'Distribuidora El Progreso S.A.',
            'Corporación De Alimentos Manabita Cía. Ltda.', 'Productos Selectos Guayaquil S.A.', 'Insumos De Oficina Modernos Cía. Ltda.',
            'Bebidas Y Refrescos Del Pacífico S.A.', 'Granos Y Cereales La Sierra Cía. Ltda.', 'Comercializadora Nueva Esperanza S.A.',
            'Soluciones Logísticas Ecuador Cía. Ltda.', 'Mayorista Distribuciones Olmedo S.A.',
        ];
        $now = now();

        foreach ($proveedores as $i => $nombre) {
            $ruc = $this->cedulaValida(200 + $i) . '001';
            DB::table('proveedores')->updateOrInsert(
                ['ruc' => $ruc],
                [
                    'nombre' => $nombre,
                    'telefono' => $i % 2 === 0 ? '042' . str_pad((string) (200000 + $i), 6, '0', STR_PAD_LEFT) : '09' . str_pad((string) (23000000 + $i), 8, '0', STR_PAD_LEFT),
                    'correo' => 'proveedor' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '@proveedorolmunol.com',
                    'direccion' => 'Av. Principal ' . (100 + $i) . ' y Calle Comercial, Ecuador',
                    'estado' => 'Activo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function crearClientes(): void
    {
        $clientes = [
            'Tienda El Buen Vecino', 'Minimarket San José', 'Bazar Comercial La Unión', 'Despensa Don Pedro', 'Comercial Mi Barrio',
            'Tienda La Economía', 'Market Santa Martha', 'Despensa Los Almendros', 'Comercial La Esquina', 'Abarrotes El Triunfo',
            'Minimarket Génesis', 'Tienda La Bendición', 'Comercial El Paraíso', 'Despensa La Familia', 'Market Nueva Esperanza',
            'Tienda San Antonio', 'Abarrotes Rosita', 'Despensa El Carmen', 'Comercial El Manaba', 'Market Las Palmas',
            'Tienda Don Luis', 'Abarrotes Zambrano', 'Comercial San Rafael', 'Despensa El Ahorro', 'Market La Pradera',
            'Tienda La Victoria', 'Abarrotes Moreira', 'Comercial Santa Isabel', 'Despensa El Progreso', 'Market Los Ceibos',
            'Tienda Mi Casita', 'Abarrotes Vera', 'Comercial El Pacífico', 'Despensa Buena Fe', 'Market La Aurora',
            'Tienda Los Ángeles', 'Abarrotes La Floresta', 'Comercial Puerto Azul', 'Despensa San Miguel', 'Market Costa Verde',
            'Tienda Sol Y Mar', 'Abarrotes El Rosal', 'Comercial Los Esteros', 'Despensa La Primavera', 'Market Manabí',
            'Tienda Las Orquídeas', 'Abarrotes Santa Clara', 'Comercial El Horizonte', 'Despensa Reina Del Cisne', 'Market Solmunol',
        ];
        $now = now();

        foreach ($clientes as $i => $nombre) {
            DB::table('clientes')->updateOrInsert(
                ['cedula_ruc' => $this->cedulaValida(400 + $i)],
                [
                    'nombre' => $nombre,
                    'telefono' => '09' . str_pad((string) (33000000 + $i), 8, '0', STR_PAD_LEFT),
                    'correo' => 'cliente' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '@clienteolmunol.com',
                    'direccion' => 'Calle ' . (10 + $i) . ' y Av. Comercial, Ecuador',
                    'estado' => 'Activo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function crearProductos(): void
    {
        $productos = $this->productosBase();
        $now = now();

        foreach ($productos as $i => [$nombre, $categoria, $precioCompra, $precioVenta]) {
            DB::table('productos')->updateOrInsert(
                ['codigo' => 'PROD-DEMO-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'nombre' => $nombre,
                    'categoria' => $categoria,
                    'descripcion' => 'Producto de inventario para venta y distribución comercial.',
                    'stock_actual' => 120 + ($i % 20),
                    'stock_minimo' => 12 + ($i % 6),
                    'precio_compra' => $precioCompra,
                    'precio_venta' => $precioVenta,
                    'estado' => 'Activo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function crearCompras(?int $userId): void
    {
        $proveedores = DB::table('proveedores')->where('estado', 'Activo')->orderBy('id')->limit(50)->get()->values();
        $productos = DB::table('productos')->where('estado', 'Activo')->orderBy('id')->limit(50)->get()->values();
        $now = now();

        if ($proveedores->isEmpty() || $productos->isEmpty()) {
            return;
        }

        foreach (range(1, 50) as $i) {
            $producto = $productos[($i - 1) % $productos->count()];
            $proveedor = $proveedores[($i - 1) % $proveedores->count()];
            $cantidad = 15 + ($i % 18);
            $precio = (float) $producto->precio_compra;

            DB::table('compras')->updateOrInsert(
                ['numero_compra' => 'COMP-DEMO-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                [
                    'proveedor_id' => $proveedor->id,
                    'producto_id' => $producto->id,
                    'fecha' => Carbon::now()->subDays(80 - $i)->toDateString(),
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'total' => round($cantidad * $precio, 2),
                    'user_id' => $userId,
                    'observacion' => 'Compra inicial de mercadería para abastecimiento.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function crearVentas(?int $userId): void
    {
        $clientes = DB::table('clientes')->where('estado', 'Activo')->orderBy('id')->limit(50)->get()->values();
        $productos = DB::table('productos')->where('estado', 'Activo')->orderBy('id')->limit(50)->get()->values();
        $now = now();

        if ($clientes->isEmpty() || $productos->isEmpty()) {
            return;
        }

        foreach (range(1, 50) as $i) {
            $producto = $productos[($i - 1) % $productos->count()];
            $cliente = $clientes[($i - 1) % $clientes->count()];
            $cantidad = 2 + ($i % 6);
            $precio = (float) $producto->precio_venta;

            DB::table('ventas')->updateOrInsert(
                ['numero_venta' => 'VENT-DEMO-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                [
                    'cliente_id' => $cliente->id,
                    'producto_id' => $producto->id,
                    'fecha' => Carbon::now()->subDays(45 - min($i, 44))->toDateString(),
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'total' => round($cantidad * $precio, 2),
                    'user_id' => $userId,
                    'observacion' => 'Venta registrada para cliente mayorista/minorista.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function crearMovimientosBodega(?int $userId): void
    {
        $productos = DB::table('productos')->where('estado', 'Activo')->orderBy('id')->limit(50)->get()->values();
        $now = now();

        if ($productos->isEmpty()) {
            return;
        }

        foreach ($productos as $i => $producto) {
            $n = $i + 1;
            $entrada = 15 + ($n % 18);
            $salida = 2 + ($n % 6);
            $stockInicial = 120 + ($i % 20);

            DB::table('movimientos_bodega')->updateOrInsert(
                ['codigo_movimiento' => 'MOV-ENT-DEMO-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT)],
                [
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'Entrada',
                    'origen' => 'Compra',
                    'documento_referencia' => 'COMP-DEMO-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                    'cantidad' => $entrada,
                    'stock_anterior' => $stockInicial,
                    'stock_nuevo' => $stockInicial + $entrada,
                    'user_id' => $userId,
                    'fecha' => Carbon::now()->subDays(80 - $n),
                    'observacion' => 'Movimiento demo generado por compra.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('movimientos_bodega')->updateOrInsert(
                ['codigo_movimiento' => 'MOV-SAL-DEMO-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT)],
                [
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'Salida',
                    'origen' => 'Venta',
                    'documento_referencia' => 'VENT-DEMO-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                    'cantidad' => $salida,
                    'stock_anterior' => $stockInicial + $entrada,
                    'stock_nuevo' => $stockInicial + $entrada - $salida,
                    'user_id' => $userId,
                    'fecha' => Carbon::now()->subDays(45 - min($n, 44)),
                    'observacion' => 'Movimiento demo generado por venta.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function productosBase(): array
    {
        return [
            ['Arroz Flor Nacional 5 Kg', 'Granos y cereales', 4.80, 5.60], ['Azúcar Blanca 1 Kg', 'Víveres y abarrotes', 0.92, 1.15],
            ['Aceite Vegetal 1 Litro', 'Víveres y abarrotes', 2.10, 2.65], ['Atún Enlatado 170 G', 'Víveres y abarrotes', 1.05, 1.35],
            ['Fideo Tallarín 400 G', 'Víveres y abarrotes', 0.82, 1.05], ['Harina De Trigo 1 Kg', 'Víveres y abarrotes', 0.95, 1.25],
            ['Lenteja Seleccionada 500 G', 'Granos y cereales', 1.00, 1.35], ['Frejol Rojo 500 G', 'Granos y cereales', 1.20, 1.60],
            ['Avena En Hojuelas 500 G', 'Granos y cereales', 1.30, 1.75], ['Café Instantáneo 100 G', 'Víveres y abarrotes', 2.40, 3.10],
            ['Leche Entera 1 Litro', 'Víveres y abarrotes', 0.90, 1.15], ['Galletas Dulces 300 G', 'Víveres y abarrotes', 1.10, 1.45],
            ['Agua Purificada 1.2 Litros', 'Bebidas', 0.38, 0.60], ['Cola Familiar 3 Litros', 'Bebidas', 2.05, 2.70],
            ['Jugo De Naranja 1 Litro', 'Bebidas', 1.25, 1.65], ['Bebida Energizante 365 Ml', 'Bebidas', 0.95, 1.35],
            ['Detergente En Polvo 1 Kg', 'Limpieza', 2.25, 2.95], ['Jabón Líquido 500 Ml', 'Limpieza', 1.45, 1.95],
            ['Cloro Desinfectante 1 Litro', 'Limpieza', 0.75, 1.05], ['Suavizante De Ropa 1 Litro', 'Limpieza', 1.90, 2.45],
            ['Escoba Plástica', 'Limpieza', 1.60, 2.30], ['Trapeador De Algodón', 'Limpieza', 1.75, 2.50],
            ['Papel Higiénico 4 Rollos', 'Limpieza', 1.80, 2.40], ['Servilletas Familiares 200 Und', 'Limpieza', 0.85, 1.20],
            ['Cuaderno Universitario 100 Hojas', 'Oficina', 1.10, 1.60], ['Bolígrafo Azul Caja 12 Und', 'Oficina', 1.80, 2.50],
            ['Resma Papel Bond A4', 'Oficina', 3.80, 4.70], ['Carpeta Plástica Oficio', 'Oficina', 0.45, 0.75],
            ['Mouse Óptico USB', 'Tecnología', 4.50, 6.50], ['Teclado USB Básico', 'Tecnología', 6.20, 8.80],
            ['Memoria USB 32 GB', 'Tecnología', 4.80, 7.20], ['Cable HDMI 1.5 M', 'Tecnología', 3.10, 4.90],
            ['Foco Led 9W', 'Ferretería', 1.20, 1.75], ['Cinta Aislante Negra', 'Ferretería', 0.55, 0.85],
            ['Candado Mediano', 'Ferretería', 2.35, 3.50], ['Destornillador Plano', 'Ferretería', 1.25, 1.95],
            ['Alcohol Antiséptico 500 Ml', 'Farmacia', 1.15, 1.70], ['Mascarilla Quirúrgica Caja 50', 'Farmacia', 1.90, 2.80],
            ['Guantes De Látex Caja 100', 'Farmacia', 3.60, 5.20], ['Gel Antibacterial 250 Ml', 'Farmacia', 1.35, 1.95],
            ['Sal Refinada 1 Kg', 'Víveres y abarrotes', 0.35, 0.55], ['Salsa De Tomate 200 G', 'Víveres y abarrotes', 0.60, 0.90],
            ['Mayonesa 200 G', 'Víveres y abarrotes', 0.75, 1.05], ['Mantequilla 250 G', 'Víveres y abarrotes', 1.50, 2.10],
            ['Cereal De Maíz 300 G', 'Granos y cereales', 1.55, 2.10], ['Té De Hierbas Caja 25', 'Víveres y abarrotes', 0.95, 1.35],
            ['Shampoo Familiar 400 Ml', 'Otros', 2.40, 3.30], ['Pasta Dental 100 Ml', 'Otros', 1.15, 1.65],
            ['Cepillo Dental Adulto', 'Otros', 0.70, 1.10], ['Fundas Plásticas Paquete 100', 'Otros', 1.30, 1.95],
        ];
    }

    private function cedulaValida(int $seed): string
    {
        $provincia = str_pad((string) ((($seed - 1) % 24) + 1), 2, '0', STR_PAD_LEFT);
        $tercerDigito = (string) ($seed % 6);
        $secuencia = str_pad((string) (100000 + ($seed * 37)), 6, '0', STR_PAD_LEFT);
        $nueveDigitos = $provincia . $tercerDigito . $secuencia;

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = ((int) $nueveDigitos[$i]) * $coeficientes[$i];
            if ($valor >= 10) {
                $valor -= 9;
            }
            $suma += $valor;
        }

        $digito = (10 - ($suma % 10)) % 10;

        return $nueveDigitos . $digito;
    }
}
