@extends('layouts.app')

@section('title', __( 'lang_v1.view_user' ))

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-4">
                <h3 style="margin: 0px;">@lang( 'lang_v1.view_user' )</h3>
            </div>
{{--            <div class="col-md-4 col-xs-12 mt-15 pull-right">--}}
{{--                {!! Form::select('user_id', $users, $user->id , ['class' => 'form-control select2', 'id' => 'user_id']); !!}--}}
{{--            </div>--}}
        </div>
        <br>

        <div class="box box-primary">
            <div class="box-body box-profile">
                <div class="row">
                    <div class="col-md-3">
                        <h3 class="profile-username">
                            {{$user->user_full_name}}
                        </h3>
                    </div>
                    <div class="col-md-9">
                        @if($user->status == 'active')
                            <span class="label label-success">
                                @lang('business.is_active')
                            </span>
                        @else
                            <span class="label label-danger">
                                @lang('lang_v1.inactive')
                            </span>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>@lang('business.email'): </strong>{{$user->email}}</p>
                        <p><strong>@lang('user.role'): </strong>{{$user->role_name}}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>@lang( 'business.username' ): </strong>{{$user->username}}</p>
                        <p><strong>@lang( 'lang_v1.cmmsn_percent' ): </strong> {{$user->cmmsn_percent}}%</p>
                    </div>
                    <div class="col-md-12">
                        <h4>@lang('lang_v1.more_info')</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>@lang( 'lang_v1.dob' ):</strong> @if(!empty($user->dob)) {{@format_date($user->dob)}} @endif</p>
                                <p><strong>@lang( 'lang_v1.contact_no' ):</strong> {{$user->contact_number ?? ''}}</p>
                                <p><strong>@lang( 'lang_v1.blood_group' ):</strong> {{$user->blood_group ?? ''}}</p>
                                <p><strong>@lang( 'lang_v1.fb_link' ):</strong> {{$user->fb_link ?? ''}}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>@lang( 'lang_v1.gender' ):</strong> @if(!empty($user->gender)) @lang('lang_v1.' .$user->gender) @endif</p>
                                <p><strong>@lang('lang_v1.id_proof_number'):</strong>{{$user->id_proof_number ?? ''}}</p>
                                <p><strong>@lang( 'lang_v1.marital_status' ):</strong> @if(!empty($user->marital_status)) @lang('lang_v1.' .$user->marital_status) @endif</p>
                                <p><strong>@lang( 'lang_v1.twitter_link' ):</strong> {{$user->twitter_link ?? ''}}</p>
                            </div>
                        </div>

                        <div class="clearfix"></div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>@lang('lang_v1.permanent_address'):</strong><br>
                                <p>{{$user->permanent_address ?? ''}}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>@lang('lang_v1.current_address'):</strong><br>
                                <p>{{$user->current_address ?? ''}}</p>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h4>@lang('lang_v1.bank_details'):</h4>
                            </div>
                            @php
                                $bank_details = !empty($user->bank_details) ? json_decode($user->bank_details, true) : [];
                            @endphp
                            <div class="col-md-6">
                                <p><strong>@lang('lang_v1.account_holder_name'):</strong> {{$bank_details['account_holder_name'] ?? ''}}</p>
                                <p><strong>@lang('lang_v1.bank_name'):</strong> {{$bank_details['bank_name'] ?? ''}}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>@lang('lang_v1.account_number'):</strong> {{$bank_details['account_number'] ?? ''}}</p>
                                <p><strong>@lang('lang_v1.branch'):</strong> {{$bank_details['branch'] ?? ''}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

{{--                        @php--}}
{{--                            if(isset($user->media->display_url)) {--}}
{{--                                $img_src = $user->media->display_url;--}}
{{--                            } else {--}}
{{--                                $img_src = 'https://ui-avatars.com/api/?name='.$user->first_name;--}}
{{--                            }--}}
{{--                        @endphp--}}

{{--                        <img class="profile-user-img img-responsive img-circle" src="{{$img_src}}" alt="User profile picture">--}}

{{--                        <h3 class="profile-username text-center">--}}
{{--                            {{$user->user_full_name}}--}}
{{--                        </h3>--}}

{{--                        <p class="text-muted text-center" title="@lang('user.role')">--}}
{{--                            {{$user->role_name}}--}}
{{--                        </p>--}}

{{--                        <ul class="list-group list-group-unbordered">--}}
{{--                            <li class="list-group-item">--}}
{{--                                <b>@lang( 'business.username' )</b>--}}
{{--                                <a class="pull-right">{{$user->username}}</a>--}}
{{--                            </li>--}}
{{--                            <li class="list-group-item">--}}
{{--                                <b>@lang( 'business.email' )</b>--}}
{{--                                <a class="pull-right">{{$user->email}}</a>--}}
{{--                            </li>--}}
{{--                            <li class="list-group-item">--}}
{{--                                <b>{{ __('lang_v1.status_for_user') }}</b>--}}
{{--                                @if($user->status == 'active')--}}
{{--                                    <span class="label label-success pull-right">--}}
{{--                                        @lang('business.is_active')--}}
{{--                                    </span>--}}
{{--                                @else--}}
{{--                                    <span class="label label-danger pull-right">--}}
{{--                                        @lang('lang_v1.inactive')--}}
{{--                                    </span>--}}
{{--                                @endif--}}
{{--                            </li>--}}
{{--                        </ul>--}}
{{--                        @can('user.update')--}}
{{--                            <a href="{{action('ManageUserController@edit', [$user->id])}}" class="btn btn-primary btn-block">--}}
{{--                                <i class="glyphicon glyphicon-edit"></i>--}}
{{--                                @lang("messages.edit")--}}
{{--                            </a>--}}
{{--                        @endcan--}}
{{--                        </div>--}}
{{--                    <!-- /.box-body -->--}}
{{--                </div>--}}
{{--                <!-- /.box -->--}}
{{--            </div>--}}
{{--            <div class="col-md-9">--}}
{{--                <div class="nav-tabs-custom">--}}
{{--                    <ul class="nav nav-tabs nav-justified">--}}
{{--                        <li class="active">--}}
{{--                            <a href="#user_info_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-user" aria-hidden="true"></i> @lang( 'lang_v1.user_info')</a>--}}
{{--                        </li>--}}
{{--                        --}}
{{--                        <li>--}}
{{--                            <a href="#documents_and_notes_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-paperclip" aria-hidden="true"></i> @lang('lang_v1.documents_and_notes')</a>--}}
{{--                        </li>--}}
{{--                    </ul>--}}

{{--                    <div class="tab-content">--}}
{{--                        <div class="tab-pane active" id="user_info_tab">--}}
{{--                            <div class="row">--}}
{{--                                <div class="col-md-12">--}}
{{--                                    <div class="col-md-6">--}}
{{--                                            <p><strong>@lang( 'lang_v1.cmmsn_percent' ): </strong> {{$user->cmmsn_percent}}%</p>--}}
{{--                                    </div>--}}
{{--                                    <div class="col-md-6">--}}
{{--                                        @php--}}
{{--                                            $selected_contacts = ''--}}
{{--                                        @endphp--}}
{{--                                        @if(count($user->contactAccess))--}}
{{--                                            @php--}}
{{--                                                $selected_contacts_array = [];--}}
{{--                                            @endphp--}}
{{--                                            @foreach($user->contactAccess as $contact)--}}
{{--                                                @php--}}
{{--                                                    $selected_contacts_array[] = $contact->name;--}}
{{--                                                @endphp--}}
{{--                                            @endforeach--}}
{{--                                            @php--}}
{{--                                                $selected_contacts = implode(', ', $selected_contacts_array);--}}
{{--                                            @endphp--}}
{{--                                        @else--}}
{{--                                            @php--}}
{{--                                                $selected_contacts = __('lang_v1.all');--}}
{{--                                            @endphp--}}
{{--                                        @endif--}}
{{--                                        <p>--}}
{{--                                            <strong>@lang( 'lang_v1.allowed_contacts' ): </strong>--}}
{{--                                                {{$selected_contacts}}--}}
{{--                                        </p>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            @include('user.show_details')--}}
{{--                        </div>--}}
{{--                        <div class="tab-pane" id="documents_and_notes_tab">--}}
{{--                            <!-- model id like project_id, user_id -->--}}
{{--                            <input type="hidden" name="notable_id" id="notable_id" value="{{$user->id}}">--}}
{{--                            <!-- model name like App\User -->--}}
{{--                            <input type="hidden" name="notable_type" id="notable_type" value="App\User">--}}
{{--                            <div class="document_note_body">--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

@endsection
@section('javascript')
    <!-- document & note.js -->
{{--    @include('documents_and_notes.document_and_note_js')--}}

    <script type="text/javascript">
        $(document).ready( function(){
            $('#user_id').change( function() {
                if ($(this).val()) {
                    window.location = "{{url('/users')}}/" + $(this).val();
                }
            });
        });
    </script>
@endsection