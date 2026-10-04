@php
    use App\Http\Livewire\Asides\Aside;
    $settings = App\Models\Setting::all();
@endphp

<style>
    /* Indication du menu parent quand un de ses sous-menus est actif */
    .sidebar .navigation > ul > li > a.active[data-toggle="collapse"] {
        background-color: rgba(65, 66, 136, 0.12) !important;
        color: #7e1615 !important;
        font-weight: 600;
    }
    .sidebar .navigation > ul > li > a.active[data-toggle="collapse"] span.caret {
        color: #7e1615 !important;
    }
    .sidebar .navigation > ul > li a[aria-expanded="true"] span.caret {
        transform: rotate(90deg);
    }
    /* Style pour le sous-menu actif */
    .sidebar .navigation > ul > li ul.collapse li a.active {
        background-color: rgba(65, 66, 136, 0.25) !important;
        color: #7e1615 !important;
        font-weight: 600;
        border-radius: 0 15px 15px 0;
    }
</style>

<aside class="sidebar">
    <nav class="navbar">
        <a class="navbar-brand brand-title" href="#">
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
                    <a href="{{ route('Admin') }}" class="{{ Aside::isDashboardActive() }}">
                        <span class="icon material-icons">dashboard</span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#adminComptaCollapse" class="{{ Aside::isComptaActive() ? 'active' : '' }}" data-toggle="collapse"
                        aria-expanded="{{ Aside::isComptaActive() ? 'true' : 'false' }}">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">money</span>
                        <span class="text">Comptabilité</span>
                    </a>

                    <ul class="collapse {{ Aside::isComptaActive() ? 'show' : '' }}" id="adminComptaCollapse" wire:ignore.self>
                        <li>
                            <a href="{{ route('type-expensives') }}" class="{{ Aside::isActive(['type-expensives*', 'typeexpensives*', 'destroy-expensives*'], ['type/expensives*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Type de dépense</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('expensives') }}" class="{{ Aside::isActive(['expensives*', 'repport-expensives*'], ['expensives*', 'erport/expensive/*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Dépense</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('list-percent') }}" class="{{ Aside::isActive(['list-percent*', 'create-post*', 'percen-edit*'], ['list/percent*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Type de pourcentage</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('virementverify') }}" class="{{ Aside::isActive('virementverify*', ['virement/auth/user/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Demande virement</span>
                            </a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ Aside::isProductActive() }}">
                        <span class="icon material-icons">store</span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('audit.products') }}" class="{{ Aside::isActive('audit.products', ['audit-produits*']) }}">
                        <span class="icon material-icons">store</span>
                        <span class="text">Audit</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ Aside::isOrderManageActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>

                <li wire:poll.5s>
                    <a href="{{ route('productcart') }}" class="{{ Aside::isCartActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier ({{ Cart::instance('cart')->count() }})</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('order') }}" class="{{ Aside::isOrderActive() }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Commandes</span>
                    </a>
                </li>

                <li>
                    <a href="#adminHistoryCollapse" class="{{ Aside::isHistoryActive() ? 'active' : '' }}" data-toggle="collapse"
                        aria-expanded="{{ Aside::isHistoryActive() ? 'true' : 'false' }}">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>

                    <ul class="collapse {{ Aside::isHistoryActive() ? 'show' : '' }}" id="adminHistoryCollapse" wire:ignore.self>

                        <li>
                            <a href="{{ route('history') }}" class="{{ Aside::isActive('history*', ['history*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ Aside::isActive('ordered-list-orderer*', ['ordered/list/ordered*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ Aside::isActive('order-report-list*', ['order/report/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ Aside::isActive('order-cancel-list*', ['order/cancel/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('trie-order-parther') }}" class="{{ Aside::isActive('trie-order-parther*', ['trie/order/parther*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Partenaires</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('repport-order') }}" class="{{ Aside::isActive('repport-order*', ['repport/order*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Rapport sur les commandes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('achat') }}" class="{{ Aside::isActive('achat*', ['costumer/achat/order*']) }}">
                                <span class="icon material-icons"></span>
                                <span class="text">Achat client</span>
                            </a>
                        </li>

                    </ul>
                </li>

                <li>
                    <a href="{{ route('costumer.index') }}" class="{{ Aside::isCustomerActive() }}">
                        <span class="icon material-icons">contact_phone</span>
                        <span class="text">Clients</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('costumer.follow-up') }}" class="{{ Aside::isSavActive() }}">
                        <span class="icon material-icons">support_agent</span>
                        <span class="text">SAV</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('parthners') }}" class="{{ Aside::isActive(['parthners', 'storepathner', 'tenacos/parthner'], ['parthners', 'store/pathner', 'tenacos/parthner']) }}">
                        <span class="icon material-icons">person</span>
                        <span class="text">Partenaires</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('livreurs.index') }}" class="{{ Aside::isActive('livreurs.*', ['livreurs', 'livreurs/*']) }}">
                        <span class="icon material-icons">directions_bike</span>
                        <span class="text">Livreurs</span>
                    </a>
                </li>


                <li>
                    <a href="{{ route('useradminlist') }}" class="{{ Aside::isActive(['useradminlist', 'user-show', 'active-user', 'set-password', 'user.update-role'], ['user/admin/list', 'user/show/*']) }}">
                        <span class="icon material-icons">groups</span>
                        <span class="text">Membres</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('livrable') }}" class="{{ Aside::isDeliveryActive() }}">
                        <span class="icon material-icons">bike_scooter</span>
                        <span class="text">Livraisons</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('consultation') }}" class="{{ Aside::isActive('consultation', ['consultation']) }}">
                        <span class="icon material-icons">calendar_today</span>
                        <span class="text">Consultations</span>
                    </a>
                </li>
                <li wire:poll.5s>
                    <a href="{{ route('brouillons') }}" class="{{ Aside::isActive(['brouillons', 'unlockbrouillon'], ['brouillons', 'unlock/brouillon/*']) }}">
                        <span class="icon material-icons">delete_sweep</span>
                        <span class="text">Brouillons ({{ $counts }})</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('archivelist') }}" class="{{ Aside::isActive('archivelist', ['archive/list']) }}">
                        <span class="icon material-icons">archive</span>
                        <span class="text">Archive</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('setting') }}" class="{{ Aside::isActive(['setting', 'setting-info', 'setting-update'], ['settings/config']) }}">
                        <span class="icon material-icons">settings</span>
                        <span class="text">Paramètre</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('show-deliveries') }}" class="{{ Aside::isRankingActive() }}">
                        <span class="icon material-icons">calendar_today</span>
                        <span class="text">Classement</span>
                    </a>
                </li>
            @elseif (Auth::user()->user_type == 'PT')
                <li>
                    <a href="#ptHistoryCollapse" class="{{ Aside::isHistoryActive() ? 'active' : '' }}" data-toggle="collapse"
                        aria-expanded="{{ Aside::isHistoryActive() ? 'true' : 'false' }}">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>

                    <ul class="collapse {{ Aside::isHistoryActive() ? 'show' : '' }}" id="ptHistoryCollapse" wire:ignore.self>

                        <li>
                            <a href="{{ route('history') }}" class="{{ Aside::isActive('history*', ['history*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ Aside::isActive('ordered-list-orderer*', ['ordered/list/ordered*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ Aside::isActive('order-report-list*', ['order/report/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ Aside::isActive('order-cancel-list*', ['order/cancel/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ Aside::isProductActive() }}">
                        <span class="icon material-icons">store</span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li wire:poll.2s>
                    <a href="{{ route('productcart') }}" class="{{ Aside::isCartActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier ({{ Cart::instance('cart')->count() }})</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('parthnersorder') }}" class="{{ Aside::isActive('parthnersorder', ['parthners/order']) }}">
                        <span class="icon material-icons">history</span>
                        <span class="text">Mes commandes</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('with-auth-user-list') }}" class="{{ Aside::isActive('with-auth-user-list', ['with/auth/user/list']) }}">
                        <span class="icon material-icons">money</span>
                        <span class="text">Demande de virement</span>
                    </a>
                </li>
            @elseif(Auth::user()->user_type == 'VDS')
                <li>
                    <a href="#vdsHistoryCollapse" class="{{ Aside::isHistoryActive() ? 'active' : '' }}" data-toggle="collapse"
                        aria-expanded="{{ Aside::isHistoryActive() ? 'true' : 'false' }}">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>

                    <ul class="collapse {{ Aside::isHistoryActive() ? 'show' : '' }}" id="vdsHistoryCollapse" wire:ignore.self>

                        <li>
                            <a href="{{ route('history') }}" class="{{ Aside::isActive('history*', ['history*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ Aside::isActive('ordered-list-orderer*', ['ordered/list/ordered*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ Aside::isActive('order-report-list*', ['order/report/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ Aside::isActive('order-cancel-list*', ['order/cancel/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ Aside::isProductActive() }}">
                        <span class="icon material-icons">store</span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li wire:poll.2s>
                    <a href="{{ route('productcart') }}" class="{{ Aside::isCartActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier ({{ Cart::instance('cart')->count() }})</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ Aside::isOrderManageActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>
            @elseif (Auth::user()->user_type == 'CSA')
                <li>
                    <a href="{{ route('order') }}" class="{{ Aside::isOrderActive() }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ Aside::isOrderManageActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>
            @elseif (Auth::user()->user_type == 'MNG')
                <li>
                    <a href="{{ route('Admin') }}" class="{{ Aside::isDashboardActive() }}">
                        <span class="icon material-icons">dashboard</span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ Aside::isProductActive() }}">
                        <span class="icon material-icons">store</span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ Aside::isOrderManageActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>
                <li wire:poll.2s>
                    <a href="{{ route('productcart') }}" class="{{ Aside::isCartActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier ({{ Cart::instance('cart')->count() }})</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order') }}" class="{{ Aside::isOrderActive() }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="#mngHistoryCollapse" class="{{ Aside::isHistoryActive() ? 'active' : '' }}" data-toggle="collapse"
                        aria-expanded="{{ Aside::isHistoryActive() ? 'true' : 'false' }}">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>
                    <ul class="collapse {{ Aside::isHistoryActive() ? 'show' : '' }}" id="mngHistoryCollapse" wire:ignore.self>
                        <li>
                            <a href="{{ route('history') }}" class="{{ Aside::isActive('history*', ['history*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ Aside::isActive('ordered-list-orderer*', ['ordered/list/ordered*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ Aside::isActive('order-report-list*', ['order/report/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ Aside::isActive('order-cancel-list*', ['order/cancel/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('repport-order') }}" class="{{ Aside::isActive('repport-order*', ['repport/order*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Rapport sur les commandes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('achat') }}" class="{{ Aside::isActive('achat*', ['costumer/achat/order*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Achat client</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="{{ route('livrable') }}" class="{{ Aside::isDeliveryActive() }}">
                        <span class="icon material-icons">bike_scooter</span>
                        <span class="text">Livraisons</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('show-deliveries') }}" class="{{ Aside::isRankingActive() }}">
                        <span class="icon material-icons">calendar_today</span>
                        <span class="text">Classement</span>
                    </a>
                </li>
                <li>
                    <a href="#mngComptaCollapse" class="{{ Aside::isComptaActive() ? 'active' : '' }}" data-toggle="collapse"
                        aria-expanded="{{ Aside::isComptaActive() ? 'true' : 'false' }}">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">money</span>
                        <span class="text">Comptabilité</span>
                    </a>
                    <ul class="collapse {{ Aside::isComptaActive() ? 'show' : '' }}" id="mngComptaCollapse" wire:ignore.self>
                        <li>
                            <a href="{{ route('type-expensives') }}" class="{{ Aside::isActive(['type-expensives*', 'typeexpensives*', 'destroy-expensives*'], ['type/expensives*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Type de dépense</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('expensives') }}" class="{{ Aside::isActive(['expensives*', 'repport-expensives*'], ['expensives*', 'erport/expensive/*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Dépense</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @elseif (Auth::user()->user_type == 'SCR')
                <li>
                    <a href="{{ route('Admin') }}" class="{{ Aside::isDashboardActive() }}">
                        <span class="icon material-icons">dashboard</span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('product') }}" class="{{ Aside::isProductActive() }}">
                        <span class="icon material-icons">store</span>
                        <span class="text">Produits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order-liste-order') }}" class="{{ Aside::isOrderManageActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Gestion commande</span>
                    </a>
                </li>
                <li wire:poll.2s>
                    <a href="{{ route('productcart') }}" class="{{ Aside::isCartActive() }}">
                        <span class="icon material-icons">add_shopping_cart</span>
                        <span class="text">Panier ({{ Cart::instance('cart')->count() }})</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('order') }}" class="{{ Aside::isOrderActive() }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Commandes</span>
                    </a>
                </li>
                <li>
                    <a href="#scrHistoryCollapse" class="{{ Aside::isHistoryActive() ? 'active' : '' }}" data-toggle="collapse"
                        aria-expanded="{{ Aside::isHistoryActive() ? 'true' : 'false' }}">
                        <span class="caret material-icons">arrow_right</span>
                        <span class="icon material-icons">history</span>
                        <span class="text">Historiques</span>
                    </a>
                    <ul class="collapse {{ Aside::isHistoryActive() ? 'show' : '' }}" id="scrHistoryCollapse" wire:ignore.self>
                        <li>
                            <a href="{{ route('history') }}" class="{{ Aside::isActive('history*', ['history*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes & produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordered-list-orderer') }}" class="{{ Aside::isActive('ordered-list-orderer*', ['ordered/list/ordered*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes en cours</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-report-list') }}" class="{{ Aside::isActive('order-report-list*', ['order/report/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes reprogrammées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('order-cancel-list') }}" class="{{ Aside::isActive('order-cancel-list*', ['order/cancel/list*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Commandes annulées</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('repport-order') }}" class="{{ Aside::isActive('repport-order*', ['repport/order*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Rapport sur les commandes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('achat') }}" class="{{ Aside::isActive('achat*', ['costumer/achat/order*']) }}">
                                <span class="icon material-icons">remove</span>
                                <span class="text">Achat client</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="{{ route('show-deliveries') }}" class="{{ Aside::isRankingActive() }}">
                        <span class="icon material-icons">calendar_today</span>
                        <span class="text">Classement</span>
                    </a>
                </li>
            @elseif (Auth::user()->user_type == 'CALLCENTER')
                <li>
                    <a href="{{ route('costumer.follow-up') }}" class="{{ Aside::isSavActive() }}">
                        <span class="icon material-icons">support_agent</span>
                        <span class="text">SAV</span>
                    </a>
                </li>
            @else
                <li>
                    <a href="{{ route('Admin') }}" class="{{ Aside::isDashboardActive() }}">
                        <span class="icon material-icons">dashboard</span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('authlivrable', Auth::user()->id) }}" class="{{ Aside::isActive('authlivrable', ['auth/livrable/*']) }}">
                        <span class="icon material-icons">shopping_cart</span>
                        <span class="text">Mes livraisons</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('livrable') }}" class="{{ Aside::isDeliveryActive() }}">
                        <span class="icon material-icons">bike_scooter</span>
                        <span class="text">Livraisons</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('show-deliveries') }}" class="{{ Aside::isRankingActive() }}">
                        <span class="icon material-icons">calendar_today</span>
                        <span class="text">Classement</span>
                    </a>
                </li>
            @endif

        </ul>

    </nav>

    <script>
        (function () {
            function syncActiveSidebar() {
                var currentPath = window.location.pathname.replace(/\/$/, '') || '/';

                // 1. If server already marked a submenu child active:
                var $activeSub = $('.sidebar .navigation ul.collapse li a.active');
                if ($activeSub.length === 1) {
                    var $collapse = $activeSub.closest('.collapse');
                    // Remove active from all standalone top-level links
                    $('.sidebar .navigation > ul > li > a:not([data-toggle="collapse"])').removeClass('active');
                    // Close other collapses
                    $('.sidebar .navigation ul.collapse').not($collapse).removeClass('show');
                    $('.sidebar .navigation a[data-toggle="collapse"]').not($collapse.prev('a[data-toggle="collapse"]')).removeClass('active').attr('aria-expanded', 'false');

                    $collapse.addClass('show');
                    $collapse.prev('a[data-toggle="collapse"]').addClass('active').attr('aria-expanded', 'true');
                    return;
                }

                // 2. If server already marked a standalone link active:
                var $activeStandalone = $('.sidebar .navigation > ul > li > a.active:not([data-toggle="collapse"])');
                if ($activeStandalone.length === 1) {
                    // Make sure no collapse is marked active or open
                    $('.sidebar .navigation ul.collapse').removeClass('show');
                    $('.sidebar .navigation a[data-toggle="collapse"]').removeClass('active').attr('aria-expanded', 'false');
                    $('.sidebar .navigation ul.collapse a').removeClass('active');
                    return;
                }

                // 3. Fallback: clean all and match strictly by current URL
                $('.sidebar .navigation a').removeClass('active');
                $('.sidebar .navigation ul.collapse').removeClass('show');
                $('.sidebar .navigation a[data-toggle="collapse"]').attr('aria-expanded', 'false');

                $('.sidebar .navigation a:not([data-toggle="collapse"])').each(function () {
                    var href = $(this).attr('href');
                    if (!href || href === '#' || href.indexOf('#') === 0) return;

                    var linkPath = (href.indexOf('http://') === 0 || href.indexOf('https://') === 0)
                        ? new URL(href).pathname.replace(/\/$/, '') || '/'
                        : href.split(/[?#]/)[0].replace(/\/$/, '') || '/';

                    if (currentPath === linkPath) {
                        $(this).addClass('active');
                        var $col = $(this).closest('.collapse');
                        if ($col.length) {
                            $col.addClass('show');
                            $col.prev('a[data-toggle="collapse"]').addClass('active').attr('aria-expanded', 'true');
                        }
                        return false;
                    }
                });
            }

            // Click handling
            $(document).on('click', '.sidebar .navigation a:not([data-toggle="collapse"])', function () {
                var $this = $(this);
                var $collapse = $this.closest('.collapse');

                // Remove active from all links and collapse headers in the sidebar
                $('.sidebar .navigation a').removeClass('active');

                if ($collapse.length) {
                    // Clicking a submenu item: close other collapses, activate this one and its parent
                    $('.sidebar .navigation ul.collapse').not($collapse).removeClass('show');
                    $('.sidebar .navigation a[data-toggle="collapse"]').not($collapse.prev('a[data-toggle="collapse"]')).attr('aria-expanded', 'false');

                    $this.addClass('active');
                    $collapse.addClass('show');
                    $collapse.prev('a[data-toggle="collapse"]').addClass('active').attr('aria-expanded', 'true');
                } else {
                    // Clicking a top-level standalone item: close ALL collapses
                    $('.sidebar .navigation ul.collapse').removeClass('show');
                    $('.sidebar .navigation a[data-toggle="collapse"]').attr('aria-expanded', 'false');
                    $this.addClass('active');
                }
            });

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', syncActiveSidebar);
            } else {
                syncActiveSidebar();
            }

            if (window.Livewire) {
                Livewire.hook('message.processed', function () {
                    syncActiveSidebar();
                });
            } else {
                document.addEventListener('livewire:load', function () {
                    if (window.Livewire) {
                        Livewire.hook('message.processed', function () {
                            syncActiveSidebar();
                        });
                    }
                });
            }
        })();
    </script>
</aside>
