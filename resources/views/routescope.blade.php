<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RouteScope</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Custom scrollbar for a cleaner look */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #000;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #444;
        }

        body {
            background-color: #000000;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: '#050505',
                        card: '#0A0A0A',
                        border: '#1F1F1F',
                        'post-text': '#4ade80',
                        'post-bg': 'rgba(74, 222, 128, 0.1)',
                        'post-border': 'rgba(74, 222, 128, 0.2)',
                        'get-text': '#60a5fa',
                        'get-bg': 'rgba(96, 165, 250, 0.1)',
                        'get-border': 'rgba(96, 165, 250, 0.2)',
                        'put-text': '#f59e0b',
                        'put-bg': 'rgba(245, 158, 11, 0.1)',
                        'put-border': 'rgba(245, 158, 11, 0.2)',
                        'delete-text': '#ef4444',
                        'delete-bg': 'rgba(239, 68, 68, 0.1)',
                        'delete-border': 'rgba(239, 68, 68, 0.2)',
                        'patch-text': '#a78bfa',
                        'patch-bg': 'rgba(167, 139, 250, 0.1)',
                        'patch-border': 'rgba(167, 139, 250, 0.2)',
                    }
                }
            }
        }
    </script>
</head>

<body class="text-gray-300 p-8 min-h-screen">

    <div class="flex justify-center mb-8">
        <div class="bg-[#111] border border-gray-800 rounded-full p-1 flex items-center text-sm font-medium">
            <button id="api-tab"
                class="px-4 py-1.5 rounded-full bg-[#1A1A1A] text-white border border-gray-700 shadow-sm transition-all">
                API Routes
            </button>
            <button id="page-tab" class="px-4 py-1.5 rounded-full text-gray-500 hover:text-gray-300 transition-all">
                Web Routes
            </button>
        </div>
    </div>

    <div class="max-w-6xl mx-auto border border-border rounded-xl bg-card overflow-hidden">

        <div class="p-6 pb-2">
            <div class="flex items-center gap-3 mb-1">
                <h1 id="route-title" class="text-2xl font-bold text-white tracking-tight">API Routes</h1>
                <span id="route-count"
                    class="bg-[#1F1F1F] text-gray-400 text-xs px-2 py-0.5 rounded-full border border-gray-800">{{ count($apiRoutes) }}</span>
            </div>
            <p class="text-gray-500 text-sm">Manage and inspect your API endpoints</p>
        </div>

        <div class="px-6 py-4 flex items-center justify-between">
            <div class="relative w-full max-w-sm group">
                <i data-lucide="search"
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within:text-gray-300 transition-colors"></i>
                <input id="search-input" type="text" placeholder="Search path, name, middleware..."
                    class="w-full bg-[#050505] border border-border rounded-lg py-2 pl-10 pr-4 text-sm text-gray-300 placeholder-gray-600 focus:outline-none focus:border-gray-600 transition-colors">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-border text-xs uppercase text-gray-500 font-medium">
                        <th class="px-6 py-3 w-24">Methods</th>
                        <th class="px-6 py-3">Path <i data-lucide="arrow-up"
                                class="inline w-3 h-3 ml-1 align-middle"></i></th>
                        <th class="px-6 py-3">Middleware</th>
                        <th class="px-6 py-3">Source</th>
                        <th class="px-6 py-3 w-32 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="routes-table-body" class="text-sm">
                </tbody>
            </table>
        </div>

        <div class="h-4"></div>
    </div>

    <script>
        // Data from Laravel controller
        const apiRoutes = @json($apiRoutes);
        const pageRoutes = @json($webRoutes);

        let currentRoutes = apiRoutes;
        let currentView = 'api';

        const tableBody = document.getElementById('routes-table-body');
        const searchInput = document.getElementById('search-input');
        const apiTab = document.getElementById('api-tab');
        const pageTab = document.getElementById('page-tab');
        const routeTitle = document.getElementById('route-title');
        const routeCount = document.getElementById('route-count');

        // Method badge colors
        function getMethodBadgeClass(method) {
            const classes = {
                'POST': 'text-post-text bg-post-bg border-post-border',
                'GET': 'text-get-text bg-get-bg border-get-border',
                'PUT': 'text-put-text bg-put-bg border-put-border',
                'PATCH': 'text-patch-text bg-patch-bg border-patch-border',
                'DELETE': 'text-delete-text bg-delete-bg border-delete-border',
            };
            return classes[method] || 'text-gray-400 bg-gray-800/40 border-gray-700';
        }

        // Escape untrusted values before inserting them into HTML
        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        // Get file extension badge
        function getFileExtensionBadge(source) {
            if (source === 'Closure') {
                return '<div class="w-4 h-4 bg-purple-900/40 border border-purple-500/30 rounded-[3px] flex items-center justify-center"><span class="text-[8px] font-bold text-purple-400">λ</span></div>';
            }

            if (source.includes('::')) {
                return '<div class="w-4 h-4 bg-indigo-900/40 border border-indigo-500/30 rounded-[3px] flex items-center justify-center"><span class="text-[8px] font-bold text-indigo-400">PHP</span></div>';
            }

            return '<div class="w-4 h-4 bg-gray-800/40 border border-gray-600/30 rounded-[3px] flex items-center justify-center"><span class="text-[8px] font-bold text-gray-400">•</span></div>';
        }

        function renderMiddleware(middleware) {
            if (!middleware.length) {
                return '<span class="text-gray-700">—</span>';
            }

            return middleware.map(m =>
                `<span class="inline-block text-[10px] font-mono px-1.5 py-0.5 mr-1 mb-1 rounded border border-gray-800 bg-[#111] text-gray-400">${escapeHtml(m)}</span>`
            ).join('');
        }

        // Only GET routes without parameters can be opened directly
        function canOpen(route) {
            return route.method === 'GET' && !route.path.includes('{');
        }

        async function copyToClipboard(text, button) {
            try {
                await navigator.clipboard.writeText(text);
                button.classList.add('text-green-400');
                setTimeout(() => button.classList.remove('text-green-400'), 1000);
            } catch (e) {
                window.prompt('Copy path:', text);
            }
        }

        // Render routes
        function renderRoutes(routes) {
            tableBody.innerHTML = '';

            if (routes.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No routes found
                        </td>
                    </tr>
                `;
                return;
            }

            routes.forEach(route => {
                const tr = document.createElement('tr');
                tr.className =
                    'border-b border-border hover:bg-[#111] transition-colors group cursor-default last:border-0 align-top';

                // Path highlighting (last segment white, rest gray)
                const pathParts = route.path.split('/');
                const lastPart = pathParts.pop();
                const prefix = pathParts.join('/') + '/';

                tr.innerHTML = `
                    <td class="px-6 py-4">
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border ${getMethodBadgeClass(route.method)}">
                            ${escapeHtml(route.method)}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-mono text-gray-500">
                        ${escapeHtml(prefix)}<span class="text-white font-medium">${escapeHtml(lastPart)}</span>
                        ${route.name ? `<div class="text-xs text-gray-600 mt-1">${escapeHtml(route.name)}</div>` : ''}
                    </td>
                    <td class="px-6 py-4">${renderMiddleware(route.middleware)}</td>
                    <td class="px-6 py-4 text-gray-500">
                        <div class="flex items-center gap-2">
                            ${getFileExtensionBadge(route.source)}
                            <span class="truncate max-w-[300px]" title="${escapeHtml(route.source)}">${escapeHtml(route.source)}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3 text-gray-500">
                            ${canOpen(route)
                                ? `<a href="${escapeHtml(route.path)}" target="_blank" rel="noopener" class="hover:text-white transition-colors" title="Open in new tab"><i data-lucide="external-link" class="w-4 h-4"></i></a>`
                                : ''}
                            <button data-copy class="hover:text-blue-400 transition-colors" title="Copy path"><i data-lucide="copy" class="w-4 h-4"></i></button>
                        </div>
                    </td>
                `;
                const copyButton = tr.querySelector('[data-copy]');
                copyButton.addEventListener('click', () => copyToClipboard(route.path, copyButton));
                tableBody.appendChild(tr);
            });

            // Re-initialize Lucide icons
            lucide.createIcons();
        }

        // Search functionality
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = currentRoutes.filter(route => {
                return route.path.toLowerCase().includes(query) ||
                    route.method.toLowerCase().includes(query) ||
                    route.source.toLowerCase().includes(query) ||
                    (route.name ?? '').toLowerCase().includes(query) ||
                    route.middleware.some(m => m.toLowerCase().includes(query));
            });
            renderRoutes(filtered);
            routeCount.textContent = filtered.length;
        });

        // Tab switching
        apiTab.addEventListener('click', () => {
            currentView = 'api';
            currentRoutes = apiRoutes;
            apiTab.classList.add('bg-[#1A1A1A]', 'text-white', 'border', 'border-gray-700', 'shadow-sm');
            apiTab.classList.remove('text-gray-500');
            pageTab.classList.remove('bg-[#1A1A1A]', 'text-white', 'border', 'border-gray-700', 'shadow-sm');
            pageTab.classList.add('text-gray-500');
            routeTitle.textContent = 'API Routes';
            searchInput.value = '';
            renderRoutes(currentRoutes);
            routeCount.textContent = currentRoutes.length;
        });

        pageTab.addEventListener('click', () => {
            currentView = 'page';
            currentRoutes = pageRoutes;
            pageTab.classList.add('bg-[#1A1A1A]', 'text-white', 'border', 'border-gray-700', 'shadow-sm');
            pageTab.classList.remove('text-gray-500');
            apiTab.classList.remove('bg-[#1A1A1A]', 'text-white', 'border', 'border-gray-700', 'shadow-sm');
            apiTab.classList.add('text-gray-500');
            routeTitle.textContent = 'Web Routes';
            searchInput.value = '';
            renderRoutes(currentRoutes);
            routeCount.textContent = currentRoutes.length;
        });

        // Initial render
        renderRoutes(currentRoutes);

        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>

</html>
