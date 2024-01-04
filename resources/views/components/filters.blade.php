<div class="box @if(!empty($class)) {{$class}} @else box-primary @endif" id="@if(!empty($idCashier)){{$idCashier}}@else accordion @endif">
  <div class="box-header with-border" style="cursor: pointer;">
    <h3 class="box-title">
      <a data-toggle="collapse" data-parent="#accordion" href="#collapseFilter">
        @if(!empty($icon)) {!! $icon !!} @else <i class="fa fa-filter" aria-hidden="true"></i> @endif {{$title ?? ''}}
      </a>
    </h3>
    {{ !empty($tool) ? $tool : '' }}
  </div>

  <div id="@if(!empty($idCashier)){{$idCashier}}@else collapseFilter @endif" class="panel-collapse active collapse @if(empty($closed)) in @endif" aria-expanded="true">
    <div class="box-body">
      {{ $slot }}
    </div>
  </div>
</div>