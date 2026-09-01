export type ProjectStatus = 'pending' | 'processing' | 'completed' | 'failed';
export type ProjectFeatureCategory = 'frontend' | 'backend' | 'fullstack';
export type ProjectFeatureComplexity = 'low' | 'medium' | 'high';

export type ProjectFeature = {
    id: number;
    name: string;
    description?: string | null;
    category: ProjectFeatureCategory;
    estimated_hours: number;
    estimated_cost: number;
    complexity: ProjectFeatureComplexity;
    sort_order: number;
};

export type ProjectEstimate = {
    total_hours: number;
    total_days: number;
    total_cost: number;
    currency: string;
    ai_notes?: string | null;
};

export type Project = {
    id: number;
    title: string;
    status: ProjectStatus;
    hourly_rate: number;
    country?: string | null;
    country_name?: string | null;
    failure_reason?: string | null;
    created_at?: string | null;
    created_at_formatted?: string | null;
    features: ProjectFeature[];
    estimate?: ProjectEstimate | null;
};

export type PaginatedProjects = {
    data: Project[];
    links?: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta?: {
        from?: number | null;
        [key: string]: unknown;
    };
};
