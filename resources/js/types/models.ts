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
};
