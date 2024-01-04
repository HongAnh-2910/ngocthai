<?php

namespace App\Http\Controllers;

use App\Unit;
use App\Product;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

use App\Utils\Util;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $commonUtil;
    protected $util;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(Util $commonUtil, Util $util)
    {
        $this->commonUtil = $commonUtil;
        $this->util = $util;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('unit.view') && !auth()->user()->can('unit.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $unit = Unit::where('business_id', $business_id)
                        ->with(['base_unit'])
                        ->select(['actual_name', 'short_name', 'allow_decimal', 'id',
                            'base_unit_id', 'base_unit_multiplier', 'is_default']);

            return Datatables::of($unit)
                ->addColumn(
                    'action',
                    '@can("unit.update")
                    <button data-href="{{action(\'UnitController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_unit_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    
                    @if(!$is_default)
                    @can("unit.delete")
                        <button data-href="{{action(\'UnitController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_unit_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan
                    @endif'
                )
                ->editColumn('allow_decimal', function ($row) {
                    if ($row->allow_decimal) {
                        return __('messages.yes');
                    } else {
                        return __('messages.no');
                    }
                })
                ->editColumn('actual_name', function ($row) {
                    if (!empty($row->base_unit_id)) {
                        return  $row->actual_name . ' (' . (float)$row->base_unit_multiplier .' '.$row->base_unit->actual_name . ')';
                    }
                    return  $row->actual_name;
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('unit.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('unit.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $quick_add = false;
        if (!empty(request()->input('quick_add'))) {
            $quick_add = true;
        }

        $default_unit = Unit::where('type', 'area')
            ->where('is_default', true)
            ->select(
                'id',
                DB::raw('actual_name as name'))
            ->first();

        $units = Unit::forDropdown($business_id, false, true, false, ['area']);
        $unit_types = $this->util->unitTypes();

        return view('unit.create')
                ->with(compact('quick_add', 'units', 'default_unit', 'unit_types'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('unit.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['actual_name', 'allow_decimal', 'short_name', 'type']);
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['created_by'] = $request->session()->get('user.id');

            if ($request->has('define_base_unit')) {
                $input['base_unit_id'] = $request->input('base_unit_id');

                if (in_array($input['type'], ['area', 'meter'])) {
                    $input['width'] = $request->input('width');
                    $input['height'] = $input['type'] == 'area' ? $request->input('height') : 1;

                    $area = round($input['width'] * $input['height'], 3);
                    $base_unit_multiplier = $this->commonUtil->num_uf($area);

                    if ($base_unit_multiplier != 0) {
                        $input['base_unit_multiplier'] = $base_unit_multiplier;
                    }
                }else{
                    $input['base_unit_multiplier'] = $request->input('base_unit_multiplier');
                }
            }

            $unit = Unit::create($input);
            $output = ['success' => true,
                        'data' => $unit,
                        'msg' => __("unit.added_success")
                    ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                        'msg' => __("messages.something_went_wrong")
                    ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('unit.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $unit = Unit::where('business_id', $business_id)->find($id);

            $default_unit = Unit::where('type', $unit->type)
                ->where('is_default', true)
                ->select(
                    'id',
                    DB::raw('actual_name as name'))
                ->first();

            $units = Unit::forDropdown($business_id, false, true, false, [$unit->type]);
            $unit_types = $this->util->unitTypes();

            return view('unit.edit')
                ->with(compact('unit', 'units', 'default_unit', 'unit_types'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('unit.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['actual_name', 'allow_decimal', 'short_name', 'type']);
                $business_id = $request->session()->get('user.business_id');

                $unit = Unit::where('business_id', $business_id)->findOrFail($id);
                $unit->actual_name = $input['actual_name'];
                $unit->allow_decimal = $input['allow_decimal'];
                $unit->short_name = isset($input['short_name']) ? $input['short_name'] : '';
                $unit->type = $input['type'];
                if ($request->has('define_base_unit')) {
                    $unit->base_unit_id = $request->input('base_unit_id');

                    if (in_array($input['type'], ['area', 'meter'])) {
                        $unit->width = $request->input('width');
                        $unit->height = $input['type'] == 'area' ? $request->input('height') : 1;

                        $area = round($unit->width * $unit->height, 3);
                        $base_unit_multiplier = $this->commonUtil->num_uf($area);

                        if ($base_unit_multiplier != 0) {
                            $unit->base_unit_multiplier = $base_unit_multiplier;
                        }
                    }else{
                        $unit->base_unit_multiplier = $request->input('base_unit_multiplier');
                    }
                } else {
                    $unit->base_unit_id = null;
                    $unit->base_unit_multiplier = null;
                }

                $unit->save();

                $output = ['success' => true,
                            'msg' => __("unit.updated_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('unit.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $unit = Unit::where('business_id', $business_id)->findOrFail($id);

                //check if any product associated with the unit
                $exists = Product::where('unit_id', $unit->id)
                                ->exists();
                if (!$exists) {
                    $unit->delete();
                    $output = ['success' => true,
                            'msg' => __("unit.deleted_success")
                            ];
                } else {
                    $output = ['success' => false,
                            'msg' => __("lang_v1.unit_cannot_be_deleted")
                            ];
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => '__("messages.something_went_wrong")'
                        ];
            }

            return $output;
        }
    }

    public function getUnitsByType(Request $request)
    {
        $type = $request->input('type');
        if (!empty($type)) {
            $business_id = $request->session()->get('user.business_id');
            $units = Unit::where('business_id', $business_id)
                ->whereNull('base_unit_id')
                ->where('type', $type)
                ->select(DB::raw('IF(short_name, CONCAT(actual_name, " (", short_name, ")"), actual_name) as name'), 'id')
                ->get();
            $html = '';
//            $html = '<option value="">'. __('messages.please_select') .'</option>';
            if (!empty($units)) {
                foreach ($units as $unit) {
                    $html .= '<option value="' . $unit->id .'">' .$unit->name . '</option>';
                }
            }
            echo $html;
            exit;
        }
    }

    public function checkUnitExisted(Request $request) {
        if ($request->ajax()) {
            $type_validate = $request->input('type_validate');
            $actual_name = $request->input('actual_name');
            $unit_id = $request->input('unit_id');
            $valid = 'true';

            if (!empty($actual_name)) {
                $business_id = $request->session()->get('user.business_id');
                $query = Unit::where('business_id', $business_id)
                    ->whereRaw("actual_name LIKE CONCAT(CONVERT(?, BINARY))", [$actual_name]);

                if ($type_validate == 'create') {
                    $query = $query->count();
                } else {
                    $query = $query->where('id', '<>', $unit_id)->count();
                }

                if ($query > 0) {
                    $valid = 'false';
                }
            }

            echo $valid;
            exit();
        }
    }
}
