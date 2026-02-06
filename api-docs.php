<?php
/*
 * API Documentation for Paste
 *
 * Paste $v3.4 2026/02/01 https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License in LICENCE for more details.
 */
 

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get site info for base URL and name
try {
    $stmt = $pdo->query("SELECT baseurl, site_name FROM site_info WHERE id = 1");
    $site = $stmt->fetch() ?: [];
    $baseurl = rtrim($site['baseurl'] ?? '', '/');
    $site_name = $site['site_name'] ?? 'Paste';
} catch (PDOException $e) {
    $baseurl = rtrim(($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    $site_name = 'Paste';
}

$api_url = $baseurl . '/api.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - <?= htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">

    <style>
        :root {
            --bg: #0f1115;
            --card-bg: #141821;
            --card-border: #1f2633;
            --text: #e6edf3;
            --muted: #7f8da3;
            --border: #1f2633;
            --accent: #0d6efd;
            --success: #3fb950;
            --danger: #f85149;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .navbar {
            background: #121826 !important;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .offcanvas.offcanvas-start {
            width: 280px;
            background: #0f1523;
            border-right: 1px solid var(--border);
        }

        .offcanvas-title {
            color: var(--text);
        }

        .sidebar-desktop {
            position: sticky;
            top: 3rem;
            background: #121826;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            height: calc(100vh - 2rem);
            overflow-y: auto;
        }

        .list-group-item {
            background: transparent;
            color: #dbe5f5;
            border: 0;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            margin-bottom: 0.25rem;
        }

        .list-group-item:hover {
            background: #0e1422;
        }

        .list-group-item.active {
            background: var(--accent);
            color: white;
            font-weight: 500;
        }

        .list-group-item.text-muted {
            background: transparent;
            color: var(--muted);
            padding-left: 1rem;
            cursor: default;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        h1, h2, h3, h4, h5, h6 {
            color: #ffffff;
        }

        .lead {
            color: #c6d4f0;
        }

        pre {
            background: #0e1422 !important;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
            overflow-x: auto;
            position: relative;
        }

        code {
            font-size: 0.9rem;
        }

        .method-badge {
            font-family: monospace;
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 6px;
            font-size: 0.82rem;
            vertical-align: middle;
        }

        .method-get    { background: rgba(63,185,80,0.18);  color: var(--success); }
        .method-post   { background: rgba(13,110,253,0.18); color: var(--accent);  }
        .method-put    { background: rgba(210,153,34,0.18); color: #d29922;      }
        .method-delete { background: rgba(248,81,73,0.18);  color: var(--danger); }

        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text);
            --bs-table-border-color: var(--border);
        }

        .table thead th {
            background: #101521;
            color: #c6d4f0;
            border-bottom: 2px solid var(--border);
        }

        .required { color: var(--danger); font-weight: 600; }
        .optional { color: var(--muted); }

        .response-example { border-left: 4px solid var(--success); padding-left: 1rem; }
        .error-example   { border-left: 4px solid var(--danger);  padding-left: 1rem; }

        .endpoint {
            scroll-margin-top: 90px;
        }

        @media (max-width: 991.98px) {
            .sidebar-desktop { display: none !important; }
        }

    </style>
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="<?= htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') ?>
        </a>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <span class="navbar-text text-muted">
                API Documentation
            </span>

            <button class="btn btn-outline-secondary d-lg-none" 
                    type="button" 
                    data-bs-toggle="offcanvas" 
                    data-bs-target="#sidebarOffcanvas" 
                    aria-controls="sidebarOffcanvas">
                <i class="bi bi-list fs-4"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">API Navigation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group" id="mobileNav">
            <a class="list-group-item active" href="#overview" data-bs-dismiss="offcanvas">Overview</a>
            <a class="list-group-item" href="#authentication" data-bs-dismiss="offcanvas">Authentication</a>
            <a class="list-group-item" href="#rate-limits" data-bs-dismiss="offcanvas">Rate Limits</a>

            <div class="list-group-item text-muted mt-3"><small>ENDPOINTS</small></div>
            <a class="list-group-item" href="#create-paste" data-bs-dismiss="offcanvas">Create Paste</a>
            <a class="list-group-item" href="#get-paste" data-bs-dismiss="offcanvas">Get Paste</a>
            <a class="list-group-item" href="#update-paste" data-bs-dismiss="offcanvas">Update Paste</a>
            <a class="list-group-item" href="#delete-paste" data-bs-dismiss="offcanvas">Delete Paste</a>
            <a class="list-group-item" href="#list-pastes" data-bs-dismiss="offcanvas">List Pastes</a>
            <a class="list-group-item" href="#search-pastes" data-bs-dismiss="offcanvas">Search Pastes</a>
            <a class="list-group-item" href="#user-info" data-bs-dismiss="offcanvas">User Info</a>

            <div class="list-group-item text-muted mt-3"><small>RESOURCES</small></div>
            <a class="list-group-item" href="#errors" data-bs-dismiss="offcanvas">Error Codes</a>
            <a class="list-group-item" href="#examples" data-bs-dismiss="offcanvas">Code Examples</a>
            <a class="list-group-item" href="docs/paste_client.py" download>
                <i class="bi bi-download me-1"></i>Python Client
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-3 px-md-4 py-4">
    <div class="row g-4">
        <!-- Desktop Sidebar -->
        <div class="col-lg-3 col-xl-2 d-none d-lg-block">
            <div class="sidebar-desktop">
                <div class="list-group" id="desktopNav">
                    <a class="list-group-item active" href="#overview">Overview</a>
                    <a class="list-group-item" href="#authentication">Authentication</a>
                    <a class="list-group-item" href="#rate-limits">Rate Limits</a>

                    <div class="list-group-item text-muted mt-3"><small>ENDPOINTS</small></div>
                    <a class="list-group-item" href="#create-paste">Create Paste</a>
                    <a class="list-group-item" href="#get-paste">Get Paste</a>
                    <a class="list-group-item" href="#update-paste">Update Paste</a>
                    <a class="list-group-item" href="#delete-paste">Delete Paste</a>
                    <a class="list-group-item" href="#list-pastes">List Pastes</a>
                    <a class="list-group-item" href="#search-pastes">Search Pastes</a>
                    <a class="list-group-item" href="#user-info">User Info</a>

                    <div class="list-group-item text-muted mt-3"><small>RESOURCES</small></div>
                    <a class="list-group-item" href="#errors">Error Codes</a>
                    <a class="list-group-item" href="#examples">Code Examples</a>
                    <a class="list-group-item" href="docs/paste_client.py" download>
                        <i class="bi bi-download me-1"></i>Python Client
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <div class="card">
                <div class="card-body p-4 p-lg-5">

                    <!-- Overview -->
                    <section id="overview" class="endpoint mb-5">
                        <h1 class="mb-4"><?= htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') ?> API Documentation</h1>
                        <p class="lead mb-4">The <?= htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') ?> API allows you to programmatically create, retrieve, update, and delete pastes.</p>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Base URL</h5>
                                <code class="fs-6 d-block p-3 bg-dark border border-secondary rounded">
                                    <?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>
                                </code>
                            </div>
                        </div>

                        <div class="alert alert-info border-0">
                            <i class="bi bi-info-circle me-2"></i>
                            All API responses are in JSON format. Set <code>Accept: application/json</code> header for best results.
                        </div>
                    </section>

                    <!-- Authentication -->
                    <section id="authentication" class="endpoint mb-5">
                        <h2 class="mb-4"><i class="bi bi-key me-2"></i>Authentication</h2>
                        <p>All authenticated endpoints require an API key. You can generate and manage API keys from your <a href="<?= htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8') ?>/profile.php">profile page</a>.</p>

                        <h5 class="mt-4 mb-3">Authentication Methods</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="bi bi-shield-check me-1"></i>Header (Recommended)</h6>
                                        <pre class="mb-0"><code>X-API-Key: your_api_key_here</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="bi bi-link-45deg me-1"></i>Query Parameter</h6>
                                        <pre class="mb-0"><code>?api_key=your_api_key_here</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="bi bi-body-text me-1"></i>POST Body</h6>
                                        <pre class="mb-0"><code>{"api_key": "your_api_key_here"}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Rate Limits -->
                    <section id="rate-limits" class="endpoint mb-5">
                        <h2 class="mb-4"><i class="bi bi-speedometer me-2"></i>Rate Limits</h2>
                        <p>API usage is rate-limited to ensure fair access:</p>
                        <ul class="mb-0">
                            <li><strong>60 requests per minute</strong> per API key</li>
                            <li>Rate limit window resets every 60 seconds</li>
                        </ul>
                        <div class="alert alert-warning mt-3 mb-0">
                            Exceeding the limit returns <code>429 Too Many Requests</code>.
                        </div>
                    </section>

                    <!-- Create Paste -->
                    <section id="create-paste" class="endpoint mb-5">
                        <h2 class="mb-4">
                            <span class="method-badge method-post me-2">POST</span>
                            Create Paste
                        </h2>
                        <p>Create a new paste with the provided content and optional settings.</p>

                        <h5 class="mt-4 mb-3">Endpoint</h5>
                        <pre><code>POST <?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=paste</code></pre>

                        <h5 class="mt-4 mb-3">Parameters</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>content</code></td><td>string</td><td class="required">Required</td><td>The main paste content</td></tr>
                                    <tr><td><code>title</code></td><td>string</td><td class="optional">Optional</td><td>Paste title (default: "Untitled")</td></tr>
                                    <tr><td><code>syntax</code></td><td>string</td><td class="optional">Optional</td><td>Syntax highlighting (e.g. python, javascript, php)</td></tr>
                                    <tr><td><code>visibility</code></td><td>string</td><td class="optional">Optional</td><td>"public", "unlisted", or "private"</td></tr>
                                    <tr><td><code>expiry</code></td><td>string</td><td class="optional">Optional</td><td>"10M", "1H", "1D", "1W", "2W", "1M", "never"</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4 mb-3">Example Request</h5>
                        <pre><code class="language-bash">curl -X POST "<?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=paste" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "print(\"Hello, World!\")",
    "title": "My Python Script",
    "syntax": "python",
    "visibility": "public"
  }'</code></pre>

                        <h5 class="mt-4 mb-3">Example Response</h5>
                        <pre class="response-example"><code class="language-json">{
  "success": true,
  "paste": {
    "id": 123,
    "slug": "aBcDeFgH",
    "url": "<?= htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8') ?>/aBcDeFgH",
    "title": "My Python Script",
    "syntax": "python",
    "visibility": "public",
    "created_at": "2026-02-03 14:30:00"
  }
}</code></pre>
                    </section>

                    <!-- Get Paste -->
                    <section id="get-paste" class="endpoint mb-5">
                        <h2 class="mb-4">
                            <span class="method-badge method-get me-2">GET</span>
                            Get Paste
                        </h2>
                        <p>Retrieve an existing paste by its numeric ID or slug.</p>

                        <h5 class="mt-4 mb-3">Endpoint</h5>
                        <pre><code>GET <?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=get&id={id_or_slug}</code></pre>

                        <h5 class="mt-4 mb-3">Parameters</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>id</code></td><td>string</td><td class="required">Required</td><td>Paste ID (numeric) or slug</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4 mb-3">Example Request</h5>
                        <pre><code class="language-bash">curl "<?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=get&id=aBcDeFgH" \
  -H "X-API-Key: YOUR_API_KEY"</code></pre>

                        <h5 class="mt-4 mb-3">Example Response</h5>
                        <pre class="response-example"><code class="language-json">{
  "success": true,
  "paste": {
    "id": 123,
    "slug": "aBcDeFgH",
    "title": "My Python Script",
    "content": "print(\"Hello, World!\")",
    "syntax": "python",
    "visibility": "public",
    "views": 42,
    "created_at": "2026-02-03 14:30:00",
    "author": "johndoe"
  }
}</code></pre>
                    </section>

                    <!-- Update Paste -->
                    <section id="update-paste" class="endpoint mb-5">
                        <h2 class="mb-4">
                            <span class="method-badge method-put me-2">PUT</span>
                            Update Paste
                        </h2>
                        <p>Modify an existing paste you own. Only supply the fields you want to change.</p>

                        <h5 class="mt-4 mb-3">Endpoint</h5>
                        <pre><code>POST/PUT <?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=update&id={id_or_slug}</code></pre>

                        <h5 class="mt-4 mb-3">Parameters</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>id</code></td><td>string</td><td class="required">Required</td><td>Paste ID or slug to update</td></tr>
                                    <tr><td><code>content</code></td><td>string</td><td class="optional">Optional</td><td>New content</td></tr>
                                    <tr><td><code>title</code></td><td>string</td><td class="optional">Optional</td><td>New title</td></tr>
                                    <tr><td><code>syntax</code></td><td>string</td><td class="optional">Optional</td><td>New syntax</td></tr>
                                    <tr><td><code>visibility</code></td><td>string</td><td class="optional">Optional</td><td>New visibility</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4 mb-3">Example Request</h5>
                        <pre><code class="language-bash">curl -X PUT "<?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=update&id=aBcDeFgH" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"title": "Updated Title", "content": "print(\"Updated content!\")"}'</code></pre>

                        <div class="alert alert-info mt-3">
                            Returns same format as Create Paste on success.
                        </div>
                    </section>

                    <!-- Delete Paste -->
                    <section id="delete-paste" class="endpoint mb-5">
                        <h2 class="mb-4">
                            <span class="method-badge method-delete me-2">DELETE</span>
                            Delete Paste
                        </h2>
                        <p>Permanently delete a paste you own.</p>

                        <h5 class="mt-4 mb-3">Endpoint</h5>
                        <pre><code>DELETE <?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=delete&id={id_or_slug}</code></pre>

                        <h5 class="mt-4 mb-3">Example Request</h5>
                        <pre><code class="language-bash">curl -X DELETE "<?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=delete&id=aBcDeFgH" \
  -H "X-API-Key: YOUR_API_KEY"</code></pre>

                        <h5 class="mt-4 mb-3">Example Response</h5>
                        <pre class="response-example"><code class="language-json">{
  "success": true,
  "message": "Paste deleted successfully"
}</code></pre>
                    </section>

                    <!-- List Pastes -->
                    <section id="list-pastes" class="endpoint mb-5">
                        <h2 class="mb-4">
                            <span class="method-badge method-get me-2">GET</span>
                            List Pastes
                        </h2>
                        <p>Retrieve a paginated list of pastes belonging to the authenticated user.</p>

                        <h5 class="mt-4 mb-3">Endpoint</h5>
                        <pre><code>GET <?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=list</code></pre>

                        <h5 class="mt-4 mb-3">Parameters</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>limit</code></td><td>integer</td><td class="optional">Optional</td><td>Results per page (default: 20, max: 100)</td></tr>
                                    <tr><td><code>offset</code></td><td>integer</td><td class="optional">Optional</td><td>Skip this many records (default: 0)</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4 mb-3">Example Request</h5>
                        <pre><code class="language-bash">curl "<?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=list&limit=10" \
  -H "X-API-Key: YOUR_API_KEY"</code></pre>
                    </section>

                    <!-- Search Pastes -->
                    <section id="search-pastes" class="endpoint mb-5">
                        <h2 class="mb-4">
                            <span class="method-badge method-get me-2">GET</span>
                            Search Pastes
                        </h2>
                        <p>Search your own pastes by title or content.</p>

                        <h5 class="mt-4 mb-3">Endpoint</h5>
                        <pre><code>GET <?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=search&q={query}</code></pre>

                        <h5 class="mt-4 mb-3">Parameters</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>q</code></td><td>string</td><td class="required">Required</td><td>Search term (min 2 characters)</td></tr>
                                    <tr><td><code>limit</code></td><td>integer</td><td class="optional">Optional</td><td>Max results (default: 20)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- User Info -->
                    <section id="user-info" class="endpoint mb-5">
                        <h2 class="mb-4">
                            <span class="method-badge method-get me-2">GET</span>
                            User Info
                        </h2>
                        <p>Get basic information about the authenticated account.</p>

                        <h5 class="mt-4 mb-3">Endpoint</h5>
                        <pre><code>GET <?= htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8') ?>?action=user</code></pre>

                        <h5 class="mt-4 mb-3">Example Response</h5>
                        <pre class="response-example"><code class="language-json">{
  "success": true,
  "user": {
    "username": "johndoe",
    "email": "john@example.com",
    "paste_count": 42,
    "api_key_count": 2
  }
}</code></pre>
                    </section>

                    <!-- Error Codes -->
                    <section id="errors" class="endpoint mb-5">
                        <h2 class="mb-4"><i class="bi bi-exclamation-triangle me-2"></i>Error Codes</h2>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Error</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>400</td><td>Bad Request</td><td>Invalid or missing parameters</td></tr>
                                    <tr><td>401</td><td>Unauthorized</td><td>Missing or invalid API key</td></tr>
                                    <tr><td>403</td><td>Forbidden</td><td>No permission (e.g. not your paste)</td></tr>
                                    <tr><td>404</td><td>Not Found</td><td>Paste does not exist</td></tr>
                                    <tr><td>429</td><td>Too Many Requests</td><td>Rate limit exceeded</td></tr>
                                    <tr><td>500</td><td>Server Error</td><td>Internal server error</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4 mb-3">Error Response Format</h5>
                        <pre class="error-example"><code class="language-json">{
  "success": false,
  "error": "Human-readable error message"
}</code></pre>
                    </section>

                    <!-- Code Examples -->
                    <section id="examples" class="endpoint mb-5">
                        <h2 class="mb-4"><i class="bi bi-code-slash me-2"></i>Code Examples</h2>

                        <ul class="nav nav-tabs mb-4" id="exampleTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="python-tab" data-bs-toggle="tab" data-bs-target="#python" type="button" role="tab">Python</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="bash-tab" data-bs-toggle="tab" data-bs-target="#bash" type="button" role="tab">Bash</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="javascript-tab" data-bs-toggle="tab" data-bs-target="#javascript" type="button" role="tab">JavaScript</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="php-tab" data-bs-toggle="tab" data-bs-target="#php" type="button" role="tab">PHP</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="python" role="tabpanel">
                                <pre><code class="language-python">import requests

API_URL = "<?php echo htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8'); ?>"
API_KEY = "your_api_key_here"

headers = {"X-API-Key": API_KEY}

# Create a paste
response = requests.post(
    f"{API_URL}?action=paste",
    headers=headers,
    json={
        "content": "print('Hello, World!')",
        "title": "My Python Paste",
        "syntax": "python"
    }
)
result = response.json()
print(f"Paste URL: {result['paste']['url']}")

# Get a paste
response = requests.get(
    f"{API_URL}?action=get&id=aBcDeFgH",
    headers=headers
)
paste = response.json()['paste']
print(f"Content: {paste['content']}")</code></pre>
                            </div>
                            <div class="tab-pane fade" id="bash" role="tabpanel">
                                <pre><code class="language-bash">#!/bin/bash
API_URL="<?php echo htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8'); ?>"
API_KEY="your_api_key_here"

# Create a paste from file
paste_file() {
    curl -s -X POST "$API_URL?action=paste" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d "{\"content\": $(cat "$1" | jq -Rs .), \"title\": \"$1\", \"syntax\": \"text\"}"
}

# Create paste from stdin
echo "Hello, World!" | curl -s -X POST "$API_URL?action=paste" \
    -H "X-API-Key: $API_KEY" \
    -H "Content-Type: application/json" \
    -d "{\"content\": \"$(cat)\"}"</code></pre>
                            </div>
                            <div class="tab-pane fade" id="javascript" role="tabpanel">
                                <pre><code class="language-javascript">const API_URL = '<?php echo htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8'); ?>';
const API_KEY = 'your_api_key_here';

// Create a paste
async function createPaste(content, title, syntax) {
    const response = await fetch(`${API_URL}?action=paste`, {
        method: 'POST',
        headers: {
            'X-API-Key': API_KEY,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ content, title, syntax })
    });
    return response.json();
}

// Get a paste
async function getPaste(id) {
    const response = await fetch(`${API_URL}?action=get&id=${id}`, {
        headers: { 'X-API-Key': API_KEY }
    });
    return response.json();
}

// Usage
createPaste('console.log("Hello!");', 'My JS Paste', 'javascript')
    .then(result => console.log('Created:', result.paste.url));</code></pre>
                            </div>

                            <div class="tab-pane fade" id="php" role="tabpanel">
                                <pre><code class="language-php">&lt;?php
$api_url = '<?php echo htmlspecialchars($api_url, ENT_QUOTES, 'UTF-8'); ?>';
$api_key = 'your_api_key_here';

// Create a paste
$data = [
    'content' => '&lt;?php echo "Hello, World!"; ?>',
    'title' => 'My PHP Paste',
    'syntax' => 'php'
];

$ch = curl_init("$api_url?action=paste");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "X-API-Key: $api_key",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Paste URL: " . $response['paste']['url'];
?></code></pre>
                            </div>
                        </div>

                        <div class="alert alert-success mt-4">
                            <i class="bi bi-download me-2"></i>
                            Download the <a href="docs/paste_client.py" class="alert-link" download>Python CLI client</a>
                        </div>
                    </section>

                    <hr class="my-5 border-secondary">

                    <p class="text-center text-muted small">
                        ← <a href="<?= htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8') ?>" class="text-muted">Back to <?= htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') ?></a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
    hljs.highlightAll();

    // Update active nav link based on scroll position
    function updateActiveNav() {
        const sections = document.querySelectorAll('.endpoint');
        let current = 'overview';
        
        sections.forEach(section => {
            if (window.scrollY >= section.offsetTop - 120) {
                current = section.id;
            }
        });

        // Update both navs
        ['#desktopNav', '#mobileNav'].forEach(navId => {
            document.querySelectorAll(navId + ' .list-group-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('href') === '#' + current) {
                    item.classList.add('active');
                }
            });
        });
    }

    window.addEventListener('scroll', updateActiveNav);
    // Run once on load
    updateActiveNav();

    // scroll + close offcanvas AFTER scroll starts
    document.querySelectorAll('#mobileNav .list-group-item[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();           // Stop normal anchor + dismiss

            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                // Scroll smoothly
                targetElement.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });

                // Update active state immediately
                document.querySelectorAll('#mobileNav .list-group-item, #desktopNav .list-group-item')
                    .forEach(el => el.classList.remove('active'));
                this.classList.add('active');

                // Close offcanvas after a delay
                setTimeout(() => {
                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('sidebarOffcanvas'));
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                }, 80);   // 80ms for scroll to start
            }
        });
    });
</script>
</body>
</html>