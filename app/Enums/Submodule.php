<?php

declare(strict_types=1);

namespace App\Enums;

enum Submodule: string
{
    // ── Invoicing ─────────────────────────────────────────────────────────────
    case InvoicingQuotes   = 'invoicing_quotes';
    case InvoicingInvoices = 'invoicing_invoices';

    // ── Reports ───────────────────────────────────────────────────────────────
    case ReportsFinancial = 'reports_financial';
    case ReportsAuditLogs = 'reports_audit_logs';

    /** Which module this submodule belongs to (for grouping / display). */
    public function module(): Module
    {
        return match($this) {
            self::InvoicingQuotes,
            self::InvoicingInvoices => Module::Invoicing,

            self::ReportsFinancial,
            self::ReportsAuditLogs  => Module::Reports,
        };
    }

    /**
     * User-level actions available within this submodule.
     * Seeded as spatie permissions into every tenant DB.
     */
    public function permissions(): array
    {
        return match($this) {
            self::InvoicingQuotes   => ['view', 'create', 'edit', 'delete'],
            self::InvoicingInvoices => ['view', 'create', 'delete'],
            self::ReportsFinancial  => ['view', 'export'],
            self::ReportsAuditLogs  => ['view'],
        };
    }

    /** Returns fully-qualified permission strings: ['invoicing_quotes.view', ...] */
    public function qualifiedPermissions(): array
    {
        return array_map(fn (string $p) => "{$this->value}.{$p}", $this->permissions());
    }

    public function label(): string
    {
        return match($this) {
            self::InvoicingQuotes   => 'Quotes',
            self::InvoicingInvoices => 'Invoices',
            self::ReportsFinancial  => 'Financial Reports',
            self::ReportsAuditLogs  => 'Audit Logs',
        };
    }
}
