@extends('layouts.app')
@section('title', 'Findings List')

@section('content')

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#0f172a,#1e293b);
                    display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0">
            <i class="bi bi-list-ul"></i>
        </div>
        <div>
            <h4 class="mb-0">Findings List</h4>
            <p class="mb-0 text-muted" style="font-size:.85rem">All vulnerability findings · infinite scroll</p>
        </div>
    </div>
    <span x-data x-text="$store.findings.total" class="badge" style="background:var(--primary);color:#fff;font-size:.8rem;padding:.4rem .75rem;border-radius:8px"></span>
</div>

{{-- ─── Main Alpine Component ─────────────────────────────────────────────── --}}
<div x-data="findingsApp()" x-init="init()" class="mt-3">

    {{-- ─── Filter / Search Bar ──────────────────────────────────────── --}}
    <div class="card mb-3" style="border:none;border-radius:14px;box-shadow:0 1px 8px rgba(0,0,0,.07)">
        <div class="card-body py-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="background:#f8fafc;border-right:none">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search name, IP, CVE, hostname…"
                               x-model="filters.search"
                               @input.debounce.300ms="applyFilters()"
                               style="border-left:none">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" x-model="filters.severity" @change="applyFilters()">
                        <option value="">All Severities</option>
                        <option value="Critical">Critical</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                        <option value="Info">Info</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button class="btn btn-sm btn-outline-secondary w-100" @click="applyFilters()"
                            :disabled="loading">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
                <div class="col-12 col-md-2 text-md-end">
                    <span class="text-muted" style="font-size:.8rem">
                        <span x-text="findings.length"></span> loaded
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Findings List ─────────────────────────────────────────────── --}}
    <div class="findings-list">

        {{-- Skeleton placeholders (shown while first load) --}}
        <template x-if="loading && findings.length === 0">
            <div>
                <template x-for="n in 5" :key="n">
                    <div class="finding-card skeleton mb-2" style="border-radius:12px;overflow:hidden">
                        <div class="skeleton-bar" style="height:64px;background:linear-gradient(90deg,#f0f4f8 25%,#e2e8f0 50%,#f0f4f8 75%);background-size:200% 100%;animation:shimmer 1.4s infinite"></div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="!loading && findings.length === 0">
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size:3rem;color:#cbd5e1"></i>
                <p class="mt-2 text-muted">No findings match your filters.</p>
                <button class="btn btn-sm btn-outline-secondary mt-1" @click="applyFilters()">
                    <i class="bi bi-arrow-clockwise"></i> Reset & Reload
                </button>
            </div>
        </template>

        {{-- Finding Cards --}}
        <template x-for="finding in findings" :key="finding.id">
            <div class="finding-card mb-2"
                 :class="{ 'is-open': openId === finding.id }"
                 style="border-radius:12px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.06);background:#fff;transition:box-shadow .2s">

                {{-- ── Collapsed Header (always visible) ── --}}
                <div class="finding-header d-flex align-items-center gap-3 px-3 py-3"
                     @click="toggleItem(finding.id)"
                     style="cursor:pointer;user-select:none;transition:background .15s"
                     :style="openId === finding.id ? 'background:#f8fafc' : ''">

                    {{-- Severity dot --}}
                    <div class="flex-shrink-0">
                        <span class="sev-badge" :class="'sev-' + finding.severity.toLowerCase()"
                              x-text="finding.severity"></span>
                    </div>

                    {{-- Title + meta --}}
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-600 text-truncate" style="font-size:.9rem;max-width:100%"
                             x-text="finding.vuln_name"></div>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <span class="meta-chip"><i class="bi bi-router"></i> <span x-text="finding.ip_address"></span></span>
                            <span class="meta-chip" x-show="finding.hostname" x-text="finding.hostname"></span>
                            <span class="meta-chip" x-show="finding.cve">
                                <i class="bi bi-shield-exclamation"></i> <span x-text="finding.cve"></span>
                            </span>
                            <span class="meta-chip" x-show="finding.cvss_score">
                                CVSS <span x-text="finding.cvss_score"></span>
                            </span>
                            <span class="meta-chip" x-show="finding.port">
                                :<span x-text="finding.port"></span>/<span x-text="finding.protocol"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Category + chevron --}}
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="meta-chip d-none d-md-inline" x-show="finding.vuln_category"
                              x-text="finding.vuln_category"></span>
                        <i class="bi chevron-icon"
                           :class="openId === finding.id ? 'bi-chevron-up' : 'bi-chevron-down'"
                           style="color:#94a3b8;font-size:.85rem;transition:transform .2s"></i>
                    </div>
                </div>

                {{-- ── Expanded Detail ── --}}
                <div x-show="openId === finding.id"
                     x-transition:enter="transition-expand"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     style="border-top:1px solid #f1f5f9;display:none">

                    {{-- Detail loading skeleton --}}
                    <template x-if="detailLoading && !detailCache[finding.id]">
                        <div class="px-3 py-3">
                            <div class="skeleton-bar mb-2" style="height:14px;width:60%;border-radius:6px;background:linear-gradient(90deg,#f0f4f8 25%,#e2e8f0 50%,#f0f4f8 75%);background-size:200% 100%;animation:shimmer 1.4s infinite"></div>
                            <div class="skeleton-bar mb-2" style="height:14px;width:80%;border-radius:6px;background:linear-gradient(90deg,#f0f4f8 25%,#e2e8f0 50%,#f0f4f8 75%);background-size:200% 100%;animation:shimmer 1.4s infinite"></div>
                            <div class="skeleton-bar" style="height:14px;width:45%;border-radius:6px;background:linear-gradient(90deg,#f0f4f8 25%,#e2e8f0 50%,#f0f4f8 75%);background-size:200% 100%;animation:shimmer 1.4s infinite"></div>
                        </div>
                    </template>

                    {{-- Detail content --}}
                    <template x-if="detailCache[finding.id]">
                        <div class="px-3 py-3">
                            <div class="row g-3">

                                {{-- Description --}}
                                <div class="col-12" x-show="detailCache[finding.id].description">
                                    <div class="detail-label">Description</div>
                                    <div class="detail-text" x-text="detailCache[finding.id].description"></div>
                                </div>

                                {{-- Remediation --}}
                                <div class="col-12" x-show="detailCache[finding.id].remediation_text">
                                    <div class="detail-label"><i class="bi bi-tools"></i> Remediation</div>
                                    <div class="detail-text" x-text="detailCache[finding.id].remediation_text"></div>
                                </div>

                                {{-- Metadata grid --}}
                                <div class="col-12">
                                    <div class="row g-2">
                                        <div class="col-6 col-md-3" x-show="detailCache[finding.id].vuln_category">
                                            <div class="detail-label">Category</div>
                                            <div x-text="detailCache[finding.id].vuln_category"></div>
                                        </div>
                                        <div class="col-6 col-md-3" x-show="detailCache[finding.id].affected_component">
                                            <div class="detail-label">Component</div>
                                            <div x-text="detailCache[finding.id].affected_component"></div>
                                        </div>
                                        <div class="col-6 col-md-3" x-show="detailCache[finding.id].scan">
                                            <div class="detail-label">Scan File</div>
                                            <div x-text="detailCache[finding.id].scan?.filename"></div>
                                        </div>
                                        <div class="col-6 col-md-3" x-show="detailCache[finding.id].assessment">
                                            <div class="detail-label">Assessment</div>
                                            <div x-text="detailCache[finding.id].assessment?.name"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Plugin output (collapsible) --}}
                                <div class="col-12" x-show="detailCache[finding.id].plugin_output" x-data="{ showOutput: false }">
                                    <div class="detail-label d-flex align-items-center gap-2">
                                        Plugin Output
                                        <button class="btn btn-xs" @click="showOutput = !showOutput"
                                                style="font-size:.7rem;padding:.1rem .4rem;border-radius:5px;background:#f1f5f9;border:none">
                                            <span x-text="showOutput ? 'Hide' : 'Show'"></span>
                                        </button>
                                    </div>
                                    <pre x-show="showOutput"
                                         style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;font-size:.75rem;max-height:300px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;margin-top:.5rem"
                                         x-text="detailCache[finding.id].plugin_output"></pre>
                                </div>

                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- ── Load More Spinner / Button ── --}}
        <div class="text-center py-3" x-show="loading && findings.length > 0">
            <div class="spinner-border spinner-border-sm" style="color:var(--primary)" role="status">
                <span class="visually-hidden">Loading…</span>
            </div>
            <span class="ms-2 text-muted" style="font-size:.85rem">Loading more findings…</span>
        </div>

        <div class="text-center py-2" x-show="!loading && !hasMore && findings.length > 0">
            <span class="text-muted" style="font-size:.8rem">
                <i class="bi bi-check-circle text-success"></i>
                All <span x-text="findings.length"></span> findings loaded
            </span>
        </div>

        {{-- Fallback "Load More" button (hidden when using IntersectionObserver) --}}
        <div class="text-center py-2" x-show="hasMore && !loading" id="load-more-btn" style="display:none!important">
            <button class="btn btn-sm btn-outline-secondary" @click="fetchFindings()">
                <i class="bi bi-arrow-down-circle"></i> Load More
            </button>
        </div>

        {{-- Intersection sentinel - when visible, load next page --}}
        <div id="scroll-sentinel" style="height:1px"></div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {

    // Shared store for total count badge in header
    Alpine.store('findings', { total: '' });

    Alpine.data('findingsApp', () => ({
        findings:     [],
        nextCursor:   null,
        hasMore:      true,
        loading:      false,
        openId:       null,
        detailCache:  {},
        detailLoading: false,
        filters:      { severity: '', search: '' },
        _observer:    null,
        _debounceTimer: null,

        init() {
            this.fetchFindings();
            this.$nextTick(() => this._setupObserver());
        },

        async fetchFindings(reset = false) {
            if (this.loading) return;
            if (!reset && !this.hasMore) return;

            this.loading = true;

            const params = new URLSearchParams();
            if (this.nextCursor && !reset)       params.set('cursor',        this.nextCursor);
            if (this.filters.severity)            params.set('severity',      this.filters.severity);
            if (this.filters.search.trim())       params.set('search',        this.filters.search.trim());

            try {
                const res  = await fetch(`/api/findings?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const json = await res.json();

                if (reset) this.findings = [];

                this.findings.push(...json.data);
                this.nextCursor = json.next_cursor ?? null;
                this.hasMore    = !!json.next_cursor;

                // Update badge
                Alpine.store('findings').total = this.findings.length + (this.hasMore ? '+' : '');

            } catch (e) {
                console.error('Findings fetch error:', e);
            } finally {
                this.loading = false;
            }
        },

        async toggleItem(id) {
            if (this.openId === id) {
                this.openId = null;
                return;
            }

            this.openId = id;

            if (this.detailCache[id]) return;

            this.detailLoading = true;
            try {
                const res  = await fetch(`/api/findings/${id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                this.detailCache[id] = await res.json();
            } catch (e) {
                console.error('Detail fetch error:', e);
            } finally {
                this.detailLoading = false;
            }
        },

        applyFilters() {
            this.nextCursor = null;
            this.hasMore    = true;
            this.openId     = null;
            this.detailCache = {};
            this.fetchFindings(true);
        },

        _setupObserver() {
            const sentinel = document.getElementById('scroll-sentinel');
            if (!sentinel || !('IntersectionObserver' in window)) return;

            this._observer = new IntersectionObserver(
                (entries) => {
                    if (entries[0].isIntersecting && this.hasMore && !this.loading) {
                        this.fetchFindings();
                    }
                },
                { rootMargin: '300px' }
            );
            this._observer.observe(sentinel);
        },

        // Helper: severity colour map
        sevColor(sev) {
            const map = {
                Critical: '#7f1d1d',
                High:     '#92400e',
                Medium:   '#854d0e',
                Low:      '#166534',
                Info:     '#1e3a5f',
            };
            return map[sev] ?? '#475569';
        },
    }));
});
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

<style>
/* ── Severity badges ───────────────────────────────────────────── */
.sev-badge {
    display: inline-block;
    font-size: .68rem;
    font-weight: 700;
    padding: .22rem .55rem;
    border-radius: 6px;
    white-space: nowrap;
    min-width: 62px;
    text-align: center;
    letter-spacing: .3px;
}
.sev-critical { background: #fee2e2; color: #7f1d1d; }
.sev-high     { background: #ffedd5; color: #92400e; }
.sev-medium   { background: #fef9c3; color: #854d0e; }
.sev-low      { background: #dcfce7; color: #166534; }
.sev-info     { background: #dbeafe; color: #1e3a5f; }

/* ── Meta chips ────────────────────────────────────────────────── */
.meta-chip {
    display: inline-block;
    font-size: .72rem;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 5px;
    padding: .1rem .4rem;
}

/* ── Detail labels ─────────────────────────────────────────────── */
.detail-label {
    font-size: .72rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: .3rem;
}
.detail-text {
    font-size: .85rem;
    color: #334155;
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-word;
}

/* ── Card hover ────────────────────────────────────────────────── */
.finding-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,.1) !important;
}
.finding-card.is-open {
    box-shadow: 0 4px 20px rgba(0,0,0,.12) !important;
}

/* ── Shimmer animation ─────────────────────────────────────────── */
@keyframes shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ── Alpine transition ─────────────────────────────────────────── */
.opacity-0 { opacity: 0; }
.opacity-100 { opacity: 1; }
[x-cloak] { display: none !important; }

/* ── Page header ───────────────────────────────────────────────── */
.page-header { margin-bottom: 1.25rem; }

/* ── fw-600 ────────────────────────────────────────────────────── */
.fw-600 { font-weight: 600; }
</style>
@endpush
