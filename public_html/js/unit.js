$(function () {
    function calculateArea(){
        var width = $('#width').val();
        var height = $('#height').val();

        if(width == ''){
            width = 0;
        }
        if(height == ''){
            height = 0;
        }

        var base_unit_multiplier = width * height;
        base_unit_multiplier = base_unit_multiplier.toFixed(3);
        $('#base_unit_multiplier').val(base_unit_multiplier);
    }

    $('#width').change(function () {
        calculateArea();
    });

    $('#height').change(function () {
        calculateArea();
    });

    $('#type').change(function(){
        var type = $(this).val();
        var default_unit_id = $('#default_unit_id').val();

        if(type == 'area' || type == 'meter'){
            $('#area_box').show();
            $('#base_unit_multiplier').attr('readonly', true);

            if(type == 'meter'){
                $('#height_box').hide();
                $('#height').val(1);
            }else{
                $('#height_box').show();
                $('#height').val('');
            }
        }else{
            $('#area_box').hide();
            $('#base_unit_multiplier').removeAttr('readonly');
            // $('#base_unit_id').removeClass('hide');
            $('#default_unit_name').hide();
        }
    });

    $(document).on('change', '#type', function() {
        getUnitsByType();
    });

    function getUnitsByType() {
        var type = $('#type').val();
        $.ajax({
            method: 'POST',
            url: '/units/get-units-by-type',
            dataType: 'html',
            data: { type: type },
            success: function(result) {
                if (result) {
                    $('#base_unit_id').html(result);
                }
            },
        });
    }
});
