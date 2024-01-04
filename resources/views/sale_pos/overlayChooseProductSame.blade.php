<style>
    .table .thead-dark th {
        color: #fff;
        background-color: #343a40;
        border-color: #454d55;
    }
    .table .thead-light th {
        color: #495057;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }
</style>
<div class="modal fade" id="{{ $idModal }}" tabindex="-1" role="dialog" aria-labelledby="{{ $idModal }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php $column = 7 ?>
                <table class="table">
                    <thead class="thead-dark">
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th>Độ dày</th>
                            <th>Dài</th>
                            <th>Rộng</th>
                            <th>Số tấm</th>
                            <th>Giá</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="thead-light">
                            <th colspan="{{ $column }}">Đơn đặt</th>
                        </tr>
                        <tr class="bill-begin" data-product="{{ json_encode($sellLineProduct) }}">
                            <td>{{ $sellLineProduct['name'] ?? 'No name' }}</td>
                            <td>{{ $sellLineProduct['thickness'] ?? '--' }}</td>
                            <td>{{ $sellLineProduct['width'] ?? '--' }}</td>
                            <td>{{ $sellLineProduct['height'] ?? '--' }}</td>
                            <td>{{ $sellLineProduct['quantity_line'] ?? '0' }}</td>
                            <td>{{ number_format($sellLineProduct['variations'][0]['sell_price_inc_tax'] ?? '0') }}</td>
                            <td></td>
                        </tr>
                        <tr class="thead-light">
                            <th colspan="{{ $column }}">Thực xuất</th>
                        </tr>
                        @foreach($allProductSame as $product)
                            <tr>
                                <td>{{ $product['name'] ?? '--' }}</td>
                                <td>{{ $product['thickness'] ?? '--' }}</td>
                                <td>{{ $sellLineProduct['width'] ?? '--' }}</td>
                                <td>{{ $sellLineProduct['height'] ?? '--' }}</td>
                                <td>{{ $sellLineProduct['quantity_line'] ?? '0' }}</td>
                                <td>{{ number_format($product['variations'][0]['sell_price_inc_tax'] ?? '0') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-border-primary" onclick="stockDeliver.fillProductToSame($(this))" data-product="{{ json_encode($product) }}">Chọn</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>