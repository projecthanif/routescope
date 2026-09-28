<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RouteScope</title>

    <style>
        :root {
            --bg: #000;
            --card: #0a0a0a;
            --input: #050505;
            --raised: #111;
            --border: #1f1f1f;
            --border-strong: #333;
            --text: #d1d5db;
            --text-strong: #fff;
            --muted: #6b7280;
            --faint: #4b5563;
            --accent: #60a5fa;
            --success: #4ade80;
            --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 32px;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font: 14px/1.5 ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 4px; }

        .icon { width: 16px; height: 16px; flex-shrink: 0; }

        .tabs { display: flex; justify-content: center; margin-bottom: 32px; }

        .tabs-inner {
            display: flex;
            gap: 4px;
            padding: 4px;
            background: var(--raised);
            border: 1px solid var(--border);
            border-radius: 999px;
        }

        .tab {
            padding: 6px 16px;
            border: 1px solid transparent;
            border-radius: 999px;
            background: none;
            color: var(--muted);
            font: inherit;
            font-weight: 500;
            cursor: pointer;
        }

        .tab:hover { color: var(--text); }

        .tab[aria-selected="true"] {
            background: #1a1a1a;
            border-color: var(--border-strong);
            color: var(--text-strong);
        }

        .card {
            max-width: 1152px;
            margin: 0 auto;
            overflow: hidden;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .header { padding: 24px 24px 8px; }
        .title-row { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
        h1 { margin: 0; color: var(--text-strong); font-size: 24px; letter-spacing: -0.02em; }

        .count {
            padding: 2px 8px;
            background: var(--border);
            border: 1px solid var(--border-strong);
            border-radius: 999px;
            color: var(--muted);
            font-size: 12px;
        }

        .subtitle { margin: 0; color: var(--muted); }

        .search { position: relative; max-width: 384px; margin: 16px 24px; }
        .search .icon { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: var(--muted); }

        .search input {
            width: 100%;
            padding: 8px 16px 8px 40px;
            background: var(--input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font: inherit;
        }

        .search input::placeholder { color: var(--faint); }
        .search input:focus { outline: none; border-color: #4b5563; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }

        th {
            padding: 12px 24px;
            border-bottom: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        td { padding: 16px 24px; vertical-align: top; border-bottom: 1px solid var(--border); }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: var(--raised); }

        .methods { display: flex; flex-wrap: wrap; gap: 4px; }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
        }

        .method-GET { color: #60a5fa; background: rgba(96, 165, 250, .1); border-color: rgba(96, 165, 250, .2); }
        .method-POST { color: #4ade80; background: rgba(74, 222, 128, .1); border-color: rgba(74, 222, 128, .2); }
        .method-PUT { color: #f59e0b; background: rgba(245, 158, 11, .1); border-color: rgba(245, 158, 11, .2); }
        .method-PATCH { color: #a78bfa; background: rgba(167, 139, 250, .1); border-color: rgba(167, 139, 250, .2); }
        .method-DELETE { color: #ef4444; background: rgba(239, 68, 68, .1); border-color: rgba(239, 68, 68, .2); }
        .method-other { color: #9ca3af; background: rgba(31, 41, 55, .4); border-color: #374151; }

        .path { font-family: var(--mono); color: var(--muted); white-space: nowrap; }
        .path strong { color: var(--text-strong); font-weight: 500; }
        .route-name { margin-top: 4px; color: var(--faint); font-size: 12px; }

        .middleware {
            display: inline-block;
            margin: 0 4px 4px 0;
            padding: 2px 6px;
            background: var(--raised);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: #9ca3af;
            font: 10px var(--mono);
        }

        .none { color: #374151; }

        .source { display: flex; align-items: center; gap: 8px; color: var(--muted); }
        .source span:last-child { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .kind {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border: 1px solid;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 700;
        }

        .kind-closure { color: #c084fc; background: rgba(88, 28, 135, .4); border-color: rgba(168, 85, 247, .3); }
        .kind-php { color: #818cf8; background: rgba(49, 46, 129, .4); border-color: rgba(99, 102, 241, .3); }
        .kind-other { color: #9ca3af; background: rgba(31, 41, 55, .4); border-color: rgba(75, 85, 99, .3); }

        .actions { display: flex; justify-content: flex-end; gap: 12px; }

        .action {
            display: inline-flex;
            padding: 0;
            border: 0;
            background: none;
            color: var(--muted);
            cursor: pointer;
        }

        .action:hover { color: var(--text-strong); }
        .action.copied { color: var(--success); }

        .empty { padding: 32px 24px; text-align: center; color: var(--muted); }
        .spacer { height: 16px; }

        @media (max-width: 640px) {
            body { padding: 16px; }
            .header, .search { margin-left: 16px; margin-right: 16px; }
            .header { padding: 16px 0 8px; }
            th, td { padding: 12px 16px; }
        }
    </style>
</head>

<body>
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none">
        <symbol id="icon-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" />
        </symbol>
        <symbol id="icon-copy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
        </symbol>
        <symbol id="icon-external" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h6v6" /><path d="M10 14 21 3" /><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
        </symbol>
    </svg>

    <div class="tabs">
        <div class="tabs-inner" role="tablist">
            <button id="api-tab" class="tab" role="tab" aria-selected="true">API Routes</button>
            <button id="web-tab" class="tab" role="tab" aria-selected="false">Web Routes</button>
        </div>
    </div>

    <div class="card">
        <div class="header">
            <div class="title-row">
                <h1 id="route-title">API Routes</h1>
                <span id="route-count" class="count">{{ count($apiRoutes) }}</span>
            </div>
            <p class="subtitle">Manage and inspect your endpoints</p>
        </div>

        <div class="search">
            <svg class="icon"><use href="#icon-search" /></svg>
            <input id="search-input" type="text" placeholder="Search path, name, middleware..." autocomplete="off">
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 96px">Methods</th>
                        <th>Path</th>
                        <th>Middleware</th>
                        <th>Source</th>
                        <th style="width: 96px; text-align: right">Actions</th>
                    </tr>
                </thead>
                <tbody id="routes-table-body"></tbody>
            </table>
        </div>

        <div class="spacer"></div>
    </div>

    <script>
        // Data from Laravel controller
        const routeSets = {
            api: { title: 'API Routes', routes: @json($apiRoutes) },
            web: { title: 'Web Routes', routes: @json($webRoutes) },
        };

        let currentView = 'api';

        const tableBody = document.getElementById('routes-table-body');
        const searchInput = document.getElementById('search-input');
        const routeTitle = document.getElementById('route-title');
        const routeCount = document.getElementById('route-count');
        const tabs = { api: document.getElementById('api-tab'), web: document.getElementById('web-tab') };

        const KNOWN_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        // Escape untrusted values before inserting them into HTML
        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function icon(name) {
            return `<svg class="icon"><use href="#icon-${name}" /></svg>`;
        }

        function renderMethods(methods) {
            return methods.map(method => {
                const variant = KNOWN_METHODS.includes(method) ? method : 'other';
                return `<span class="badge method-${variant}">${escapeHtml(method)}</span>`;
            }).join('');
        }

        function renderSourceKind(source) {
            if (source === 'Closure') {
                return '<span class="kind kind-closure">λ</span>';
            }

            if (source.includes('::')) {
                return '<span class="kind kind-php">PHP</span>';
            }

            return '<span class="kind kind-other">•</span>';
        }

        function renderMiddleware(middleware) {
            if (!middleware.length) {
                return '<span class="none">—</span>';
            }

            return middleware.map(m => `<span class="middleware">${escapeHtml(m)}</span>`).join('');
        }

        // Only GET routes without parameters can be opened directly
        function canOpen(route) {
            return route.methods.includes('GET') && route.parameters.length === 0;
        }

        async function copyToClipboard(text, button) {
            try {
                await navigator.clipboard.writeText(text);
                button.classList.add('copied');
                setTimeout(() => button.classList.remove('copied'), 1000);
            } catch (e) {
                window.prompt('Copy path:', text);
            }
        }

        function matches(route, query) {
            return [route.uri, route.source, route.name ?? '', ...route.methods, ...route.middleware]
                .some(value => value.toLowerCase().includes(query));
        }

        function renderRoutes() {
            const query = searchInput.value.trim().toLowerCase();
            const routes = routeSets[currentView].routes.filter(route => matches(route, query));

            routeCount.textContent = routes.length;
            tableBody.innerHTML = '';

            if (routes.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="empty">No routes found</td></tr>';
                return;
            }

            routes.forEach(route => {
                const tr = document.createElement('tr');

                // Path highlighting (last segment white, rest gray)
                const pathParts = route.uri.split('/');
                const lastPart = pathParts.pop();
                const prefix = pathParts.join('/') + '/';

                tr.innerHTML = `
                    <td><div class="methods">${renderMethods(route.methods)}</div></td>
                    <td>
                        <div class="path">${escapeHtml(prefix)}<strong>${escapeHtml(lastPart)}</strong></div>
                        ${route.name ? `<div class="route-name">${escapeHtml(route.name)}</div>` : ''}
                    </td>
                    <td>${renderMiddleware(route.middleware)}</td>
                    <td>
                        <div class="source">
                            ${renderSourceKind(route.source)}
                            <span title="${escapeHtml(route.source)}">${escapeHtml(route.source)}</span>
                        </div>
                    </td>
                    <td>
                        <div class="actions">
                            ${canOpen(route)
                                ? `<a href="${escapeHtml(route.uri)}" target="_blank" rel="noopener" class="action" title="Open in new tab">${icon('external')}</a>`
                                : ''}
                            <button type="button" data-copy class="action" title="Copy path">${icon('copy')}</button>
                        </div>
                    </td>
                `;

                const copyButton = tr.querySelector('[data-copy]');
                copyButton.addEventListener('click', () => copyToClipboard(route.uri, copyButton));
                tableBody.appendChild(tr);
            });
        }

        function switchView(view) {
            currentView = view;
            Object.entries(tabs).forEach(([key, tab]) => tab.setAttribute('aria-selected', String(key === view)));
            routeTitle.textContent = routeSets[view].title;
            searchInput.value = '';
            renderRoutes();
        }

        searchInput.addEventListener('input', renderRoutes);
        tabs.api.addEventListener('click', () => switchView('api'));
        tabs.web.addEventListener('click', () => switchView('web'));

        renderRoutes();
    </script>
</body>

</html>
