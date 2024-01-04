@extends('layouts.app')
@section('title', __('role.add_role'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>@lang( 'role.add_role' )</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action('RoleController@store'), 'method' => 'post', 'id' => 'role_add_form' ]) !!}
        <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label('name', __( 'user.role_name' ) . ':*') !!}
              {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'user.role_name' ) ]); !!}
          </div>
        </div>
        </div>
        @if(in_array('service_staff', $enabled_modules))
        <div class="row">
        <div class="col-md-2">
          <h4>@lang( 'lang_v1.user_type' )</h4>
        </div>
        <div class="col-md-9 col-md-offset-1">
          <div class="col-md-12">
          <div class="checkbox">
            <label>
              {!! Form::checkbox('is_service_staff', 1, false,
              [ 'class' => 'input-icheck']); !!} {{ __( 'restaurant.service_staff' ) }}
            </label>
            @show_tooltip(__('restaurant.tooltip_service_staff'))
          </div>
          </div>
        </div>
        </div>
        @endif
        <div class="row">
        <div class="col-md-3">
          <label>@lang( 'user.permissions' ):*</label>
        </div>
        </div>
        <div class="row check_group">
            <div class="col-md-1">
                <h4>@lang( 'role.admin_role' )</h4>
            </div>
            <div class="col-md-2">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.confirm_bank_transfer_method', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.confirm_bank_transfer_method' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.confirm_reduce_debt', false, ['class' => 'input-icheck']); !!}
                            {{ __('role.confirm_reduce_debt') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.close_end_of_day', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.close_end_of_day' ) }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.user' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'user.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'user.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'user.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'user.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.delete' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'user.roles' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'roles.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.view_role' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'roles.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.add_role' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'roles.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit_role' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'roles.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.delete_role' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.supplier' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'supplier.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'supplier.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'supplier.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'supplier.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.delete' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.customer' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'customer.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'customer.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'customer.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'customer.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.delete' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'business.product' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.delete' ) }}
              </label>
            </div>
          </div>
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.opening_stock', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.add_opening_stock' ) }}
              </label>
            </div>
          </div>--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'view_purchase_price', false,['class' => 'input-icheck']); !!}
                {{ __('lang_v1.view_purchase_price') }}
              </label>
              @show_tooltip(__('lang_v1.view_purchase_price_tooltip'))
            </div>
          </div>
        </div>
        </div>
        <hr>
        @if(in_array('purchases', $enabled_modules) || in_array('stock_adjustment', $enabled_modules) )
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.purchase' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.delete' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.payments', false,['class' => 'input-icheck']); !!}
                {{ __('lang_v1.purchase.payments') }}
              </label>
              @show_tooltip(__('lang_v1.purchase_payments'))
            </div>
          </div>
{{--          <div class="col-md-12">--}}
{{--            <div class="checkbox">--}}
{{--              <label>--}}
{{--                {!! Form::checkbox('permissions[]', 'view_own_purchase', false,['class' => 'input-icheck']); !!}--}}
{{--                {{ __('lang_v1.view_own_purchase') }}--}}
{{--              </label>--}}
{{--            </div>--}}
{{--          </div>--}}

        </div>
        </div>
        <hr>
        @endif
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'sale.sale' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'direct_sell.access', false, ['class' => 'input-icheck']); !!}
                    {{ __('role.direct_sell.access') }}
                </label>
            </div>
        </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.view' ) }}
              </label>
            </div>
          </div>
{{--          @if(in_array('pos_sale', $enabled_modules))--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.create' ) }}
              </label>
            </div>
          </div>
{{--            @endif--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.update' ) }}
              </label>
            </div>
          </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.cancel', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.cancel' ) }}
                    </label>
                </div>
            </div>
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.delete' ) }}
              </label>
            </div>
          </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.approve_transfer_money_bill', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.approve_transfer_money_bill' ) }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.approve_return_bill', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.approve_return_bill' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.approve_exchange_bill', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.approve_exchange_bill' ) }}
                    </label>
                </div>
            </div>--}}
          {{--@if(in_array('add_sale', $enabled_modules))
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'direct_sell.access', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.direct_sell.access' ) }}
              </label>
            </div>
          </div>
          @endif--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'list_drafts', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.list_drafts' ) }}
              </label>
            </div>
          </div>
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'list_quotations', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.list_quotations' ) }}
              </label>
            </div>
          </div>--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'view_own_sell_only', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.view_own_sell_only' ) }}
              </label>
            </div>
          </div>--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.payments', false, ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.sell.payments') }}
              </label>
              @show_tooltip(__('lang_v1.sell_payments'))
            </div>
          </div>--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'edit_product_price_from_sale_screen', false, ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.edit_product_price_from_sale_screen') }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'edit_product_price_from_pos_screen', false, ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.edit_product_price_from_pos_screen') }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'edit_product_discount_from_sale_screen', false, ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.edit_product_discount_from_sale_screen') }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'edit_product_discount_from_pos_screen', false, ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.edit_product_discount_from_pos_screen') }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'discount.access', false, ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.discount.access') }}
              </label>
            </div>
          </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.accept_received_money_to_custom', false, ['class' => 'input-icheck']); !!}
                        {{ __('lang_v1.accepted_customer') }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.confirm_bank_transfer_method', false, ['class' => 'input-icheck']); !!}
                        {{ __('lang_v1.confirm_bank_transfer_method') }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.create_bill_transfer', false, ['class' => 'input-icheck']); !!}
                        {{ __('lang_v1.create_bill_transfer') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'access_shipping', false, ['class' => 'input-icheck']); !!}
                        {{ __('lang_v1.access_shipping') }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.create_exchange_bill', false, ['class' => 'input-icheck']); !!}
                        {{ __('lang_v1.sell.create_exchange_bill') }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.create_return_bill', false, ['class' => 'input-icheck']); !!}
                        {{ __('lang_v1.sell.create_return_bill') }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.receipt_expense', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.sell.receipt_expense' ) }}
                    </label>
                </div>
            </div>--}}
          @if(in_array('types_of_service', $enabled_modules))
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'access_types_of_service', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.access_types_of_service' ) }}
              </label>
            </div>
          </div>
          @endif
        </div>
        </div>
        <hr>
        <div class="row check_group">
            <div class="col-md-1">
                <h4>@lang( 'lang_v1.Cashier' )</h4>
            </div>
            <div class="col-md-2">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-9">
                {{--<div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'direct_sell.access', false, ['class' => 'input-icheck']); !!}
                            {{ __('role.direct_sell.access') }}
                        </label>
                    </div>
                </div>--}}
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.receipt_expense', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.sell.receipt_expense') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.payments', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.sell.payments') }}
                        </label>
                        @show_tooltip(__('lang_v1.sell_payments'))
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.accept_received_money_to_custom', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.accepted_customer') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.add_reduce_debt', false, ['class' => 'input-icheck']); !!}
                            {{ __('role.add_reduce_debt') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row check_group">
            <div class="col-md-1">
                <h4>@lang( 'lang_v1.stock_managerment' )</h4>
            </div>
            <div class="col-md-2">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.view_deliver_orders', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.stock.view_deliver_orders' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.to_deliver', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.stock.to_deliver' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.create_stock_transfer_bill', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.stock.create_stock_transfer_bill' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'transfer.delete', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.transfer.delete' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.create_stock_adjustment_bill', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.stock.create_stock_adjustment_bill' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'confirm_export', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.stock.confirm_export') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.approve_adjustment_stock', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.stock.approve_adjustment_stock') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'adjustment.delete', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.adjustment.delete' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'shipping.create', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.shipping.create') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'shipping.update', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.shipping.update') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sale.reverse_size', false, ['class' => 'input-icheck']); !!}
                            {{ __('role.sale.reverse_size') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row check_group">
            <div class="col-md-1">
                <h4>@lang( 'sale.return' )</h4>
            </div>
            <div class="col-md-2">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'return.list', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.return.list') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.create_return_bill', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.sell.create_return_bill') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'return.update', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.return.update') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'approval_sell_return', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.return.approval') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'return.cancel', false, ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.return.cancel') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.brand' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'brand.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'brand.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'brand.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'brand.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.delete' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        {{--<div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.tax_rate' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_rate.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_rate.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_rate.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_rate.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.delete' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>--}}
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.unit' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'unit.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'unit.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'unit.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'unit.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.delete' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'category.category' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'category.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'category.create', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'category.update', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'category.delete', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.delete' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.report' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
            {{--@if(in_array('purchases', $enabled_modules) || in_array('add_sale', $enabled_modules) || in_array('pos_sale', $enabled_modules))
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('permissions[]', 'purchase_n_sell_report.view', false,
                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase_n_sell_report.view' ) }}
                  </label>
                </div>
              </div>
            @endif--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_report.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_report.view' ) }}
              </label>
            </div>
          </div>--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'contacts_report.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.contacts_report.view' ) }}
              </label>
            </div>
          </div>
          {{--@if(in_array('expenses', $enabled_modules))
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'expense_report.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense_report.view' ) }}
              </label>
            </div>
          </div>
          @endif--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'profit_loss_report.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.profit_loss_report.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'stock_report.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.stock_report.view' ) }}
              </label>
            </div>
          </div>
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'trending_product_report.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.trending_product_report.view' ) }}
              </label>
            </div>
          </div>--}}
{{--          <div class="col-md-12">--}}
{{--            <div class="checkbox">--}}
{{--              <label>--}}
{{--                {!! Form::checkbox('permissions[]', 'register_report.view', false, --}}
{{--                [ 'class' => 'input-icheck']); !!} {{ __( 'role.register_report.view' ) }}--}}
{{--              </label>--}}
{{--            </div>--}}
{{--          </div>--}}

          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sales_representative.view', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sales_representative.view' ) }}
              </label>
            </div>
          </div>--}}

            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.revenue_date', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.revenue_date' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.revenue_by_month', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.revenue_by_month' ) }}
                    </label>
                </div>
            </div>
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.revenue_by_month', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.revenue_by_month' ) }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.reporting_date', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.reporting_date' ) }}
                    </label>
                </div>
            </div>--}}
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.transfer', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.transfer' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.input_output_inventory', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.input_output_inventory' ) }}
                    </label>
                </div>
            </div>

            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'target.report-owner-target', false,
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.target.report-owner-target' ) }}
                    </label>
                </div>
            </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'role.settings' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'business_settings.access', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.business_settings.access' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'barcode_settings.access', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.barcode_settings.access' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'invoice_settings.access', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.invoice_settings.access' ) }}
              </label>
            </div>
          </div>
          {{--@if(in_array('expenses', $enabled_modules))
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'expense.access', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense.access' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'view_own_expense', false,['class' => 'input-icheck']); !!}
                {{ __('lang_v1.view_own_expense') }}
              </label>
            </div>
          </div>
          @endif--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'access_printers', false,['class' => 'input-icheck']); !!}
                {{ __('lang_v1.access_printers') }}
              </label>
            </div>
          </div>

        </div>
        </div>
        <hr>

        <div class="row check_group">
            <div class="col-md-1">
                <h4>@lang( 'role.warehouse' )</h4>
            </div>
            <div class="col-md-2">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'warehouse.view', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.warehouse.view' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'warehouse.create', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.warehouse.create' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'warehouse.update', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.warehouse.update' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'warehouse.delete', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.warehouse.delete' ) }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <hr>

        <div class="row check_group">
            <div class="col-md-1">
                <h4>@lang( 'role.target' )</h4>
            </div>
            <div class="col-md-2">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'target.view', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.target.view' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'target.create', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.target.create' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'target.update', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.target.update' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'target.delete', false,
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.target.delete' ) }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <hr>

        <div class="row check_group">
        <div class="col-md-3">
          <h4>@lang( 'role.dashboard' ) @show_tooltip(__('tooltip.dashboard_permission'))</h4>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'dashboard.data', true,
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.dashboard.data' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-3">
          <h4>@lang( 'account.account' )</h4>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'account.access', true,
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.access_accounts' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        @if(in_array('tables', $enabled_modules) && in_array('service_staff', $enabled_modules) )
        <div class="row check_group">
        <div class="col-md-1">
          <h4>@lang( 'restaurant.bookings' )</h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > {{ __( 'role.select_all' ) }}
              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'crud_all_bookings', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'restaurant.add_edit_view_all_booking' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'crud_own_bookings', false,
                [ 'class' => 'input-icheck']); !!} {{ __( 'restaurant.add_edit_view_own_booking' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        @endif
        <div class="row">
        <div class="col-md-3">
          <h4>@lang( 'lang_v1.access_selling_price_groups' )</h4>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'access_default_selling_price', false,
                [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.default_selling_price') }}
              </label>
            </div>
          </div>
          @if(count($selling_price_groups) > 0)
          @foreach($selling_price_groups as $selling_price_group)
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('spg_permissions[]', 'selling_price_group.' . $selling_price_group->id, false,
                [ 'class' => 'input-icheck']); !!} {{ $selling_price_group->name }}
              </label>
            </div>
          </div>
          @endforeach
          @endif
        </div>
        </div>
        @if(in_array('tables', $enabled_modules))
          <div class="row">
            <div class="col-md-3">
              <h4>@lang( 'restaurant.restaurant' )</h4>
            </div>
            <div class="col-md-9">
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('permissions[]', 'access_tables', false,
                    [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.access_tables') }}
                  </label>
                </div>
              </div>
            </div>
          </div>
        @endif

        @include('role.partials.module_permissions')
        <div class="row">
        <div class="col-md-12">
           <button type="submit" class="btn btn-primary pull-right">@lang( 'messages.save' )</button>
        </div>
        </div>

        {!! Form::close() !!}
    @endcomponent
</section>
<!-- /.content -->
@endsection