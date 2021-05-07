<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ReferencesCompaniesTemplate
 *
 * @property int $id
 * @property string $name
 * @property string $ad_type
 * @property string $phrase
 * @property string $header_1
 * @property string $header_2
 * @property string $text
 * @property string $link_text
 * @property string $region
 * @property string $bet
 * @property string $quick_link
 * @property string $quick_link_addr
 * @property string $details
 * @property string $quick_link_descr
 * @property string $link
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ReferencesCompaniesTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ReferencesCompaniesTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ReferencesCompaniesTemplate query()
 * @mixin \Eloquent
 */
class ReferencesCompaniesTemplate extends Model
{
    protected $fillable = [
            'name',
            'ad_type',
            'phrase',
            'header_1',
            'header_2',
            'text',
            'link_text',
            'region',
            'bet',
            'quick_link',
            'quick_link_addr',
            'details',
            'quick_link_descr',
            'link',
    ];
}
