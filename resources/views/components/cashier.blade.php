<div class="{{$class ?? 'box-solid'}}" @if(!empty($id)) id="{{$id}}" @endif>
    @if(empty($header))
        @if(!empty($title) || !empty($tool))
        <div class="box-header">
            {!!$icon ?? '' !!}
            <h3 class="box-title">{{ $title ?? '' }}</h3>
            {!!$tool ?? ''!!}
        </div>
        @endif
    @else
        <div class="box-header">
            {!! $header !!}
        </div>
    @endif

    <div class="box-body row" style="padding: 0px !important;margin-bottom: 20px">
        {{$slot}}
    </div>
    <!-- /.box-body -->
</div>