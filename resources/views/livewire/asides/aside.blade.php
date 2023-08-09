<aside class="sidebar">
    <nav class="navbar">
        <a class="navbar-brand brand-title" href="#">
            <img src="{{ asset('assets/images/tena.jpg') }}" alt="" class="logo">TENACE COS
        </a>
    </nav>
    <nav class="navigation shadow-sm">
        <div class="navigation-arrow">
            <i class="material-icons">chevron_left</i>
        </div>
        <ul>


             @if (Auth::user()->user_type=="ADMINUSER")

             <li>
                 <a href="{{ route('Admin') }}" class="active">
                     <span class="icon material-icons"  >dashboard</span>
                     <span class="text">Dashboard</span>
                 </a>
             </li>
             <li>
                 <a href="{{ route('product') }}" class="">
                     <span class="icon material-icons"  >store
                     </span>
                     <span class="text">Produits</span>
                 </a>
             </li>
             <li wire:poll.2s>
                 <a href="{{ route('productcart') }}" class="">
                     <span class="icon material-icons"  >add_shopping_cart</span>
                     <span class="text">Panier ({{ Cart::instance('cart')->count() }})</span>
                 </a>
             </li>
             <li>
                 <a href="{{ route('order') }}" class="">
                     <span class="icon material-icons"  >shopping_cart</span>
                     <span class="text">Commandes</span>
                 </a>
             </li>
             <li>
                 <a href="{{ route('history') }}" class="">
                     <span class="icon material-icons"  >history</span>
                     <span class="text">Historiques</span>
                 </a>
             </li>
             <li>
                 <a href="{{ route('costumer.index') }}" class="">
                     <span class="icon material-icons"  >contact_phone</span>
                     <span class="text">Clients</span>
                 </a>
             </li>
             <li>
                 <a href="{{ route('parthners') }}" class="">
                     <span class="icon material-icons"  >person
                     </span>
                     <span class="text">Partenaires</span>
                 </a>
             </li>
             <li>
                 <a href="{{ route('livreurs.index') }}" class="">
                     <span class="icon material-icons"  >directions_bike
                     </span>
                     <span class="text">Livreurs</span>
                 </a>
             </li>

             <li>
                 <a href="{{ route('useradminlist') }}" class="">
                     <span class="icon material-icons" >groups</span>
                     <span class="text">Membres</span>
                 </a>
             </li>

             <li>
                 <a href="{{ route('livrable') }}" class="">
                     <span class="icon material-icons"  >bike_scooter
                     </span>
                     <span class="text">Livraisons</span>
                 </a>
             </li>
             <li>
                 <a href="{{route('brouillons')}}" class="">
                     <span class="icon material-icons"  >delete_sweep
                     </span>
                     <span class="text">Brouillons</span>
                 </a>
             </li>
             <li>
                 <a href="{{route('archivelist')}}" class="">
                     <span class="icon material-icons"  >archive
                     </span>
                     <span class="text">Archive</span>
                 </a>
             </li>
             @elseif (Auth::user()->user_type=="PT")
             <li>
                <a href="{{ route('product') }}" class="">
                    <span class="icon material-icons"  >store
                    </span>
                    <span class="text">Produits</span>
                </a>
            </li>
            <li wire:poll.2s>
                <a href="{{ route('productcart') }}" class="">
                    <span class="icon material-icons"  >add_shopping_cart</span>
                    <span class="text">Panier ({{ Cart::instance('cart')->count() }})</span>
                </a>
            </li>
            <li>
                <a href="{{ route('parthnersorder') }}" class="">
                    <span class="icon material-icons"  >history</span>
                    <span class="text">Mes commandes</span>
                </a>
            </li>

             @else

            <li>
                <a href="{{ route('authlivrable',Auth::user()->id) }}" class="">
                    <span class="icon material-icons">shopping_cart
                    </span>
                    <span class="text">Mes livraisons</span>
                </a>
            </li>

             <li>
                <a href="{{ route('livrable') }}" class="">
                    <span class="icon material-icons"  >bike_scooter
                    </span>
                    <span class="text">Livraisons</span>
                </a>
             </li>

             @endif




        </ul>

    </nav>
</aside>
