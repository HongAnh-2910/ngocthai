@extends('layouts.app')

@section('title', __( 'user.edit_user' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'user.edit_user' )</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action('ManageUserController@update', [$user->id]), 'method' => 'PUT', 'id' => 'user_edit_form' ]) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['title' => __('lang_v1.user_info'), 'class' => 'box-primary'])
{{--            <div class="col-md-2">--}}
{{--                <div class="form-group">--}}
{{--                  {!! Form::label('surname', __( 'business.prefix' ) . ':') !!}--}}
{{--                    {!! Form::text('surname', $user->surname, ['class' => 'form-control', 'placeholder' => __( 'business.prefix_placeholder' ) ]); !!}--}}
{{--                </div>--}}
{{--            </div>--}}
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('first_name', __( 'business.first_name' ) . ':*') !!}
                    {!! Form::text('first_name', $user->first_name, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.first_name' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('last_name', __( 'business.last_name' ) . ':') !!}
                    {!! Form::text('last_name', $user->last_name, ['class' => 'form-control', 'placeholder' => __( 'business.last_name' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('email', __( 'business.email' ) . ':*') !!}
                    {!! Form::text('email', $user->email, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.email' ) ]); !!}
                </div>
            </div>
{{--                <div class="col-md-6">--}}
{{--                    <div class="form-group">--}}
{{--                        {!! Form::label('cmmsn_percent', __( 'lang_v1.cmmsn_percent' ) . ':') !!}--}}
{{--                        @show_tooltip(__('lang_v1.commsn_percent_help'))--}}
{{--                        {!! Form::text('cmmsn_percent', $user->cmmsn_percent, ['class' => 'form-control input_number', 'placeholder' => __( 'lang_v1.cmmsn_percent' )]); !!}--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                <div class="col-md-4">--}}
{{--                    <div class="form-group">--}}
{{--                        <div class="checkbox">--}}
{{--                            <br/>--}}
{{--                            <label>--}}
{{--                                {!! Form::checkbox('selected_contacts', 1,--}}
{{--                                $user->selected_contacts,--}}
{{--                                [ 'class' => 'input-icheck', 'id' => 'selected_contacts']); !!} {{ __( 'lang_v1.allow_selected_contacts' ) }}--}}
{{--                            </label>--}}
{{--                            @show_tooltip(__('lang_v1.allow_selected_contacts_tooltip'))--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
            <div class="clearfix"></div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('password', __( 'business.password' ) . ':') !!}
                        {!! Form::password('password', ['class' => 'form-control', 'placeholder' => __( 'business.password'), 'required' => empty($user->allow_login) ? true : false ]); !!}
                        <p class="help-block">@lang('user.leave_password_blank')</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('confirm_password', __( 'business.confirm_password' ) . ':') !!}
                        {!! Form::password('confirm_password', ['class' => 'form-control', 'placeholder' => __( 'business.confirm_password' ), 'required' => empty($user->allow_login) ? true : false ]); !!}

                    </div>
                </div>

            <div class="col-sm-4 selected_contacts_div @if(!$user->selected_contacts) hide @endif">
                <div class="form-group">
                  {!! Form::label('selected_contacts', __('lang_v1.selected_contacts') . ':') !!}
                    <div class="form-group">
                      {!! Form::select('selected_contact_ids[]', $contacts, $contact_access, ['class' => 'form-control select2', 'multiple', 'style' => 'width: 100%;' ]); !!}
                    </div>
                </div>
            </div>

            <div class="clearfix"></div>
            <div class="col-md-4">
                <div class="form-group">
                  <div class="checkbox">
                    <label>
                         {!! Form::checkbox('is_active', $user->status, $is_checked_checkbox, ['class' => 'input-icheck status']); !!} {{ __('lang_v1.status_for_user') }}
                    </label>
                    @show_tooltip(__('lang_v1.tooltip_enable_user_active'))
                  </div>
                </div>
            </div>

        @endcomponent
        </div>
        <div class="col-md-12">
        @component('components.widget', ['title' => __('lang_v1.roles_and_permissions')])
            <div class="col-md-4">
                <div class="form-group">
                    <div class="checkbox">
                      <label>
                        {!! Form::checkbox('allow_login', 1, !empty($user->allow_login),
                        [ 'class' => 'input-icheck', 'id' => 'allow_login']); !!} {{ __( 'lang_v1.allow_login' ) }}
                      </label>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="user_auth_fields @if(empty($user->allow_login)) hide @endif">
            @if(empty($user->allow_login))
                <div class="col-md-4">
                    <div class="form-group">
                      {!! Form::label('username', __( 'business.username' ) . ':') !!}
                      @if(!empty($username_ext))
                        <div class="input-group">
                          {!! Form::text('username', null, ['class' => 'form-control', 'placeholder' => __( 'business.username' ) ]); !!}
                          <span class="input-group-addon">{{$username_ext}}</span>
                        </div>
                        <p class="help-block" id="show_username"></p>
                      @else
                          {!! Form::text('username', null, ['class' => 'form-control', 'placeholder' => __( 'business.username' ) ]); !!}
                      @endif
                      <p class="help-block">@lang('lang_v1.username_help')</p>
                    </div>
                </div>
            @endif
{{--            <div class="col-md-4">--}}
{{--                <div class="form-group">--}}
{{--                  {!! Form::label('password', __( 'business.password' ) . ':') !!}--}}
{{--                    {!! Form::password('password', ['class' => 'form-control', 'placeholder' => __( 'business.password'), 'required' => empty($user->allow_login) ? true : false ]); !!}--}}
{{--                    <p class="help-block">@lang('user.leave_password_blank')</p>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-md-4">--}}
{{--                <div class="form-group">--}}
{{--                  {!! Form::label('confirm_password', __( 'business.confirm_password' ) . ':') !!}--}}
{{--                    {!! Form::password('confirm_password', ['class' => 'form-control', 'placeholder' => __( 'business.confirm_password' ), 'required' => empty($user->allow_login) ? true : false ]); !!}--}}

{{--                </div>--}}
{{--            </div>--}}
            </div>
            <div class="clearfix"></div>
            @php
                $role_id = !empty($user->roles->first()->id) ? $user->roles->first()->id : null
            @endphp
            <div class="col-md-6">
                <div class="form-group">
                  {!! Form::label('role', __( 'user.role' ) . ':*') !!} @show_tooltip(__('lang_v1.admin_role_location_permission_help'))
                    {!! Form::select('role', $roles, $role_id, ['class' => 'form-control select2', 'style' => 'width: 100%;']); !!}
                </div>
            </div>
            <div class="clearfix"></div>

            <div id="permissions_box" {{ $role_id == 1 ? 'style=display:none' : '' }}>
                <div class="col-md-12">
                    <h4>@lang( 'role.access_locations' ) @show_tooltip(__('tooltip.access_locations_permission'))</h4>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('access_all_locations', 'access_all_locations', !is_array($permitted_locations) && $permitted_locations == 'all',
                            [ 'class' => 'input-icheck']); !!} {{ __( 'role.all_locations' ) }}
                        </label>
                    </div>
                </div>
                @foreach($locations as $location)
                        <div class="col-md-2">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('location_permissions['. $location->id .'][location]', 'location.' . $location->id, is_array($permitted_locations) && in_array($location->id, $permitted_locations),
                                    [ 'class' => 'input-icheck']); !!} {{ $location->name }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('location_permissions['. $location->id .'][access_all_warehouses]', 'access_all_warehouses', is_array($permitted_warehouses) && in_array('location.' . $location->id.'.access_all_warehouses', $permitted_warehouses),
                                          ['class' => 'input-icheck']); !!} {{ __( 'role.all_warehouses', ['location' => $location->name]) }}
                                    </label>
                                </div>
                            </div>

                            @foreach($location->warehouses as $warehouse)
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox('location_permissions['. $location->id .'][warehouses][]', 'warehouse.' . $warehouse->id, is_array($permitted_warehouses) && in_array($warehouse->id, $permitted_warehouses),
                                              [ 'class' => 'input-icheck']); !!} {{ $warehouse->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
            </div>
        @endcomponent
        </div>
    </div>
    @include('user.edit_profile_form_part', ['bank_details' => !empty($user->bank_details) ? json_decode($user->bank_details, true) : null])

    @if(!empty($form_partials))
      @foreach($form_partials as $partial)
        {!! $partial !!}
      @endforeach
    @endif
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right" id="submit_user_button">@lang( 'messages.update' )</button>
        </div>
    </div>
    {!! Form::close() !!}
  @stop
@section('javascript')
<script type="text/javascript">
  $(document).ready(function(){
    $('#selected_contacts').on('ifChecked', function(event){
      $('div.selected_contacts_div').removeClass('hide');
    });
    $('#selected_contacts').on('ifUnchecked', function(event){
      $('div.selected_contacts_div').addClass('hide');
    });
    $('#allow_login').on('ifChecked', function(event){
      $('div.user_auth_fields').removeClass('hide');
    });
    $('#allow_login').on('ifUnchecked', function(event){
      $('div.user_auth_fields').addClass('hide');
    });
  });

  $('form#user_edit_form').validate({
                rules: {
                    first_name: {
                        required: true,
                    },
                    email: {
                        email: true,
                        remote: {
                            url: "/business/register/check-email",
                            type: "post",
                            data: {
                                email: function() {
                                    return $( "#email" ).val();
                                },
                                user_id: {{$user->id}}
                            }
                        }
                    },
                    password: {
                        minlength: 5
                    },
                    confirm_password: {
                        equalTo: "#password",
                    },
                    username: {
                        minlength: 5,
                        remote: {
                            url: "/business/register/check-username",
                            type: "post",
                            data: {
                                username: function() {
                                    return $( "#username" ).val();
                                },
                                @if(!empty($username_ext))
                                  username_ext: "{{$username_ext}}"
                                @endif
                            }
                        }
                    }
                },
                messages: {
                    password: {
                        minlength: 'Password should be minimum 5 characters',
                    },
                    confirm_password: {
                        equalTo: 'Should be same as password'
                    },
                    username: {
                        remote: 'Invalid username or User already exist'
                    },
                    email: {
                        remote: '{{ __("validation.unique", ["attribute" => __("business.email")]) }}'
                    }
                }
            });
</script>
@endsection