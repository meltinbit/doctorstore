export type ShopifyStore = {
    id: number;
    shop_domain: string;
    shop_name: string | null;
    scopes: string;
    created_at: string;
    latest_scan?: Scan | null;
    auto_scan_enabled: boolean;
    auto_scan_schedule: string | null;
    email_summary_enabled: boolean;
    email_summary_address: string | null;
};

export type Scan = {
    id: number;
    status: 'pending' | 'running' | 'complete' | 'failed';
    total_metafields: number;
    total_definitions: number;
    total_issues: number;
    quality_score: number | null;
    scanned_at: string | null;
    error_message: string | null;
};

export type ScanIssue = {
    id: number;
    namespace: string;
    key: string;
    resource_type: string;
    issue_type: string;
    occurrences: number;
};
