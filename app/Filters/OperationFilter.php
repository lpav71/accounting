<?php

namespace App\Filters;

use App\Http\Requests\OperationFilterRequest;
use App\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Kyslik\LaravelFilterable\Filter;

/**
 * Фильтр модели Operation
 *
 * @package App\Filters
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class OperationFilter extends Filter
{

    public function __construct(OperationFilterRequest $request)
    {
        parent::__construct($request);
    }

    /**
     * Карта фильтров
     * (доступные фильтры и их алиасы)
     *
     * @return array
     */
    public function filterMap(): array
    {
        return [
            'operableId' => 'operableId',
            'type' => 'type',
            'date' => 'date',
        ];
    }

    /**
     * Фильтр по оперируемому объекту
     *
     * @param int|null $operableId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function operableId(?int $operableId = null): Builder
    {
//        if (!is_null($operableId)) {
//            $product = Product::find($operableId);
//            if($product->isComposite()) {
//                \Session::flash('warning', __('The product is composite, the products of which it consists will be displayed'));
//                $products = $product->products()->pluck('id');
//                $builder = $this->builder->whereIn('operable_id', $products);
//            } else {
//                $builder = $this->builder->where('operable_id', $operableId);
//            }
//
//            return $builder;
//        }
//
//        return $this->builder;

        return !is_null($operableId) ? $this->builder->where('operable_id', $operableId) : $this->builder;
    }

    /**
     * Фильтр по типу операции
     *
     * @param string|null $type
     * @return Builder
     */
    public function type(?string $type = null): Builder
    {
        return !is_null($type) ? $this->builder->where('type', $type) : $this->builder;
    }

    /**
     * Фильтр по дате операции
     *
     * @param Carbon|null $date
     * @return Builder
     */
    public function date(?Carbon $date = null): Builder
    {
        return !is_null($date) ?
            $this
                ->builder
                ->where('created_at', '>=', $date->setTime(0, 0, 0, 0))
                ->where('created_at', '<=', $date->setTime(23, 59, 59, 999))
            :
            $this->builder;
    }
}
