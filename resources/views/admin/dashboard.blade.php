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
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        header .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 16px;
        }
        header .brand svg { width: 26px; height: 26px; }
        header a.logout {
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.2s;
        }
        header a.logout:hover { color: #f87171; }

        main { padding: 20px 16px; max-width: 1100px; margin: 0 auto; }
        @media (min-width: 640px) { main { padding: 32px 24px; } }

        h2 { font-size: 20px; margin-bottom: 4px; }
        @media (min-width: 640px) { h2 { font-size: 22px; } }
        p.subtitle { color: #64748b; font-size: 13px; margin-bottom: 20px; }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 28px;
        }
        @media (min-width: 640px) {
            .stats { grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
        }
        .stat {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 14px 16px;
        }
        @media (min-width: 640px) { .stat { padding: 16px 24px; } }
        .stat .num { font-size: 24px; font-weight: 700; }
        @media (min-width: 640px) { .stat .num { font-size: 28px; } }
        .stat .lbl { color: #64748b; font-size: 12px; margin-top: 2px; }
        .stat.error .num { color: #f87171; }

        /* Negocios */
        h3.section { font-size: 16px; margin: 0 0 6px; }
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        @media (min-width: 480px) { .grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; } }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: border-color 0.2s;
        }
        .card:hover { border-color: #25d366; }
        .card-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .card-header h3 { font-size: 15px; font-weight: 600; }
        .badge {
            font-size: 11px; font-weight: 600;
            padding: 3px 10px; border-radius: 999px; white-space: nowrap;
        }
        .badge.activo  { background: #14532d; color: #4ade80; }
        .badge.inactivo { background: #450a0a; color: #f87171; }
        .tipo { display: flex; align-items: center; gap: 8px; color: #94a3b8; font-size: 13px; }
        .tipo span {
            background: #0f172a; border: 1px solid #334155; border-radius: 6px;
            padding: 4px 10px; color: #e2e8f0; font-size: 13px;
        }
        .phone { color: #64748b; font-size: 12px; font-family: monospace; word-break: break-all; }
        .btn {
            display: inline-block; text-align: center;
            padding: 9px 16px; background: #25d366; color: #000;
            font-weight: 600; font-size: 13px; border-radius: 8px;
            text-decoration: none; transition: background 0.2s;
        }
        .btn:hover { background: #1ebe57; }
        .btn.disabled {
            background: #1e293b; border: 1px solid #334155;
            color: #475569; cursor: not-allowed; pointer-events: none;
        }
        .empty {
            text-align: center; color: #475569;
            padding: 48px 0; grid-column: 1 / -1;
        }
        .empty p { margin-top: 8px; font-size: 14px; }

        /* Section header + refresh */
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 40px; margin-bottom: 14px;
        }
        .btn-refresh {
            display: inline-flex; align-items: center; gap: 6px;
            background: #1e293b; border: 1px solid #334155;
            color: #94a3b8; font-size: 12px; font-weight: 500;
            padding: 6px 12px; border-radius: 8px;
            text-decoration: none; transition: border-color 0.2s, color 0.2s;
            white-space: nowrap;
        }
        .btn-refresh:hover { border-color: #25d366; color: #f1f5f9; }

        /* Tabla desktop */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 560px; }
        th { text-align: left; color: #64748b; padding: 8px 10px; border-bottom: 1px solid #1e293b; font-size: 12px; }
        td { padding: 9px 10px; border-bottom: 1px solid #1e293b; vertical-align: middle; }
        tr:hover td { background: #1e293b; }

        /* Cards mobile para mensajes */
        .log-cards { display: flex; flex-direction: column; gap: 8px; }
        .log-card {
            background: #1e293b; border: 1px solid #334155;
            border-radius: 10px; padding: 12px 14px;
        }
        .log-card-top {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 6px; gap: 8px;
        }
        .log-card-msg {
            font-size: 13px; color: #cbd5e1; margin-bottom: 6px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; cursor: pointer;
        }
        .log-card-msg.expanded { display: block; overflow: visible; word-break: break-word; }
        .log-card-meta { display: flex; flex-wrap: wrap; gap: 8px; font-size: 11px; color: #64748b; }

        .ok   { color: #4ade80; }
        .fail { color: #f87171; }
        .msg  { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #cbd5e1; cursor: pointer; }
        .msg:hover { color: #f1f5f9; }
        .msg.expanded { white-space: normal; overflow: visible; max-width: 400px; }
        .ts   { color: #475569; white-space: nowrap; }
        .dir-in  { color: #4ade80; font-size: 11px; font-weight: 600; }
        .dir-out { color: #60a5fa; font-size: 11px; font-weight: 600; }
        .status-read      { color: #60a5fa; }
        .status-delivered { color: #94a3b8; }
        .status-sent      { color: #64748b; }

        /* Mostrar tabla en desktop, cards en mobile */
        .show-mobile { display: block; }
        .show-desktop { display: none; }
        @media (min-width: 700px) {
            .show-mobile  { display: none; }
            .show-desktop { display: block; }
        }
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
    <p class="subtitle">Panel de control</p>

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

    <div class="section-header">
        <h3 class="section" style="margin:0">Últimos mensajes</h3>
        <a href="{{ route('admin.dashboard') }}" class="btn-refresh">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 4v6h6"/><path d="M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
            Actualizar
        </a>
    </div>

    @if($logs->isEmpty())
        <p style="color:#475569;font-size:14px">Sin mensajes registrados todavía.</p>
    @else

    {{-- Mobile: cards --}}
    <div class="log-cards show-mobile">
        @foreach($logs as $log)
        @php
            $isOut = $log->type === 'outgoing';
        @endphp
        <div class="log-card">
            <div class="log-card-top">
                <div style="display:flex;align-items:center;gap:8px">
                    @if($isOut)
                        <span class="dir-out">↑ bot</span>
                    @else
                        <span class="dir-in">↓ cliente</span>
                    @endif
                    <span style="font-size:13px;font-weight:600">{{ $log->tenant?->nombre ?? '—' }}</span>
                </div>
                <span class="ts" style="font-size:11px">{{ $log->created_at->format('d/m H:i') }}</span>
            </div>
            <div class="log-card-msg">{{ $log->message ?? ($isOut ? '(respuesta bot)' : '(multimedia)') }}</div>
            <div class="log-card-meta">
                <span style="font-family:monospace">{{ $log->from ?? '—' }}</span>
                @if(!$isOut)
                    @if($log->api_ok)
                        <span class="ok">✓ API OK</span>
                    @else
                        <span class="fail">✗ Error{{ $log->fallback_sent ? ' (fallback)' : '' }}</span>
                    @endif
                @endif
                @if($log->status === 'read')
                    <span class="status-read">✓✓ leído</span>
                @elseif($log->status === 'delivered')
                    <span class="status-delivered">✓✓ entregado</span>
                @elseif($log->status === 'sent')
                    <span class="status-sent">✓ enviado</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Desktop: tabla --}}
    <div class="table-wrap show-desktop">
    <table>
        <thead>
            <tr>
                <th></th>
                <th>Negocio</th>
                <th>Teléfono</th>
                <th>Mensaje</th>
                <th>Envío API</th>
                <th>Estado WA</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>
                    @if($log->type === 'outgoing')
                        <span class="dir-out">↑ bot</span>
                    @else
                        <span class="dir-in">↓ cliente</span>
                    @endif
                </td>
                <td>{{ $log->tenant?->nombre ?? '—' }}</td>
                <td style="font-family:monospace;font-size:12px">{{ $log->from ?? '—' }}</td>
                <td class="msg">{{ $log->message ?? ($log->type === 'outgoing' ? '(respuesta bot)' : '(multimedia)') }}</td>
                <td>
                    @if($log->type === 'outgoing')
                        <span style="color:#475569">—</span>
                    @elseif($log->api_ok)
                        <span class="ok">✓ OK</span>
                    @else
                        <span class="fail">✗ Error{{ $log->fallback_sent ? ' (fallback)' : '' }}</span>
                    @endif
                </td>
                <td>
                    @if($log->status === 'read')
                        <span class="status-read">✓✓ leído</span>
                    @elseif($log->status === 'delivered')
                        <span class="status-delivered">✓✓ entregado</span>
                    @elseif($log->status === 'sent')
                        <span class="status-sent">✓ enviado</span>
                    @else
                        <span style="color:#334155">—</span>
                    @endif
                </td>
                <td class="ts">{{ $log->created_at->format('d/m H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>

    @endif
</main>
<script>
document.querySelectorAll('.msg, .log-card-msg').forEach(el => {
    el.addEventListener('click', () => el.classList.toggle('expanded'));
});
</script>
</body>
</html>
