<?php
// ============================================================
// core/Paginator.php
// Calculates everything needed for result pagination.
//
// Usage:
// $paginator = new Paginator($totalItems, $perPage, $currentPage);
// $offset    = $paginator->offset();      -> For SQL queries
// $total     = $paginator->totalPages();  -> For UI rendering
//
// To support filter parameters in page links:
// $paginator->setExtraParams($filters);
// ============================================================

class Paginator
{
    // Total results count from the database
    private int $totalItems;

    // Items per page (e.g. 10)
    private int $perPage;

    // Current page number from the URL
    private int $currentPage;

    // Extra URL params to preserve in pagination links (like search filters)
    private array $extraParams = [];

    // Initialize the Paginator with basic counts and limits.
    public function __construct(int $totalItems, int $perPage, int $currentPage)
    {
        // Ensure values are logical and not negative
        $this->totalItems  = max(0, $totalItems);
        $this->perPage     = max(1, $perPage);

        // Ensure the current page is at least 1
        $this->currentPage = max(1, (int) $currentPage);
    }

    // Attach active filters so they are preserved across page navigations.
    // Must be called after construction.
    public function setExtraParams(array $params): void
    {
        // Remove empty values to keep URLs clean
        $this->extraParams = array_filter($params, fn($v) => $v !== '' && $v !== null && $v !== 0);
    }

    // Calculate the SQL OFFSET value to skip previous page results.
    // Used directly in SQL queries: LIMIT ? OFFSET ?
    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    // Calculate the total number of pages needed.
    public function totalPages(): int
    {
        if ($this->totalItems === 0) {
            return 0;
        }

        // Round up to ensure the last partial page is counted
        return (int) ceil($this->totalItems / $this->perPage);
    }

    // Get the current page number.
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    // Check if there is a previous page available.
    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    // Check if there is a next page available.
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages();
    }

    // Get the previous page number.
    public function prevPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    // Get the next page number.
    public function nextPage(): int
    {
        return min($this->totalPages(), $this->currentPage + 1);
    }

    // Get the configured items per page limit.
    public function perPage(): int
    {
        return $this->perPage;
    }

    // Get the total items counted in the database.
    public function totalItems(): int
    {
        return $this->totalItems;
    }

    // Generate an array of page numbers to render in the UI.
    // Instead of showing 1 to 100, it limits the range around the current page.
    public function pages(int $range = 2): array
    {
        $total = $this->totalPages();

        if ($total <= 1) {
            return [];
        }

        // Determine start and end boundaries for the UI buttons
        $start = max(1, $this->currentPage - $range);
        $end   = min($total, $this->currentPage + $range);

        return range($start, $end);
    }

    // Build a page URL including all active filter parameters.
    // Ensures filter state is preserved across page navigation links.
    public function pageUrl(int $pageNum, string $pageName): string
    {
        // Start with base parameters
        $params = [
            'page'     => $pageName,
            'page_num' => $pageNum,
        ];

        // Append any active filter parameters
        foreach ($this->extraParams as $key => $value) {
            // Skip page_num in filters to avoid conflicts
            if ($key !== 'page_num' && $key !== 'page') {
                $params[$key] = $value;
            }
        }

        // Build a safe URL query string
        return '?' . http_build_query($params);
    }
}