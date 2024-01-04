<script type="text/javascript">
    base_path = "{{url('/')}}";
    //used for push notification
    APP = {};
    APP.PUSHER_APP_KEY = '{{config('broadcasting.connections.pusher.key')}}';
    APP.PUSHER_APP_CLUSTER = '{{config('broadcasting.connections.pusher.options.cluster')}}';
    //variable from app service provider
    APP.PUSHER_ENABLED = '{{$__is_pusher_enabled}}';
    @auth
        @php
            $user = Auth::user();
        @endphp
        APP.USER_ID = "{{$user->id}}";
    @else
        APP.USER_ID = '';
    @endauth
</script>

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js?v=$asset_v"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js?v=$asset_v"></script>
<![endif]-->
<script src="{{ asset('js/vendor.js?v=' . $asset_v) }}"></script>

@if(file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif
@php
    $business_date_format = session('business.date_format', config('constants.default_date_format'));
    $datepicker_date_format = str_replace('d', 'dd', $business_date_format);
    $datepicker_date_format = str_replace('m', 'mm', $datepicker_date_format);
    $datepicker_date_format = str_replace('Y', 'yyyy', $datepicker_date_format);

    $moment_date_format = str_replace('d', 'DD', $business_date_format);
    $moment_date_format = str_replace('m', 'MM', $moment_date_format);
    $moment_date_format = str_replace('Y', 'YYYY', $moment_date_format);

    $business_time_format = session('business.time_format');
    $moment_time_format = 'HH:mm';
    if($business_time_format == 12){
        $moment_time_format = 'hh:mm A';
    }

    $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];

    $default_datatable_page_entries = !empty($common_settings['default_datatable_page_entries']) ? $common_settings['default_datatable_page_entries'] : 25;
@endphp

<script>
    moment.tz.setDefault('{{ Session::get("business.time_zone") }}');
    $(document).ready(function(){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if(config('app.debug') == false)
            $.fn.dataTable.ext.errMode = 'throw';
        @endif
    });

    var financial_year = {
        start: moment('{{ Session::get("financial_year.start") }}'),
        end: moment('{{ Session::get("financial_year.end") }}'),
    }
    var receipt_today = {
        start: moment(),
        end: moment(),
    }
    @if(file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    //Default setting for select2
    $.fn.select2.defaults.set("language", "{{session()->get('user.language', config('app.locale'))}}");
    @endif

    var datepicker_date_format = "{{$datepicker_date_format}}";
    var moment_date_format = "{{$moment_date_format}}";
    var moment_time_format = "{{$moment_time_format}}";

    var app_locale = "{{session()->get('user.language', config('app.locale'))}}";

    var non_utf8_languages = [
        @foreach(config('constants.non_utf8_languages') as $const)
        "{{$const}}",
        @endforeach
    ];

    var __default_datatable_page_entries = "{{$default_datatable_page_entries}}";

    var __new_notification_count_interval = "{{config('constants.new_notification_count_interval', 60)}}000";
</script>

{{--@if(file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif--}}

<script src="{{ asset('js/lang/vi.js?v=' . $asset_v) }}"></script>

<script src="{{ asset('js/functions.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/common.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/app.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/help-tour.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/documents_and_note.js?v=' . $asset_v) }}"></script>

<!-- TODO -->
@if(file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@endif
@php
    $validation_lang_file = 'messages_' . session()->get('user.language', config('app.locale') ) . '.js';
@endphp
@if(file_exists(public_path() . '/js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file))
    <script src="{{ asset('js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file . '?v=' . $asset_v) }}"></script>
@endif

@if(!empty($__system_settings['additional_js']))
    {!! $__system_settings['additional_js'] !!}
@endif
@yield('javascript')

@if(Module::has('Essentials'))
  @includeIf('essentials::layouts.partials.footer_part')
@endif

<script src="https://www.gstatic.com/firebasejs/8.1.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.1.0/firebase-messaging.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.1.0/firebase-analytics.js"></script>

<script type="text/javascript">
    const firebaseConfig = {
        apiKey: "AIzaSyC34S_qkSaiKYnd_ROLcJQlAFZ-zxyhJR8",
        authDomain: "ngoc-thai-a941b.firebaseapp.com",
        databaseURL: "https://ngoc-thai-a941b.firebaseio.com",
        projectId: "ngoc-thai-a941b",
        storageBucket: "ngoc-thai-a941b.appspot.com",
        messagingSenderId: "242358485222",
        appId: "1:242358485222:web:904067178b1de4050c7b27",
        measurementId: "G-Y81F6ELZWL"
    };

    firebase.initializeApp(firebaseConfig);
    firebase.analytics();

    <!-- Firebase set token -->
    // Retrieve Firebase Messaging object.
    const messaging = firebase.messaging();
    messaging.usePublicVapidKey('BAJ3lVNwPIYWoGPs17xgGkfN6li9UEvnt9MUDhn6m6zoeZ-3ENXiguj3zr7N1TYc0qowahLP2iDPs32MOXPpvlY');
    messaging.requestPermission().then(function() {
        console.log('Notification permission granted.');
        // TODO(developer): Retrieve an Instance ID token for use with FCM.
        // ...
    }).catch(function(err) {
        console.log('Unable to get permission to notify.', err);
    });

    messaging.getToken().then(function(currentToken) {
        if (currentToken) {
            $('#device_token').val(currentToken);
            // updateUIForPushEnabled(currentToken);
        } else {
            // Show permission request.
            console.log('No Instance ID token available. Request permission to generate one.');
            setTokenSentToServer(false);
        }
    }).catch(function(err) {
        console.log('An error occurred while retrieving token. ', err);
        // showToken('Error retrieving Instance ID token. ', err);
        setTokenSentToServer(false);
    });
    function setTokenSentToServer(sent) {
        window.localStorage.setItem('sentToServer', sent ? '1' : '0');
    }

    <!-- Firebase receive notifications -->
    // setting notifications
    var notificationsWrapper = $('.notifications-menu');
    var notificationsCountElem = notificationsWrapper.find('.notifications_count');
    var notificationsCount = notificationsCountElem.html();
    if(notificationsCount != ''){
        notificationsCount = parseInt(notificationsCount);
    }else{
        notificationsCount = 0;
    }
    var notifications = notificationsWrapper.find('#notifications_list');
    messaging.onMessage((payload) => {
        // console.log('Message received. ', payload);
        notificationsCount += 1;
        notificationsCountElem.html(notificationsCount);
    });
</script>