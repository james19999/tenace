<?php

namespace App\Http\Controllers\Costumers;

use Carbon\Carbon;
use App\Models\Costumer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


class CostumerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('costumer.index');
    }

    public function datatable(Request $request)
    {
        $draw = max((int) $request->input('draw', 0), 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $search = trim((string) $request->input('search.value', ''));

        $columns = ['id', 'name', 'phone', 'email', 'adresse', 'id'];
        $orderColumn = (int) $request->input('order.0.column', 1);
        $orderBy = $columns[$orderColumn] ?? 'name';
        $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Costumer::query();
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($customers) use ($term) {
                $customers->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('adresse', 'like', $term);
            });
        }

        $recordsFiltered = (clone $query)->count();
        $customers = $query->select(['id', 'name', 'phone', 'email', 'adresse'])
            ->orderBy($orderBy, $direction)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $customers->values()->map(function (Costumer $customer, int $index) use ($start) {
            $editUrl = route('costumer.edit', $customer->id);
            $deleteUrl = route('costumer.destroy', $customer->id);
            $actions = '<div class="btn-group btn-group-sm">'
                .'<a href="'.$editUrl.'" class="btn btn-warning text-white"><i class="material-icons">edit</i> Modifier</a>'
                .'<form method="POST" action="'.$deleteUrl.'" onsubmit="return confirm(\'Supprimer ce client ?\')">'
                .'<input type="hidden" name="_token" value="'.e(csrf_token()).'">'
                .'<input type="hidden" name="_method" value="DELETE">'
                .'<button type="submit" class="btn btn-danger"><i class="material-icons">delete</i> Supprimer</button>'
                .'</form></div>';

            return [
                'number' => $start + $index + 1,
                'name' => e($customer->name),
                'phone' => e($customer->phone),
                'email' => e($customer->email ?? '@'),
                'address' => e($customer->adresse ?? '-'),
                'actions' => $actions,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => Costumer::count(),
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $term = trim($validated['q'] ?? '');
        if (mb_strlen($term) < 2) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }

        $costumers = Costumer::query()
            ->where(function ($customers) use ($term) {
                $prefix = $term.'%';
                $customers->where('name', 'like', $prefix)
                    ->orWhere('phone', 'like', $prefix)
                    ->orWhere('email', 'like', $prefix);
            })
            ->orderBy('name')
            ->paginate(20, ['id', 'name', 'phone'], 'page', (int) ($validated['page'] ?? 1));

        return response()->json([
            'results' => $costumers->getCollection()->map(fn (Costumer $costumer) => [
                'id' => $costumer->id,
                'text' => $costumer->name.' | '.$costumer->phone,
            ]),
            'pagination' => ['more' => $costumers->hasMorePages()],
        ]);
    }

    public function topcostumer(Request $request)
    {

        $montharray = [
            1 => "Janvier",
            2 => "Février",
            3 => "Mars",
            4 => "Avril",
            5 => "Mai",
            6 => "Juin",
            7 => "Juillet",
            8 => "Août",
            9 => "Septembre",
            10 => "Octobre",
            11 => "Novembre",
            12 => "Décembre"
         ];



                # code...
        $limit = min(max((int) $request->input('limit', 10), 1), 500);
        $month = (int) $request->input('month');
        $month = $month >= 1 && $month <= 12 ? $month : null;

        $costumers = Costumer::withCount('orders')
                ->withSum('orders', 'total')
                ->orderByDesc('orders_sum_total')
                ->when($month, function ($query) use ($month) {
                    return $query->whereMonth('created_at', $month);
                })
                ->whereYear('created_at', Carbon::now()->year)
                ->limit($limit)
                ->get();
                return view('costumer.top_costumer', compact('costumers', 'montharray', 'limit', 'month'));

    }
    public function viewcostumer($id)
    {
        //
                //
                $costumers= Costumer::where('id',$id)->withCount('orders')
                ->withSum('orders','total')
                ->first();

        return view('costumer.view_costomer',compact('costumers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('costumer.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
             'name'=>'required',
             'phone'=>'required',
             'adresse'=>'required',
        ]);


            Costumer::create([
                 'name'=>$request->name,
                 'phone'=>str_replace(' ', '',$request->phone)  ,
                 'email'=>$request->email,
                 'adresse'=>$request->adresse,
                 'user_id'=>Auth::user()->id,
            ]);

            return  redirect()->route('costumer.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $costumers=Costumer::findOrfail($id);

        return view('costumer.edit',compact('costumers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $costumers=Costumer::findOrfail($id);

        $costumers->update([
            'name'=>$request->name,
            'phone'=>str_replace(' ', '',$request->phone),
            'email'=>$request->email,
            'adresse'=>$request->adresse,
        ]);


        return redirect()->route('costumer.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        Costumer::destroy($id);

        return back();
    }
}
