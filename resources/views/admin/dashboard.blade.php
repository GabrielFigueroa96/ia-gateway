<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — WhatsApp Gateway</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }
        header {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        header .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 17px;
        }
        header .brand svg { width: 28px; height: 28px; }
        header a.logout {
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.2s;
        }
        header a.logout:hover { color: #f87171; }
        main { padding: 32px; max-width: 1100px; margin: 0 auto; }
        h2 { font-size: 22px; margin-bottom: 6px; }
        p.subtitle { color: #64748b; font-size: 14px; margin-bottom: 28px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: border-color 0.2s;
        }
        .card:hover { border-color: #25d366; }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header h3 { font-size: 17px; font-weight: 600; }
        .badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
        }
        .badge.activo { background: #14532d; color: #4ade80; }
        .badge.inactivo { background: #450a0a; color: #f87171; }
        .tipo {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 13px;
        }
        .tipo span {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 4px 10px;
            color: #e2e8f0;
            font-size: 13px;
        }
        .phone {
            color: #64748b;
            font-size: 12px;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            text-align: center;
            padding: 9px 16px;
            background: #25d366;
            color: #000;
            font-weight: 600;
            font-size: 13px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn:hover { background: #1ebe57; }
        .btn.disabled {
            background: #1e293b;
            border: 1px solid #334155;
            color: #475569;
            cursor: not-allowed;
            pointer-events: none;
        }
        .empty {
            text-align: center;
            color: #475569;
            padding: 60px 0;
            grid-column: 1 / -1;
        }
        .empty p { margin-top: 8px; font-size: 14px; }
        .stats {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 16px 24px;
            flex: 1;
        }
        .stat .num { font-size: 28px; font-weight: 700; }
        .stat .lbl { color: #64748b; font-size: 13px; margin-top: 2px; }
        .stat.error .num { color: #f87171; }
        h3.section { font-size: 17px; margin: 32px 0 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; color: #64748b; padding: 8px 12px; border-bottom: 1px solid #1e293b; }
        td { padding: 9px 12px; border-bottom: 1px solid #1e293b; vertical-align: middle; }
        tr:hover td { background: #1e293b; }
        .ok   { color: #4ade80; }
        .fail { color: #f87171; }
        .msg  { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #cbd5e1; }
        .ts   { color: #475569; white-space: nowrap; }
    </style>
</head>
<body>
<header>
    <div class="brand">
        <svg viewBox="0 0 24 24" fill="#25d366" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
        WhatsApp Gateway
    </div>
    <a href="{{ route('admin.logout') }}" class="logout">Cerrar sesión</a>
</header>

<main>
    <h2>WhatsApp Gateway</h2>
    <p class="subtitle" style="margin-bottom:20px">Panel de control</p>

    <div class="stats">
        <div class="stat">
            <div class="num">{{ $stats['total'] }}</div>
            <div class="lbl">Mensajes totales</div>
        </div>
        <div class="stat">
            <div class="num">{{ $stats['hoy'] }}</div>
            <div class="lbl">Mensajes hoy</div>
        </div>
        <div class="stat error">
            <div class="num">{{ $stats['errores'] }}</div>
            <div class="lbl">Errores hoy</div>
        </div>
        <div class="stat">
            <div class="num">{{ $tenants->count() }}</div>
            <div class="lbl">Negocios activos</div>
        </div>
    </div>

    <h3 class="section">Negocios conectados</h3>
    <p class="subtitle">{{ $tenants->count() }} {{ $tenants->count() === 1 ? 'negocio registrado' : 'negocios registrados' }}</p>

    <div class="grid">
        @forelse($tenants as $tenant)
        <div class="card">
            <div class="card-header">
                <h3>{{ $tenant->nombre }}</h3>
                <span class="badge {{ $tenant->activo ? 'activo' : 'inactivo' }}">
                    {{ $tenant->activo ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
            <div class="tipo">
                <span>🤖 {{ $tenant->tipo_ia }}</span>
            </div>
            <div class="phone">ID: {{ $tenant->phone_number_id }}</div>
            @if($tenant->url_admin)
                <a href="{{ $tenant->url_admin }}" target="_blank" class="btn">Ir al admin →</a>
            @else
                <span class="btn disabled">Sin panel configurado</span>
            @endif
        </div>
        @empty
        <div class="empty">
            <div style="font-size:40px">🤖</div>
            <p>No hay negocios registrados todavía.</p>
        </div>
        @endforelse
    </div>

    <h3 class="section">Últimos mensajes</h3>
    @if($logs->isEmpty())
        <p style="color:#475569;font-size:14px">Sin mensajes registrados todavía.</p>
    @else
    <table>
        <thead>
            <tr>
                <th>Negocio</th>
                <th>Desde</th>
                <th>Tipo</th>
                <th>Mensaje</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->tenant?->nombre ?? '—' }}</td>
                <td style="font-family:monospace;font-size:12px">{{ $log->from ?? '—' }}</td>
                <td>{{ $log->type }}</td>
                <td class="msg">{{ $log->message ?? '(multimedia)' }}</td>
                <td>
                    @if($log->api_ok)
                        <span class="ok">✓ OK</span>
                    @else
                        <span class="fail">✗ Error{{ $log->fallback_sent ? ' (fallback)' : '' }}</span>
                    @endif
                </td>
                <td class="ts">{{ $log->created_at->format('d/m H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</main>
</body>
</html>
