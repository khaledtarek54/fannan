<?php

namespace App\Http\Resources;

trait BasePaging
{

    /**
     * Get the pagination links for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    protected function paginationLinks()
    {
        $this->resource->withQueryString();

        return [
            "next" => $this->resource->nextPageUrl(),
            "prev" => $this->resource->previousPageUrl(),
            "first" => $this->resource->url(1),
            "last" => $this->resource->url($this->resource->lastPage()),
        ];
    }

    /**
     * Gather the meta data for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    protected function meta()
    {
        return [
            "total_items" => $this->resource->total(),
            "total_pages" => $this->resource->lastPage(),
            "current_page_number" => $this->resource->currentPage(),
            "per_page_items" => $this->resource->perPage(),
            "has_more_pages" => $this->resource->hasMorePages(),
            "path" => $this->resource->path(),
        ];
    }
}
