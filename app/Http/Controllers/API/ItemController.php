<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Item;
use App\Models\Price;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Spatie\QueryBuilder\QueryBuilder;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pagination = 5;
        if ($_GET['pagination']) {
            $pagination = $_GET['pagination'];
        }
        return QueryBuilder::for(Item::class)
            ->allowedIncludes(['prices', 'file'])
            ->allowedSorts('id', 'name', 'description')
            ->allowedFilters(['name'])->paginate($pagination)
            ->appends(request()->query());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $price_error=false;
        foreach (explode(';',$request['price']) as $price) {
            if (!is_numeric($price)) {
                $price_error=true;
            }
        }
        $validation = [
            'name' => 'required|max:255',
            'description' => 'required|max:60000',
            'price' => 'required',
            'file' => 'mimes:jpeg,png,jpg,gif,svg|required|file'
        ];
        if ($price_error) {
            $validation['price'] = 'required|numeric';
        }
        $validator = Validator::make($data, $validation);

        if($validator->fails()){
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        if ($item = Item::create($data)) {
            // Add prices to database and create pivot link with created item
            Price::addToItem($item, $data['price']);
            // Save file if item was successfully added
            $file = $request->file('file');
            $file_model= new File();
            $file_model->file_type='App\Models\Item';
            $file_model->file_id=$item->id;
            // Save file
            $file_model->path = $file->storeAs('images',Str::random(40).'.'.$file->getClientOriginalExtension(), ['disk' => 'public']);
            // Create thumbnail scaled to max 100 px either height or width
            $img=Image::make('storage/'.$file_model->path)->resize(100,100, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->resizeCanvas(100, 100);
            // Name for thumbnail
            $random_for_thumbnail = Str::random(40);
            $img->save(public_path('storage\\images\\'.$random_for_thumbnail.'.'.$file->getClientOriginalExtension()));
            $file_model->extension = $file->getClientOriginalExtension();
            $file->name=$file->getClientOriginalName();
            $file_model->thumbnail='images/'.$random_for_thumbnail.'.'.$file->getClientOriginalExtension();
            $file_model->save();
        }

        return response([ 'item' => new \App\Http\Resources\Item($item), 'success' => 'Created successfully'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        return response([ 'item' => new \App\Http\Resources\Item($item), 'success' => 'Retrieved successfully'], 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Item $item)
    {
        $data = $request->all();

        $price_error=false;
        foreach (explode(';',$request['price']) as $price) {
            if (!is_numeric($price)) {
                $price_error=true;
            }
        }
        $validation = [
            'name' => 'required|max:255',
            'description' => 'required|max:60000',
            'price' => 'required'
        ];
        if ($price_error) {
            $validation['price'] = 'required|numeric';
        }
        if ($request->file('file')) {
            $validation['file'] = 'mimes:jpeg,png,jpg,gif,svg|required|file';
        }
        $validator = Validator::make($data, $validation);

        if($validator->fails()){
            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        if ($request->file('file')) {
            $item->file->delete();
            $file = $request->file('file');
            $file_model= new File();
            $file_model->file_type='App\Models\Item';
            $file_model->file_id=$item->id;
            $file_model->path = $file->storeAs('images',Str::random(40).'.'.$file->getClientOriginalExtension(), ['disk' => 'public']);
            $img=Image::make('storage/'.$file_model->path)->resize(100,100, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->resizeCanvas(100, 100);
            $random_for_thumbnail = Str::random(40);
            $img->save(public_path('storage\\images\\'.$random_for_thumbnail.'.'.$file->getClientOriginalExtension()));
            $file_model->extension = $file->getClientOriginalExtension();
            $file->name=$file->getClientOriginalName();
            $file_model->thumbnail='images/'.$random_for_thumbnail.'.'.$file->getClientOriginalExtension();
            $file_model->save();
        }

        $item->prices()->sync([]);
        Price::addToItem($item, $data['price']);
        $item->update($request->all());

        return response([ 'item' => new \App\Http\Resources\Item($item), 'success' => 'Updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy(Item $item)
    {
        $item->delete();

        return response(['success' => 'Deleted item']);
    }
}
