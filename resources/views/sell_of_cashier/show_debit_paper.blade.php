<div class="modal-dialog modal-lg no-print" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.debit_paper') (<b>@lang('sale.invoice_no')
                    :</b> {{ $sell->invoice_no }})
            </h4>
        </div>
        <div class="modal-body" style="height: 36vh">
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group row">
                        @foreach($sell->media as $media)
                            <div class="col-sm-6">
                                <div class="img-thumbnail-debit">
                                    {!! $media->thumbnail([300, 300], 'view_image') !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default no-print"
                    data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('.view_image').each(function () {
            $(this).click(function () {
                window.open($(this).attr('src'));
            })
        })
    })
</script>