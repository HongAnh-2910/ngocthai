@php
    $quantity_available = $plate_stock->qty_available - $total_selected_quantity;
@endphp
<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        {!! Form::open(['method' => 'post', 'id' => 'choose_plate_manually_form']) !!}
        {!! Form::hidden('plate_stock_id', $plate_stock->id, ['id' => 'plate_stock_id']) !!}
        {!! Form::hidden('order_width', $width, ['id' => 'order_width']) !!}
        {!! Form::hidden('height', $plate_stock->height, ['id' => 'order_height']) !!}
        {!! Form::hidden('order_quantity', $quantity, ['id' => 'order_quantity']) !!}
        {!! Form::hidden('selected_quantity', $total_selected_quantity, ['id' => 'selected_quantity']) !!}
        {!! Form::hidden('total_deliver_quantity', $total_deliver_quantity, ['id' => 'total_deliver_quantity']) !!}
        {!! Form::hidden('deliver_width', $selected_width, ['id' => 'deliver_width_hidden']) !!}
        {!! Form::hidden('row_id', $row_id, ['id' => 'row_id']) !!}
        {!! Form::hidden('row_index', $row_index, ['id' => 'row_index']) !!}
        {!! Form::hidden('transaction_sell_line_id', $transaction_sell_line_id, ['id' => 'transaction_sell_line_id']) !!}
        {!! Form::hidden('remaining_widths', $remaining_widths_json, ['id' => 'remaining_widths_hidden']) !!}
        {!! Form::hidden('remaining_widths_if_cut', $remaining_widths_if_cut_json, ['id' => 'remaining_widths_if_cut_hidden']) !!}
        {!! Form::hidden('remaining_widths_if_not_cut', $remaining_widths_if_not_cut_json, ['id' => 'remaining_widths_if_not_cut_hidden']) !!}
        {!! Form::hidden('new_remaining_widths_text', $new_remaining_widths_text, ['id' => 'new_remaining_widths_text']) !!}
        {!! Form::hidden('row_insert_after_id', $row_insert_after_id, ['id' => 'row_insert_after_id']) !!}
        {!! Form::hidden('quantity_before_cut', $quantity_before_cut, ['id' => 'quantity_before_cut_hidden']) !!}
        {!! Form::hidden('origin_width', $plate_stock->width, ['id' => 'origin_width']) !!}
        {!! Form::hidden('origin_qty_available', $plate_stock->qty_available, ['id' => 'origin_qty_available']) !!}
        {!! Form::hidden('selected_remaining_widths_json', $selected_remaining_widths_json, ['id' => 'selected_remaining_widths_json']) !!}
        {!! Form::hidden('current_remaining_widths_text', $current_remaining_widths_text, ['id' => 'current_remaining_widths_text']) !!}
        {!! Form::hidden('enabled_not_cut', $enabled_not_cut, ['id' => 'enabled_not_cut']) !!}
        {!! Form::hidden('plates_if_not_cut', $plates_if_not_cut, ['id' => 'plates_if_not_cut']) !!}
        {!! Form::hidden('plates_for_print', $plates_for_print, ['id' => 'plates_for_print']) !!}
        {!! Form::hidden('split_step', isset($split_step) ? $split_step : '', ['id' => 'split_step']) !!}
        <div class="hide" id="sell_entry_row_html">{{ $sell_entry_row_html }}</div>

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'sale.choose_plate_manually' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    @component('components.widget', ['class' => 'box-primary', 'title' => __('sale.custom_plate_need_to_cut')])
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::radio('get_from_remaining_plate', 0, empty($all_remaining_widths) ? true : false, [ 'class' => 'input-icheck get_from_remaining_plate', ($plate_stock->qty_available - $total_selected_quantity == 0) ? 'disabled' : '']); !!} {{ __( 'sale.from_new_plate' ) }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::radio('get_from_remaining_plate', 1, !empty($all_remaining_widths) ? true : false, [ 'class' => 'input-icheck get_from_remaining_plate', empty($all_remaining_widths) ? 'disabled' : '']); !!} {{ __( 'sale.from_remaining_plate' ) }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12" id="all_remaining_widths_box" {{ empty($all_remaining_widths) ? 'style=display:none' : '' }}>
                            <div class="table-responsive">
                                <table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive" id="choose_plate_manually_form">
                                    <thead>
                                    <tr>
                                        <th>@lang( 'sale.width_remaining_plate' )</th>
                                        <th>@lang( 'sale.quantity_remaining_plate' )</th>
                                        <th>@lang( 'sale.select_remaining_plate' )</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($all_remaining_widths as $key => $remaining_width)
                                        @php
                                            if($remaining_width['width'] == $selected_width){
                                                $quantity_available = $remaining_width['quantity'];
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="remaining_width">{{  @size_format($remaining_width['width']) }}</span>
                                            </td>
                                            <td>
                                                <span class="remaining_qty_available">{{  @num_format($remaining_width['quantity']) }}</span>
                                            </td>
                                            <td>
                                                {!! Form::radio('select_remaining_plate', $remaining_width['width'], $remaining_width['width'] == $selected_width ? true : false, ['class' => 'input-icheck select_remaining_plate']); !!}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {!! Form::hidden('quantity_available', $quantity_available, ['id' => 'quantity_available']) !!}
                    @endcomponent
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    @component('components.widget', ['class' => 'box-primary', 'title' => __('sale.custom_quantity_to_cut')])
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('auto_cut', __('sale.auto_cut' ) . ':' ) !!}
                                    {!! Form::select('auto_cut', [1 => __('sale.auto_cut_on'), 0 => __('sale.auto_cut_off')], $auto_cut, ['class' => 'form-control', 'id' => 'auto_cut']) !!}
                                </div>
                            </div>
                            <div class="col-md-3" id="plate_quantity_need_to_cut_from_one_roll_box" style="display: none">
                                <div class="form-group">
                                    {!! Form::label('plate_quantity_need_to_cut_from_one_roll', __('sale.plate_quantity_need_to_cut_from_one_roll' ) . ':' ) !!}
                                    {!! Form::number('plate_quantity_need_to_cut_from_one_roll', $plate_quantity_need_to_cut_from_one_roll, [
                                        'class' => 'form-control',
                                        'id' => 'plate_quantity_need_to_cut_from_one_roll',
                                        'placeholder' => __('sale.plate_quantity_need_to_cut_from_one_roll'),
                                        'min' => 1,
                                        'max' => $max_quantity_cut_from_one,
                                    ]); !!}
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive" id="show_plate_manually_table">
                                <thead>
                                <tr>
                                    <th>@lang( 'product.product_name' )</th>
                                    @if($plate_stock->product->unit->type == 'area')
                                        <th>@lang( 'sale.height_before' )</th>
                                    @endif
                                    <th>@lang( 'sale.width_before' )</th>
                                    <th>@lang('sale.warehouse')</th>
                                    <th>@lang( 'sale.stock' )</th>
                                    <th>@lang('sale.order_width')</th>
                                    <th>@lang( 'sale.plate_quantity_before' )</th>
                                    <th>@lang( 'sale.plate_quantity_after' )</th>
                                    <th id="width_remaining_title">@lang( 'sale.area_remaining' )</th>
                                    <th>
                                        <span id="cut_option_title">@lang( 'sale.cut_option' )</span>
                                        <span id="split_step_title"></span>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>{{ $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku }}</td>
                                    @if($plate_stock->product->unit->type == 'area')
                                        <td>{{ @size_format($plate_stock->height) }}</td>
                                    @endif
                                    <td>
                                        <span id="width">{{ @size_format($selected_width) }}</span>
                                    </td>
                                    <td>{{ $plate_stock->warehouse->name }}</td>
                                    <td>
                                        <span id="qty_available">{{ @num_format($quantity_available) }}
                                    </td>
                                    <td>{{ @size_format($width) }}</td>
                                    <td>
                                        <span id="quantity_before_cut">{{ $quantity_before_cut }}</span>
                                    </td>
                                    <td>
                                        {!! Form::number('quantity_after_cut', $quantity_after_cut, ['class' => 'form-control', 'id' => 'quantity_after_cut']) !!}
                                    </td>
                                    <td id="width_remaining_content">
                                        <span id="deliver_remaining_width">
                                            @php
                                                $show_cut = false;
                                            @endphp

                                            @foreach($remaining_widths as $remaining_width)
                                                @php
                                                    $prefix = '';
                                                    if(count($remaining_widths) > 1){
                                                        $prefix = '- '.__('unit.roll').' '.$loop->iteration.': ';
                                                    }

                                                    $allow_cut_text = '';
                                                    if($remaining_width['not_cut'] == 0 && $remaining_width['cut'] != 0){
                                                        $allow_cut_text = ' ('. __('sale.enabled_not_cut') .')';
                                                    }
                                                @endphp

                                                @if($remaining_width['cut'] > 0)
                                                    @php
                                                        $show_cut = true;
                                                    @endphp
                                                    <p class="remaining_width_text {{ !$is_cut ? 'hide' : '' }}">{{ $prefix }}{{ @size_format($remaining_width['cut']) }}m{{ $allow_cut_text }}</p>
                                                @endif
                                                @if($remaining_width['not_cut'] > 0)
                                                    @php
                                                        $show_cut = true;
                                                    @endphp
                                                @endif
                                            @endforeach

                                            @if(!$show_cut)
                                                <p class="remaining_width_text {{ !$is_cut ? 'hide' : '' }}">0m</p>
                                            @endif

                                            <p class="remaining_width_if_not_cut_text {{ $is_cut ? 'hide' : '' }}">0m</p>
                                        </span>
                                    </td>
                                    <td>
                                        {!! Form::select('is_cut', [1 => __('sale.cut_option_yes'), 0 => __('sale.cut_option_no')], $is_cut, ['class' => 'form-control input-sm', 'id' => 'is_cut', $enabled_not_cut ? '' : 'disabled']) !!}
                                        <span id="split_step_content"></span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    @endcomponent
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="choose_plate_manually_submit">@lang( 'messages.agree' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->


<script type="text/javascript">
    $(document).ready(function() {
        if($('form#add_deliver_form').length > 0){
            deliver_form = $('form#add_deliver_form');
        }else{
            deliver_form = $('form#edit_deliver_form');
        }

        $('#choose_plate_manually_form #quantity_after_cut').change(function(){
            if(validateForm()){
                changePlateManually();
            }
        });

        $('#choose_plate_manually_form #plate_quantity_need_to_cut_from_one_roll').on('change', function(e) {
            let plate_quantity_need_to_cut_from_one_roll = parseInt($(this).val());
            let min = parseInt($(this).attr('min'));
            let max = parseInt($(this).attr('max'));

            if(plate_quantity_need_to_cut_from_one_roll < min || plate_quantity_need_to_cut_from_one_roll > max){
                $(this).val(min);
            }

            if(validateForm()){
                changePlateManually();
            }
        });

        $('#choose_plate_manually_form #auto_cut').change(function(){
            let auto_cut = $(this).val();
            if(auto_cut == 0){
                $('#plate_quantity_need_to_cut_from_one_roll_box').show();
            }else{
                $('#plate_quantity_need_to_cut_from_one_roll_box').hide();
            }

            if(validateForm()){
                changePlateManually();
            }
        });

        $('#choose_plate_manually_form #is_cut').change(function(){
            let quantity_before_cut = __read_number($('#quantity_before_cut'));
            let quantity_after_cut = __read_number($('#quantity_after_cut'));
            let is_cut = $(this).find(":selected").val();

            if(is_cut == 0 && quantity_after_cut > quantity_before_cut){
                swal({
                    title: LANG.sure,
                    text: LANG.change_not_cut_confirm,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willOk => {
                    is_cut = 1;
                    $('#is_cut').val(is_cut);

                    if (willOk) {
                        $('#split_step').val('first');
                        $('#width_remaining_title').hide();
                        $('#width_remaining_content').hide();
                        changePlateManually();
                    }else{
                        $('#choose_plate_manually_form .remaining_width_text').each(function(){
                            $(this).removeClass('hide');
                        });

                        $('#choose_plate_manually_form .remaining_width_if_not_cut_text').each(function(){
                            $(this).addClass('hide');
                        });
                    }
                });
            }

            if(is_cut == 1){
                $('#choose_plate_manually_form .remaining_width_text').each(function(){
                    $(this).removeClass('hide');
                });

                $('#choose_plate_manually_form .remaining_width_if_not_cut_text').each(function(){
                    $(this).addClass('hide');
                });
            }else{
                $('#choose_plate_manually_form .remaining_width_text').each(function(){
                    $(this).addClass('hide');
                });

                $('#choose_plate_manually_form .remaining_width_if_not_cut_text').each(function(){
                    $(this).removeClass('hide');
                });
            }
        });

        async function changePlateManually() {
            let result = await $.ajax({
                method: 'post',
                url: '/stock-to-deliver/change_plate_manually',
                dataType: 'json',
                data: $('#choose_plate_manually_form').serialize(),
                success: function (result) {
                    if(result.success){
                        $('#choose_plate_manually_form #quantity_before_cut').html(result.data.quantity_before_cut);
                        $('#choose_plate_manually_form #quantity_before_cut_hidden').val(result.data.quantity_before_cut);
                        $('#choose_plate_manually_form #quantity_after_cut').val(result.data.quantity_after_cut);
                        $('#choose_plate_manually_form #remaining_widths_hidden').val(result.data.remaining_widths_json);
                        $('#choose_plate_manually_form #plates_if_not_cut').val(result.data.plates_if_not_cut);
                        $('#choose_plate_manually_form #remaining_widths_if_cut_hidden').val(result.data.remaining_widths_if_cut_json);
                        $('#choose_plate_manually_form #remaining_widths_if_not_cut_hidden').val(result.data.remaining_widths_if_not_cut_json);
                        $('#choose_plate_manually_form #enabled_not_cut').val(result.data.enabled_not_cut);
                        $('#choose_plate_manually_form #selected_width').val(result.data.selected_width);
                        $('#choose_plate_manually_form  #new_remaining_widths_text').val(result.data.new_remaining_widths_text);
                        $('#choose_plate_manually_form  #sell_entry_row_html').html(result.data.sell_entry_row_html);
                        $('#choose_plate_manually_form #split_step').val(result.data.split_step);
                        $('#choose_plate_manually_form #plates_for_print').val(result.data.plates_for_print);

                        //Change enabled_not_cut
                        if (result.data.enabled_not_cut){
                            $('#choose_plate_manually_form #is_cut').removeAttr('disabled');
                        }

                        //Change auto cut
                        $('#choose_plate_manually_form #auto_cut').val(result.data.auto_cut);
                        $('#choose_plate_manually_form #plate_quantity_need_to_cut_from_one_roll').val(result.data.plate_quantity_need_to_cut_from_one_roll);
                        $('#choose_plate_manually_form #plate_quantity_need_to_cut_from_one_roll').attr('max', result.data.max_quantity_cut_from_one);

                        if(result.data.auto_cut == 1){
                            $('#plate_quantity_need_to_cut_from_one_roll_box').hide();
                        }else{
                            $('#plate_quantity_need_to_cut_from_one_roll_box').show();
                        }

                        //Show new remaining widths
                        let remaining_widths = JSON.parse(result.data.remaining_widths_json);
                        let remaining_widths_html = '';
                        let show_cut = false;

                        $.each(remaining_widths, function(index, remaining_width){
                            let prefix = '';
                            if (remaining_widths.length > 1) {
                                prefix = '- '+ LANG.roll +' '+ (index + 1) +': ';
                            }

                            let allow_cut_text = '';
                            if (remaining_width['not_cut'] == 0 && remaining_width['cut'] != 0){
                                allow_cut_text = ' ('+ LANG.enabled_not_cut +')';
                            }

                            if (remaining_width['cut'] > 0) {
                                show_cut = true;
                                remaining_widths_html += '<p class="remaining_width_text">'+ prefix + remaining_width['cut'].toFixed(3) +'m'+ allow_cut_text +'</p>';
                            }
                        });

                        if(!show_cut){
                            remaining_widths_html = '<p class="remaining_width_text">0m</p>';
                        }

                        $('#show_plate_manually_table #deliver_remaining_width').html(remaining_widths_html);

                        //If is split
                        if(result.data.split_step == 'first'){
                            $('#choose_plate_manually_submit').html('Tiếp theo');
                            $('#quantity_after_cut').attr('readonly', true);

                            $('#choose_plate_manually_form #cut_option_title').html('Lần cắt').show();
                            $('#choose_plate_manually_form #split_step_content').html('Lần 1: Chọn tấm có cắt').show();
                            $('#choose_plate_manually_form #is_cut').hide();

                            $('#choose_plate_manually_form #auto_cut').attr('disabled', true);
                            $('#plate_quantity_need_to_cut_from_one_roll').attr('readonly', true);

                            $('#choose_plate_manually_form .get_from_remaining_plate').attr('readonly', true);
                        }else{
                            $('#choose_plate_manually_submit').html('Đồng ý');
                            $('#quantity_after_cut').removeAttr('readonly');

                            if(result.data.enabled_not_cut){
                                $('#choose_plate_manually_form #is_cut').show();

                                // $('#choose_plate_manually_form #cut_option_title').html('').hide();
                                $('#choose_plate_manually_form #split_step_content').html('').hide();
                                $('#choose_plate_manually_form #is_cut').show();
                            }else{
                                $('#choose_plate_manually_form #is_cut').val(1);
                                $('#choose_plate_manually_form #is_cut').attr('disabled', true);
                            }

                            $('#choose_plate_manually_form #auto_cut').removeAttr('disabled');
                            $('#plate_quantity_need_to_cut_from_one_roll').removeAttr('readonly');
                        }
                    }else{
                        swal({
                            title: result.message,
                            icon: 'warning',
                        });
                    }
                }
            });

            return result;
        }

        //Validate quantity after cut
        function validateForm(){
            let error = '';
            let quantity_after_cut = __read_number($('#choose_plate_manually_form #quantity_after_cut'));

            if(quantity_after_cut <= 0){
                error = 'plate_quantity_must_greater_than_0';
            }
            if(error != ''){
                let message;

                if(error == 'plate_quantity_must_greater_than_0'){
                    message = LANG.plate_quantity_must_greater_than_0;
                }

                swal({
                    title: message,
                    icon: 'warning',
                });

                return false;
            }

            return true;
        };

        $('#choose_plate_manually_form input[name="select_remaining_plate"]').change(function(){
            let tr = $(this).closest('tr');
            let remaining_width = __read_number(tr.find('.remaining_width'));
            let remaining_qty_available = __read_number(tr.find('.remaining_qty_available'));

            __write_size($('#choose_plate_manually_form #width'), remaining_width);
            __write_number($('#choose_plate_manually_form #qty_available'), remaining_qty_available);
            $('#choose_plate_manually_form #quantity_available').val(remaining_qty_available);
            $('#choose_plate_manually_form #deliver_width_hidden').val(remaining_width);

            changePlateManually();
        });

        $('#choose_plate_manually_form input[name="get_from_remaining_plate"]').change(function(){
            let get_from_remaining_plate = parseInt($('input[name="get_from_remaining_plate"]:checked').val());
            let width;
            let qty_available;

            if(get_from_remaining_plate){
                let tr = $('input[name="select_remaining_plate"]:checked').closest('tr');
                width = __read_number(tr.find('.remaining_width'));
                qty_available = __read_number(tr.find('.remaining_qty_available'));

                $('#all_remaining_widths_box').show();
            }else{
                width = __read_number($('#origin_width'));
                let total_selected_quantity = __read_number($('#choose_plate_manually_form #selected_quantity'));
                let origin_qty_available = __read_number($('#choose_plate_manually_form #origin_qty_available'));
                qty_available = origin_qty_available - total_selected_quantity;

                $('#all_remaining_widths_box').hide();
            }

            __write_size($('#choose_plate_manually_form #width'), width);
            __write_number($('#choose_plate_manually_form #qty_available'), qty_available);
            $('#choose_plate_manually_form #quantity_available').val(qty_available);
            $('#choose_plate_manually_form #deliver_width_hidden').val(width);

            changePlateManually();
        });

        $('#choose_plate_manually_form').submit(function(event) {
            event.preventDefault();
            if(validateForm()){
                changePlateManually().then(function(){
                    let row_insert_after_id = $('#choose_plate_manually_form #row_insert_after_id').val();
                    let row_insert_after = $('#'+ row_insert_after_id);
                    let sell_entry_row_html = $('#choose_plate_manually_form #sell_entry_row_html').html();
                    let plate_stock_id = parseInt($('#choose_plate_manually_form #plate_stock_id').val());
                    let new_remaining_widths_text = $('#choose_plate_manually_form #new_remaining_widths_text').val();
                    let row_id = $('#choose_plate_manually_form #row_id').val();
                    let row_index = $('#choose_plate_manually_form #row_index').val();
                    let prev_id = '';
                    let is_cut = $('#choose_plate_manually_form #is_cut').find(":selected").val();

                    let tr_new = $(sell_entry_row_html).insertAfter(row_insert_after);

                    if($('#selected_remaining_widths_'+ plate_stock_id).val() == undefined){
                        deliver_form.append('<input type="hidden" name="selected_remaining_widths['+ plate_stock_id + ']" id="selected_remaining_widths_'+ plate_stock_id + '" class="selected_remaining_widths" data-plate_stock_id="'+ plate_stock_id + '" value="">');
                    }

                    let current_remaining_widths_element = $('#selected_remaining_widths_'+ plate_stock_id);
                    let current_remaining_widths_index =  current_remaining_widths_element.attr('data-index');

                    if(current_remaining_widths_index == undefined){
                        current_remaining_widths_index = 0;
                    }else{
                        current_remaining_widths_index = parseInt(current_remaining_widths_index) + 1;
                    }

                    /*//Disable cut option of previous same plate
                    if(current_remaining_widths_index > 0){
                        let prev_remaining_widths_id = current_remaining_widths_element.attr('data-row_id_'+ (current_remaining_widths_index - 1));
                        $('#'+ prev_remaining_widths_id).find('.is_cut_input').attr('disabled', true);
                    }*/

                    current_remaining_widths_element.val(new_remaining_widths_text);
                    current_remaining_widths_element.attr('data-index', current_remaining_widths_index);
                    current_remaining_widths_element.attr('data-value_'+ current_remaining_widths_index, new_remaining_widths_text);
                    current_remaining_widths_element.attr('data-row_id_'+ current_remaining_widths_index, row_id);

                    //Update remaining plates to store DB
                    if($('#remaining_plates_'+ row_id).val() == undefined){
                        tr_new.append('<input type="hidden" name="products['+ row_index +'][plate_stock]['+ row_id + '][remaining_plates]" id="remaining_plates_'+ row_id + '" class="remaining_plates" value="">');
                    }

                    let remaining_widths_json;
                    if(is_cut == 1){
                        remaining_widths_json = $('#choose_plate_manually_form #remaining_widths_if_cut_hidden').val();
                    }else{
                        remaining_widths_json = $('#choose_plate_manually_form #remaining_widths_if_not_cut_hidden').val();
                    }

                    let remaining_widths = JSON.parse(remaining_widths_json);
                    let remaining_plates = [];
                    let selected_width = __read_number($('#choose_plate_manually_form #deliver_width_hidden'));
                    let order_number = 1;

                    if(current_remaining_widths_index > 0){
                        $('#select_plate_deliver_table').find('.remaining_plates').each(function(){
                            let prev_remaining_plates_element = $(this);

                            if(prev_remaining_plates_element.attr('id') != 'remaining_plates_'+ row_id){
                                let prev_tr = prev_remaining_plates_element.closest('tr');
                                let prev_remaining_plates_json = prev_remaining_plates_element.val();
                                let prev_remaining_plates = JSON.parse(prev_remaining_plates_json);
                                let exist = false;

                                $.each(prev_remaining_plates, function(index, value){
                                    if(value.width == selected_width){
                                        prev_id = prev_tr.attr('id');
                                        prev_remaining_plates[index]['next_id'] = row_id;
                                        order_number = prev_remaining_plates[index]['order_number'] + 1;
                                        prev_remaining_plates_json = JSON.stringify(prev_remaining_plates);
                                        prev_remaining_plates_element.val(prev_remaining_plates_json);
                                        exist = true;

                                        // prev_tr.find('.cut_plate_sort').html(`(${prev_remaining_plates[index]['order_number']})`);
                                        // tr_new.find('.cut_plate_sort').html(`(${order_number})`);

                                        return false;
                                    }
                                });

                                if(exist){
                                    return false;
                                }
                            }
                        });
                    }

                    $.each(remaining_widths, function(index, value){
                        remaining_plates.push({
                            width: value.width,
                            quantity: value.quantity,
                            plate_stock_id: plate_stock_id,
                            order_number: order_number,
                            id: row_id,
                            next_id: '',
                            prev_id: prev_id,
                        });
                    });

                    let remaining_plates_json = JSON.stringify(remaining_plates);
                    let remaining_plates_element = $('#remaining_plates_'+ row_id);

                    remaining_plates_element.val(remaining_plates_json);

                    //-- Set sort order cut --//
                    let current_plates_for_print_json = $('#plates_for_print').val();
                    let current_plates_for_print = JSON.parse(current_plates_for_print_json);

                    let plates_for_print = [];
                    if(is_cut == 1 && current_plates_for_print.hasOwnProperty('cut')){
                        plates_for_print = current_plates_for_print.cut;
                    }else if(is_cut == 0 && current_plates_for_print.hasOwnProperty('not_cut')){
                        plates_for_print = current_plates_for_print.not_cut;
                    }

                    let new_plates_sort_order = [];
                    let is_exist = false;
                    let transaction_sell_line_id = parseInt($('#transaction_sell_line_id').val());

                    if(typeof $('#plates_sort_order').val() === 'undefined'){
                        deliver_form.append('<input type="hidden" name="plates_sort_order" id="plates_sort_order" value="">');
                    }else{
                        let plates_sort_order_json = $('#plates_sort_order').val();
                        let plates_sort_order = JSON.parse(plates_sort_order_json);
                        new_plates_sort_order = JSON.parse(plates_sort_order_json);

                        const origin_width = $('#origin_width').val();
                        const selected_quantity = $('#quantity_before_cut_hidden').val();
                        let plate_sort_order_for_cut_from_same_plate = null;

                        console.log('plates_for_print', plates_for_print);
                        console.log('plates_sort_order', plates_sort_order);

                        plates_for_print.forEach(function(plate_for_print){
                            plates_sort_order.every(function(plate_sort_order, index){
                                if(plate_sort_order.plate_stock_id == plate_stock_id) {
                                    if(plate_for_print.is_origin == 1){
                                        console.log('is_origin');
                                        if(plate_for_print.remaining_width == plate_sort_order.remaining_width && plate_sort_order.deliver_plates.length === 1 && plate_sort_order.deliver_plates[0].deliver_width == plate_for_print.deliver_width){
                                            console.log('if');
                                            is_exist = true;
                                            new_plates_sort_order[index].selected_quantity += plate_for_print.selected_quantity;
                                            new_plates_sort_order[index].remaining_quantity += plate_for_print.remaining_quantity;
                                            new_plates_sort_order[index].deliver_plates[0].deliver_quantity += plate_for_print.deliver_quantity;
                                            return false;
                                        }
                                    }else{
                                        console.log('not is_origin');
                                        if(plate_for_print.selected_width == plate_sort_order.remaining_width){
                                            console.log('if 2');
                                            if (plate_sort_order.remaining_quantity > plate_for_print.remaining_quantity){
                                                console.log('if 3');
                                                new_plates_sort_order[index].selected_quantity -= plate_for_print.selected_quantity;
                                                new_plates_sort_order[index].remaining_quantity -= plate_for_print.remaining_quantity;

                                                if (plate_sort_order.deliver_plates.length > 0){
                                                    console.log('if 4');
                                                    const deliver_plates_last_index = plate_sort_order.deliver_plates.length - 1;
                                                    console.log('deliver_plates_last_index', deliver_plates_last_index);
                                                    console.log('before new_plates_sort_order[index].deliver_plates[deliver_plates_last_index]', new_plates_sort_order[index].deliver_plates[deliver_plates_last_index]);
                                                    new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity = plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity - plate_for_print.deliver_quantity;
                                                    console.log('after new_plates_sort_order[index].deliver_plates[deliver_plates_last_index]', new_plates_sort_order[index].deliver_plates[deliver_plates_last_index]);

                                                    //Get plate_sort_order_for_cut_from_same_plate
                                                    console.log('plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity - new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity', plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity, new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity);
                                                    const deliver_plates = plate_sort_order.deliver_plates;

                                                    deliver_plates[deliver_plates.length - 1] = {
                                                        ...deliver_plates[deliver_plates.length - 1],
                                                        deliver_quantity: plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity - new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity,
                                                    }

                                                    deliver_plates.push({
                                                        deliver_width: plate_for_print.deliver_width,
                                                        deliver_quantity: plate_for_print.deliver_quantity,
                                                    });
                                                    console.log('deliver_plates', deliver_plates);

                                                    plate_sort_order_for_cut_from_same_plate = {
                                                        ...plate_sort_order,
                                                        transaction_sell_line_id,
                                                        selected_quantity: plate_for_print.selected_quantity,
                                                        remaining_width: plate_for_print.remaining_width,
                                                        remaining_quantity: plate_for_print.remaining_quantity,
                                                        deliver_plates,
                                                    };

                                                    is_exist = true;
                                                }

                                                return false;

                                                /*console.log('if 3');
                                                new_plates_sort_order[index].remaining_quantity = plate_sort_order.remaining_quantity - plate_for_print.remaining_quantity;

                                                if (plate_sort_order.deliver_plates.length > 0){
                                                    const deliver_plates_last_index = plate_sort_order.deliver_plates.length - 1;
                                                    console.log('deliver_plates_last_index', deliver_plates_last_index);
                                                    console.log('before new_plates_sort_order[index].deliver_plates[deliver_plates_last_index]', new_plates_sort_order[index].deliver_plates[deliver_plates_last_index]);
                                                    new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity = plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity - plate_for_print.deliver_quantity;
                                                    console.log('after new_plates_sort_order[index].deliver_plates[deliver_plates_last_index]', new_plates_sort_order[index].deliver_plates[deliver_plates_last_index]);
                                                }

                                                return false;*/
                                            }else{
                                                console.log('else 3');
                                                is_exist = true;
                                                new_plates_sort_order[index].remaining_width = plate_for_print.remaining_width;
                                                new_plates_sort_order[index].remaining_quantity = plate_for_print.remaining_quantity;

                                                new_plates_sort_order[index].deliver_plates.push({
                                                    deliver_width: plate_for_print.deliver_width,
                                                    deliver_quantity: plate_for_print.deliver_quantity,
                                                });
                                                return false;
                                            }
                                        }
                                    }
                                }
                                return true;
                            });
                        });

                        //Update if is cut from same plate
                        console.log('plate_sort_order_for_cut_from_same_plate', plate_sort_order_for_cut_from_same_plate);
                        if(plate_sort_order_for_cut_from_same_plate) {
                            new_plates_sort_order.push(plate_sort_order_for_cut_from_same_plate);
                        }
                    }

                    console.log('is_exist', is_exist);
                    if(!is_exist){
                        plates_for_print.forEach(function(item){
                            new_plates_sort_order.push({
                                transaction_sell_line_id: transaction_sell_line_id,
                                plate_stock_id: plate_stock_id,

                                selected_width: item.selected_width,
                                selected_quantity: item.selected_quantity,

                                deliver_plates: [
                                    {
                                        deliver_width: item.deliver_width,
                                        deliver_quantity: item.deliver_quantity,
                                    }
                                ],

                                remaining_width: item.remaining_width,
                                remaining_quantity: item.remaining_quantity,

                                is_origin: item.is_origin,
                            });
                        });
                    }

                    $('#plates_sort_order').val(JSON.stringify(new_plates_sort_order));

                    console.log('new_plates_sort_order', new_plates_sort_order);
                    console.log('_________');

                    //TODO: If is split
                    let split_step = $('#choose_plate_manually_form #split_step').val();
                    if(split_step == 'first'){
                        let quantity = __read_number($('#order_quantity'));
                        let width = parseFloat($('#order_width').val());
                        let height = parseFloat($('#order_height').val());
                        let transaction_sell_line_id = __read_number($('#transaction_sell_line_id'));

                        //Calculate total_deliver_quantity
                        let total_deliver_quantity = 0;
                        $('#select_plate_deliver_table .deliver_row_'+ row_index).find('.deliver_quantity').each(function(){
                            total_deliver_quantity += __read_number($(this));
                        });
                        if(total_deliver_quantity >= quantity){
                            swal({
                                title: LANG.enough_plate_to_be_cut,
                                icon: 'warning',
                                dangerMode: true,
                            });
                            return;
                        }

                        //Calculate total_selected_quantity
                        let total_selected_quantity = 0;
                        $('#select_plate_deliver_table .deliver_row').each(function(){
                            let selected_plate_stock_id = $(this).data('plate_stock_id');
                            let selected_quantity = __read_number($(this).find('.deliver_selected_quantity'));
                            let selected_width = __read_number($(this).find('.deliver_origin_width'));
                            let origin_width = __read_number($(this).find('.origin_width'));

                            if(plate_stock_id == selected_plate_stock_id && selected_width == origin_width){
                                total_selected_quantity += selected_quantity;
                            }
                        });

                        //Get remaining_widths
                        let remaining_widths = '';
                        if($('#selected_remaining_widths_'+ plate_stock_id).val() != undefined){
                            remaining_widths = $('#selected_remaining_widths_'+ plate_stock_id).val();
                        }

                        //Get current_remaining_widths_text
                        let current_remaining_widths_element = $('#selected_remaining_widths_' + plate_stock_id);
                        let current_remaining_widths_text;
                        if(current_remaining_widths_element.val() != undefined) {
                            current_remaining_widths_text = current_remaining_widths_element.val();
                        }else{
                            current_remaining_widths_text = '';
                        }

                        //Get row_insert_after_id
                        let row_insert_after_id = tr_new.attr('id');

                        //Update for last plate
                        $.ajax({
                            method: 'POST',
                            url: '/stock-to-deliver/get_sell_entry_row',
                            dataType: 'json',
                            data: {
                                plate_stock_id: plate_stock_id,
                                width: width,
                                height: height,
                                quantity: quantity,
                                total_deliver_quantity: total_deliver_quantity,
                                total_selected_quantity: total_selected_quantity,
                                remaining_widths: remaining_widths,
                                row_index: row_index,
                                transaction_sell_line_id: transaction_sell_line_id,
                                current_remaining_widths_text: current_remaining_widths_text,
                                row_insert_after_id: row_insert_after_id,
                                is_cut: 0,
                            },
                            success: function(result) {
                                if(result.success){
                                    $('.choose_plate_manually').html(result.data);

                                    $('#choose_plate_manually_form #split_step').val('last');
                                    $('#choose_plate_manually_form #cut_option_title').html('Lần cắt').show();
                                    $('#choose_plate_manually_form #split_step_content').html('Lần 2: Chọn tấm không cắt');
                                    $('#choose_plate_manually_form #is_cut').hide();
                                    $('#width_remaining_title').hide();
                                    $('#width_remaining_content').hide();

                                    // $('#choose_plate_manually_form #get_from_remaining_plate').attr('disabled', true);
                                    $('#choose_plate_manually_form #auto_cut').attr('disabled', true);
                                    $('#choose_plate_manually_form #quantity_after_cut').attr('readonly', true);
                                }else{
                                    swal({
                                        title: result.message,
                                        icon: 'warning',
                                    });
                                }
                            },
                        });
                    }else{
                        $('.choose_plate_manually').modal('hide');
                    }
                });
            }
        });
    });
</script>

