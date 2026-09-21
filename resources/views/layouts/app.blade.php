<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WhatsApp Order & Shipping Label System')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 250px;
            --topbar-height: 60px;
            --primary-color: #25D366;
            --primary-dark: #128C7E;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .bg-indigo { background-color: #6610f2 !important; }
        .text-whatsapp { color: #128C7E !important; }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #1e293b;
            color: #f8fafc;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s;
        }

        #sidebar .brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            background: #0f172a;
            font-weight: 700;
            font-size: 1.1rem;
            color: #25D366;
            border-bottom: 1px solid #334155;
        }

        #sidebar .nav-link {
            color: #94a3b8;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        #sidebar .nav-link:hover, #sidebar .nav-link.active {
            color: #ffffff;
            background: #334155;
            border-left-color: #25D366;
        }

        #sidebar .submenu {
            padding-left: 2.25rem;
            background: #0f172a;
        }

        #sidebar .submenu .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Main Content Styling */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        #topbar {
            height: var(--topbar-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .page-content {
            padding: 1.5rem;
            flex: 1;
        }

        /* Metric Card styling */
        .metric-card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
        }

        .metric-card .card-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .metric-card .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* Toast Container */
        .toast-container-custom {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 1060;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <nav id="sidebar">
        <div class="brand">
            <i class="bi bi-whatsapp fs-4"></i>
            <span>Label Manager</span>
        </div>
        <div class="nav flex-column py-2">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <!-- Orders Section -->
            <a href="#ordersSubmenu" data-bs-toggle="collapse" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i> Orders <i class="bi bi-chevron-down ms-auto fs-7"></i>
            </a>
            <div class="collapse {{ request()->routeIs('orders.*') ? 'show' : '' }} submenu" id="ordersSubmenu">
                <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.index') && !request('tab') ? 'active' : '' }}">All Orders</a>
                <a href="{{ route('orders.index', ['tab' => 'NEW']) }}" class="nav-link {{ request('tab') === 'NEW' ? 'active' : '' }}">New Orders</a>
                <a href="{{ route('orders.index', ['tab' => 'VERIFIED']) }}" class="nav-link {{ request('tab') === 'VERIFIED' ? 'active' : '' }}">Pending Verification</a>
                <a href="{{ route('orders.index', ['tab' => 'ACCEPTED']) }}" class="nav-link {{ request('tab') === 'ACCEPTED' ? 'active' : '' }}">Accepted</a>
                <a href="{{ route('orders.index', ['tab' => 'REJECTED']) }}" class="nav-link {{ request('tab') === 'REJECTED' ? 'active' : '' }}">Rejected</a>
                <a href="{{ route('orders.index', ['tab' => 'COMPLETED']) }}" class="nav-link {{ request('tab') === 'COMPLETED' ? 'active' : '' }}">Completed</a>
            </div>

            <!-- WhatsApp Section -->
            <a href="#whatsappSubmenu" data-bs-toggle="collapse" class="nav-link {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                <i class="bi bi-whatsapp"></i> WhatsApp <i class="bi bi-chevron-down ms-auto fs-7"></i>
            </a>
            <div class="collapse {{ request()->routeIs('whatsapp.*') ? 'show' : '' }} submenu" id="whatsappSubmenu">
                <a href="{{ route('whatsapp.chats') }}" class="nav-link {{ request()->routeIs('whatsapp.chats') ? 'active' : '' }}">Chats</a>
                <a href="{{ route('whatsapp.messages') }}" class="nav-link {{ request()->routeIs('whatsapp.messages') ? 'active' : '' }}">Messages</a>
                <a href="{{ route('whatsapp.import') }}" class="nav-link {{ request()->routeIs('whatsapp.import') ? 'active' : '' }}">Import Messages</a>
            </div>

            <!-- Labels Section -->
            <a href="#labelsSubmenu" data-bs-toggle="collapse" class="nav-link {{ request()->routeIs('labels.*') ? 'active' : '' }}">
                <i class="bi bi-qr-code-scan"></i> Labels <i class="bi bi-chevron-down ms-auto fs-7"></i>
            </a>
            <div class="collapse {{ request()->routeIs('labels.*') ? 'show' : '' }} submenu" id="labelsSubmenu">
                <a href="{{ route('labels.index') }}" class="nav-link {{ request()->routeIs('labels.index') ? 'active' : '' }}">All Labels</a>
                <a href="{{ route('labels.unmatched') }}" class="nav-link {{ request()->routeIs('labels.unmatched') ? 'active' : '' }}">Pending Matching</a>
                <a href="{{ route('labels.import') }}" class="nav-link {{ request()->routeIs('labels.import') ? 'active' : '' }}">Import Labels</a>
            </div>

            <!-- Meesho RPA Bot -->
            <a href="{{ route('meesho.index') }}" class="nav-link {{ request()->routeIs('meesho.*') ? 'active' : '' }}">
                <i class="bi bi-robot text-warning"></i> Meesho RPA
            </a>

            <!-- Packing Mode -->
            <a href="{{ route('packing.index') }}" class="nav-link {{ request()->routeIs('packing.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Packing Mode
            </a>

            <!-- Reports Section -->
            <a href="#reportsSubmenu" data-bs-toggle="collapse" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports <i class="bi bi-chevron-down ms-auto fs-7"></i>
            </a>
            <div class="collapse {{ request()->routeIs('reports.*') ? 'show' : '' }} submenu" id="reportsSubmenu">
                <a href="{{ route('reports.orders') }}" class="nav-link {{ request()->routeIs('reports.orders') ? 'active' : '' }}">Order Report</a>
                <a href="{{ route('reports.labels') }}" class="nav-link {{ request()->routeIs('reports.labels') ? 'active' : '' }}">Label Report</a>
            </div>

            <!-- Settings -->
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Settings
            </a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div id="main-content">
        <header id="topbar">
            <div>
                <h5 class="m-0 fw-semibold text-dark">@yield('page-title', 'Dashboard')</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark border"><i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name ?? 'Admin' }}</span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</button>
                </form>
            </div>
        </header>

        <main class="page-content">
            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Floating Toast Container -->
    <div class="toast-container-custom" id="toastContainer"></div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Helper Toast JS -->
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const bgClass = type === 'success' ? 'bg-success text-white' : 'bg-danger text-white';
            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center ${bgClass} border-0 show shadow mb-2`;
            toastEl.setAttribute('role', 'alert');
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-octagon'} me-2"></i> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            container.appendChild(toastEl);
            setTimeout(() => {
                toastEl.remove();
            }, 4000);
        }
    </script>
    @stack('scripts')
</body>
</html>
