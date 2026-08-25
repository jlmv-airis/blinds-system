<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artículos - Precios L1 y L2</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        tr:hover { background: #f1f1f1; }
        .price { text-align: right; font-family: monospace; }
        .l1 { color: #2196F3; font-weight: bold; }
        .l2 { color: #FF9800; font-weight: bold; }
        .empty { color: #999; font-style: italic; }
        .search-box { margin-bottom: 15px; padding: 8px; width: 300px; font-size: 14px; }
        .info { margin-bottom: 15px; color: #666; }
    </style>
</head>
<body>
    <h1>Artículos - Listas de Precios</h1>
    <p class="info">
        <span class="l1">L1 Local</span> = Precio local (price)<br>
        <span class="l2">L2 Foránea</span> = Precio foránea (price_list_2)
    </p>
    <input type="text" id="search" class="search-box" placeholder="Buscar artículo, SKU, modelo...">
    <table id="articlesTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Artículo</th>
                <th>SKU</th>
                <th>Categoría</th>
                <th>Modelo</th>
                <th>Unidad</th>
                <th class="price l1">L1 Local</th>
                <th class="price l2">L2 Foránea</th>
            </tr>
        </thead>
        <tbody>
            @foreach($articles as $article)
            <tr>
                <td>{{ $article->id }}</td>
                <td>{{ $article->article }}</td>
                <td>{{ $article->sku }}</td>
                <td>{{ $article->category ?? '-' }}</td>
                <td>{{ $article->model ?? '-' }}</td>
                <td>{{ $article->unit ?? '-' }}</td>
                <td class="price">${{ number_format($article->price, 2) }}</td>
                <td class="price">
                    @if($article->price_list_2)
                        ${{ number_format($article->price_list_2, 2) }}
                    @else
                        <span class="empty">Sin L2</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <script>
        document.getElementById('search').addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('#articlesTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
