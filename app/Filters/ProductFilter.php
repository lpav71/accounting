<?php

namespace App\Filters;

use Kyslik\LaravelFilterable\Generic\Filter;

class ProductFilter extends Filter
{
    /**
     * Defines columns that end-user may filter by.
     *
     * @var array
     */
    protected $filterables = ['name', 'reference', 'ean'];

    /**
     * Define allowed generics, and for which fields.
     *
     * @return void
     */
    protected function settings()
    {
        $this->for(['name', 'reference', 'ean'])->setDefaultFilterType('~');
    }
}
