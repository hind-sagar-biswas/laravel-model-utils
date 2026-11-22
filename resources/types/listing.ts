export type SortOrder = 'asc' | 'desc';
export type FilterValue =
    | string
    | number
    | boolean
    | null
    | Array<string | number>;

/** Mirrors ListingParameters::toArray() */
export interface ListingProps<F extends string = string> {
    search: string | null;
    per_page: number;
    sort_by: string | null;
    order: SortOrder | null;
    filters: Partial<Record<F, FilterValue>> | [];
}