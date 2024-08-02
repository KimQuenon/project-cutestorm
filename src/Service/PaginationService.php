<?php
namespace App\Service;

class PaginationService
{
    public function paginate(array $items, int $currentPage, int $itemsPerPage): array
    {
        $totalItems = count($items);
        $totalPages = (int)ceil($totalItems / $itemsPerPage);

        $offset = ($currentPage - 1) * $itemsPerPage;
        $currentItems = array_slice($items, $offset, $itemsPerPage);

        return [
            'items' => $currentItems,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ];
    }
}

