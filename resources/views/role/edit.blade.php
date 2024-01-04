@extends('layouts.app')
@section('title', __('role.edit_role'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>@lang( 'role.edit_role' )</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action('RoleController@update', [$role->id]), 'method' => 'PUT', 'id' => 'role_form' ]) !!}
        <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label('name', __( 'user.role_name' ) . ':*') !!}
              {!! Form::text('name', str_replace( '#' . auth()->user()->business_id, '', $role->name) , ['class' => 'form-control', 'required', 'placeholder' => __( 'user.role_name' ) ]); !!}
          </div>
        </div>
        </div>
        <div class="row">
        <div class="col-md-2">
          <h4>@lang( 'lang_v1.user_type' )</h4>
        </div>
        <div class="col-md-9 col-md-offset-1">
          <div class="col-md-12">
          <div class="checkbox">
            <label>
              {!! Form::checkbox('is_service_staff', 1, $role->is_service_staff,
              [ 'class' => 'input-icheck']); !!} {{ __( 'restaurant.service_staff' ) }}
            </label>
            @show_tooltip(__('restaurant.tooltip_service_staff'))
          </div>
          </div>
        </div>
        </div>
        <div class="row">
        <div class="col-md-3">
          <label>@lang( 'user.permissions' ):</label>
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
                            {!! Form::checkbox('permissions[]', 'sell.confirm_bank_transfer_method', in_array('sell.confirm_bank_transfer_method', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.confirm_bank_transfer_method' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.confirm_reduce_debt', in_array('sell.confirm_reduce_debt', $role_permissions), ['class' => 'input-icheck']); !!}
                            {{ __('role.confirm_reduce_debt') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.close_end_of_day', in_array('sell.close_end_of_day', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'user.view', in_array('user.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'user.create', in_array('user.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'user.update', in_array('user.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.user.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'user.delete', in_array('user.delete', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'roles.view', in_array('roles.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.view_role' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'roles.create', in_array('roles.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.add_role' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'roles.update', in_array('roles.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit_role' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'roles.delete', in_array('roles.delete', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'supplier.view', in_array('supplier.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'supplier.create', in_array('supplier.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'supplier.update', in_array('supplier.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.supplier.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'supplier.delete', in_array('supplier.delete', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'customer.view', in_array('customer.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'customer.create', in_array('customer.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'customer.update', in_array('customer.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'customer.delete', in_array('customer.delete', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'product.view', in_array('product.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.create', in_array('product.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.update', in_array('product.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.delete', in_array('product.delete', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.product.delete' ) }}
              </label>
            </div>
          </div>
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'product.opening_stock', in_array('product.opening_stock', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.add_opening_stock' ) }}
              </label>
            </div>
          </div>--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'view_purchase_price', in_array('view_purchase_price', $role_permissions),['class' => 'input-icheck']); !!}
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
                {!! Form::checkbox('permissions[]', 'purchase.view', in_array('purchase.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.create', in_array('purchase.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.update', in_array('purchase.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.delete', in_array('purchase.delete', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase.delete' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'purchase.payments', in_array('purchase.payments', $role_permissions),['class' => 'input-icheck']); !!}
                {{ __('lang_v1.purchase.payments') }}
              </label>
              @show_tooltip(__('lang_v1.purchase_payments'))
            </div>
          </div>
{{--          <div class="col-md-12">--}}
{{--            <div class="checkbox">--}}
{{--              <label>--}}
{{--                {!! Form::checkbox('permissions[]', 'view_own_purchase', in_array('view_own_purchase', $role_permissions),['class' => 'input-icheck']); !!}--}}
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
                    {!! Form::checkbox('permissions[]', 'direct_sell.access', in_array('direct_sell.access', $role_permissions), ['class' => 'input-icheck']); !!}
                    {{ __('role.direct_sell.access') }}
                </label>
            </div>
        </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.view', in_array('sell.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.view' ) }}
              </label>
            </div>
          </div>
{{--          @if(in_array('pos_sale', $enabled_modules))--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.create', in_array('sell.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.create' ) }}
              </label>
            </div>
          </div>
{{--          @endif--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.update', in_array('sell.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.update' ) }}
              </label>
            </div>
          </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.cancel', in_array('sell.cancel', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.cancel' ) }}
                    </label>
                </div>
            </div>
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.approve_transfer_money_bill', in_array('sell.approve_transfer_money_bill', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.approve_transfer_money_bill' ) }}
                    </label>
                </div>
            </div>--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.delete', in_array('sell.delete', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sell.delete' ) }}
              </label>
            </div>
          </div>--}}
          {{--@if(in_array('add_sale', $enabled_modules))
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'direct_sell.access', in_array('direct_sell.access', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.direct_sell.access' ) }}
              </label>
            </div>
          </div>
          @endif--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'list_drafts', in_array('list_drafts', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.list_drafts' ) }}
              </label>
            </div>
          </div>
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'list_quotations', in_array('list_quotations', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.list_quotations' ) }}
              </label>
            </div>
          </div>--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'view_own_sell_only', in_array('view_own_sell_only', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.view_own_sell_only' ) }}
              </label>
            </div>
          </div>--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sell.payments', in_array('sell.payments', $role_permissions), ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.sell.payments') }}
              </label>
              @show_tooltip(__('lang_v1.sell_payments'))
            </div>
          </div>--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'edit_product_price_from_sale_screen', in_array('edit_product_price_from_sale_screen', $role_permissions), ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.edit_product_price_from_sale_screen') }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'edit_product_price_from_pos_screen', in_array('edit_product_price_from_pos_screen', $role_permissions), ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.edit_product_price_from_pos_screen') }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'edit_product_discount_from_sale_screen', in_array('edit_product_discount_from_sale_screen', $role_permissions), ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.edit_product_discount_from_sale_screen') }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'edit_product_discount_from_pos_screen', in_array('edit_product_discount_from_pos_screen', $role_permissions), ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.edit_product_discount_from_pos_screen') }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'discount.access', in_array('discount.access', $role_permissions), ['class' => 'input-icheck']); !!}
                {{ __('lang_v1.discount.access') }}
              </label>
            </div>
          </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.accept_received_money_to_custom', in_array('sell.accept_received_money_to_custom', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.accepted_customer' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.confirm_bank_transfer_method', in_array('sell.confirm_bank_transfer_method', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.confirm_bank_transfer_method' ) }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.create_bill_transfer', in_array('sell.create_bill_transfer', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.create_bill_transfer' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'access_shipping', in_array('access_shipping', $role_permissions), ['class' => 'input-icheck']); !!}
                        {{ __('lang_v1.access_shipping') }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.create_return_bill', in_array('sell.create_return_bill', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.sell.create_return_bill' ) }}
                    </label>
                </div>
            </div>--}}
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.receipt_expense', in_array('sell.receipt_expense', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.sell.receipt_expense' ) }}
                    </label>
                </div>
            </div>--}}
          @if(in_array('types_of_service', $enabled_modules))
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'access_types_of_service', in_array('access_types_of_service', $role_permissions),
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
                            {!! Form::checkbox('permissions[]', 'direct_sell.access', in_array('direct_sell.access', $role_permissions), ['class' => 'input-icheck']); !!}
                            {{ __('role.direct_sell.access') }}
                        </label>
                    </div>
                </div>--}}
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.receipt_expense', in_array('sell.receipt_expense', $role_permissions), ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.sell.receipt_expense') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.payments', in_array('sell.payments', $role_permissions), ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.sell.payments') }}
                        </label>
                        @show_tooltip(__('lang_v1.sell_payments'))
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.accept_received_money_to_custom', in_array('sell.accept_received_money_to_custom', $role_permissions), ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.accepted_customer') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.add_reduce_debt', in_array('sell.add_reduce_debt', $role_permissions), ['class' => 'input-icheck']); !!}
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
                            {!! Form::checkbox('permissions[]', 'stock.view_deliver_orders', in_array('stock.view_deliver_orders', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.stock.view_deliver_orders' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.to_deliver', in_array('stock.to_deliver', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.stock.to_deliver' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.create_stock_transfer_bill', in_array('stock.create_stock_transfer_bill', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.stock.create_stock_transfer_bill' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'confirm_export', in_array('confirm_export', $role_permissions), ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.stock.confirm_export') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.approve_adjustment_stock', in_array('stock.approve_adjustment_stock', $role_permissions), ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.stock.approve_adjustment_stock') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'transfer.delete', in_array('transfer.delete', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.transfer.delete' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'stock.create_stock_adjustment_bill', in_array('stock.create_stock_adjustment_bill', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.stock.create_stock_adjustment_bill' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'adjustment.delete', in_array('adjustment.delete', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.adjustment.delete' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'shipping.create', in_array('shipping.create', $role_permissions),
                            ['class' => 'input-icheck']); !!} {{ __('lang_v1.shipping.create') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'shipping.update', in_array('shipping.update', $role_permissions),
                            ['class' => 'input-icheck']); !!} {{ __('lang_v1.shipping.update') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sale.reverse_size', in_array('sale.reverse_size', $role_permissions),
                            ['class' => 'input-icheck']); !!} {{ __('role.sale.reverse_size') }}
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
                            {!! Form::checkbox('permissions[]', 'return.list', in_array('return.list', $role_permissions),
                            ['class' => 'input-icheck']); !!} {{ __('lang_v1.return.list') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'sell.create_return_bill', in_array('sell.create_return_bill', $role_permissions),
                            ['class' => 'input-icheck']); !!} {{ __('lang_v1.sell.create_return_bill') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'return.update', in_array('return.update', $role_permissions),
                            ['class' => 'input-icheck']); !!} {{ __('lang_v1.return.update') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'approval_sell_return', in_array('approval_sell_return', $role_permissions), ['class' => 'input-icheck']); !!}
                            {{ __('lang_v1.return.approval') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'return.cancel', in_array('return.cancel', $role_permissions),
                            ['class' => 'input-icheck']); !!} {{ __('lang_v1.return.cancel') }}
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
                {!! Form::checkbox('permissions[]', 'brand.view', in_array('brand.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'brand.create', in_array('brand.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'brand.update', in_array('brand.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'brand.delete', in_array('brand.delete', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'tax_rate.view', in_array('tax_rate.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_rate.create', in_array('tax_rate.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_rate.update', in_array('tax_rate.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_rate.delete', in_array('tax_rate.delete', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.delete' ) }}
              </label>
            </div>
          </div>
        </div>
        </div>--}}
{{--        <hr>--}}
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
                {!! Form::checkbox('permissions[]', 'unit.view', in_array('unit.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'unit.create', in_array('unit.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'unit.update', in_array('unit.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'unit.delete', in_array('unit.delete', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'category.view', in_array('category.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.view' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'category.create', in_array('category.create', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.create' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'category.update', in_array('category.update', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.update' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'category.delete', in_array('category.delete', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'purchase_n_sell_report.view', in_array('purchase_n_sell_report.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.purchase_n_sell_report.view' ) }}
              </label>
            </div>
          </div>
        @endif--}}
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'contacts_report.view', in_array('contacts_report.view', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.contacts_report.view' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'profit_loss_report.view', in_array('profit_loss_report.view', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.profit_loss_report.view' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock_report.view', in_array('stock_report.view', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'role.stock_report.view' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.revenue_date', in_array('report.revenue_date', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.revenue_date' ) }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.revenue_by_month', in_array('report.revenue_by_month', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.revenue_by_month' ) }}
                    </label>
                </div>
            </div>
            {{--<div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.reporting_date', in_array('report.reporting_date', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.reporting_date' ) }}
                    </label>
                </div>
            </div>--}}
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.transfer', in_array('report.transfer', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.transfer' ) }}
                    </label>
                </div>
            </div>

            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'report.input_output_inventory', in_array('report.input_output_inventory', $role_permissions),
                        [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.report.input_output_inventory' ) }}
                    </label>
                </div>
            </div>

          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'tax_report.view', in_array('tax_report.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_report.view' ) }}
              </label>
            </div>
          </div>--}}
          {{--@if(in_array('expenses', $enabled_modules))
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'expense_report.view', in_array('expense_report.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense_report.view' ) }}
              </label>
            </div>
          </div>
          @endif--}}
          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'trending_product_report.view', in_array('trending_product_report.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.trending_product_report.view' ) }}
              </label>
            </div>
          </div>--}}

{{--          <div class="col-md-12">--}}
{{--            <div class="checkbox">--}}
{{--              <label>--}}
{{--                {!! Form::checkbox('permissions[]', 'register_report.view', in_array('register_report.view', $role_permissions), --}}
{{--                [ 'class' => 'input-icheck']); !!} {{ __( 'role.register_report.view' ) }}--}}
{{--              </label>--}}
{{--            </div>--}}
{{--          </div>--}}

          {{--<div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'sales_representative.view', in_array('sales_representative.view', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.sales_representative.view' ) }}
              </label>
            </div>
          </div>--}}

          <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'target.report-owner-target', in_array('target.report-owner-target', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'business_settings.access', in_array('business_settings.access', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.business_settings.access' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'barcode_settings.access', in_array('barcode_settings.access', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.barcode_settings.access' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'invoice_settings.access', in_array('invoice_settings.access', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.invoice_settings.access' ) }}
              </label>
            </div>
          </div>
          {{--@if(in_array('expenses', $enabled_modules))
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'expense.access', in_array('expense.access', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'role.expense.access' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'view_own_expense', in_array('view_own_expense', $role_permissions),['class' => 'input-icheck']); !!}
                {{ __('lang_v1.view_own_expense') }}
              </label>
            </div>
          </div>
          @endif--}}
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'access_printers', in_array('access_printers', $role_permissions),['class' => 'input-icheck']); !!}
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
                            {!! Form::checkbox('permissions[]', 'warehouse.view', in_array('warehouse.view', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.warehouse.view' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'warehouse.create', in_array('warehouse.create', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.warehouse.create' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'warehouse.update', in_array('warehouse.update', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.warehouse.update' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'warehouse.delete', in_array('warehouse.delete', $role_permissions),
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
                            {!! Form::checkbox('permissions[]', 'target.view', in_array('target.view', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.target.view' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'target.create', in_array('target.create', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.target.create' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'target.update', in_array('target.update', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.target.update' ) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('permissions[]', 'target.delete', in_array('target.delete', $role_permissions),
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.target.delete' ) }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <hr>

        <div class="row">
        <div class="col-md-3">
          <h4>@lang( 'role.dashboard' )</h4>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'dashboard.data', in_array('dashboard.data', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'account.access', in_array('account.access', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'crud_all_bookings', in_array('crud_all_bookings', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __( 'restaurant.add_edit_view_all_booking' ) }}
              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('permissions[]', 'crud_own_bookings', in_array('crud_own_bookings', $role_permissions),
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
                {!! Form::checkbox('permissions[]', 'access_default_selling_price', in_array('access_default_selling_price', $role_permissions),
                [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.default_selling_price') }}
              </label>
            </div>
          </div>
          @if(count($selling_price_groups) > 0)
          @foreach($selling_price_groups as $selling_price_group)
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('spg_permissions[]', 'selling_price_group.' . $selling_price_group->id, in_array('selling_price_group.' . $selling_price_group->id, $role_permissions),
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
                    {!! Form::checkbox('permissions[]', 'access_tables', in_array('access_tables', $role_permissions),
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
           <button type="submit" class="btn btn-primary pull-right">@lang( 'messages.update' )</button>
        </div>
        </div>

        {!! Form::close() !!}
    @endcomponent
</section>
<!-- /.content -->
@endsection
