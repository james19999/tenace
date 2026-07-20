<?php

namespace App\Http\Livewire\Orders;

use App\Models\Product;
use Livewire\Component;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use Illuminate\Support\Facades\DB;

class EditOrder extends Component
{

    public Order $order;

    public $items = [];

    public $tax = 0;
    public $remis = 0;

    public $subtotal = 0;
    public $total = 0;
    public $montant = 0;

    public $search = '';

    public $products = [];


    public function mount(Order $order)
    {
        $this->order = $order;

        $this->tax = $order->tax;
        $this->remis = $order->remis;


        foreach ($order->orderItems as $item) {

            $this->items[] = [

                'id' => $item->id,

                'product_id' => $item->product_id,

                'name' => $item->product?->name ?? 'Produit supprimé',

                'price' => $item->price,

                'quantity' => $item->quantity,

                'high_price' => $item->high_price

            ];
        }


        $this->calcul();
    }



    /**
     * Recherche des produits
     */
    public function updatedSearch()
    {

        if(strlen($this->search) < 2){

            $this->products = [];

            return;
        }


        $this->products = Product::where(function($query){

                $query->where('name','like','%'.$this->search.'%')
                      ;

            })
            ->where('qt_initial','>',0)
            ->limit(10)
            ->get();

    }



    /**
     * Ajouter un produit
     */
    public function addProduct($id)
    {

        $product = Product::find($id);


        if(!$product){

            return;
        }



        foreach($this->items as &$item){


            if($item['product_id'] == $product->id){


                $item['quantity']++;


                $this->calcul();


                $this->search = '';

                $this->products = [];


                return;

            }

        }



        $this->items[] = [

            'id'=>null,

            'product_id'=>$product->id,

            'name'=>$product->name,

            'price'=>$product->price,

            'quantity'=>1,

            'high_price'=>$product->high_price ?? 0

        ];



        $this->calcul();


        $this->search = '';

        $this->products = [];

    }





    /**
     * Calcul des montants
     */
    public function calcul()
    {

        $this->subtotal = 0;


        foreach($this->items as $item){

            $this->subtotal +=
                $item['price'] * $item['quantity'];

        }



        $this->total = $this->subtotal;



        if($this->remis > 0){

            $this->total -=
                ($this->subtotal * $this->remis) / 100;

        }



        $this->montant =
            $this->total + $this->tax;

    }





    /**
     * Augmenter quantité
     */
    public function increment($index)
    {

        $product = Product::find(
            $this->items[$index]['product_id']
        );


        if(!$product){

            return;

        }



        if($this->items[$index]['quantity'] >= $product->qt_initial){

            return;

        }



        $this->items[$index]['quantity']++;


        $this->calcul();

    }





    /**
     * Diminuer quantité
     */
    public function decrement($index)
    {

        if($this->items[$index]['quantity'] > 1){


            $this->items[$index]['quantity']--;


            $this->calcul();

        }

    }





    /**
     * Supprimer produit
     */
    public function remove($index)
    {

        unset($this->items[$index]);


        $this->items = array_values($this->items);


        $this->calcul();

    }





    /**
     * Recalcul automatique
     */
    public function updated($property)
    {

        if(
            str_contains($property,'items')
            ||
            $property == 'tax'
            ||
            $property == 'remis'
        ){

            $this->calcul();

        }

    }





    /**
     * Sauvegarde commande
     */
    public function save()
    {


        $this->validate([

            'tax'=>'required|numeric|min:0',

            'remis'=>'required|numeric|min:0|max:100',

            'items'=>'required|array|min:1'

        ]);



        DB::transaction(function(){



            $this->order->update([


                'subtotal'=>$this->subtotal,

                'tax'=>$this->tax,

                'remis'=>$this->remis,

                'total'=>$this->total,

                'montant'=>$this->montant


            ]);




            // suppression ancienne composition

            $this->order->orderItems()->delete();




            // recréation

            foreach($this->items as $item){


                OrderItem::create([


                    'order_id'=>$this->order->id,

                    'product_id'=>$item['product_id'],

                    'price'=>$item['price'],

                    'quantity'=>$item['quantity'],

                    'high_price'=>$item['high_price']


                ]);

            }



        });



        session()->flash(
            'success',
            'Commande modifiée avec succès'
        );


    }




    public function render()
    {

        return view('livewire.orders.edit-order')->extends('layouts.admin')
            ->section('content');

    }

}
