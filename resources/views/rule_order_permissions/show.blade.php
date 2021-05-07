@extends('layouts.app')

@section('content')
    <b>Имя правила </b>{{$ruleOrderPermission->name}}<p></p>

    <b>Пользователи </b>
    @foreach($ruleOrderPermission->user as $user)
        {{$user->name}}
    @endforeach
    <p></p>

    <b>Роли </b>
    @foreach($ruleOrderPermission->role as $role)
        {{$role->name}}
    @endforeach
    <p></p>

    <b>Магазины </b>
    @foreach($ruleOrderPermission->channel as $channel)
        {{$channel->name}}
    @endforeach
    <p></p>

    <b>Статусы </b>
    @foreach($ruleOrderPermission->orderState as $orderState)
        {{$orderState->name}}
    @endforeach
    <p></p>

    <b>Доставка </b>
    @foreach($ruleOrderPermission->carrier as $carrier)
        {{$carrier->name}}
    @endforeach
    <p></p>

    <b>Службы доставки </b>
    @foreach($ruleOrderPermission->carrierGroup as $carrierGroup)
        {{$carrierGroup->name}}
    @endforeach
    <p></p>
@endsection
