<?='<?xml version="1.0"?>'?>
<yml_catalog>
    <offers>
        @foreach($products as $product)
            <offer id="{{ $product->id }}C0" group_id="{{ $product->id }}" available="true">
                <price>{{ $product->properties['price'] }}</price>
                <currencyId>RUB</currencyId>
                @if(isset($product->properties['channelCategory']))
                <categoryId>{{$product->properties['channelCategory']}}</categoryId>
                @else
                <categoryId>27</categoryId>
                @endif
                
                @foreach($product->properties['images'] as $image)
                    <picture>{{ str_replace(' ', '%20', $image) }}</picture>
                @endforeach
                <vendor>{{ $manufacturer->name }}</vendor>
                <vendorCode>{{ $product->reference }}</vendorCode>
                <description><![CDATA[]]></description>
                <barcode/>
                <upc/>
                @foreach($product->properties['attributes'] as $attribute)
                 {{--   @if(in_array($attribute['name'],['Пол','Тип механизма','Стекло','Диаметр']))--}}
                    @if($attribute['name'] != 'Артикул/модель' && $attribute['name'] != 'channelCategory')
                        <param name="@if($attribute['name']=='Пол')Часы@else{{ $attribute['name'] }}@endif">{{ substr($attribute['value'],0,240) }}</param>
                    @endif
                @endforeach
                <name>{{ $product->name }}</name>
            </offer>
        @endforeach
    </offers>
</yml_catalog>
