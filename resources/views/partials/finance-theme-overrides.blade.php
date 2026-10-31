<style>
    .finance-page,
    .finance-report-page,
    .finance-audit-page,
    .fi-page,
    .fd-page,
    .fs-page {
        --finance-muted: var(--app-muted, #788496);
        --finance-border: color-mix(in srgb, var(--app-text) 14%, var(--app-surface));
        --finance-wash: color-mix(in srgb, var(--app-primary) 6%, var(--app-surface));
        color: var(--app-text) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-panel, .finance-stat, .finance-details, .finance-action-strip > a,
        .report-heading, .report-filters, .report-summary > div, .report-table-wrap,
        .audit-filters, .audit-event, .audit-empty, .audit-values > div,
        .fi-panel, .fd-panel, .fs-card, .finance-audit-page > header) {
        background: var(--app-surface) !important;
        color: var(--app-text) !important;
        border-color: var(--finance-border) !important;
        border-radius: var(--app-radius) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-table, .fi-table, .report-table-wrap table) thead th {
        background: var(--finance-wash) !important;
        color: var(--finance-muted) !important;
        border-color: var(--finance-border) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-table, .fi-table, .report-table-wrap table) tbody td,
    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(h1, h2, h3, strong, dt, dd, label, p, small, span, summary) {
        border-color: var(--finance-border);
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-table, .fi-table, .report-table-wrap table) tbody td {
        color: var(--app-text) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-page-heading h1, .finance-panel h2, .finance-stat strong,
        .finance-details summary strong, .report-heading h1, .report-table-heading h2,
        .report-summary strong, .audit-event-head strong, .audit-values dd,
        .fi-header h1, .fi-table td strong, .fd-header h1, .fd-panel h2,
        .fd-panel dd, .fs-header h1, .fs-section h2) {
        color: var(--app-text) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-page-heading p, .finance-panel small, .finance-stat small,
        .finance-panel-heading p, .finance-action-strip small, .finance-details summary small,
        .report-heading p, .report-filters label, .report-summary small,
        .report-table-heading p, .report-table-wrap td small, .audit-event-head small,
        .audit-reason, .audit-values dt, .audit-empty, .fi-header p, .fi-table td small,
        .fd-header p, .fd-panel dt, .fd-payment span, .fd-payment small,
        .fs-header p, .fs-grid small) {
        color: var(--finance-muted) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(a:not(.btn), .finance-kicker, .report-kicker, .report-table-wrap td a,
        .report-export .material-icons, .audit-event summary, .audit-filters label,
        .audit-values h2, .fi-header>div>a, .fi-table td>a, .fd-header>div>a,
        .fs-header>div>a, .finance-page-heading>div>a) {
        color: var(--app-primary) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-hero, .report-heading) {
        background: var(--app-primary) !important;
        color: var(--app-primary-contrast) !important;
        border-color: var(--app-primary) !important;
    }

    .finance-page .finance-hero :is(h1, p, .finance-kicker),
    .finance-report-page .report-heading :is(h1, p, .report-kicker) {
        color: var(--app-primary-contrast) !important;
    }

    .finance-page .finance-stat-primary { border-left-color: var(--app-primary) !important; }
    .finance-page .finance-positive,
    .finance-page .finance-chart-period,
    .finance-page .finance-action-strip > a > .material-icons:first-child,
    .finance-page .finance-details summary > .material-icons,
    .finance-page .finance-signature-note .material-icons,
    .finance-report-page .report-export:hover,
    .finance-report-page .report-table-heading > span,
    .finance-audit-page > header span { color: var(--app-primary) !important; }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-filter, .report-filters, .audit-filters, .finance-signature-note,
        .remittance-cash-summary, .remittance-cash-item, .fd-balance, .audit-values > div) {
        background-color: var(--finance-wash) !important;
        border-color: var(--finance-border) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-panel, .finance-stat, .finance-details, .finance-action-strip > a,
        .report-heading, .report-filters, .report-summary > div, .report-table-wrap,
        .audit-filters, .audit-event, .audit-empty, .audit-values > div,
        .fi-panel, .fd-panel, .fs-card, .finance-audit-page > header,
        .finance-table, .fi-table, .report-table-wrap table) {
        border-color: var(--finance-border) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-table tbody tr:hover, .fi-table tbody tr:hover, .report-table-wrap tbody tr:hover) {
        background: var(--finance-wash) !important;
    }

    :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.form-control, select, textarea, input:not([type="checkbox"]):not([type="radio"]),
        .report-export, .fi-status, .fd-status, .report-status, .finance-page .page-link,
        .finance-panel .page-link, .fi-pagination .page-link, .report-table-wrap .page-link) {
        border-radius: var(--app-radius) !important;
    }

    .finance-report-page .report-export,
    .finance-audit-page .audit-values > div,
    .finance-page .finance-details,
    .finance-page .finance-panel,
    .finance-page .finance-stat,
    .fi-page .fi-panel,
    .fd-page .fd-panel,
    .fs-page .fs-card { border-radius: var(--app-radius) !important; }

    html[data-app-theme="dark"] :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page) {
        --finance-muted: var(--app-muted);
        --finance-border: var(--app-border);
        --finance-wash: color-mix(in srgb, var(--app-primary) 13%, var(--app-surface));
    }

    html[data-app-theme="dark"] :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-panel, .finance-stat, .finance-details, .finance-action-strip > a,
        .report-heading, .report-filters, .report-summary > div, .report-table-wrap,
        .audit-filters, .audit-event, .audit-empty, .audit-values > div,
        .fi-panel, .fd-panel, .fs-card, .finance-audit-page > header) {
        background: var(--app-surface) !important;
        color: var(--app-text) !important;
        border-color: var(--app-border) !important;
    }

    html[data-app-theme="dark"] :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-filter, .report-filters, .audit-filters, .finance-signature-note,
        .remittance-cash-summary, .remittance-cash-item, .fd-balance, .audit-values > div,
        .finance-table thead th, .fi-table thead th, .report-table-wrap thead th) {
        background: var(--finance-wash) !important;
        color: var(--app-text) !important;
        border-color: var(--app-border) !important;
    }

    html[data-app-theme="dark"] :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-table tbody td, .fi-table tbody td, .report-table-wrap tbody td,
        .finance-panel h2, .finance-stat strong, .finance-page-heading h1,
        .report-heading h1, .report-table-heading h2, .report-summary strong,
        .audit-event-head strong, .audit-values dd, .fi-header h1, .fi-table td strong,
        .fd-header h1, .fd-panel h2, .fd-panel dd, .fs-header h1, .fs-section h2) {
        color: var(--app-text) !important;
        border-color: var(--app-border) !important;
    }

    html[data-app-theme="dark"] :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-page-heading p, .finance-panel small, .finance-stat small,
        .finance-panel-heading p, .finance-action-strip small, .finance-details summary small,
        .report-heading p, .report-filters label, .report-summary small,
        .report-table-heading p, .report-table-wrap td small, .audit-event-head small,
        .audit-reason, .audit-values dt, .audit-empty, .fi-header p, .fi-table td small,
        .fd-header p, .fd-panel dt, .fd-payment span, .fd-payment small,
        .fs-header p, .fs-grid small, .text-muted) {
        color: var(--app-muted) !important;
    }

    html[data-app-theme="dark"] :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-hero, .report-heading) {
        background: var(--app-primary) !important;
        color: var(--app-primary-contrast) !important;
    }

    html[data-app-theme="dark"] .finance-page .finance-hero :is(h1, p, .finance-kicker),
    html[data-app-theme="dark"] .finance-report-page .report-heading :is(h1, p, .report-kicker) {
        color: var(--app-primary-contrast) !important;
    }

    html[data-app-theme="dark"] :is(.finance-page, .finance-report-page, .finance-audit-page, .fi-page, .fd-page, .fs-page)
    :is(.finance-table tbody tr:hover, .fi-table tbody tr:hover, .report-table-wrap tbody tr:hover) {
        background: var(--finance-wash) !important;
    }

    html[data-app-theme="dark"] .finance-page :is(.badge-success, .finance-positive) {
        color: var(--app-accent-contrast) !important;
    }

    html[data-app-theme="dark"] .finance-report-page .report-status.status-received,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-paid,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-approved,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-closed { background:#173b2b !important;color:#9be0b9 !important; }
    html[data-app-theme="dark"] .finance-report-page .report-status.status-awaiting-review,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-awaiting-director,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-awaiting-signature,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-in-transit,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-partially-paid { background:#423414 !important;color:#f1c66c !important; }
    html[data-app-theme="dark"] .finance-report-page .report-status.status-rejected,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-discrepancy-open,
    html[data-app-theme="dark"] .finance-report-page .report-status.status-canceled { background:#482525 !important;color:#ffaaa5 !important; }
</style>
