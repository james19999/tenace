<header>
    <div>
        <ul class="navbar-nav">
            <li class="nav-item icon">
                <button class="hamburger" id="hamburger-btn">
                    <span class="material-icons">menu</span>
                </button>
            </li>


            <li class="nav-item d-sm-none d-none d-md-block">
                <a href="#" class="nav-link">
                    {{-- <i class="material-icons align-middle">add</i> New --}}
                </a>
            </li>
            <li class="nav-item d-sm-none d-none d-md-block">

            </li>
            <li class="flex-fill"></li>
            <li class="nav-item search-bar" id="search-bar">

            </li>
            <li class="nav-item icon">

            </li>
           <li class="nav-item icon">

            </li>


            <!-- Messages Dropdown Menu -->
            <li class="nav-item dropdown with-caret  ">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <div class="avatar avatar-sm avatar-dnd bg-primary">
                        <img src="{{ asset('assets/images/tena.png') }}" height="100" width="100" class="avatar-img align-top rounded-circle" alt="">
                    </div>
                </a>
                <div class="dropdown-menu p-1 dropdown-menu-right">
                    <span class="dropdown-item">
                        {{ Auth::user()->name }}
                    </span>
                 <a class="dropdown-item" href="{{ route('logout') }}"
                    onclick="event.preventDefault();
                                  document.getElementById('logout-form').submit();">
                     {{ __('Déconnexion') }}
                 </a>

                 <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                     @csrf
                 </form>

                </div>
            </li>
        </ul>
    </div>
</header>
