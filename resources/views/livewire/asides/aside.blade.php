@php
    $active = fn (...$routes) => request()->routeIs(...$routes) ? 'active' : '';
@endphp
<aside class="sidebar">
    <nav class="navbar">
        <a class="navbar-brand brand-title" href="#">
            @php
                $settings = App\Models\Setting::all();
            @endphp
            @forelse ($settings as $setting)
                <img src="{{ url('image/', $setting->img) }}" style="background-color: white ; " alt=""
                    class="logo img-thumbnail ">{{ $setting->name }}

            @empty

                <img src="{{ asset('assets/images/tena.png') }}" style="background-color: white ; " alt=""
                    class="logo img-thumbnail ">GEST +
            @endforelse
        </a>
    </nav>
    <nav class="navigation shadow-sm">
        <div class="navigation-arrow">
            <i class="material-icons">chevron_left</i>
        </div>
        <ul>


            @if (Auth::user()->user_type == 'ADMINUSER')
                <li>
                    <a href="{{ route('Admin') }}" class="{{ $active('Admin') }}">
                        <span class="icon material-icons">dashboard</span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#adminAccountingCollapse" class="{{ $active('type-expensives', 'expensives', 'list-percent', 'virementverify') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">money</span>
                        <span class="text">Comptabilité</span>
                    </a>

                    <ul class="collapse {{ $active('type-expensives', 'expensives', 'list-percent', 'virementverify') ? 'show' : '' }}" id="adminAccountingCollapse">
                        <li>
                            <a href="{{ route('type-expensives') }}" class="{{ $active('type-expensives') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Type de dépense</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('expensives') }}" class="{{ $active('expensives') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Dépense</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('list-percent') }}" class="{{ $active('list-percent') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Type de pourcentage</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('virementverify') }}" class="{{ $active('virementverify') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Demande virement</span>
                            </a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ $active('product') }}">
                        <span class="icon material-icons">store
                        </span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('audit.products') }}" class="{{ $active('audit.products') }}">
                        <span class="icon material-icons">store
                        </span>
                        <span class="text">Audit</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ $active('order-liste-order') }}">
                        <span class="icon material-icons">add_shopping_cart
                        </span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('productcart') }}" class="{{ $active('productcart') }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier <span class="badge badge-pill badge-danger js-cart-count">{{ $cartCount }}</span></span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('order') }}" class="{{ $active('order') }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Commandes</span>
                    </a>
                </li>

                <li>
                    <a href="#adminHistoryCollapse" class="{{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list', 'trie-order-parther', 'repport-order', 'achat') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>

                    <ul class="collapse {{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list', 'trie-order-parther', 'repport-order', 'achat') ? 'show' : '' }}" id="adminHistoryCollapse">

                        <li>
                            <a href="{{ route('history') }}" class="{{ $active('history') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ $active('ordered-list-orderer') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ $active('order-report-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ $active('order-cancel-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('trie-order-parther') }}" class="{{ $active('trie-order-parther') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Partenaires</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('repport-order') }}" class="{{ $active('repport-order') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Rapport sur les commandes</span>
                            </a>
                        </li>
                        <li>

                            <a href=" {{ route('achat') }}
                            " class="{{ $active('achat') }}">
                                <span class="icon material-icons">
                                </span>
                                <span class="text">Achat client</span>
                            </a>
                        </li>

                    </ul>
                </li>

                <li>
                    <a href="{{ route('costumer.index') }}" class="{{ $active('costumer.index') }}">
                        <span class="icon material-icons">contact_phone</span>
                        <span class="text">Clients</span>
                    </a>
                </li>
                <li>
                    <a href="#serviceClientMenu" class="{{ $active('costumer.follow-up', 'service-cases.*', 'customer-loyalty.*') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">support_agent</span>
                        <span class="text">SAV</span>
                    </a>
                    <ul class="collapse {{ $active('costumer.follow-up', 'service-cases.*', 'customer-loyalty.*') ? 'show' : '' }}" id="serviceClientMenu">
                        <li><a class="{{ $active('costumer.follow-up') }}" href="{{ route('costumer.follow-up') }}"><span class="icon material-icons">remove</span><span class="text">Suivi des contacts</span></a></li>
                        <li><a class="{{ $active('service-cases.index') }}" href="{{ route('service-cases.index') }}"><span class="icon material-icons">remove</span><span class="text">Réclamations</span></a></li>
                        <li><a class="{{ $active('customer-loyalty.index') }}" href="{{ route('customer-loyalty.index') }}"><span class="icon material-icons">remove</span><span class="text">Fidélisation</span></a></li>
                    </ul>
                </li>
                <li>
                    <a href="{{ route('parthners') }}" class="{{ $active('parthners') }}">
                        <span class="icon material-icons">person
                        </span>
                        <span class="text">Partenaires</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('livreurs.index') }}" class="{{ $active('livreurs.index') }}">
                        <span class="icon material-icons">directions_bike
                        </span>
                        <span class="text">Livreurs</span>
                    </a>
                </li>


                <li>
                    <a href="{{ route('useradminlist') }}" class="{{ $active('useradminlist') }}">
                        <span class="icon material-icons">groups</span>
                        <span class="text">Membres</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('livrable') }}" class="{{ $active('livrable') }}">
                        <span class="icon material-icons">bike_scooter
                        </span>
                        <span class="text">Livraisons</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('consultation') }}" class="{{ $active('consultation') }}">
                        <span class="icon material-icons">calendar_today
                        </span>
                        <span class="text">Consultations</span>
                    </a>
                </li>
                <li wire:poll.5s>
                    <a href="{{ route('brouillons') }}" class="{{ $active('brouillons') }}">
                        <span class="icon material-icons">delete_sweep
                        </span>
                        <span class="text">Brouillons ({{ $counts }})</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('archivelist') }}" class="{{ $active('archivelist') }}">
                        <span class="icon material-icons">archive
                        </span>
                        <span class="text">Archive</span>
                    </a>
                </li>
                <li>
                    <a class="{{ $active('setting') }}" href="{{ route('setting') }}">
                        <span class="icon material-icons">settings
                        </span>
                        <span class="text">Paramètre</span>
                    </a>
                </li>
                <li>

                    <a href=" {{ route('show-deliveries') }}
                    " class="{{ $active('show-deliveries') }}">
                        <span class="icon material-icons">calendar_today
                        </span>
                        <span class="text">Classement</span>
                    </a>
                </li>
            @elseif (Auth::user()->user_type == 'PT')
                <li>
                    <a href="#ptHistoryCollapse" class="{{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>

                    <ul class="collapse {{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list') ? 'show' : '' }}" id="ptHistoryCollapse">

                        <li>
                            <a href="{{ route('history') }}" class="{{ $active('history') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ $active('ordered-list-orderer') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ $active('order-report-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ $active('order-cancel-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>


                    </ul>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ $active('product') }}">
                        <span class="icon material-icons">store
                        </span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('productcart') }}" class="{{ $active('productcart') }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier <span class="badge badge-pill badge-danger js-cart-count">{{ $cartCount }}</span></span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('parthnersorder') }}" class="{{ $active('parthnersorder') }}">
                        <span class="icon material-icons">history</span>
                        <span class="text">Mes commandes</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('with-auth-user-list') }}" class="{{ $active('with-auth-user-list') }}">
                        <span class="icon material-icons">money</span>
                        <span class="text">Demande de virement</span>
                    </a>
                </li>
            @elseif(Auth::user()->user_type == 'VDS')
                <li>
                    <a href="#vdsHistoryCollapse" class="{{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>

                    <ul class="collapse {{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list') ? 'show' : '' }}" id="vdsHistoryCollapse">

                        <li>
                            <a href="{{ route('history') }}" class="{{ $active('history') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ $active('ordered-list-orderer') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ $active('order-report-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ $active('order-cancel-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>


                    </ul>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ $active('product') }}">
                        <span class="icon material-icons">store
                        </span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('productcart') }}" class="{{ $active('productcart') }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier <span class="badge badge-pill badge-danger js-cart-count">{{ $cartCount }}</span></span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ $active('order-liste-order') }}">
                        <span class="icon material-icons">add_shopping_cart
                        </span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>
            @elseif (Auth::user()->user_type == 'CSA')
                <li>
                    <a href="{{ route('order') }}" class="{{ $active('order') }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ $active('order-liste-order') }}">
                        <span class="icon material-icons">add_shopping_cart
                        </span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>
            @elseif (Auth::user()->user_type == 'MNG')
                <li>
                    <a href="{{ route('Admin') }}" class="{{ $active('Admin') }}">
                        <span class="icon material-icons">dashboard</span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ $active('product') }}">
                        <span class="icon material-icons">store</span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ $active('order-liste-order') }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('productcart') }}" class="{{ $active('productcart') }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier <span class="badge badge-pill badge-danger js-cart-count">{{ $cartCount }}</span></span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order') }}" class="{{ $active('order') }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="#mngHistoryCollapse" class="{{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list', 'repport-order', 'achat') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>
                    <ul class="collapse {{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list', 'repport-order', 'achat') ? 'show' : '' }}" id="mngHistoryCollapse">
                        <li>
                            <a href="{{ route('history') }}" class="{{ $active('history') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ $active('ordered-list-orderer') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ $active('order-report-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ $active('order-cancel-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('repport-order') }}" class="{{ $active('repport-order') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Rapport sur les commandes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('achat') }}" class="{{ $active('achat') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Achat client</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="{{ route('livrable') }}" class="{{ $active('livrable') }}">
                        <span class="icon material-icons">motorcycle</span>
                        <span class="text">Livrables</span>
                    </a>
                </li>
                <li>
                    <a href="#mngServiceClientMenu" class="{{ $active('service-cases.*', 'customer-loyalty.*') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">support_agent</span>
                        <span class="text">SAV</span>
                    </a>
                    <ul class="collapse {{ $active('service-cases.*', 'customer-loyalty.*') ? 'show' : '' }}" id="mngServiceClientMenu">
                        <li>
                            <a href="{{ route('service-cases.index') }}" class="{{ $active('service-cases.index') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Réclamations</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('customer-loyalty.index') }}" class="{{ $active('customer-loyalty.index') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Fidélisation</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#mngComptaCollapse" class="{{ $active('type-expensives', 'expensives') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">money</span>
                        <span class="text">Comptabilité</span>
                    </a>
                    <ul class="collapse {{ $active('type-expensives', 'expensives') ? 'show' : '' }}" id="mngComptaCollapse">
                        <li>
                            <a href="{{ route('type-expensives') }}" class="{{ $active('type-expensives') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Type de dépense</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('expensives') }}" class="{{ $active('expensives') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Dépense</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @elseif (Auth::user()->user_type == 'SCR')
                <li>
                    <a href="{{ route('Admin') }}" class="{{ $active('Admin') }}">
                        <span class="icon material-icons">dashboard</span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ $active('product') }}">
                        <span class="icon material-icons">store</span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ $active('order-liste-order') }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('productcart') }}" class="{{ $active('productcart') }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier <span class="badge badge-pill badge-danger js-cart-count">{{ $cartCount }}</span></span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order') }}" class="{{ $active('order') }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="#scrHistoryCollapse" class="{{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list', 'repport-order', 'achat') }}" data-toggle="collapse">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>
                    <ul class="collapse {{ $active('history', 'ordered-list-orderer', 'order-report-list', 'order-cancel-list', 'repport-order', 'achat') ? 'show' : '' }}" id="scrHistoryCollapse">
                        <li>
                            <a href="{{ route('history') }}" class="{{ $active('history') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ $active('ordered-list-orderer') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ $active('order-report-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ $active('order-cancel-list') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('repport-order') }}" class="{{ $active('repport-order') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Rapport sur les commandes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('achat') }}" class="{{ $active('achat') }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Achat client</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="{{ route('ranking') }}" class="{{ $active('ranking') }}">
                        <span class="icon material-icons">grade</span>
                        <span class="text">Classement</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('service-cases.index') }}" class="{{ $active('service-cases.index') }}">
                        <span class="icon material-icons">support_agent</span>
                        <span class="text">Réclamations</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('customer-loyalty.index') }}" class="{{ $active('customer-loyalty.index') }}">
                        <span class="icon material-icons">loyalty</span>
                        <span class="text">Fidélisation</span>
                    </a>
                </li>
            @elseif (Auth::user()->user_type == 'CALLCENTER')
                <li>
                    <a href="{{ route('costumer.follow-up') }}" class="{{ $active('costumer.follow-up') }}">
                        <span class="icon material-icons">support_agent</span>
                        <span class="text">SAV</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('service-cases.index') }}" class="{{ $active('service-cases.index') }}">
                        <span class="icon material-icons">support_agent</span>
                        <span class="text">Réclamations</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('customer-loyalty.index') }}" class="{{ $active('customer-loyalty.index') }}">
                        <span class="icon material-icons">loyalty</span>
                        <span class="text">Fidélisation</span>
                    </a>
                </li>
            @else
                <li>
                    <a href="{{ route('Admin') }}" class="{{ $active('Admin') }}">
                        <span class="icon material-icons">dashboard</span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                @if (in_array(Auth::user()->user_type, ['MNG', 'SCR'], true))
                    <li>
                        <a href="{{ route('service-cases.index') }}" class="{{ $active('service-cases.index') }}">
                            <span class="icon material-icons">support_agent</span>
                            <span class="text">Réclamations</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('customer-loyalty.index') }}" class="{{ $active('customer-loyalty.index') }}">
                            <span class="icon material-icons">loyalty</span>
                            <span class="text">Fidélisation</span>
                        </a>
                    </li>
                @endif
                <li>
                    <a href="{{ route('authlivrable', Auth::user()->id) }}" class="{{ $active('authlivrable') }}">
                        <span class="icon material-icons">shopping_cart
                        </span>
                        <span class="text">Mes livraisons</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('livrable') }}" class="{{ $active('livrable') }}">
                        <span class="icon material-icons">bike_scooter
                        </span>
                        <span class="text">Livraisons</span>
                    </a>
                </li>

                <li>

                    <a href=" {{ route('show-deliveries') }}
                    " class="{{ $active('show-deliveries') }}">
                        <span class="icon material-icons">calendar_today
                        </span>
                        <span class="text">Classement</span>
                    </a>
                </li>
            @endif




        </ul>

    </nav>


</aside>

@once
    <script>
        if (!window.tenaceCartCountListener) {
            window.tenaceCartCountListener = true;
            window.addEventListener('cart-count-updated', function (event) {
                document.querySelectorAll('.js-cart-count').forEach(function (badge) {
                    badge.textContent = event.detail.count;
                });
            });
        }
    </script>
@endonce
