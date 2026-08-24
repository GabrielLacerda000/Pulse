export type LeadStatus =
    | 'new'
    | 'contacted'
    | 'conversation'
    | 'meeting'
    | 'proposal'
    | 'won'
    | 'lost';

export type Lead = {
    id: number;
    company_name: string;
    category: string | null;
    phone: string | null;
    whatsapp_phone: string | null;
    email: string | null;
    website: string | null;
    instagram: string | null;
    has_website: boolean;
    has_instagram: boolean;
    city: string | null;
    state: string | null;
    status: LeadStatus;
    type: string | null;
    score: number | null;
    rating: string | null;
    reviews_count: number | null;
    source: string | null;
    google_maps_url: string | null;
    source_created_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    message_templates: RenderedMessageTemplate[];
};

export type LeadSortColumn = 'company_name' | 'score' | 'status';

export type LeadFilters = {
    search?: string;
    status?: LeadStatus | '';
    category?: string;
    city?: string;
    sort?: LeadSortColumn | '';
    direction?: 'asc' | 'desc' | '';
};

export type LeadFilterOptions = {
    statuses: LeadStatus[];
    categories: string[];
    cities: string[];
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export type LeadImportPreview = {
    total: number;
    new: number;
    duplicated: number;
    invalid: number;
};

export type MessageTemplate = {
    id: number;
    name: string;
    content: string;
    channel: string;
    created_at: string;
    updated_at: string;
};

export type RenderedMessageTemplate = {
    id: number;
    name: string;
    renderedContent: string;
};

export type WhatsAppConnectionStatus =
    | 'disconnected'
    | 'connecting'
    | 'qr_required'
    | 'connected'
    | 'logged_out';

export type WhatsAppConnection = {
    status: WhatsAppConnectionStatus;
    phone: string | null;
    qr: string | null;
};
