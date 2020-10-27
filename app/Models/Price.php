<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;
    protected $fillable = [
        'amount',
    ];

    public static function addToItem($item, $prices_string)
    {
        $prices = explode(';', $prices_string);
        foreach ($prices as $price) {
            // find price in database
            if ($price_model = Price::where('amount', $price)->first()) {
                $item->prices()->attach($price_model->id);
            } else {
                //if not then add one
                $price_model = new Price();
                $price_model->amount=$price;
                $price_model->save();
                $item->prices()->attach($price_model->id);
            }
        }
    }

    public function items()
    {
        return $this->belongsToMany('App\Models\Item');
    }
}
