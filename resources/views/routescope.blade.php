<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>RouteScope</title>

    <style>
        :root {
            --bg: #fafafa;
            --surface: #fff;
            --hover: #f4f4f5;
            --line: #e4e4e7;
            --text: #18181b;
            --muted: #71717a;
            --faint: #a1a1aa;
            --accent: #2563eb;
            --get: #2563eb;
            --post: #16a34a;
            --put: #d97706;
            --patch: #7c3aed;
            --delete: #dc2626;
            --sans: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #09090b;
                --surface: #0f0f11;
                --hover: #18181b;
                --line: #27272a;
                --text: #f4f4f5;
                --muted: #a1a1aa;
                --faint: #52525b;
                --accent: #60a5fa;
                --get: #60a5fa;
                --post: #4ade80;
                --put: #fbbf24;
                --patch: #a78bfa;
                --delete: #f87171;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font: 14px/1.5 var(--sans);
            -webkit-font-smoothing: antialiased;
        }

        .page { max-width: 1040px; margin: 0 auto; padding: 48px 24px 64px; }

        header { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
        h1 { margin: 0; font-size: 18px; font-weight: 600; letter-spacing: -0.01em; }
        .summary { color: var(--muted); font-size: 13px; }

        .toolbar { display: flex; gap: 12px; margin-bottom: 16px; }

        .search { position: relative; flex: 1; }

        .search input {
            width: 100%;
            height: 36px;
            padding: 0 40px 0 12px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--text);
            font: inherit;
        }

        .search input::placeholder { color: var(--faint); }
        .search input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }

        kbd {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            padding: 0 6px;
            border: 1px solid var(--line);
            border-radius: 4px;
            color: var(--faint);
            font: 11px/18px var(--mono);
        }

        .search input:focus + kbd { display: none; }

        .segmented { display: flex; padding: 3px; background: var(--hover); border-radius: 8px; }

        .segmented button {
            height: 30px;
            padding: 0 12px;
            border: 0;
            border-radius: 6px;
            background: none;
            color: var(--muted);
            font: inherit;
            font-size: 13px;
            cursor: pointer;
        }

        .segmented button:hover { color: var(--text); }
        .segmented button[aria-pressed="true"] { background: var(--surface); color: var(--text); box-shadow: 0 1px 2px rgb(0 0 0 / .08); }
        .segmented .n { margin-left: 6px; color: var(--faint); font-variant-numeric: tabular-nums; }

        .list { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }

        .route + .route { border-top: 1px solid var(--line); }
        .group + .group { border-top: 1px solid var(--line); }

        .group-head {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 16px;
            border: 0;
            border-bottom: 1px solid var(--line);
            background: var(--bg);
            color: var(--text);
            font: 600 12px var(--mono);
            text-align: left;
            cursor: pointer;
        }

        .group-head:hover { background: var(--hover); }
        .group-head:focus-visible { outline: 2px solid var(--accent); outline-offset: -2px; }
        .group-head .count { color: var(--faint); font: 400 12px var(--sans); font-variant-numeric: tabular-nums; }
        .chevron { width: 12px; height: 12px; color: var(--faint); transition: transform .15s; }
        .group.collapsed .chevron { transform: rotate(-90deg); }
        .group.collapsed .group-head { border-bottom: 0; }
        .group.collapsed .group-body { display: none; }
        .path .prefix { color: var(--faint); }

        .row {
            display: grid;
            grid-template-columns: 72px minmax(0, 1fr) auto;
            align-items: center;
            gap: 16px;
            width: 100%;
            padding: 12px 16px;
            border: 0;
            background: none;
            color: inherit;
            font: inherit;
            text-align: left;
            cursor: pointer;
        }

        .row:hover, .route.open .row { background: var(--hover); }
        .row:focus-visible { outline: 2px solid var(--accent); outline-offset: -2px; }

        .methods { display: flex; flex-direction: column; gap: 2px; font: 600 11px/1.4 var(--mono); letter-spacing: .02em; }
        .m-GET { color: var(--get); }
        .m-POST { color: var(--post); }
        .m-PUT { color: var(--put); }
        .m-PATCH { color: var(--patch); }
        .m-DELETE { color: var(--delete); }
        .m-other { color: var(--muted); }

        .main { min-width: 0; }
        .path { overflow: hidden; font: 13px var(--mono); text-overflow: ellipsis; white-space: nowrap; }
        .path .param { color: var(--accent); }
        .meta { overflow: hidden; margin-top: 2px; color: var(--muted); font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .meta .sep { margin: 0 6px; color: var(--faint); }

        .side { display: flex; align-items: center; gap: 12px; }
        .name { color: var(--faint); font: 12px var(--mono); white-space: nowrap; }

        .actions { display: flex; gap: 2px; opacity: 0; transition: opacity .1s; }
        .row:hover .actions, .row:focus-within .actions, .route.open .actions { opacity: 1; }

        .action {
            display: inline-flex;
            padding: 6px;
            border: 0;
            border-radius: 6px;
            background: none;
            color: var(--muted);
            cursor: pointer;
        }

        .action:hover { background: var(--line); color: var(--text); }
        .action.done { color: var(--post); }
        .icon { width: 14px; height: 14px; }

        .details { display: none; padding: 4px 16px 16px 104px; background: var(--hover); }
        .route.open .details { display: block; }

        dl { display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 8px 16px; margin: 0; font-size: 12px; }
        dt { color: var(--muted); }
        dd { margin: 0; overflow-wrap: anywhere; font-family: var(--mono); }

        .chip {
            display: inline-block;
            margin: 0 4px 4px 0;
            padding: 1px 6px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 4px;
        }

        .none { color: var(--faint); }
        .empty { padding: 48px 16px; color: var(--muted); text-align: center; }

        @media (max-width: 640px) {
            .page { padding: 24px 16px 48px; }
            header { flex-direction: column; gap: 4px; }
            .toolbar { flex-wrap: wrap; gap: 8px; }
            .search { flex-basis: 100%; }
            .row { grid-template-columns: 56px minmax(0, 1fr) auto; gap: 12px; padding: 12px; }
            .name { display: none; }
            .actions { opacity: 1; }
            .details { padding: 4px 12px 16px; }
            dl { grid-template-columns: 1fr; gap: 2px; }
            dd { margin-bottom: 8px; }
        }
    </style>
</head>

<body>
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none">
        <symbol id="icon-copy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="14" height="14" x="8" y="8" rx="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
        </symbol>
        <symbol id="icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5" />
        </symbol>
        <symbol id="icon-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6" />
        </symbol>
        <symbol id="icon-external" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h6v6" /><path d="M10 14 21 3" /><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
        </symbol>
    </svg>

    <div class="page">
        <header>
            <h1>RouteScope</h1>
            <span class="summary" id="summary"></span>
        </header>

        <div class="toolbar">
            <div class="search">
                <input id="search" type="search" placeholder="Filter routes" autocomplete="off" spellcheck="false" aria-label="Filter routes">
                <kbd>/</kbd>
            </div>
            <div class="segmented" role="group" aria-label="Route type">
                <button type="button" data-view="all" aria-pressed="true">All<span class="n" data-count="all"></span></button>
                <button type="button" data-view="api" aria-pressed="false">API<span class="n" data-count="api"></span></button>
                <button type="button" data-view="web" aria-pressed="false">Web<span class="n" data-count="web"></span></button>
            </div>
            <div class="segmented" role="group" aria-label="Layout">
                <button type="button" data-layout="grouped" aria-pressed="true">Grouped</button>
                <button type="button" data-layout="flat" aria-pressed="false">Flat</button>
            </div>
        </div>

        <div class="list" id="list"></div>
    </div>

    <script>
        const routes = {
            api: @json($apiRoutes),
            web: @json($webRoutes),
        };
        routes.all = [...routes.api, ...routes.web].sort((a, b) => a.uri.localeCompare(b.uri));

        const KNOWN_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        const list = document.getElementById('list');
        const search = document.getElementById('search');
        const summary = document.getElementById('summary');
        const viewButtons = document.querySelectorAll('[data-view]');

        const layoutButtons = document.querySelectorAll('[data-layout]');
        const collapsed = new Set();

        let view = 'all';
        let layout = readPreference('routescope.layout', 'grouped');

        function readPreference(key, fallback) {
            try {
                return localStorage.getItem(key) ?? fallback;
            } catch (e) {
                return fallback;
            }
        }

        function writePreference(key, value) {
            try {
                localStorage.setItem(key, value);
            } catch (e) {
                // Storage can be unavailable (private mode, blocked site data); the default is fine.
            }
        }

        // Escape untrusted values before inserting them into HTML
        function esc(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function icon(name) {
            return `<svg class="icon" aria-hidden="true"><use href="#icon-${name}" /></svg>`;
        }

        function chips(values) {
            return values.length
                ? values.map(v => `<span class="chip">${esc(v)}</span>`).join('')
                : '<span class="none">None</span>';
        }

        // Highlight {parameters} in the path
        function renderPath(uri, prefix = '') {
            const highlight = text => esc(text).replace(/\{[^}]+\}/g, match => `<span class="param">${match}</span>`);
            const dimmed = prefix.length > 1 && uri.startsWith(prefix) ? prefix : '';

            return (dimmed ? `<span class="prefix">${highlight(dimmed)}</span>` : '') + highlight(uri.slice(dimmed.length));
        }

        function renderMeta(route) {
            const parts = [esc(route.source)];

            if (route.middleware.length) {
                parts.push(esc(route.middleware.join(', ')));
            }

            if (route.domain) {
                parts.push(esc(route.domain));
            }

            return parts.join('<span class="sep">·</span>');
        }

        function renderParameters(parameters) {
            if (!parameters.length) {
                return '<span class="none">None</span>';
            }

            return parameters.map(p => {
                const label = p.name + (p.optional ? '?' : '') + (p.pattern ? ` = ${p.pattern}` : '');
                return `<span class="chip">${esc(label)}</span>`;
            }).join('');
        }

        function renderDetails(route) {
            const location = route.file ? `${route.file}${route.line ? ':' + route.line : ''}` : null;

            return `
                <dl>
                    <dt>Action</dt><dd>${esc(route.action)}</dd>
                    ${location ? `<dt>Defined in</dt><dd>${esc(location)}</dd>` : ''}
                    <dt>Name</dt><dd>${route.name ? esc(route.name) : '<span class="none">Unnamed</span>'}</dd>
                    <dt>Parameters</dt><dd>${renderParameters(route.parameters)}</dd>
                    <dt>Middleware</dt><dd>${chips(route.middleware)}</dd>
                    <dt>Resolved middleware</dt><dd>${chips(route.resolved_middleware)}</dd>
                </dl>
            `;
        }

        // Only GET routes without parameters can be opened directly
        function canOpen(route) {
            return route.methods.includes('GET') && route.parameters.length === 0;
        }

        async function copy(text, button) {
            try {
                await navigator.clipboard.writeText(text);
                button.classList.add('done');
                button.innerHTML = icon('check');
                setTimeout(() => {
                    button.classList.remove('done');
                    button.innerHTML = icon('copy');
                }, 1200);
            } catch (e) {
                window.prompt('Copy path:', text);
            }
        }

        function matches(route, query) {
            return [route.uri, route.source, route.action, route.name ?? '', ...route.methods, ...route.middleware]
                .some(value => value.toLowerCase().includes(query));
        }

        // "/api/v1/auth/login" → base "/api/v1", key "/api/v1/auth". Leading "api" and
        // version segments ("v1", "v2") are kept in the base rather than treated as groups.
        function prefixOf(uri) {
            const segments = uri.split('/').filter(Boolean);
            const base = [];

            while (segments.length && /^(api|v\d+)$/i.test(segments[0])) {
                base.push(segments.shift());
            }

            const basePath = '/' + base.join('/');
            const next = segments[0];

            // The base itself, or a parameter right after it, stays in the base group
            if (!next || next.startsWith('{')) {
                return { key: basePath, base: basePath };
            }

            return { key: (basePath === '/' ? '' : basePath) + '/' + next, base: basePath };
        }

        // Group routes by prefix, folding single-route groups into their base so the list
        // isn't a wall of one-row groups.
        function groupRoutes(allRoutes) {
            const prefixes = new Map(allRoutes.map(route => [route, prefixOf(route.uri)]));
            const sizes = new Map();

            prefixes.forEach(({ key }) => sizes.set(key, (sizes.get(key) ?? 0) + 1));

            const groups = new Map();

            allRoutes.forEach(route => {
                const { key, base } = prefixes.get(route);
                const groupKey = sizes.get(key) > 1 ? key : base;

                if (!groups.has(groupKey)) groups.set(groupKey, []);
                groups.get(groupKey).push(route);
            });

            return [...groups.entries()].sort(([a], [b]) => a.localeCompare(b));
        }

        function renderGroup(key, groupRoutes, forceOpen) {
            const section = document.createElement('section');
            section.className = 'group' + (collapsed.has(key) && !forceOpen ? ' collapsed' : '');

            const head = document.createElement('button');
            head.type = 'button';
            head.className = 'group-head';
            head.setAttribute('aria-expanded', String(!section.classList.contains('collapsed')));
            head.innerHTML = `<svg class="chevron" aria-hidden="true"><use href="#icon-chevron" /></svg>${esc(key)}<span class="count">${groupRoutes.length}</span>`;
            head.addEventListener('click', () => {
                const isCollapsed = section.classList.toggle('collapsed');
                head.setAttribute('aria-expanded', String(!isCollapsed));
                isCollapsed ? collapsed.add(key) : collapsed.delete(key);
            });

            const body = document.createElement('div');
            body.className = 'group-body';
            body.replaceChildren(...groupRoutes.map(route => renderRoute(route, key)));

            section.replaceChildren(head, body);

            return section;
        }

        function renderRoute(route, prefix = '') {
            const item = document.createElement('div');
            item.className = 'route';

            const methods = route.methods.map(m =>
                `<span class="m-${KNOWN_METHODS.includes(m) ? m : 'other'}">${esc(m)}</span>`
            ).join('');

            item.innerHTML = `
                <div class="row" role="button" tabindex="0" aria-expanded="false">
                    <div class="methods">${methods}</div>
                    <div class="main">
                        <div class="path">${renderPath(route.uri, prefix)}</div>
                        <div class="meta">${renderMeta(route)}</div>
                    </div>
                    <div class="side">
                        ${route.name ? `<span class="name">${esc(route.name)}</span>` : ''}
                        <div class="actions">
                            ${canOpen(route) ? `<a class="action" href="${esc(route.uri)}" target="_blank" rel="noopener" title="Open in new tab">${icon('external')}</a>` : ''}
                            <button type="button" class="action" data-copy title="Copy path">${icon('copy')}</button>
                        </div>
                    </div>
                </div>
                <div class="details">${renderDetails(route)}</div>
            `;

            const row = item.querySelector('.row');
            const toggle = () => {
                const open = item.classList.toggle('open');
                row.setAttribute('aria-expanded', String(open));
            };

            row.addEventListener('click', event => {
                if (!event.target.closest('.action')) toggle();
            });
            row.addEventListener('keydown', event => {
                if ((event.key === 'Enter' || event.key === ' ') && event.target === row) {
                    event.preventDefault();
                    toggle();
                }
            });

            const copyButton = item.querySelector('[data-copy]');
            copyButton.addEventListener('click', () => copy(route.uri, copyButton));

            return item;
        }

        function render() {
            const query = search.value.trim().toLowerCase();
            const visible = routes[view].filter(route => matches(route, query));

            summary.textContent = query
                ? `${visible.length} of ${routes[view].length} routes`
                : `${routes.all.length} routes`;

            if (layout === 'grouped') {
                // Group the whole view, then filter, so groups don't reshuffle while typing
                const shown = new Set(visible);
                const groups = groupRoutes(routes[view])
                    .map(([key, groupRoutes]) => [key, groupRoutes.filter(route => shown.has(route))])
                    .filter(([, groupRoutes]) => groupRoutes.length);

                list.replaceChildren(...groups.map(([key, groupRoutes]) => renderGroup(key, groupRoutes, query !== '')));
            } else {
                list.replaceChildren(...visible.map(route => renderRoute(route)));
            }

            if (!visible.length) {
                list.innerHTML = `<div class="empty">${query ? 'No routes match your filter' : 'No routes'}</div>`;
            }
        }

        document.querySelectorAll('[data-count]').forEach(el => {
            el.textContent = routes[el.dataset.count].length;
        });

        viewButtons.forEach(button => button.addEventListener('click', () => {
            view = button.dataset.view;
            viewButtons.forEach(b => b.setAttribute('aria-pressed', String(b === button)));
            render();
        }));

        function setLayout(value) {
            layout = value === 'flat' ? 'flat' : 'grouped';
            layoutButtons.forEach(b => b.setAttribute('aria-pressed', String(b.dataset.layout === layout)));
        }

        layoutButtons.forEach(button => button.addEventListener('click', () => {
            setLayout(button.dataset.layout);
            writePreference('routescope.layout', layout);
            render();
        }));

        setLayout(layout);

        search.addEventListener('input', render);

        // "/" focuses the filter, Escape clears it
        document.addEventListener('keydown', event => {
            if (event.key === '/' && document.activeElement !== search) {
                event.preventDefault();
                search.focus();
            } else if (event.key === 'Escape' && document.activeElement === search) {
                search.value = '';
                search.blur();
                render();
            }
        });

        render();
    </script>
</body>

</html>
