<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Item extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'file' => [
                'thumbnail' => url('storage/'.$this->file->thumbnail),
                'path' => url('storage/'.$this->file->path),
                'name' => $this->file->name
            ],
            'prices' => $this->prices,
        ];
    }
}
