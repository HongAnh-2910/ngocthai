@php
    $all_notifications = auth()->user()->notifications;
    $unread_notifications = $all_notifications->where('read_at', null);
    $total_unread = count($unread_notifications);
@endphp
<!-- Notifications: style can be found in dropdown.less -->
<li class="dropdown notifications-menu">
    <a href="#" class="dropdown-toggle load_notifications" data-toggle="dropdown" id="show_unread_notifications" data-loaded="false">
        <i class="fas fa-bell"></i>
        <span class="label label-warning notifications_count">@if(!empty($total_unread)){{$total_unread}}@endif</span>
    </a>
    <ul class="dropdown-menu">
        <!-- <li class="header">You have 10 unread notifications</li> -->
        <li>
            <!-- inner menu: contains the actual data -->

            <ul class="menu" id="notifications_list">
            </ul>
        </li>

        @if(count($all_notifications) > 10)
            <li class="footer load_more_li">
                <div style="width: 100%">
                    <div class="pull-left">
                        <a href="/sells-of-cashier/transaction-payments" class="btn btn-default btn-flat view_all_payment">@lang('messages.view_all')</a>
                    </div>
                    <div class="pull-right">
                        <a href="#" class="btn btn-default btn-flat load_more_notifications">@lang('lang_v1.load_more')</a>
                    </div>
                </div>
            </li>
        @endif
    </ul>
</li>

<input type="hidden" id="notification_page" value="1">
