<!-- Fix for scroll issue in new booking -->
<style type="text/css">
  .modal {
    overflow-y:auto; 
  }
</style>
<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => $notification_template['template_for'] == 'send_ledger' ? action('ContactController@sendLedger') : action('NotificationController@send'), 'method' => 'post', 'id' => 'send_notification_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'lang_v1.send_notification' ) - {{$template_name}}</h4>
    </div>

    <div class="modal-body">
      <div class="form-group @if($notification_template['template_for'] == 'send_ledger') hide @endif">
        <label class="radio-inline">
          {!! Form::radio('notification_type', 'email_only', true, ['class' => 'input-icheck']); !!} @lang('lang_v1.send_email_only')
        </label>
        <label class="radio-inline">
          {!! Form::radio('notification_type', 'sms_only', false, ['class' => 'input-icheck']); !!} @lang('lang_v1.send_sms_only')
        </label>
        <label class="radio-inline">
          {!! Form::radio('notification_type', 'both', false, ['class' => 'input-icheck']); !!} @lang('lang_v1.send_both_email_n_sms')
        </label>
      </div>
      <div id="email_div">
        <div class="form-group">
          {!! Form::label('to_email', __('lang_v1.to').':') !!} @show_tooltip(__('lang_v1.notification_email_tooltip'))
          {!! Form::text('to_email', $contact->email, ['class' => 'form-control' , 'placeholder' => __('lang_v1.to')]); !!}
        </div>
        <div class="form-group">
          {!! Form::label('subject', __('lang_v1.email_subject').':') !!}
          {!! Form::text('subject', $notification_template['subject'], ['class' => 'form-control' , 'placeholder' => __('lang_v1.email_subject')]); !!}
        </div>
        <div class="form-group">
          {!! Form::label('cc', 'CC:') !!}
          {!! Form::email('cc', $notification_template['cc'], ['class' => 'form-control' , 'placeholder' => 'CC']); !!}
        </div>
        <div class="form-group">
          {!! Form::label('bcc', 'BCC:') !!}
          {!! Form::email('bcc', $notification_template['bcc'], ['class' => 'form-control' , 'placeholder' => 'BCC']); !!}
        </div>
        <div class="form-group">
          {!! Form::label('email_body', __('lang_v1.email_body').':') !!}
          {!! Form::textarea('email_body', $notification_template['email_body'], ['class' => 'form-control', 'placeholder' => __('lang_v1.email_body'), 'rows' => 6]); !!}
        </div>
        @if($notification_template['template_for'] == 'send_ledger')
          <p class="help-block">*@lang('lang_v1.ledger_attacment_help')</p>
        @endif
      </div>
      <div id="sms_div" class="hide">
        <div class="form-group">
          {!! Form::label('mobile_number', __('lang_v1.mobile_number').':') !!}
          {!! Form::text('mobile_number', $contact->mobile, ['class' => 'form-control', 'placeholder' => __('lang_v1.mobile_number')]); !!}
        </div>
        <div class="form-group">
          {!! Form::label('sms_body', __('lang_v1.sms_body').':') !!}
          {!! Form::textarea('sms_body', $notification_template['sms_body'], ['class' => 'form-control', 'placeholder' => __('lang_v1.sms_body'), 'rows' => 6]); !!}
        </div>
      </div>
      <strong>@lang('lang_v1.available_tags'):</strong> <p class="help-block">{{implode(', ', $tags)}}</p>

      @if(!empty($transaction))
        {!! Form::hidden('transaction_id', $transaction->id); !!}
      @endif

      @if($notification_template['template_for'] == 'send_ledger')
        {!! Form::hidden('contact_id', $contact->id); !!}
        {!! Form::hidden('start_date', $start_date); !!}
        {!! Form::hidden('end_date', $end_date); !!}
      @endif

      {!! Form::hidden('template_for', $notification_template['template_for']); !!}

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary" id="send_notification_btn">@lang('lang_v1.send')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
// Fix for not updating textarea value on modal
  // CKEDITOR.on('instanceReady', function(){
  //    $.each( CKEDITOR.instances, function(instance) {
  //     CKEDITOR.instances[instance].on("change", function(e) {
  //         for ( instance in CKEDITOR.instances )
  //         CKEDITOR.instances[instance].updateElement();
  //     });
  //    });
  // });

  if (_.isNull(tinyMCE.activeEditor)) {
        tinymce.init({
            selector: 'textarea#email_body',
        });
    }
    
  $(document).ready(function(){
    //initialize iCheck
    $('input[type="checkbox"].input-icheck, input[type="radio"].input-icheck').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue'
    });
  });

  $(document).on('ifChanged', 'input[type=radio][name=notification_type]', function(){
    var notification_type = $(this).val();
    if (notification_type == 'email_only') {
      $('div#email_div').removeClass('hide');
      $('div#sms_div').addClass('hide');
    } else if(notification_type == 'sms_only'){
      $('div#email_div').addClass('hide');
      $('div#sms_div').removeClass('hide');
    } else if(notification_type == 'both'){
      $('div#email_div').removeClass('hide');
      $('div#sms_div').removeClass('hide');
    }
  });
  $('#send_notification_form').submit(function(e){
    e.preventDefault();
    tinyMCE.triggerSave();
    var data = $(this).serialize();
    $('#send_notification_btn').text("@lang('lang_v1.sending')...");
    $('#send_notification_btn').attr('disabled', 'disabled');
    $.ajax({
      method: "POST",
      url: $(this).attr("action"),
      dataType: "json",
      data: $(this).serialize(),
      success: function(result){
        if(result.success == true){
          $('div.view_modal').modal('hide');
          toastr.success(result.msg);
        } else {
          toastr.error(result.msg);
        }
        $('#send_notification_btn').text("@lang('lang_v1.send')");
        $('#send_notification_btn').removeAttr('disabled');
      }
    });
  });
</script>